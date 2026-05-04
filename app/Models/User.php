<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Fortify\TwoFactorAuthenticatable;

#[Fillable(['name', 'email', 'password', 'document_locale'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable, Billable;

    protected function casts(): array
    {
        return [
            'email_verified_at'      => 'datetime',
            'password'               => 'hashed',
            'two_factor_confirmed_at'=> 'datetime',
            'is_admin'               => 'boolean',
            'is_active'              => 'boolean',
            'plan_expires_at'        => 'datetime',
        ];
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    // ── Relationships ─────────────────────────────────────────

    public function company(): HasOne
    {
        return $this->hasOne(Company::class);
    }

    public function wasteEntries(): HasMany
    {
        return $this->hasMany(WasteEntry::class);
    }

    public function userExports(): HasMany
    {
        return $this->hasMany(UserExport::class);
    }

    // ── Quota helpers ─────────────────────────────────────────

    public function effectivePlan(): string
    {
        // Admin-assigned plan expiry override
        if ($this->plan !== 'free' && $this->plan_expires_at && $this->plan_expires_at->isPast()) {
            return 'free';
        }
        // Active Stripe subscription
        if ($this->subscribed('default')) {
            return 'pro';
        }

        return $this->plan ?? 'free';
    }

    public function aiScanQuota(): ?int
    {
        if ($this->ai_scan_limit !== null) {
            return $this->ai_scan_limit;
        }

        return config("plans.{$this->effectivePlan()}.ai_scans");
    }

    public function exportQuota(): ?int
    {
        if ($this->export_limit !== null) {
            return $this->export_limit;
        }

        return config("plans.{$this->effectivePlan()}.exports");
    }

    public function billingPeriodStart(): \Carbon\Carbon
    {
        // Stripe subscribers: anchor to subscription start; free users: registration date.
        $subscription = $this->subscription('default');
        $anchor = ($subscription && $subscription->active())
            ? $subscription->created_at
            : $this->created_at;

        $anchorDay = (int) $anchor->day;
        $today     = now();

        // Try the anchor day in the current month; Carbon clamps to the last day
        // of shorter months (e.g. registered on Jan 31 → Feb 28).
        $start = $today->copy()->startOfDay()->day($anchorDay);

        // If that date is still in the future, the period started last month.
        if ($start->isFuture()) {
            $start = $start->subMonthNoOverflow();
        }

        return $start;
    }

    public function aiScansUsedThisMonth(): int
    {
        return $this->wasteEntries()
            ->where('source', 'ai_scan')
            ->where('created_at', '>=', $this->billingPeriodStart())
            ->count();
    }

    public function exportsUsedThisMonth(): int
    {
        return $this->userExports()
            ->where('created_at', '>=', $this->billingPeriodStart())
            ->count();
    }

    public function canAiScan(): bool
    {
        $quota = $this->aiScanQuota();

        return $quota === null || $this->aiScansUsedThisMonth() < $quota;
    }

    public function canExport(): bool
    {
        $quota = $this->exportQuota();

        return $quota === null || $this->exportsUsedThisMonth() < $quota;
    }
}
