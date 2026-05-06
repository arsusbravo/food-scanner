<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\Company;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Support\Facades\Http;
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

        if ($mode === 'invite_only') {
            $rules['invite_token'] = [
                'required',
                Rule::exists('invitations', 'token')
                    ->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now())),
            ];
        } elseif ($mode === 'open') {
            $turnstileToken = $input['cf-turnstile-response'] ?? '';
            $verify = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v1/siteverify', [
                'secret'   => config('services.turnstile.secret'),
                'response' => $turnstileToken,
            ]);

            if (! ($verify->json('success') ?? false)) {
                throw ValidationException::withMessages([
                    'cf-turnstile-response' => 'CAPTCHA verification failed. Please try again.',
                ]);
            }
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

        return $user;
    }
}
