<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Recognises a dish from a photo and writes a menu title plus a short
 * description that mentions where the dish comes from. Stateless.
 */
class DishWriter
{
    /** Languages the menu text can be written in, keyed by app locale. */
    public const LANGUAGES = [
        'en' => 'English',
        'nl' => 'Dutch',
        'de' => 'German',
        'fr' => 'French',
        'es' => 'Spanish',
        'zh-TW' => 'Traditional Chinese',
        'zh-CN' => 'Simplified Chinese',
        'tr' => 'Turkish',
    ];

    private const MAX_PARAGRAPHS = 2;

    private const SYSTEM_PROMPT = <<<'PROMPT'
You write menu text for a restaurant. Look at the photo of ONE dish and return ONLY a valid JSON object:
{
  "dish_name": "the common name of the dish",
  "origin": "country or region the dish comes from, or null",
  "title": "a short, appealing menu title (max 8 words)",
  "description": "the menu description",
  "confidence": "high"|"medium"|"low"
}
Rules:
- Description: at most 2 short paragraphs separated by one blank line. Paragraph 1 says what the dish is and how it looks and tastes. Paragraph 2 says where it comes from and what it is traditionally about. If the origin is unknown, write one paragraph only.
- Describe only what you can see or what is well established about this dish. Never invent a story, a chef, a date, a restaurant, an award or an ingredient you cannot see. Do not mention allergens or dietary claims (vegan, gluten-free, halal ...).
- "origin" is the place the dish is traditionally from (for example "Thailand" or "Sicily, Italy"). Use null when the dish is a fusion, a house creation or you are not sure. Never guess an origin to fill the field.
- If the owner gives a dish name or origin, trust it and write about that dish; do not contradict it.
- If the photo does not show a dish, set confidence to "low", dish_name to "unknown", origin to null and write a one-line title and description saying that no dish was recognised.
- No emojis, no hashtags, no prices, no calls to action ("order now"). Plain text only.
No text outside the JSON object.
PROMPT;

    public function __construct(private readonly AiVisionClient $client) {}

    /**
     * @param  string  $image  base64-encoded JPEG
     * @param  string  $locale  one of the keys of self::LANGUAGES
     * @param  array{name?: ?string, origin?: ?string}  $hints  what the owner already knows
     * @return array{dish_name: string, origin: ?string, title: string, description: string, confidence: string}
     *
     * @throws FoodWasteScanException with a client-safe message
     */
    public function write(string $image, string $locale, array $hints = [], string $logTag = 'DishWriter'): array
    {
        $language = self::LANGUAGES[$locale] ?? 'English';

        $prompt = self::SYSTEM_PROMPT."\nWrite title, description and origin in {$language}.";

        $text = 'Write the menu text for this dish and return the JSON object.';

        if (! empty($hints['name'])) {
            $text .= ' The dish is called: '.$hints['name'].'.';
        }

        if (! empty($hints['origin'])) {
            $text .= ' It comes from: '.$hints['origin'].'.';
        }

        $raw = $this->client->send($prompt, [$image], $text, 900, 60, $logTag, $this->model());

        $result = $this->normalise($raw);

        if ($result === null) {
            Log::error("[{$logTag}] parse failed", ['raw' => $raw]);

            throw new FoodWasteScanException('Could not read the AI answer. Please try again.');
        }

        return $result;
    }

    /**
     * @return array{dish_name: string, origin: ?string, title: string, description: string, confidence: string}|null
     */
    public function normalise(string $raw): ?array
    {
        $cleaned = preg_replace('/^```(?:json)?\s*/i', '', trim($raw));
        $cleaned = preg_replace('/\s*```$/i', '', trim((string) $cleaned));

        $data = json_decode(trim((string) $cleaned), true);

        if (! is_array($data)) {
            return null;
        }

        $title = $this->text($data['title'] ?? null);
        $description = $this->description($data['description'] ?? null);

        if ($title === null || $description === null) {
            return null;
        }

        $origin = $this->text($data['origin'] ?? null);

        if ($origin !== null && in_array(Str::lower($origin), ['null', 'unknown', 'n/a'], true)) {
            $origin = null;
        }

        $confidence = in_array($data['confidence'] ?? null, ['high', 'medium', 'low'], true) ? $data['confidence'] : 'medium';

        return [
            'dish_name' => Str::limit($this->text($data['dish_name'] ?? null) ?? $title, 120, ''),
            'origin' => $origin === null ? null : Str::limit($origin, 120, ''),
            'title' => Str::limit($title, 120, ''),
            'description' => $description,
            'confidence' => $confidence,
        ];
    }

    private function text(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    /** Keeps at most two paragraphs, whatever the model returned. */
    private function description(mixed $value): ?string
    {
        $value = $this->text($value);

        if ($value === null) {
            return null;
        }

        $paragraphs = preg_split('/\R\s*\R/', str_replace("\r", '', $value)) ?: [$value];
        $paragraphs = array_values(array_filter(array_map('trim', $paragraphs), fn (string $p) => $p !== ''));

        return implode("\n\n", array_slice($paragraphs, 0, self::MAX_PARAGRAPHS));
    }

    private function model(): string
    {
        return (string) (config('services.ai.provider', 'anthropic') === 'openrouter'
            ? config('services.openrouter.dish_model')
            : config('services.anthropic.dish_model'));
    }
}
