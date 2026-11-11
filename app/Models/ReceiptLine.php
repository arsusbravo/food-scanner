<?php

namespace App\Models;

use Database\Factories\ReceiptLineFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceiptLine extends Model
{
    /** @use HasFactory<ReceiptLineFactory> */
    use HasFactory;

    protected $fillable = [
        'receipt_id',
        'description',
        'normalized_item',
        'quantity',
        'unit',
        'unit_price',
        'line_total',
        'vat_rate',
        'category',
        'price_per_kg',
        'base_unit',
        'base_price',
        'base_quantity',
        'pack_size',
        'pack_unit',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_price' => 'decimal:4',
            'line_total' => 'decimal:2',
            'vat_rate' => 'decimal:2',
            'price_per_kg' => 'decimal:4',
            'base_price' => 'decimal:4',
            'base_quantity' => 'decimal:3',
            'pack_size' => 'decimal:3',
        ];
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(Receipt::class);
    }
}
