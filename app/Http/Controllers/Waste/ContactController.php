<?php

namespace App\Http\Controllers\Waste;

use App\Http\Controllers\Controller;
use App\Mail\ContactMessageReceived;
use App\Models\ContactConversation;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    public function index(Request $request): Response
    {
        $conversations = ContactConversation::where('user_id', $request->user()->id)
            ->withCount('messages')
            ->with(['messages' => fn ($q) => $q->latest()->limit(1)])
            ->orderByDesc('last_message_at')
            ->get(['id', 'subject', 'unread_by_user', 'last_message_at', 'created_at', 'user_id']);

        return Inertia::render('waste/Contact', [
            'conversations' => $conversations->map(fn ($c) => [
                'id'             => $c->id,
                'subject'        => $c->subject,
                'unread'         => $c->unread_by_user,
                'last_message_at'=> $c->last_message_at,
                'messages_count' => $c->messages_count,
                'preview'        => $c->messages->first()?->body,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => ['required', Rule::in(['question', 'feedback', 'complaint', 'other'])],
            'body'    => ['required', 'string', 'max:2000'],
        ]);

        $conversation = ContactConversation::create([
            'user_id'         => $request->user()->id,
            'subject'         => $validated['subject'],
            'unread_by_admin' => true,
            'unread_by_user'  => false,
            'last_message_at' => now(),
        ]);

        $message = ContactMessage::create([
            'conversation_id' => $conversation->id,
            'body'            => $validated['body'],
            'is_admin_reply'  => false,
        ]);

        Mail::to('info@arsus.nl')->send(new ContactMessageReceived($request->user(), $conversation, $message));

        return redirect()->route('waste.contact.show', $conversation->id);
    }

    public function show(Request $request, ContactConversation $conversation): Response
    {
        abort_if($conversation->user_id !== $request->user()->id, 403);

        $conversation->update(['unread_by_user' => false]);

        $messages = $conversation->messages()
            ->orderBy('created_at')
            ->get(['id', 'body', 'is_admin_reply', 'created_at']);

        return Inertia::render('waste/ContactConversation', [
            'conversation' => [
                'id'      => $conversation->id,
                'subject' => $conversation->subject,
            ],
            'messages' => $messages,
        ]);
    }

    public function reply(Request $request, ContactConversation $conversation): RedirectResponse
    {
        abort_if($conversation->user_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $message = ContactMessage::create([
            'conversation_id' => $conversation->id,
            'body'            => $validated['body'],
            'is_admin_reply'  => false,
        ]);

        $conversation->update([
            'unread_by_admin' => true,
            'last_message_at' => now(),
        ]);

        Mail::to('info@arsus.nl')->send(new ContactMessageReceived($request->user(), $conversation, $message));

        return back();
    }
}
