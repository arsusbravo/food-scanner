<?php

use App\Models\SiteSetting;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register when registration is open', function () {
    SiteSetting::set('registration_mode', 'open');
    config(['services.turnstile.enabled' => false]);

    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'company_name' => 'Test Kitchen',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('waste.home', absolute: false));
});
