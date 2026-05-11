<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\ContactReplyReceived;
use App\Models\ContactConversation;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    public function index(): Response
    {
        $conversations = ContactConversation::with('user:id,name,email')
            ->withCount('messages')
            ->addSelect([
                'preview' => ContactMessage::select('body')
                    ->whereColumn('conversation_id', 'contact_conversations.id')
                    ->orderByDesc('created_at')
                    ->limit(1),
            ])
            ->orderByDesc('last_message_at')
            ->get(['id', 'user_id', 'subject', 'unread_by_admin', 'last_message_at']);

        return Inertia::render('admin/Contact', [
            'conversations' => $conversations->map(fn ($c) => [
                'id'             => $c->id,
                'subject'        => $c->subject,
                'unread'         => $c->unread_by_admin,
                'last_message_at'=> $c->last_message_at,
                'messages_count' => $c->messages_count,
                'preview'        => $c->preview,
                'user'           => $c->user,
            ]),
        ]);
    }

    public function show(ContactConversation $conversation): Response
    {
        $conversation->update(['unread_by_admin' => false]);

        $messages = $conversation->messages()
            ->orderBy('created_at')
            ->get(['id', 'body', 'is_admin_reply', 'created_at']);

        return Inertia::render('admin/ContactThread', [
            'conversation' => [
                'id'      => $conversation->id,
                'subject' => $conversation->subject,
                'user'    => $conversation->user->only(['id', 'name', 'email']),
            ],
            'messages' => $messages,
        ]);
    }

    public function reply(Request $request, ContactConversation $conversation): RedirectResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $reply = ContactMessage::create([
            'conversation_id' => $conversation->id,
            'body'            => $validated['body'],
            'is_admin_reply'  => true,
            'replied_by'      => $request->user()->id,
        ]);

        $conversation->update([
            'unread_by_user'  => true,
            'last_message_at' => now(),
        ]);

        $user = $conversation->user;
        Mail::to($user->email, $user->name)->send(new ContactReplyReceived($user, $conversation, $reply));

        return back();
    }
}
