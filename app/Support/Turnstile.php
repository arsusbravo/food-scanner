<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;

class Turnstile
{
    /**
     * Per-request memo of verified tokens.
     *
     * A Turnstile token is single-use at Cloudflare's endpoint, but Fortify's
     * login pipeline invokes the authenticate callback twice for users without
     * two-factor (RedirectIfTwoFactorAuthenticatable + AttemptToAuthenticate).
     * Memoising by token keeps the second call from re-verifying — and failing —
     * the now-spent token.
     *
     * @var array<string, bool>
     */
    protected static array $verified = [];

    /**
     * Verify a Cloudflare Turnstile token against the siteverify endpoint.
     *
     * When no secret is configured (e.g. local dev) the check is skipped so
     * the auth flows keep working — this mirrors the frontend, which only
     * renders the widget when a site key is present.
     */
    public static function verify(?string $token, ?string $ip = null): bool
    {
        $secret = config('services.turnstile.secret');

        if (! $secret) {
            return true;
        }

        if (! $token) {
            return false;
        }

        if (array_key_exists($token, static::$verified)) {
            return static::$verified[$token];
        }

        $response = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v1/siteverify', array_filter([
            'secret'   => $secret,
            'response' => $token,
            'remoteip' => $ip,
        ]));

        return static::$verified[$token] = (bool) ($response->json('success') ?? false);
    }
}
