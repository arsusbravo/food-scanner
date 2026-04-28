<?php

namespace App\Http\Controllers\Waste;

use App\Http\Controllers\Controller;
use Illuminate\Http\Client\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class AIScanController extends Controller
{
    private const SYSTEM_PROMPT = <<<'PROMPT'
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
No text outside the JSON object.
PROMPT;

    public function index(): InertiaResponse
    {
        return Inertia::render('waste/AiScan');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'photo' => ['required', 'string', 'min:100'],
        ]);

        $imageData = $validated['photo'];
        $provider = config('services.ai.provider', 'anthropic');
        $model    = $provider === 'openrouter'
            ? config('services.openrouter.model', 'unknown')
            : 'claude-sonnet-4-6';

        Log::info('[AIScan] request', [
            'provider'    => $provider,
            'model'       => $model,
            'photo_bytes' => (int) (strlen($imageData) * 0.75), // approx decoded bytes
        ]);

        try {
            $response = $provider === 'openrouter'
                ? $this->callOpenRouter($imageData)
                : $this->callAnthropic($imageData);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('[AIScan] connection error', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Connection to AI timed out. Please try again.'], 422);
        }

        Log::info('[AIScan] api response', [
            'status' => $response->status(),
            'body'   => substr($response->body(), 0, 500),
        ]);

        if ($response->failed()) {
            $reason = $response->json('error.message')
                ?? $response->json('error')
                ?? $response->body();
            Log::error('[AIScan] api error', [
                'status' => $response->status(),
                'reason' => $reason,
            ]);
            return response()->json(['error' => 'AI error: ' . $reason], 422);
        }

        $rawText = $provider === 'openrouter'
            ? $response->json('choices.0.message.content', '')
            : $response->json('content.0.text', '');

        Log::info('[AIScan] raw text', ['text' => $rawText]);

        // Strip markdown code fences that some models wrap around JSON
        $cleaned = preg_replace('/^```(?:json)?\s*/i', '', trim($rawText));
        $cleaned = preg_replace('/\s*```$/i', '', trim($cleaned));

        $extracted = json_decode(trim($cleaned), true);

        if (! $extracted || ! isset($extracted['item_name'], $extracted['category'], $extracted['reason'])) {
            Log::error('[AIScan] parse failed', [
                'raw'     => $rawText,
                'cleaned' => $cleaned,
                'decoded' => $extracted,
            ]);
            return response()->json(['error' => 'Could not parse AI response: ' . substr($rawText, 0, 200)], 422);
        }

        Log::info('[AIScan] success', ['item' => $extracted['item_name']]);

        return response()->json([
            'item_name' => $extracted['item_name'],
            'weight_kg' => $extracted['estimated_weight_kg'] ?? null,
            'category' => $extracted['category'],
            'reason' => $extracted['reason'],
            'confidence' => $extracted['confidence'] ?? 'medium',
            'notes' => $extracted['notes'] ?? null,
        ]);
    }

    private function callAnthropic(string $imageData): Response
    {
        return Http::withToken(config('services.anthropic.key'))
            ->withHeader('anthropic-version', '2023-06-01')
            ->timeout(30)
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => 'claude-sonnet-4-6',
                'max_tokens' => 512,
                'system' => self::SYSTEM_PROMPT,
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

    private function callOpenRouter(string $imageData): Response
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
                        'content' => self::SYSTEM_PROMPT,
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
