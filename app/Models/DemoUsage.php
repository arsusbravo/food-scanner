<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DemoUsage extends Model
{
    protected $fillable = [
        'device_id',
        'ip',
        'country',
        'locale',
        'referer',
        'user_agent',
        'scans',
        'reports',
    ];

    protected function casts(): array
    {
        return [
            'scans'   => 'integer',
            'reports' => 'integer',
        ];
    }

    /**
     * Per-device event log. Keyed on `device_id` (not the surrogate `id`)
     * because that's how the events are written.
     */
    public function events(): HasMany
    {
        return $this->hasMany(DemoEvent::class, 'device_id', 'device_id');
    }
}
