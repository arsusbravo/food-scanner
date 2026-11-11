<?php

namespace App\Models;

use Database\Factories\ReceiptFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Receipt extends Model
{
    /** @use HasFactory<ReceiptFactory> */
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_CONFIRMED = 'confirmed';

    /** Line categories; the first four match the waste categories. */
    public const LINE_CATEGORIES = ['protein', 'veg', 'dairy', 'prepared', 'beverage', 'dry_goods', 'non_food', 'other'];

    /** Line categories that count as food when comparing purchases with waste. */
    public const FOOD_CATEGORIES = ['protein', 'veg', 'dairy', 'prepared', 'dry_goods'];

    protected $fillable = [
        'user_id',
        'supplier_name',
        'supplier_vat_id',
        'invoice_number',
        'invoice_date',
        'currency',
        'net_total',
        'vat_total',
        'gross_total',
        'confidence',
        'status',
        'notes',
        'ai_raw_response',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'net_total' => 'decimal:2',
            'vat_total' => 'decimal:2',
            'gross_total' => 'decimal:2',
            'ai_raw_response' => 'array',
        ];
    }

    protected static function booted(): void
    {
        // Remove the stored photos together with the receipt.
        static::deleting(function (Receipt $receipt): void {
            // One photo can show several receipts, so only remove a file nobody else uses.
            $receipt->images->each(function (ReceiptImage $image) use ($receipt): void {
                $stillUsed = ReceiptImage::where('path', $image->path)->where('receipt_id', '!=', $receipt->id)->exists();

                if (! $stillUsed) {
                    Storage::delete($image->path);
                }
            });

            // Drop our own rows now so the next receipt sharing a file sees it is the last user.
            $receipt->images()->delete();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(ReceiptLine::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ReceiptImage::class)->orderBy('page');
    }

    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    /**
     * True when the line totals match neither the net nor the gross total
     * (lines may be printed with or without VAT), within a small tolerance.
     */
    public function hasLineTotalMismatch(): bool
    {
        $lineTotals = $this->lines->pluck('line_total')->filter(fn ($total) => $total !== null);

        if ($lineTotals->isEmpty()) {
            return false;
        }

        $sum = (float) $lineTotals->sum();

        foreach ([$this->gross_total, $this->net_total] as $total) {
            if ($total !== null && abs($sum - (float) $total) <= max(0.05, 0.01 * (float) $total)) {
                return false;
            }
        }

        return $this->gross_total !== null || $this->net_total !== null;
    }

    /**
     * Another receipt of the same user with the same supplier, invoice number
     * and total, which usually means the same document was scanned twice.
     */
    public function findDuplicate(): ?self
    {
        if ($this->invoice_number === null || $this->invoice_number === '' || $this->gross_total === null) {
            return null;
        }

        return self::where('user_id', $this->user_id)
            ->where('supplier_name', $this->supplier_name)
            ->where('invoice_number', $this->invoice_number)
            ->where('gross_total', $this->gross_total)
            ->when($this->exists, fn (Builder $query) => $query->whereKeyNot($this->getKey()))
            ->first();
    }
}
