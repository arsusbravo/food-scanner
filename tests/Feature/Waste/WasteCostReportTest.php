<?php

use App\Models\User;
use App\Models\WasteEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function userWithEntries(?array $prices): User
{
    $user = User::factory()->create(['waste_prices' => $prices, 'currency' => 'EUR']);

    WasteEntry::factory()->for($user)->create(['category' => 'protein', 'weight_kg' => 2, 'logged_at' => now()]);
    WasteEntry::factory()->for($user)->create(['category' => 'veg', 'weight_kg' => 4, 'logged_at' => now()]);

    return $user;
}

test('the report computes cost and co2e from prices', function () {
    $user = userWithEntries(['protein' => 10, 'veg' => 2]);

    $this->actingAs($user)
        ->get(route('waste.report.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('waste/Report')
            ->where('summary.protein.cost', 20)
            ->where('summary.veg.cost', 8)
            ->where('summary.dairy.cost', null)
            ->where('totalCost', 28)
            // 2 kg protein x 15 + 4 kg veg x 1.5
            ->where('totalCo2e', 36)
            ->where('currency', 'EUR'));
});

test('the report has null cost when no prices are set', function () {
    $user = userWithEntries(null);

    $this->actingAs($user)
        ->get(route('waste.report.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('totalCost', null)
            ->where('summary.protein.cost', null)
            ->where('totalCo2e', 36));
});

test('insights expose monthly cost and prices state', function () {
    $user = userWithEntries(['protein' => 10, 'veg' => 2]);

    $this->actingAs($user)
        ->get(route('waste.insights.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('hasCostData', true)
            ->where('thisMonthCost', 28)
            ->where('thisMonthCo2e', 36)
            ->where('costByCategory.protein', 20));
});

test('insights report prices as unset when none saved', function () {
    $user = userWithEntries(null);

    $this->actingAs($user)
        ->get(route('waste.insights.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('hasCostData', false)
            ->where('thisMonthCost', 0));
});

test('home shows this weeks cost', function () {
    $user = userWithEntries(['protein' => 10, 'veg' => 2]);
    $user->update(['document_locale' => 'en']);

    $this->actingAs($user)
        ->get(route('waste.home'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('hasCostData', true)
            ->where('weekCost', 28));
});

test('csv includes cost columns only when prices are set', function () {
    $withPrices = userWithEntries(['protein' => 10, 'veg' => 2]);
    $csv = $this->actingAs($withPrices)->get(route('waste.report.csv'))->getContent();
    expect($csv)->toContain('CO2e (kg),Cost (EUR)');

    $withoutPrices = userWithEntries(null);
    $csv = $this->actingAs($withoutPrices)->get(route('waste.report.csv'))->getContent();
    expect($csv)->toContain('CO2e (kg)')->not->toContain('Cost (');
});

test('the pdf renders with and without prices', function (?array $prices) {
    $user = userWithEntries($prices);

    $this->actingAs($user)
        ->get(route('waste.report.pdf'))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
})->with([
    'with prices' => [['protein' => 10, 'veg' => 2]],
    'without prices' => [null],
]);

test('an entry can be stored with its own cost', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('waste.entries.store'), [
        'category' => 'protein',
        'item_name' => 'Chicken breast',
        'weight_kg' => 2,
        'cost' => 19.5,
        'reason' => 'spoilage',
    ])->assertSessionHasNoErrors();

    expect(WasteEntry::first()->cost)->toBe('19.50');
});

test('a negative entry cost is rejected', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('waste.entries.store'), [
        'category' => 'protein',
        'item_name' => 'Chicken',
        'weight_kg' => 2,
        'cost' => -5,
        'reason' => 'spoilage',
    ])->assertSessionHasErrors('cost');
});

test('entry cost overrides category price and unpriced entries fall back', function () {
    $user = User::factory()->create(['waste_prices' => ['protein' => 10]]);
    // 2 kg with own cost 50 (instead of 2 x 10), 1 kg falls back to 1 x 10.
    WasteEntry::factory()->for($user)->create(['category' => 'protein', 'weight_kg' => 2, 'cost' => 50]);
    WasteEntry::factory()->for($user)->create(['category' => 'protein', 'weight_kg' => 1, 'cost' => null]);

    $this->actingAs($user)
        ->get(route('waste.report.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.protein.cost', 60)
            ->where('totalCost', 60));
});

test('entry costs alone are enough without category prices', function () {
    $user = User::factory()->create();
    WasteEntry::factory()->for($user)->create(['category' => 'veg', 'weight_kg' => 2, 'cost' => 7]);
    WasteEntry::factory()->for($user)->create(['category' => 'dairy', 'weight_kg' => 3, 'cost' => null]);

    $this->actingAs($user)
        ->get(route('waste.insights.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('hasCostData', true)
            ->where('thisMonthCost', 7));
});

test('item prices are suggested from the most recent costed entry', function () {
    $user = User::factory()->create();
    WasteEntry::factory()->for($user)->create(['item_name' => 'Chicken Breast', 'weight_kg' => 2, 'cost' => 16, 'logged_at' => now()->subDays(2)]);
    WasteEntry::factory()->for($user)->create(['item_name' => 'chicken breast ', 'weight_kg' => 2, 'cost' => 19, 'logged_at' => now()]);
    WasteEntry::factory()->for($user)->create(['item_name' => 'Lettuce', 'weight_kg' => 1, 'cost' => null]);

    $this->actingAs($user)
        ->get(route('waste.entries.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('itemPrices.chicken breast', 9.5)
            ->missing('itemPrices.lettuce'));
});

test('entry notes are passed to the log and home lists', function () {
    $user = User::factory()->create(['document_locale' => 'en']);
    WasteEntry::factory()->for($user)->create(['notes' => 'Delivery arrived warm']);

    $this->actingAs($user)->get(route('waste.entries.index'))
        ->assertInertia(fn (Assert $page) => $page->where('recentEntries.0.notes', 'Delivery arrived warm'));

    $this->actingAs($user)->get(route('waste.home'))
        ->assertInertia(fn (Assert $page) => $page->where('recentEntries.0.notes', 'Delivery arrived warm'));

    $this->actingAs($user)->get(route('waste.report.pdf'))->assertOk();
});
