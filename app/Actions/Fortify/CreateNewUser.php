<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\Company;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\Turnstile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    public function create(array $input): User
    {
        $mode = SiteSetting::get('registration_mode', 'invite_only');

        $rules = [
            ...$this->profileRules(),
            'password'     => $this->passwordRules(),
            'company_name' => ['required', 'string', 'max:255'],
        ];

        // Cloudflare Turnstile is enforced for every registration, regardless
        // of mode (invite-only or open).
        if (! Turnstile::verify($input['cf-turnstile-response'] ?? null, request()->ip())) {
            throw ValidationException::withMessages([
                'cf-turnstile-response' => 'CAPTCHA verification failed. Please try again.',
            ]);
        }

        if ($mode === 'invite_only') {
            $rules['invite_token'] = [
                'required',
                Rule::exists('invitations', 'token')
                    ->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now())),
            ];
        }

        Validator::make($input, $rules)->validate();

        $user = User::create([
            'name'     => $input['name'],
            'email'    => $input['email'],
            'password' => $input['password'],
        ]);

        Company::create([
            'user_id' => $user->id,
            'name'    => $input['company_name'],
        ]);

        // Link the new account back to its demo device so we can compute
        // demo → signup conversion in admin/DemoStats.
        if ($deviceId = request()->cookie('demo_id')) {
            $user->forceFill(['demo_device_id' => $deviceId])->save();
            Log::info('[Demo] conversion', ['user_id' => $user->id, 'device_id' => $deviceId]);
        }

        return $user;
    }
}
