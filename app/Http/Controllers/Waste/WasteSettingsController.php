<?php

namespace App\Http\Controllers\Waste;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class WasteSettingsController extends Controller
{
    public function index(Request $request): Response
    {
        $user    = $request->user()->load('company');
        $company = $user->company;

        return Inertia::render('waste/Settings', [
            'user' => [
                'name'            => $user->name,
                'email'           => $user->email,
                'document_locale' => $user->document_locale,
            ],
            'company' => $company ? [
                'name'        => $company->name,
                'phone'       => $company->phone,
                'address'     => $company->address,
                'city'        => $company->city,
                'postal_code' => $company->postal_code,
                'country'     => $company->country,
            ] : null,
            'plan'        => $user->effectivePlan(),
            'scans_used'  => $user->aiScansUsedThisMonth(),
            'scan_quota'  => $user->aiScanQuota(),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
        ]);

        $user->name  = $validated['name'];
        $user->email = $validated['email'];
        $user->save();

        return back()->with('success', 'Profile updated.');
    }

    public function updateCompany(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'phone'       => ['nullable', 'string', 'max:50'],
            'address'     => ['nullable', 'string', 'max:255'],
            'city'        => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country'     => ['nullable', Rule::in(['NL', 'BE', 'LU', 'DE', 'FR'])],
        ]);

        $request->user()->company()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated,
        );

        // Auto-set document language on first save (does not overwrite manual choice)
        $country = $validated['country'] ?? null;
        if ($country && is_null($request->user()->document_locale)) {
            $map = ['NL' => 'nl', 'BE' => 'nl', 'DE' => 'de', 'FR' => 'fr', 'LU' => 'en'];
            $request->user()->update(['document_locale' => $map[$country]]);
        }

        return back()->with('success', 'Company updated.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($validated['current_password'], $request->user()->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'The current password is incorrect.',
            ]);
        }

        $request->user()->update([
            'password' => $validated['password'],
        ]);

        return back()->with('success', 'Password updated.');
    }

    public function updateDocumentLocale(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'document_locale' => ['nullable', Rule::in(['en', 'nl', 'de', 'fr', 'es'])],
        ]);

        $request->user()->update([
            'document_locale' => $validated['document_locale'] ?: null,
        ]);

        return back()->with('success', 'Document language updated.');
    }
}
