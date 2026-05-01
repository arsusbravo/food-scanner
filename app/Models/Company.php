<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'name', 'phone', 'address', 'city', 'postal_code', 'country'])]
class Company extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
