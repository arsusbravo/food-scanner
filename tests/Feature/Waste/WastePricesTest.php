<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a user can save prices and currency', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('waste.settings.prices'), [
            'currency' => 'GBP',
            'prices' => ['protein' => '12.5', 'veg' => '', 'dairy' => '4', 'prepared' => null],
        ])
        ->assertSessionHasNoErrors();

    $user->refresh();

    expect($user->currency)->toBe('GBP')
        ->and($user->waste_prices)->toEqual(['protein' => 12.5, 'veg' => null, 'dairy' => 4.0, 'prepared' => null]);
});

test('invalid prices and currencies are rejected', function (array $payload, string $errorKey) {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('waste.settings.prices'), $payload)
        ->assertSessionHasErrors($errorKey);
})->with([
    'negative price' => [['currency' => 'EUR', 'prices' => ['protein' => -1]], 'prices.protein'],
    'non numeric price' => [['currency' => 'EUR', 'prices' => ['veg' => 'abc']], 'prices.veg'],
    'unknown currency' => [['currency' => 'XXX', 'prices' => ['dairy' => 1]], 'currency'],
]);

test('saving prices does not change another user', function () {
    $user = User::factory()->create();
    $other = User::factory()->create(['waste_prices' => ['protein' => 9], 'currency' => 'USD']);

    $this->actingAs($user)->patch(route('waste.settings.prices'), [
        'currency' => 'EUR',
        'prices' => ['protein' => 3],
    ]);

    expect($other->fresh()->waste_prices)->toBe(['protein' => 9])
        ->and($other->fresh()->currency)->toBe('USD');
});

test('guests cannot save prices', function () {
    $this->patch(route('waste.settings.prices'), ['currency' => 'EUR', 'prices' => []])
        ->assertRedirect(route('login'));
});
