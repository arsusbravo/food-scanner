<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class RegistrationController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/Registrations', [
            'mode' => SiteSetting::get('registration_mode', 'invite_only'),
            'invitations' => Invitation::with(['createdBy:id,name', 'acceptedBy:id,name'])
                ->latest()
                ->get()
                ->map(fn($inv) => [
                    'id'          => $inv->id,
                    'email'       => $inv->email,
                    'note'        => $inv->note,
                    'created_by'  => $inv->createdBy?->name,
                    'expires_at'  => $inv->expires_at?->toDateString(),
                    'accepted_at' => $inv->accepted_at?->toDateTimeString(),
                    'accepted_by' => $inv->acceptedBy?->name,
                    'status'      => $inv->status,
                    'url'         => url('/register?token=' . $inv->token),
                ]),
        ]);
    }

    public function updateMode(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mode' => ['required', Rule::in(['invite_only', 'open'])],
        ]);

        SiteSetting::set('registration_mode', $validated['mode']);

        return back();
    }

    public function storeInvitation(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email'      => ['nullable', 'string', 'max:255'],
            'note'       => ['nullable', 'string', 'max:255'],
            'expires_at' => ['nullable', 'date', 'after:today'],
        ]);

        Invitation::create([
            'token'      => bin2hex(random_bytes(24)),
            'email'      => $validated['email'] ?: null,
            'note'       => $validated['note'] ?: null,
            'created_by' => $request->user()->id,
            'expires_at' => $validated['expires_at'] ?: null,
        ]);

        return back();
    }

    public function destroyInvitation(Invitation $invitation): RedirectResponse
    {
        $invitation->delete();
        return back();
    }
}
