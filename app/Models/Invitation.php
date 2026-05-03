<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['token', 'email', 'note', 'created_by', 'expires_at', 'accepted_at', 'accepted_by'])]
class Invitation extends Model
{
    protected function casts(): array
    {
        return [
            'expires_at'  => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    public function scopeValid(Builder $query): Builder
    {
        return $query->whereNull('accepted_at')
            ->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function markAccepted(User $user): void
    {
        $this->update([
            'accepted_at' => now(),
            'accepted_by' => $user->id,
        ]);
    }

    public function getStatusAttribute(): string
    {
        if ($this->accepted_at) return 'used';
        if ($this->expires_at && $this->expires_at->isPast()) return 'expired';
        return 'pending';
    }
}
