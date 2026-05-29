<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Immutable demo-interaction event. Insert-only — no `updated_at`, no
 * Eloquent timestamps; we set `created_at` manually so the schema stays
 * `created_at`-only (see migration).
 */
class DemoEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'device_id',
        'type',
        'ip',
        'country',
        'locale',
        'referer',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }
}
