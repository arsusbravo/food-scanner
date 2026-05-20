<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DemoUsage extends Model
{
    protected $fillable = [
        'device_id',
        'ip',
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
}
