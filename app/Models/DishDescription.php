<?php

namespace App\Models;

use Database\Factories\DishDescriptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A menu title and description written from a dish photo. The photo itself is
 * never stored.
 */
class DishDescription extends Model
{
    /** @use HasFactory<DishDescriptionFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'dish_name', 'origin', 'title', 'description', 'confidence', 'locale'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
