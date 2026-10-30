<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('regular users cannot visit the admin dashboard', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('dashboard'))->assertRedirect();
});

test('admins can visit the dashboard', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    $this->get(route('dashboard'))->assertOk();
});
