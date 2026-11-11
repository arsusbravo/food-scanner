<?php

namespace App\Models;

use Database\Factories\ProductLinkFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Says that two cleaned product names (receipt_lines.normalized_item) are the
 * same product for one user. `canonical_key` is always a final key, never a
 * key that is itself linked elsewhere.
 */
class ProductLink extends Model
{
    /** @use HasFactory<ProductLinkFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'item_key', 'canonical_key'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
