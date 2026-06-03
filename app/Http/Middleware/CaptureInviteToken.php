<?php

namespace App\Http\Middleware;

use App\Models\Invitation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Whenever a public GET request carries a valid `?token=…` on ANY path,
 * stamp the token on the session and count the click. The visitor stays
 * on whatever URL they linked to (demo, prices, faq, anything) — they're
 * not redirected. When they later open `/register`, Fortify's registerView
 * reads `session('invite_token')` and shows the registration form.
 *
 * Invalid/expired tokens fall through silently. Already-logged-in users
 * also get the session stamp; it has no effect on them because Fortify
 * won't let an authenticated user hit /register anyway.
 */
class CaptureInviteToken
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET') || ! $request->hasSession()) {
            return $next($request);
        }

        $token = $request->query('token');
        if (! is_string($token) || $token === '') {
            return $next($request);
        }

        $invitation = Invitation::valid()->where('token', $token)->first();
        if (! $invitation) {
            return $next($request);
        }

        if ($request->session()->get('invite_click_counted') !== $token) {
            $invitation->increment('clicks');
            $request->session()->put('invite_click_counted', $token);
        }
        $request->session()->put('invite_token', $token);

        return $next($request);
    }
}
