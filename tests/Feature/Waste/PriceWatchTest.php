<?php

use App\Models\ProductLink;
use App\Models\Receipt;
use App\Models\ReceiptLine;
use App\Models\User;
use App\Support\PriceWatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->withoutVite());

/**
 * One confirmed receipt with a single priced line.
 *
 * @param  array<string, mixed>  $line
 */
function observe(User $user, string $supplier, int $daysAgo, string $item, float $price, float $quantity = 1, array $line = [], string $status = 'confirmed'): ReceiptLine
{
    $receipt = Receipt::factory()->for($user)->create([
        'supplier_name' => $supplier,
        'invoice_date' => now()->subDays($daysAgo)->toDateString(),
        'status' => $status,
    ]);

    return ReceiptLine::factory()->for($receipt)->create([
        'description' => ucfirst($item),
        'normalized_item' => $item,
        'line_total' => $price * $quantity,
        'base_unit' => 'pc',
        'base_price' => $price,
        'base_quantity' => $quantity,
        'pack_size' => null,
        'pack_unit' => null,
        ...$line,
    ]);
}

test('a price rise of 10% or more is flagged with its euro impact', function () {
    $user = User::factory()->create();
    observe($user, 'Bidfood', 40, 'zalm', 10.00);
    observe($user, 'Bidfood', 5, 'zalm', 11.50, 4);

    $changes = (new PriceWatch($user))->changes();

    expect($changes)->toHaveCount(1)
        ->and($changes[0])->toMatchArray(['product' => 'Zalm', 'supplier' => 'Bidfood', 'change_pct' => 15.0, 'impact' => 6.0])
        ->and($changes[0]['previous'])->toMatchArray(['price' => 10.0])
        ->and($changes[0]['latest'])->toMatchArray(['price' => 11.5]);
});

test('price drops are flagged too and rises come first', function () {
    $user = User::factory()->create();
    observe($user, 'A', 30, 'melk', 2.00);
    observe($user, 'A', 3, 'melk', 1.50);
    observe($user, 'A', 30, 'kaas', 5.00);
    observe($user, 'A', 3, 'kaas', 6.00);

    $changes = (new PriceWatch($user))->changes();

    expect(array_column($changes, 'product'))->toBe(['Kaas', 'Melk'])
        ->and($changes[1]['change_pct'])->toBe(-25.0);
});

test('small changes, single observations and same-day prices are not flagged', function () {
    $user = User::factory()->create();
    observe($user, 'A', 30, 'small', 10.00);
    observe($user, 'A', 3, 'small', 10.90);          // +9%
    observe($user, 'A', 3, 'single', 5.00);          // only one observation
    observe($user, 'A', 3, 'sameday', 5.00);
    observe($user, 'A', 3, 'sameday', 9.00);          // same date, no earlier price

    expect((new PriceWatch($user))->changes())->toBe([]);
});

test('old changes, drafts and other users are ignored', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    observe($user, 'A', 400, 'old', 1.00);
    observe($user, 'A', 300, 'old', 2.00);            // latest is older than 180 days
    observe($user, 'A', 30, 'draft', 1.00);
    observe($user, 'A', 3, 'draft', 2.00, 1, [], 'draft');
    observe($other, 'A', 30, 'theirs', 1.00);
    observe($other, 'A', 3, 'theirs', 2.00);

    expect((new PriceWatch($user))->changes())->toBe([])
        ->and((new PriceWatch($other))->changes())->toHaveCount(1);
});

test('different pack sizes of a product are separate price points', function () {
    $user = User::factory()->create();
    observe($user, 'A', 30, 'boter', 2.00, 1, ['pack_size' => 0.25, 'pack_unit' => 'kg']);
    observe($user, 'A', 3, 'boter', 3.50, 1, ['pack_size' => 0.5, 'pack_unit' => 'kg']);

    expect((new PriceWatch($user))->changes())->toBe([]);
});

test('a cheaper supplier is suggested with a monthly saving', function () {
    $user = User::factory()->create();
    observe($user, 'Bidfood', 40, 'zalm', 11.50, 3);
    observe($user, 'Bidfood', 10, 'zalm', 11.50, 3);   // 6 bought from the main supplier in 90 days
    observe($user, 'Sligro', 20, 'zalm', 10.00, 1);

    $comparisons = (new PriceWatch($user))->comparisons();

    expect($comparisons)->toHaveCount(1)
        ->and($comparisons[0])->toMatchArray(['product' => 'Zalm', 'basis' => 'item', 'saving_per_unit' => 1.5, 'saving_pct' => 13.0, 'monthly_saving' => 3.0])
        ->and($comparisons[0]['current']['supplier'])->toBe('Bidfood')
        ->and($comparisons[0]['cheaper']['supplier'])->toBe('Sligro');
});

test('no comparison when the main supplier is cheapest, the gap is small or the prices are old', function () {
    $user = User::factory()->create();
    observe($user, 'A', 10, 'cheap', 5.00, 5);
    observe($user, 'B', 10, 'cheap', 6.00, 1);        // main supplier is already cheapest
    observe($user, 'A', 10, 'near', 10.00, 5);
    observe($user, 'B', 10, 'near', 9.80, 1);          // only 2% cheaper
    observe($user, 'A', 10, 'stale', 10.00, 5);
    observe($user, 'B', 200, 'stale', 5.00, 1);        // outside the 90 day window

    expect((new PriceWatch($user))->comparisons())->toBe([]);
});

test('pack sizes are compared per kg when they differ and skipped when unknown', function () {
    $user = User::factory()->create();
    // A: 250 g at 2.00 = 8/kg (main, 4 packs = 1 kg), B: 500 g at 3.00 = 6/kg.
    observe($user, 'A', 10, 'boter', 2.00, 4, ['pack_size' => 0.25, 'pack_unit' => 'kg']);
    observe($user, 'B', 10, 'boter', 3.00, 1, ['pack_size' => 0.5, 'pack_unit' => 'kg']);
    // Different buckets without sizes cannot be compared.
    observe($user, 'A', 10, 'kaas', 2.00, 4, ['pack_size' => 0.25, 'pack_unit' => 'kg']);
    observe($user, 'B', 10, 'kaas', 3.00, 1);

    $comparisons = (new PriceWatch($user))->comparisons();

    expect($comparisons)->toHaveCount(1)
        ->and($comparisons[0])->toMatchArray(['product' => 'Boter', 'basis' => 'kg', 'saving_per_unit' => 2.0, 'monthly_saving' => 0.67]);
});

test('linking two names makes them one product and unlinking splits them again', function () {
    $user = User::factory()->create();
    observe($user, 'PLUS', 10, 'pangasius', 2.50, 6);
    observe($user, 'Aldi', 10, 'pangasiusfilet', 2.00, 1);

    $watch = new PriceWatch($user);
    expect($watch->comparisons())->toBe([]);

    expect($watch->link('pangasiusfilet', 'pangasius'))->toBeTrue();
    $comparisons = (new PriceWatch($user))->comparisons();
    expect($comparisons)->toHaveCount(1)
        ->and($comparisons[0]['current']['supplier'])->toBe('PLUS')
        ->and($comparisons[0]['cheaper']['supplier'])->toBe('Aldi');

    ProductLink::where('user_id', $user->id)->delete();
    expect((new PriceWatch($user))->comparisons())->toBe([]);
});

test('links never chain and linking unknown names does nothing', function () {
    $user = User::factory()->create();
    foreach (['a', 'b', 'c'] as $name) {
        observe($user, 'S'.$name, 10, $name, 1.0);
    }

    $watch = new PriceWatch($user);
    $watch->link('a', 'b');
    $watch->link('b', 'c');

    expect(ProductLink::where('user_id', $user->id)->pluck('canonical_key', 'item_key')->all())->toBe(['a' => 'c', 'b' => 'c'])
        ->and($watch->link('a', 'c'))->toBeFalse()
        ->and($watch->link('a', 'nonexistent'))->toBeFalse();
});

test('similar names at different suppliers are suggested, same supplier or unrelated names are not', function () {
    $user = User::factory()->create();
    observe($user, 'PLUS', 10, 'pangasius', 2.50);
    observe($user, 'Aldi', 10, 'pangasiusfilet', 2.00);
    observe($user, 'PLUS', 10, 'kipfilet', 5.00);
    observe($user, 'PLUS', 10, 'kipfilet vers', 5.20);   // same supplier
    observe($user, 'Aldi', 10, 'chocolade', 1.00);

    $suggestions = (new PriceWatch($user))->suggestions();

    expect($suggestions)->toHaveCount(1)
        ->and([$suggestions[0]['from_key'], $suggestions[0]['to_key']])->toContain('pangasius', 'pangasiusfilet');
});

test('the prices page shows changes, comparisons and the tracked line count', function () {
    $user = User::factory()->create();
    observe($user, 'Bidfood', 40, 'zalm', 10.00, 3);
    observe($user, 'Bidfood', 5, 'zalm', 11.50, 3);
    observe($user, 'Sligro', 20, 'zalm', 10.00, 1);

    $this->actingAs($user)->get(route('waste.report.prices'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('waste/ReportPrices')
            ->where('trackedLines', 3)
            ->has('changes', 1)
            ->has('comparisons', 1)
            ->where('potentialSaving', fn ($saving) => $saving > 0)
            ->where('threshold', 10));
});

test('the prices page is empty for a user without price data and closed to guests', function () {
    $this->get(route('waste.report.prices'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create())->get(route('waste.report.prices'))
        ->assertInertia(fn (Assert $page) => $page->where('trackedLines', 0)->where('potentialSaving', 0)->has('changes', 0));
});

test('products can be linked and unlinked from the page, only by their owner', function () {
    $user = User::factory()->create();
    observe($user, 'PLUS', 10, 'pangasius', 2.50);
    observe($user, 'Aldi', 10, 'pangasiusfilet', 2.00);

    $this->actingAs($user)->post(route('waste.report.prices.link'), ['from_key' => 'pangasiusfilet', 'to_key' => 'pangasius'])->assertRedirect();
    $link = ProductLink::where('user_id', $user->id)->firstOrFail();

    $this->actingAs(User::factory()->create())->delete(route('waste.report.prices.unlink', $link))->assertNotFound();
    expect(ProductLink::count())->toBe(1);

    $this->actingAs($user)->delete(route('waste.report.prices.unlink', $link))->assertRedirect();
    expect(ProductLink::count())->toBe(0);
});

test('linking names that are not on the users receipts is refused', function () {
    $user = User::factory()->create();
    observe($user, 'PLUS', 10, 'pangasius', 2.50);
    observe(User::factory()->create(), 'Aldi', 10, 'notmine', 2.00);

    $this->actingAs($user)->post(route('waste.report.prices.link'), ['from_key' => 'notmine', 'to_key' => 'pangasius'])->assertStatus(422);

    expect(ProductLink::count())->toBe(0);
});

test('the backfill command adds price data to old lines and leaves discounts alone', function () {
    $user = User::factory()->create();
    $receipt = Receipt::factory()->for($user)->create();
    $old = ReceiptLine::factory()->for($receipt)->create(['description' => 'Boter 250 g', 'line_total' => 3.5, 'quantity' => null, 'unit' => null, 'unit_price' => null, 'base_unit' => null, 'base_price' => null, 'base_quantity' => null]);
    $discount = ReceiptLine::factory()->for($receipt)->create(['description' => 'Korting', 'line_total' => -1, 'quantity' => null, 'unit' => null, 'unit_price' => null, 'base_unit' => null, 'base_price' => null, 'base_quantity' => null]);

    $this->artisan('receipts:backfill-prices')->expectsOutput('Updated 1 receipt lines.')->assertSuccessful();

    expect((float) $old->fresh()->base_price)->toBe(3.5)
        ->and((float) $old->fresh()->pack_size)->toBe(0.25)
        ->and($discount->fresh()->base_price)->toBeNull();
});
