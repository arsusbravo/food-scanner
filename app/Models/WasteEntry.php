<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WasteEntry extends Model
{
    protected $fillable = [
        'user_id',
        'category',
        'item_name',
        'weight_kg',
        'reason',
        'notes',
        'logged_at',
        'source',
        'photo_path',
        'ai_raw_response',
    ];

    protected function casts(): array
    {
        return [
            'weight_kg' => 'decimal:4',
            'ai_raw_response' => 'array',
            'logged_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
