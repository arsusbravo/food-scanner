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
use Laravel\Fortify\TwoFactorAuthenticatable;

#[Fillable(['name', 'email', 'password', 'document_locale'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

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
        if ($this->plan !== 'free' && $this->plan_expires_at && $this->plan_expires_at->isPast()) {
            return 'free';
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

    public function aiScansUsedThisMonth(): int
    {
        return $this->wasteEntries()
            ->where('source', 'ai_scan')
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();
    }

    public function exportsUsedThisMonth(): int
    {
        return $this->userExports()
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
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
