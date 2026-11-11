<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceiptImage extends Model
{
    protected $fillable = ['receipt_id', 'path', 'page'];

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(Receipt::class);
    }
}
