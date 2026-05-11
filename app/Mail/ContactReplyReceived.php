<?php

namespace App\Mail;

use App\Models\ContactConversation;
use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactReplyReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly ContactConversation $conversation,
        public readonly ContactMessage $message,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new \Illuminate\Mail\Mailables\Address('info@kitchenlog.eu', 'KitchenLog'),
            subject: 'We replied to your message — KitchenLog Support',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.contact.reply',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
