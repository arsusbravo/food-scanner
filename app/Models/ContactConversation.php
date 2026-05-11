<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContactConversation extends Model
{
    protected $fillable = ['user_id', 'subject', 'unread_by_user', 'unread_by_admin', 'last_message_at'];

    protected $casts = [
        'unread_by_user'  => 'boolean',
        'unread_by_admin' => 'boolean',
        'last_message_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ContactMessage::class, 'conversation_id');
    }
}
