<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Models\Invitation;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\Turnstile;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Number of failed login attempts (per IP, within the decay window) after
     * which the Cloudflare Turnstile challenge is required on login.
     *
     * Keep in sync with LOGIN_TURNSTILE_AFTER in resources/js/pages/auth/Login.vue.
     */
    private const LOGIN_TURNSTILE_AFTER = 2;

    /** Seconds the failed-login counter is kept before it decays. */
    private const LOGIN_TURNSTILE_DECAY = 900;

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Cache key tracking failed login attempts for the request's IP.
     */
    private function loginTurnstileKey(Request $request): string
    {
        return 'turnstile-login:'.$request->ip();
    }

    /**
     * Whether the login form should present a Turnstile challenge for this IP.
     */
    private function loginTurnstileRequired(Request $request): bool
    {
        return RateLimiter::attempts($this->loginTurnstileKey($request)) >= self::LOGIN_TURNSTILE_AFTER;
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);

        // Adaptive (step-up) Turnstile on login: the challenge is only required
        // once an IP has accumulated repeated failed attempts. This runs inside
        // Fortify's pipeline, so the login rate limiter and two-factor flow
        // still apply afterwards.
        //
        // Note: for users without 2FA this callback runs twice per request
        // (RedirectIfTwoFactorAuthenticatable, then AttemptToAuthenticate).
        // Turnstile::verify() is memoised by token, and the failure counter is
        // gated on a request attribute so it only moves once per attempt.
        Fortify::authenticateUsing(function (Request $request) {
            $key = $this->loginTurnstileKey($request);

            if ($this->loginTurnstileRequired($request)
                && ! Turnstile::verify($request->input('cf-turnstile-response'), $request->ip())) {
                throw ValidationException::withMessages([
                    'cf-turnstile-response' => 'CAPTCHA verification failed. Please try again.',
                ]);
            }

            $user = User::where(Fortify::username(), $request->input(Fortify::username()))->first();
            $passed = $user && Hash::check($request->input('password'), $user->password);

            if (! $request->attributes->get('turnstile_login_counted')) {
                $request->attributes->set('turnstile_login_counted', true);

                $passed
                    ? RateLimiter::clear($key)
                    : RateLimiter::hit($key, self::LOGIN_TURNSTILE_DECAY);
            }

            return $passed ? $user : null;
        });
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn (Request $request) => Inertia::render('auth/Login', [
            'canResetPassword' => Features::enabled(Features::resetPasswords()),
            'canRegister' => Features::enabled(Features::registration()),
            'status' => $request->session()->get('status'),
            'turnstileSiteKey' => config('services.turnstile.site_key'),
            'requireTurnstile' => $this->loginTurnstileRequired($request),
        ]));

        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('auth/ResetPassword', [
            'email' => $request->email,
            'token' => $request->route('token'),
        ]));

        Fortify::requestPasswordResetLinkView(fn (Request $request) => Inertia::render('auth/ForgotPassword', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::verifyEmailView(fn (Request $request) => Inertia::render('auth/VerifyEmail', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::registerView(function (Request $request) {
            $mode = SiteSetting::get('registration_mode', 'invite_only');

            if ($mode === 'invite_only') {
                // Token from URL takes priority, fall back to session
                $token = $request->query('token') ?: $request->session()->get('invite_token', '');

                $invitation = $token ? Invitation::valid()->where('token', $token)->first() : null;

                if (! $invitation) {
                    // Token was invalid or expired — remove it from the session so
                    // the homepage stops re-writing it to localStorage on next visit.
                    $request->session()->forget('invite_token');
                    return Inertia::render('auth/InviteOnly');
                }

                // Count click once per session per token
                if ($request->query('token') && $request->session()->get('invite_click_counted') !== $token) {
                    $invitation->increment('clicks');
                    $request->session()->put('invite_click_counted', $token);
                }

                $request->session()->put('invite_token', $token);

                return Inertia::render('auth/Register', [
                    'inviteToken'      => $token,
                    'mode'             => 'invite_only',
                    'turnstileSiteKey' => config('services.turnstile.site_key'),
                ]);
            }

            return Inertia::render('auth/Register', [
                'inviteToken'      => null,
                'mode'             => 'open',
                'turnstileSiteKey' => config('services.turnstile.site_key'),
            ]);
        });

        Fortify::twoFactorChallengeView(fn () => Inertia::render('auth/TwoFactorChallenge'));

        Fortify::confirmPasswordView(fn () => Inertia::render('auth/ConfirmPassword'));
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });
    }
}
