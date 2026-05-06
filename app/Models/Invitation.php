<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['token', 'email', 'note', 'created_by', 'expires_at', 'clicks'])]
class Invitation extends Model
{
    protected function casts(): array
    {
        return ['expires_at' => 'datetime'];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeValid(Builder $query): Builder
    {
        return $query->where(
            fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now())
        );
    }

    public function getStatusAttribute(): string
    {
        if ($this->expires_at && $this->expires_at->isPast()) return 'expired';
        return 'active';
    }
}
