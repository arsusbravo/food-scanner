<?php

use App\Models\Receipt;
use App\Models\ReceiptLine;
use App\Models\User;
use App\Models\WasteEntry;
use App\Support\PurchaseSummary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->withoutVite());

function receiptWithLines(User $user, array $attributes, array $lines): Receipt
{
    $receipt = Receipt::factory()->for($user)->create($attributes);

    foreach ($lines as [$category, $amount]) {
        ReceiptLine::factory()->for($receipt)->create(['category' => $category, 'line_total' => $amount]);
    }

    return $receipt;
}

test('the summary counts only confirmed receipts of the user inside the dates', function () {
    $user = User::factory()->create();
    $today = now()->toDateString();

    receiptWithLines($user, ['supplier_name' => 'Bidfood', 'gross_total' => 100, 'vat_total' => 9, 'invoice_date' => $today], [['veg', 60], ['beverage', 40]]);
    receiptWithLines($user, ['supplier_name' => 'Bidfood', 'gross_total' => 50, 'vat_total' => 4.5, 'invoice_date' => $today], [['dairy', 50]]);
    receiptWithLines($user, ['supplier_name' => 'Sligro', 'gross_total' => 20, 'vat_total' => 2, 'invoice_date' => $today], [['dry_goods', 20]]);
    receiptWithLines($user, ['supplier_name' => 'Old', 'gross_total' => 999, 'invoice_date' => now()->subMonths(4)->toDateString()], [['veg', 999]]);
    Receipt::factory()->draft()->for($user)->create(['gross_total' => 777, 'invoice_date' => $today]);
    receiptWithLines(User::factory()->create(), ['gross_total' => 555, 'invoice_date' => $today], [['veg', 555]]);

    $summary = new PurchaseSummary($user);
    $from = now()->startOfMonth();
    $to = now()->endOfMonth();

    expect($summary->totals($from, $to))->toBe(['spend' => 170.0, 'vat' => 15.5, 'count' => 3])
        ->and($summary->totals())->toBe(['spend' => 1169.0, 'vat' => 24.5, 'count' => 4])
        ->and($summary->bySupplier($from, $to))->toBe([
            ['supplier' => 'Bidfood', 'total' => 150.0, 'count' => 2],
            ['supplier' => 'Sligro', 'total' => 20.0, 'count' => 1],
        ])
        ->and($summary->byCategory($from, $to))->toBe(['veg' => 60.0, 'dairy' => 50.0, 'beverage' => 40.0, 'dry_goods' => 20.0])
        // food = protein, veg, dairy, prepared, dry goods (not beverages)
        ->and($summary->foodSpend($from, $to))->toBe(130.0);
});

test('the purchases report defaults to this month and offers other periods', function () {
    $user = User::factory()->create();
    receiptWithLines($user, ['gross_total' => 100, 'invoice_date' => now()->toDateString()], [['veg', 100]]);
    receiptWithLines($user, ['gross_total' => 40, 'invoice_date' => now()->subMonthNoOverflow()->toDateString()], [['veg', 40]]);
    receiptWithLines($user, ['gross_total' => 5, 'invoice_date' => now()->subYears(2)->toDateString()], [['veg', 5]]);

    $this->actingAs($user);

    $this->get(route('waste.report.purchases'))
        ->assertInertia(fn (Assert $page) => $page->component('waste/ReportPurchases')->where('period', 'this_month')->where('totals.spend', 100)->where('hasAnyReceipts', true));

    $this->get(route('waste.report.purchases', ['period' => 'last_month']))
        ->assertInertia(fn (Assert $page) => $page->where('totals.spend', 40));

    $this->get(route('waste.report.purchases', ['period' => 'all']))
        ->assertInertia(fn (Assert $page) => $page->where('totals.spend', 145)->where('dateFrom', ''));

    $this->get(route('waste.report.purchases', ['period' => 'custom', 'date_from' => now()->subYears(3)->toDateString(), 'date_to' => now()->subYear()->toDateString()]))
        ->assertInertia(fn (Assert $page) => $page->where('totals.spend', 5));
});

test('the purchases report knows when the user has no receipts at all', function () {
    $this->actingAs(User::factory()->create())->get(route('waste.report.purchases'))
        ->assertInertia(fn (Assert $page) => $page->where('hasAnyReceipts', false)->where('totals.count', 0));
});

test('guests cannot open the purchases report', function () {
    $this->get(route('waste.report.purchases'))->assertRedirect(route('login'));
});

test('insights combine purchases and waste into a bought versus binned share', function () {
    $user = User::factory()->create(['waste_prices' => ['veg' => 2]]);
    receiptWithLines($user, ['supplier_name' => 'Bidfood', 'gross_total' => 100, 'invoice_date' => now()->toDateString()], [['veg', 80], ['beverage', 20]]);
    // 5 kg of vegetables binned at 2 per kg = 10 of waste cost, against 80 of food bought.
    WasteEntry::factory()->for($user)->create(['category' => 'veg', 'weight_kg' => 5, 'logged_at' => now()]);

    $this->actingAs($user)->get(route('waste.insights.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('purchases.spendThisMonth', 100)
            ->where('purchases.foodSpend', 80)
            ->where('purchases.wasteCost', 10)
            ->where('purchases.binnedPercent', 12.5)
            ->where('purchases.bySupplier.0.supplier', 'Bidfood'));
});

test('insights show no bought versus binned share without purchases or waste cost', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('waste.insights.index'))
        ->assertInertia(fn (Assert $page) => $page->where('purchases.binnedPercent', null)->where('purchases.receiptsThisMonth', 0));
});

test('the waste report supports the same period choices', function () {
    $user = User::factory()->create();
    WasteEntry::factory()->for($user)->create(['weight_kg' => 1, 'logged_at' => now()]);
    WasteEntry::factory()->for($user)->create(['weight_kg' => 2, 'logged_at' => now()->subMonthNoOverflow()->startOfMonth()->addDay()]);
    WasteEntry::factory()->for($user)->create(['weight_kg' => 4, 'logged_at' => now()->subMonths(8)]);

    $this->actingAs($user);

    $this->get(route('waste.report.index'))
        ->assertInertia(fn (Assert $page) => $page->component('waste/Report')->where('period', 'this_month')->where('grandTotal', 1));

    $this->get(route('waste.report.index', ['period' => 'last_month']))
        ->assertInertia(fn (Assert $page) => $page->where('period', 'last_month')->where('grandTotal', 2));

    $this->get(route('waste.report.index', ['period' => 'all']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('grandTotal', 7)
            ->where('dateFrom', now()->subMonths(8)->toDateString())
            ->where('dateTo', now()->toDateString()));

    $this->get(route('waste.report.index', ['date_from' => now()->subMonths(9)->toDateString(), 'date_to' => now()->subMonths(7)->toDateString()]))
        ->assertInertia(fn (Assert $page) => $page->where('period', 'custom')->where('grandTotal', 4));
});
