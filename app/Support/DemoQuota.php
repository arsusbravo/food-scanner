<?php

namespace App\Support;

use App\Models\DemoEvent;
use App\Models\DemoUsage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Meters the public demo without an account.
 *
 * Primary count is per device, keyed by a long-lived signed `demo_id` cookie
 * (Laravel's EncryptCookies middleware signs/encrypts it). A higher per-IP/day
 * ceiling — tracked via the RateLimiter, no extra storage — is a backstop
 * against someone clearing the cookie to reset their allowance.
 */
class DemoQuota
{
    private const COOKIE = 'demo_id';
    private const COOKIE_MINUTES = 60 * 24 * 365; // 1 year

    private ?string $deviceId = null;

    public function __construct(private readonly Request $request) {}

    /** Lifetime allowance per device. Configured in config/plans.php → demo. */
    private function deviceScans(): int   { return (int) config('plans.demo.scans', 2); }
    private function deviceReports(): int { return (int) config('plans.demo.reports', 1); }

    /** Per-IP/day ceiling — backstop against cookie reset. */
    private function ipScansPerDay(): int   { return (int) config('plans.demo.ip_scans_day', 25); }
    private function ipReportsPerDay(): int { return (int) config('plans.demo.ip_reports_day', 8); }

    /**
     * Resolve the device id, issuing (and queuing) a signed cookie on first use.
     */
    public function deviceId(): string
    {
        if ($this->deviceId !== null) {
            return $this->deviceId;
        }

        $id = $this->request->cookie(self::COOKIE);

        if (! is_string($id) || $id === '') {
            $id = (string) Str::uuid();
            Cookie::queue(Cookie::make(
                self::COOKIE,
                $id,
                self::COOKIE_MINUTES,
                null, null, null,
                true,   // httpOnly
                false,
                'lax',
            ));
        }

        return $this->deviceId = $id;
    }

    private function usage(): DemoUsage
    {
        return DemoUsage::firstOrCreate(
            ['device_id' => $this->deviceId()],
            $this->telemetry(),
        );
    }

    /**
     * First-touch acquisition metadata for the `demo_usages` row + every
     * event written by logEvent(). Country is taken from Cloudflare's
     * CF-IPCountry header (free, accurate, set on every proxied request);
     * null when the app isn't behind Cloudflare (e.g. local dev).
     *
     * @return array{ip:?string, country:?string, locale:?string, referer:?string, user_agent:?string}
     */
    private function telemetry(): array
    {
        $country = $this->request->header('CF-IPCountry');
        // Cloudflare sends 'XX' for unknown / Tor; treat as null for clean stats.
        if ($country === 'XX' || $country === 'T1') {
            $country = null;
        }

        return [
            'ip'         => $this->request->ip(),
            'country'    => $country,
            'locale'     => $this->request->cookie('locale'),
            'referer'    => $this->request->header('referer'),
            'user_agent' => $this->request->userAgent(),
        ];
    }

    /**
     * Insert an immutable demo-interaction event. Called once per visit /
     * scan / report from DemoController. Telemetry is read from the live
     * request so each event carries its own country/locale/referer (a
     * device may roam between visits).
     */
    public function logEvent(string $type): void
    {
        // created_at is managed automatically by Eloquent; UPDATED_AT is null
        // on the DemoEvent model so no updated_at column is written.
        DemoEvent::create([
            'device_id' => $this->deviceId(),
            'type'      => $type,
            ...$this->telemetry(),
        ]);
    }

    /**
     * Remaining allowance for this device, clamped at zero.
     *
     * @return array{scans:int, reports:int}
     */
    public function remaining(): array
    {
        $usage = $this->usage();

        return [
            'scans'   => max(0, $this->deviceScans() - $usage->scans),
            'reports' => max(0, $this->deviceReports() - $usage->reports),
        ];
    }

    /**
     * @throws DemoQuotaException when the device or IP has no scans left
     */
    public function ensureCanScan(): void
    {
        if ($this->usage()->scans >= $this->deviceScans()) {
            throw new DemoQuotaException(
                "You've used all " . $this->deviceScans() . ' free demo scans. Create an account to keep going.'
            );
        }

        if (RateLimiter::tooManyAttempts($this->ipKey('scan'), $this->ipScansPerDay())) {
            throw new DemoQuotaException('The demo scan limit for your network was reached. Please try again tomorrow or create an account.');
        }
    }

    /**
     * @throws DemoQuotaException when the device or IP has no report PDFs left
     */
    public function ensureCanReport(): void
    {
        if ($this->usage()->reports >= $this->deviceReports()) {
            throw new DemoQuotaException(
                "You've used all " . $this->deviceReports() . ' free demo reports. Create an account for unlimited official reports.'
            );
        }

        if (RateLimiter::tooManyAttempts($this->ipKey('report'), $this->ipReportsPerDay())) {
            throw new DemoQuotaException('The demo report limit for your network was reached. Please try again tomorrow or create an account.');
        }
    }

    public function recordScan(): void
    {
        $usage = $this->usage();
        $usage->increment('scans');
        $usage->forceFill(['ip' => $this->request->ip()])->save();
        RateLimiter::hit($this->ipKey('scan'), 86400);
    }

    public function recordReport(): void
    {
        $usage = $this->usage();
        $usage->increment('reports');
        $usage->forceFill(['ip' => $this->request->ip()])->save();
        RateLimiter::hit($this->ipKey('report'), 86400);
    }

    private function ipKey(string $kind): string
    {
        return "demo-{$kind}-ip:" . $this->request->ip() . ':' . now()->toDateString();
    }
}
