<x-mail::message>
# New support message from {{ $user->name }}

**From:** {{ $user->name }} ({{ $user->email }})
**Subject:** {{ ucfirst($conversation->subject) }}

---

{{ $message->body }}

---

<x-mail::button :url="url('/admin/contact/' . $conversation->id)">
View conversation & reply
</x-mail::button>

KitchenLog Support
</x-mail::message>
