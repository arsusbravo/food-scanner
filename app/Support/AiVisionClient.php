<?php

namespace App\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends one or more JPEG images plus a prompt to the configured vision model
 * (Anthropic or OpenRouter) and returns the raw text answer.
 */
class AiVisionClient
{
    /**
     * @param  list<string>  $images  base64-encoded JPEG images
     * @param  string|null  $model  overrides the provider's default model
     *
     * @throws FoodWasteScanException with a client-safe message
     */
    public function send(string $systemPrompt, array $images, string $userText, int $maxTokens = 4096, int $timeout = 60, string $logTag = 'AIVision', ?string $model = null): string
    {
        $provider = config('services.ai.provider', 'anthropic');

        Log::info("[{$logTag}] request", [
            'provider' => $provider,
            'model' => $model,
            'images' => count($images),
            'bytes' => (int) (array_sum(array_map('strlen', $images)) * 0.75),
        ]);

        try {
            $response = $provider === 'openrouter'
                ? $this->callOpenRouter($systemPrompt, $images, $userText, $maxTokens, $timeout, $model)
                : $this->callAnthropic($systemPrompt, $images, $userText, $maxTokens, $timeout, $model);
        } catch (ConnectionException $e) {
            Log::error("[{$logTag}] connection error", ['message' => $e->getMessage()]);
            throw new FoodWasteScanException('Connection to AI timed out. Please try again.');
        }

        if ($response->failed()) {
            $reason = $response->json('error.message') ?? $response->json('error') ?? $response->body();
            Log::error("[{$logTag}] api error", ['status' => $response->status(), 'reason' => $reason]);
            throw new FoodWasteScanException('AI error: '.(is_string($reason) ? $reason : json_encode($reason)));
        }

        $text = $provider === 'openrouter'
            ? $response->json('choices.0.message.content', '')
            : $response->json('content.0.text', '');

        return (string) $text;
    }

    /**
     * @param  list<string>  $images
     */
    private function callAnthropic(string $systemPrompt, array $images, string $userText, int $maxTokens, int $timeout, ?string $model): Response
    {
        $content = array_map(fn (string $image) => [
            'type' => 'image',
            'source' => ['type' => 'base64', 'media_type' => 'image/jpeg', 'data' => $image],
        ], $images);

        $content[] = ['type' => 'text', 'text' => $userText];

        return Http::withToken(config('services.anthropic.key'))
            ->withHeader('anthropic-version', '2023-06-01')
            ->timeout($timeout)
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => $model ?? 'claude-sonnet-4-6',
                'max_tokens' => $maxTokens,
                'system' => $systemPrompt,
                'messages' => [['role' => 'user', 'content' => $content]],
            ]);
    }

    /**
     * @param  list<string>  $images
     */
    private function callOpenRouter(string $systemPrompt, array $images, string $userText, int $maxTokens, int $timeout, ?string $model): Response
    {
        $content = array_map(fn (string $image) => [
            'type' => 'image_url',
            'image_url' => ['url' => 'data:image/jpeg;base64,'.$image],
        ], $images);

        $content[] = ['type' => 'text', 'text' => $userText];

        return Http::withToken(config('services.openrouter.key'))
            ->withHeader('HTTP-Referer', config('app.url'))
            ->withHeader('X-Title', config('app.name'))
            ->timeout($timeout)
            ->post('https://openrouter.ai/api/v1/chat/completions', [
                'model' => $model ?? config('services.openrouter.model', 'anthropic/claude-sonnet-4-6'),
                'max_tokens' => $maxTokens,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $content],
                ],
            ]);
    }
}
