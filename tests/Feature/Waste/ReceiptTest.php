<?php

use App\Models\Receipt;
use App\Models\ReceiptLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function fakePhoto(): string
{
    $image = imagecreatetruecolor(40, 40);
    ob_start();
    imagejpeg($image);

    return base64_encode((string) ob_get_clean());
}

function fakeAiReceipt(): void
{
    Http::fake([
        'api.anthropic.com/*' => Http::response(['content' => [['text' => json_encode([
            'supplier_name' => 'Bidfood',
            'invoice_number' => 'INV-77',
            'invoice_date' => '2026-11-03',
            'currency' => 'EUR',
            'net_total' => 100,
            'vat_total' => 9,
            'gross_total' => 109,
            'confidence' => 'high',
            'lines' => [
                ['description' => 'Kipfilet', 'amount' => 19, 'category' => 'protein'],
                ['description' => 'Tomaten', 'amount' => 90, 'category' => 'veg'],
            ],
        ])]]]),
    ]);
}

function payload(array $overrides = []): array
{
    return [
        'invoice_date' => '2026-11-03',
        'currency' => 'EUR',
        'gross_total' => 34,
        'supplier_name' => 'Bidfood',
        'lines' => [
            ['description' => 'Kipfilet', 'line_total' => 19, 'category' => 'protein'],
            ['description' => 'Tomaten', 'line_total' => 15, 'category' => 'veg'],
        ],
        ...$overrides,
    ];
}

beforeEach(function () {
    $this->withoutVite();
    Storage::fake();
    config(['services.anthropic.key' => 'test', 'services.ai.provider' => 'anthropic']);
});

test('a scan creates a draft with lines and stores the images', function () {
    fakeAiReceipt();
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('waste.receipts.scan'), ['photos' => [fakePhoto(), fakePhoto()]])
        ->assertOk()
        ->assertJsonCount(1, 'receipts')
        ->assertJsonCount(2, 'receipts.0.lines')
        ->assertJsonPath('receipts.0.supplier_name', 'Bidfood')
        ->assertJsonPath('receipts.0.status', 'draft')
        ->assertJsonPath('receipts.0.duplicate_id', null)
        ->assertJsonPath('categories.0', 'protein')
        ->assertJsonPath('skipped', 0);

    $receipt = Receipt::findOrFail($response->json('receipts.0.id'));

    expect($receipt->status)->toBe('draft')
        ->and($receipt->supplier_name)->toBe('Bidfood')
        ->and($receipt->lines)->toHaveCount(2)
        ->and($receipt->images)->toHaveCount(2)
        ->and($receipt->lines->firstWhere('description', 'Kipfilet')->line_total)->toBe('19.00');

    Storage::assertExists($receipt->images->first()->path);
});

test('a scan is refused for invalid images', function () {
    $this->actingAs(User::factory()->create())
        ->postJson(route('waste.receipts.scan'), ['photos' => [base64_encode(str_repeat('x', 200))]])
        ->assertStatus(422);

    Http::assertNothingSent();
});

test('a scan is blocked when the monthly receipt quota is used up', function () {
    fakeAiReceipt();
    config(['plans.free.receipts' => 1]);
    $user = User::factory()->create();
    Receipt::factory()->for($user)->create();

    $this->actingAs($user)->postJson(route('waste.receipts.scan'), ['photos' => [fakePhoto()]])
        ->assertForbidden();

    Http::assertNothingSent();
});

test('a failing ai call returns a client-safe error and saves nothing', function () {
    Http::fake(['api.anthropic.com/*' => Http::response(['error' => ['message' => 'overloaded']], 529)]);
    $user = User::factory()->create();

    $this->actingAs($user)->postJson(route('waste.receipts.scan'), ['photos' => [fakePhoto()]])
        ->assertStatus(422);

    expect(Receipt::count())->toBe(0);
});

test('a draft can be edited and confirmed', function () {
    $user = User::factory()->create();
    $receipt = Receipt::factory()->draft()->for($user)->create();

    $this->actingAs($user)->patch(route('waste.receipts.update', $receipt), payload(['confirm' => true]))
        ->assertRedirect(route('waste.receipts.index'));

    $receipt->refresh();

    expect($receipt->status)->toBe('confirmed')
        ->and($receipt->lines)->toHaveCount(2)
        ->and($receipt->lines->firstWhere('description', 'Tomaten')->normalized_item)->toBe('tomaten');
});

test('saving without confirming keeps the draft status', function () {
    $user = User::factory()->create();
    $receipt = Receipt::factory()->draft()->for($user)->create();

    $this->actingAs($user)->patch(route('waste.receipts.update', $receipt), payload())->assertRedirect();

    expect($receipt->fresh()->status)->toBe('draft');
});

test('invalid receipt data is rejected', function (array $overrides, string $key) {
    $user = User::factory()->create();
    $receipt = Receipt::factory()->draft()->for($user)->create();

    $this->actingAs($user)->patch(route('waste.receipts.update', $receipt), payload($overrides))
        ->assertSessionHasErrors($key);
})->with([
    'missing total' => [['gross_total' => null], 'gross_total'],
    'bad category' => [['lines' => [['description' => 'x', 'category' => 'bogus']]], 'lines.0.category'],
    'non numeric amount' => [['lines' => [['description' => 'x', 'category' => 'veg', 'line_total' => 'abc']]], 'lines.0.line_total'],
]);

test('other users cannot see, change, delete or download a receipt', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $receipt = Receipt::factory()->for($owner)->create();
    $image = $receipt->images()->create(['path' => 'receipts/1/x.jpg', 'page' => 1]);
    Storage::put($image->path, 'data');

    $this->actingAs($stranger);

    $this->get(route('waste.receipts.show', $receipt))->assertNotFound();
    $this->patch(route('waste.receipts.update', $receipt), payload())->assertForbidden();
    $this->delete(route('waste.receipts.destroy', $receipt))->assertNotFound();
    $this->get(route('waste.receipts.image', [$receipt, $image]))->assertNotFound();

    expect(Receipt::find($receipt->id))->not->toBeNull();
});

test('the owner can view the image and deleting removes receipt and files', function () {
    $user = User::factory()->create();
    $receipt = Receipt::factory()->for($user)->create();
    $image = $receipt->images()->create(['path' => "receipts/{$user->id}/x.jpg", 'page' => 1]);
    Storage::put($image->path, 'data');

    $this->actingAs($user)->get(route('waste.receipts.image', [$receipt, $image]))->assertOk();
    $this->delete(route('waste.receipts.destroy', $receipt))->assertRedirect(route('waste.receipts.index'));

    expect(Receipt::count())->toBe(0);
    Storage::assertMissing($image->path);
});

test('show flags duplicates and line total mismatches', function () {
    $user = User::factory()->create();
    $first = Receipt::factory()->for($user)->create(['supplier_name' => 'Bidfood', 'invoice_number' => 'A1', 'gross_total' => 50, 'net_total' => 45]);
    $second = Receipt::factory()->draft()->for($user)->create(['supplier_name' => 'Bidfood', 'invoice_number' => 'A1', 'gross_total' => 50, 'net_total' => 45]);
    ReceiptLine::factory()->for($second)->create(['line_total' => 10]);

    $this->actingAs($user)->get(route('waste.receipts.show', $second))
        ->assertInertia(fn (Assert $page) => $page
            ->where('duplicateId', $first->id)
            ->where('lineTotalMismatch', true));
});

test('matching line totals are not flagged', function () {
    $user = User::factory()->create();
    $receipt = Receipt::factory()->for($user)->create(['gross_total' => 109, 'net_total' => 100]);
    ReceiptLine::factory()->for($receipt)->create(['line_total' => 100]);

    $this->actingAs($user)->get(route('waste.receipts.show', $receipt))
        ->assertInertia(fn (Assert $page) => $page->where('lineTotalMismatch', false)->where('duplicateId', null));
});

test('the index lists all confirmed receipts and every draft when no dates are chosen', function () {
    $user = User::factory()->create();
    Receipt::factory()->for($user)->create(['gross_total' => 100, 'invoice_date' => now()->toDateString()]);
    Receipt::factory()->for($user)->create(['gross_total' => 999, 'invoice_date' => now()->subMonths(5)->toDateString()]);
    Receipt::factory()->draft()->for($user)->create(['gross_total' => 40, 'invoice_date' => now()->toDateString()]);
    Receipt::factory()->draft()->for($user)->create(['gross_total' => 25, 'invoice_date' => now()->subMonths(5)->toDateString()]);
    Receipt::factory()->for(User::factory())->create(['gross_total' => 5]);
    Receipt::factory()->draft()->for(User::factory())->create();

    $this->actingAs($user)->get(route('waste.receipts.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('receipts', 2)
            ->has('drafts', 2)
            ->where('totalSpend', 1099)
            ->where('confirmedCount', 2)
            ->where('dateFrom', ''));
});

test('the index narrows confirmed receipts to the chosen dates but still shows every draft', function () {
    $user = User::factory()->create();
    Receipt::factory()->for($user)->create(['gross_total' => 100, 'invoice_date' => '2026-10-01']);
    Receipt::factory()->for($user)->create(['gross_total' => 50, 'invoice_date' => '2026-06-03']);
    Receipt::factory()->draft()->for($user)->create(['invoice_date' => '2026-01-01']);

    $this->actingAs($user)->get(route('waste.receipts.index', ['date_from' => '2026-09-01', 'date_to' => '2026-10-31']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('receipts', 1)
            ->has('drafts', 1)
            ->where('totalSpend', 100));
});

/**
 * Returns the named part of an .xlsx download.
 */
function xlsxPart(string $binary, string $part): string
{
    $path = tempnam(sys_get_temp_dir(), 'xlsx-test');
    file_put_contents($path, $binary);

    $zip = new ZipArchive;
    expect($zip->open($path))->toBeTrue();
    $xml = (string) $zip->getFromName($part);
    $zip->close();
    unlink($path);

    return $xml;
}

test('the default export is a real xlsx file with confirmed receipts only', function () {
    $user = User::factory()->create();
    $receipt = Receipt::factory()->for($user)->create(['supplier_name' => 'Bidfood', 'invoice_date' => '2026-01-01', 'gross_total' => 109.5, 'net_total' => 100.5]);
    ReceiptLine::factory()->for($receipt)->create(['description' => 'Kipfilet', 'line_total' => 9.5]);
    Receipt::factory()->draft()->for($user)->create(['supplier_name' => 'Draft Co']);

    $response = $this->actingAs($user)->get(route('waste.receipts.export', ['mode' => 'receipts']))->assertOk();

    expect($response->headers->get('content-type'))->toContain('spreadsheetml.sheet')
        ->and($response->headers->get('content-disposition'))->toContain('.xlsx');

    $sheet = xlsxPart($response->streamedContent(), 'xl/worksheets/sheet1.xml');

    expect(simplexml_load_string($sheet))->not->toBeFalse()
        ->and($sheet)->toContain('Bidfood')
        ->and($sheet)->toContain('<v>109.5</v>')
        ->and($sheet)->toContain('<v>46023</v>')
        ->and($sheet)->not->toContain('Draft Co');

    expect($user->exportsUsedThisMonth())->toBe(1);
});

test('the line export lists every product line in the xlsx', function () {
    $user = User::factory()->create();
    $receipt = Receipt::factory()->for($user)->create();
    ReceiptLine::factory()->for($receipt)->create(['description' => 'Kipfilet & <friet>', 'line_total' => 9.5]);

    $sheet = xlsxPart($this->actingAs($user)->get(route('waste.receipts.export', ['mode' => 'lines']))->streamedContent(), 'xl/worksheets/sheet1.xml');

    expect(simplexml_load_string($sheet))->not->toBeFalse()
        ->and($sheet)->toContain('Kipfilet &amp; &lt;friet&gt;')
        ->and($sheet)->toContain('<v>9.5</v>');
});

test('csv export uses commas and dots', function () {
    $user = User::factory()->create();
    $receipt = Receipt::factory()->for($user)->create(['supplier_name' => 'Bidfood', 'gross_total' => 109.5]);
    ReceiptLine::factory()->for($receipt)->create(['description' => 'Kipfilet', 'line_total' => 9.5]);

    $response = $this->actingAs($user)->get(route('waste.receipts.export', ['mode' => 'lines', 'format' => 'csv']))->assertOk();
    $csv = $response->streamedContent();

    expect($response->headers->get('content-disposition'))->toContain('.csv')
        ->and($csv)->toContain('Date,Supplier,"Invoice number",Description,Category,Amount,Currency')
        ->and($csv)->toContain('Kipfilet')
        ->and($csv)->toContain('9.50');
});

test('export is blocked when the export quota is used up', function () {
    config(['plans.free.exports' => 0]);
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('waste.receipts.export'))->assertSessionHasErrors('quota');
});

test('deleting an account removes its receipt files', function () {
    $user = User::factory()->create();
    $receipt = Receipt::factory()->for($user)->create();
    $image = $receipt->images()->create(['path' => "receipts/{$user->id}/x.jpg", 'page' => 1]);
    Storage::put($image->path, 'data');

    $user->delete();

    Storage::assertMissing($image->path);
});

function fakeAiTwoReceipts(): void
{
    Http::fake([
        'api.anthropic.com/*' => Http::response(['content' => [['text' => json_encode(['receipts' => [
            ['supplier_name' => 'PLUS', 'gross_total' => 10, 'lines' => [['description' => 'Uien', 'amount' => 10, 'category' => 'veg']]],
            ['supplier_name' => 'Jumbo', 'gross_total' => 5, 'lines' => [['description' => 'Melk', 'amount' => 5, 'category' => 'dairy']]],
            ['supplier_name' => 'Sligro', 'gross_total' => 7, 'lines' => [['description' => 'Rijst', 'amount' => 7, 'category' => 'dry_goods']]],
        ]])]]]),
    ]);
}

test('one photo with several receipts creates a draft for each', function () {
    fakeAiTwoReceipts();
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('waste.receipts.scan'), ['photos' => [fakePhoto()]])
        ->assertOk()
        ->assertJsonCount(3, 'receipts')
        ->assertJsonPath('skipped', 0);

    expect(Receipt::where('user_id', $user->id)->pluck('supplier_name')->all())->toEqual(['PLUS', 'Jumbo', 'Sligro']);
    expect($response->json('receipts.0.url'))->not->toBe($response->json('receipts.1.url'));
});

test('receipts beyond the monthly allowance are skipped and reported', function () {
    fakeAiTwoReceipts();
    config(['plans.free.receipts' => 2]);
    $user = User::factory()->create();

    $this->actingAs($user)->postJson(route('waste.receipts.scan'), ['photos' => [fakePhoto()]])
        ->assertOk()
        ->assertJsonCount(2, 'receipts')
        ->assertJsonPath('skipped', 1);

    expect(Receipt::count())->toBe(2);
});

test('a photo shared by several receipts is only deleted with the last one', function () {
    fakeAiTwoReceipts();
    $user = User::factory()->create();

    $this->actingAs($user)->postJson(route('waste.receipts.scan'), ['photos' => [fakePhoto()]])->assertOk();

    [$first, $second, $third] = Receipt::with('images')->get()->all();
    $path = $first->images->first()->path;

    expect($second->images->first()->path)->toBe($path);
    Storage::assertExists($path);

    $this->delete(route('waste.receipts.destroy', $first))->assertRedirect();
    $this->delete(route('waste.receipts.destroy', $second))->assertRedirect();
    Storage::assertExists($path);

    $this->delete(route('waste.receipts.destroy', $third))->assertRedirect();
    Storage::assertMissing($path);
});

test('receipts are read with the dedicated receipt model', function (string $provider, string $url, string $model) {
    config([
        'services.ai.provider' => $provider,
        'services.openrouter.key' => 'test',
        'services.openrouter.model' => 'cheap/model',
        'services.openrouter.receipt_model' => 'vendor/receipt-model',
        'services.anthropic.receipt_model' => 'claude-receipt',
    ]);
    $body = json_encode(['receipts' => [['supplier_name' => 'A', 'gross_total' => 1, 'lines' => [['description' => 'x', 'amount' => 1, 'category' => 'veg']]]]]);
    Http::fake([
        'openrouter.ai/*' => Http::response(['choices' => [['message' => ['content' => $body]]]]),
        'api.anthropic.com/*' => Http::response(['content' => [['text' => $body]]]),
    ]);

    $this->actingAs(User::factory()->create())->postJson(route('waste.receipts.scan'), ['photos' => [fakePhoto()]])->assertOk();

    Http::assertSent(fn ($request) => str_contains($request->url(), $url) && $request['model'] === $model);
})->with([
    'openrouter' => ['openrouter', 'openrouter.ai', 'vendor/receipt-model'],
    'anthropic' => ['anthropic', 'api.anthropic.com', 'claude-receipt'],
]);

test('a scanned receipt can be confirmed in place with a json request', function () {
    $user = User::factory()->create();
    $receipt = Receipt::factory()->draft()->for($user)->create();

    $this->actingAs($user)->patchJson(route('waste.receipts.update', $receipt), payload(['confirm' => true]))
        ->assertOk()
        ->assertJson(['id' => $receipt->id, 'status' => 'confirmed']);

    expect($receipt->fresh()->status)->toBe('confirmed')
        ->and($receipt->lines()->count())->toBe(2);
});

test('an invalid in-place confirmation returns validation errors and keeps the draft', function () {
    $user = User::factory()->create();
    $receipt = Receipt::factory()->draft()->for($user)->create();

    $this->actingAs($user)->patchJson(route('waste.receipts.update', $receipt), payload(['confirm' => true, 'gross_total' => null]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('gross_total');

    expect($receipt->fresh()->status)->toBe('draft');
});

test('a scanned receipt can be deleted in place with a json request', function () {
    $user = User::factory()->create();
    $receipt = Receipt::factory()->draft()->for($user)->create();

    $this->actingAs($user)->deleteJson(route('waste.receipts.destroy', $receipt))
        ->assertOk()
        ->assertJson(['deleted' => true]);

    expect(Receipt::count())->toBe(0);
});

test('the scan response flags a duplicate of an existing receipt', function () {
    fakeAiReceipt();
    $user = User::factory()->create();
    $existing = Receipt::factory()->for($user)->create(['supplier_name' => 'Bidfood', 'invoice_number' => 'INV-77', 'gross_total' => 109]);

    $this->actingAs($user)->postJson(route('waste.receipts.scan'), ['photos' => [fakePhoto()]])
        ->assertOk()
        ->assertJsonPath('receipts.0.duplicate_id', $existing->id);
});

test('saving a receipt stores the price basis of every line', function () {
    $user = User::factory()->create();
    $receipt = Receipt::factory()->draft()->for($user)->create();

    $this->actingAs($user)->patch(route('waste.receipts.update', $receipt), payload([
        'confirm' => true,
        'gross_total' => 100,
        'lines' => [
            ['description' => 'Boter 250 g', 'line_total' => 3.5, 'category' => 'dairy'],
            ['description' => '2X Protein bar', 'line_total' => 5.18, 'quantity' => 2, 'unit' => 'pc', 'unit_price' => 2.59, 'category' => 'prepared'],
            ['description' => 'Korting', 'line_total' => -1, 'category' => 'other'],
        ],
    ]))->assertRedirect();

    $lines = $receipt->lines()->orderBy('id')->get();

    expect((float) $lines[0]->base_price)->toBe(3.5)
        ->and((float) $lines[0]->pack_size)->toBe(0.25)
        ->and($lines[0]->normalized_item)->toBe('boter')
        ->and((float) $lines[1]->base_price)->toBe(2.59)
        ->and((float) $lines[1]->base_quantity)->toBe(2.0)
        ->and($lines[2]->base_price)->toBeNull();
});
