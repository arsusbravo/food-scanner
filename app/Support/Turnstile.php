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
     * Whether Turnstile is active. Disabled in the local environment (the
     * Cloudflare widget rejects unauthorised domains like *.test and throws),
     * and when keys are not configured. Production with keys → enabled.
     */
    public static function enabled(): bool
    {
        if (app()->environment('local')) {
            return false;
        }

        return ! empty(config('services.turnstile.site_key'))
            && ! empty(config('services.turnstile.secret'));
    }

    /**
     * Site key for the frontend widget, or null when Turnstile is disabled
     * (so no widget is rendered).
     */
    public static function siteKey(): ?string
    {
        return self::enabled() ? config('services.turnstile.site_key') : null;
    }

    /**
     * Verify a Cloudflare Turnstile token against the siteverify endpoint.
     *
     * When Turnstile is disabled (local env / no keys) the check is skipped so
     * the auth and demo flows keep working — this mirrors the frontend, which
     * only renders the widget when a site key is present.
     */
    public static function verify(?string $token, ?string $ip = null): bool
    {
        if (! self::enabled()) {
            return true;
        }

        $secret = config('services.turnstile.secret');

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
