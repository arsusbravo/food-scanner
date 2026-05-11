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

class ContactMessageReceived extends Mailable
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
            replyTo: [new \Illuminate\Mail\Mailables\Address($this->user->email, $this->user->name)],
            subject: '[KitchenLog Support] New message from ' . $this->user->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.contact.received',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
