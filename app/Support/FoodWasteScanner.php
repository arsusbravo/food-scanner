<?php

namespace App\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Core AI food-waste photo analysis. Shared by the authenticated scan endpoint
 * (AIScanController) and the public demo (DemoController) so both run the exact
 * same model path. Stateless: it never persists anything.
 */
class FoodWasteScanner
{
    private const SYSTEM_PROMPT_BASE = <<<'PROMPT'
You are a food waste auditor for a commercial kitchen.
Analyse the photograph of discarded food and return ONLY a valid JSON object:
{
  "item_name": "string",
  "estimated_weight_kg": number,
  "category": "protein"|"veg"|"dairy"|"prepared",
  "reason": "spoilage"|"overproduction"|"expiry"|"prep_waste"|"other",
  "confidence": "high"|"medium"|"low",
  "notes": "string or null"
}
Category guide: protein=meat/fish/eggs/legumes, veg=vegetables/fruit/herbs, dairy=milk/cheese/cream, prepared=cooked dishes/sauces/pastries.
The fields category, reason, and confidence MUST use the exact English enum values listed above.
No text outside the JSON object.
PROMPT;

    private const LANGUAGE_NAMES = [
        'en' => 'English',
        'nl' => 'Dutch',
        'de' => 'German',
        'fr' => 'French',
        'es' => 'Spanish',
    ];

    /**
     * Analyse a base64-encoded JPEG and return the normalised result.
     *
     * @return array{item_name:string, weight_kg:float|int|null, category:string, reason:string, confidence:string, notes:?string}
     *
     * @throws FoodWasteScanException for any failure with a client-safe message
     */
    public function scan(string $imageData, string $locale, string $logTag = 'AIScan'): array
    {
        $provider = config('services.ai.provider', 'anthropic');
        $model    = $provider === 'openrouter'
            ? config('services.openrouter.model', 'unknown')
            : 'claude-sonnet-4-6';

        Log::info("[{$logTag}] request", [
            'provider'    => $provider,
            'model'       => $model,
            'photo_bytes' => (int) (strlen($imageData) * 0.75), // approx decoded bytes
        ]);

        $systemPrompt = $this->buildSystemPrompt($locale);

        try {
            $response = $provider === 'openrouter'
                ? $this->callOpenRouter($imageData, $systemPrompt)
                : $this->callAnthropic($imageData, $systemPrompt);
        } catch (ConnectionException $e) {
            Log::error("[{$logTag}] connection error", ['message' => $e->getMessage()]);
            throw new FoodWasteScanException('Connection to AI timed out. Please try again.');
        }

        Log::info("[{$logTag}] api response", [
            'status' => $response->status(),
            'body'   => substr($response->body(), 0, 500),
        ]);

        if ($response->failed()) {
            $reason = $response->json('error.message')
                ?? $response->json('error')
                ?? $response->body();
            Log::error("[{$logTag}] api error", [
                'status' => $response->status(),
                'reason' => $reason,
            ]);
            throw new FoodWasteScanException('AI error: ' . $reason);
        }

        $rawText = $provider === 'openrouter'
            ? $response->json('choices.0.message.content', '')
            : $response->json('content.0.text', '');

        Log::info("[{$logTag}] raw text", ['text' => $rawText]);

        // Strip markdown code fences that some models wrap around JSON
        $cleaned = preg_replace('/^```(?:json)?\s*/i', '', trim($rawText));
        $cleaned = preg_replace('/\s*```$/i', '', trim($cleaned));

        $extracted = json_decode(trim($cleaned), true);

        if (! $extracted || ! isset($extracted['item_name'], $extracted['category'], $extracted['reason'])) {
            Log::error("[{$logTag}] parse failed", [
                'raw'     => $rawText,
                'cleaned' => $cleaned,
                'decoded' => $extracted,
            ]);
            throw new FoodWasteScanException('Could not parse AI response: ' . substr($rawText, 0, 200));
        }

        Log::info("[{$logTag}] success", ['item' => $extracted['item_name']]);

        return [
            'item_name'  => $extracted['item_name'],
            'weight_kg'  => $extracted['estimated_weight_kg'] ?? null,
            'category'   => $extracted['category'],
            'reason'     => $extracted['reason'],
            'confidence' => $extracted['confidence'] ?? 'medium',
            'notes'      => $extracted['notes'] ?? null,
        ];
    }

    private function buildSystemPrompt(string $locale): string
    {
        $lang = self::LANGUAGE_NAMES[$locale] ?? 'English';

        if ($lang === 'English') {
            return self::SYSTEM_PROMPT_BASE;
        }

        return self::SYSTEM_PROMPT_BASE . "\nWrite item_name and notes in {$lang}.";
    }

    private function callAnthropic(string $imageData, string $systemPrompt): Response
    {
        return Http::withToken(config('services.anthropic.key'))
            ->withHeader('anthropic-version', '2023-06-01')
            ->timeout(30)
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => 'claude-sonnet-4-6',
                'max_tokens' => 512,
                'system' => $systemPrompt,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'image',
                                'source' => [
                                    'type' => 'base64',
                                    'media_type' => 'image/jpeg',
                                    'data' => $imageData,
                                ],
                            ],
                            [
                                'type' => 'text',
                                'text' => 'Analyse this food waste photograph and return the JSON object.',
                            ],
                        ],
                    ],
                ],
            ]);
    }

    private function callOpenRouter(string $imageData, string $systemPrompt): Response
    {
        return Http::withToken(config('services.openrouter.key'))
            ->withHeader('HTTP-Referer', config('app.url'))
            ->withHeader('X-Title', config('app.name'))
            ->timeout(30)
            ->post('https://openrouter.ai/api/v1/chat/completions', [
                'model' => config('services.openrouter.model', 'anthropic/claude-sonnet-4-6'),
                'max_tokens' => 512,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $systemPrompt,
                    ],
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => 'data:image/jpeg;base64,' . $imageData,
                                ],
                            ],
                            [
                                'type' => 'text',
                                'text' => 'Analyse this food waste photograph and return the JSON object.',
                            ],
                        ],
                    ],
                ],
            ]);
    }
}
