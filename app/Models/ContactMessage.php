<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactMessage extends Model
{
    protected $fillable = ['conversation_id', 'body', 'is_admin_reply', 'replied_by'];

    protected $casts = ['is_admin_reply' => 'boolean'];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ContactConversation::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'replied_by');
    }
}
