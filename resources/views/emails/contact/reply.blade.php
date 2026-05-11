<x-mail::message>
# You have a reply from KitchenLog Support

Hi {{ $user->name }},

Our support team replied to your **{{ ucfirst($conversation->subject) }}** message.

---

{{ $message->body }}

---

<x-mail::button :url="url('/waste/contact/' . $conversation->id)">
View conversation & reply
</x-mail::button>

KitchenLog Support<br>
[info@kitchenlog.eu](mailto:info@kitchenlog.eu)
</x-mail::message>
