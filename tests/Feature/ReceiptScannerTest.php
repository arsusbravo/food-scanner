<?php

use App\Support\AiVisionClient;
use App\Support\FoodWasteScanException;
use App\Support\ReceiptScanner;

function scanner(?string $answer = null): ReceiptScanner
{
    $client = Mockery::mock(AiVisionClient::class);

    if ($answer !== null) {
        $client->shouldReceive('send')->andReturn($answer);
    }

    return new ReceiptScanner($client);
}

test('numbers with decimal commas and thousands separators are parsed', function (mixed $input, ?float $expected) {
    expect(scanner()->number($input))->toBe($expected);
})->with([
    'plain float' => [12.5, 12.5],
    'dot decimal' => ['12.50', 12.5],
    'comma decimal' => ['12,50', 12.5],
    'euro format' => ['1.234,56', 1234.56],
    'us format' => ['1,234.56', 1234.56],
    'currency symbol' => ['€ 9,99', 9.99],
    'empty' => ['', null],
    'text' => ['n/a', null],
    'null' => [null, null],
]);

test('item names are normalised without quantities and punctuation', function () {
    expect(ReceiptScanner::normalizeItem('Kipfilet 2,5 kg (vers)'))->toBe('kipfilet vers')
        ->and(ReceiptScanner::normalizeItem('TOMATEN 500g'))->toBe('tomaten')
        ->and(ReceiptScanner::normalizeItem('Olive Oil 5L'))->toBe('olive oil');
});

test('a model answer is normalised into a draft', function () {
    $answer = json_encode([
        'supplier_name' => '  Bidfood ',
        'invoice_number' => 'INV-1',
        'invoice_date' => '2026-11-03',
        'currency' => 'eur',
        'net_total' => '100,00',
        'vat_total' => 9,
        'gross_total' => '109,00',
        'confidence' => 'weird',
        'lines' => [
            ['description' => 'Kipfilet 2 kg', 'amount' => '19,00', 'category' => 'protein'],
            ['description' => '', 'amount' => 1],
            ['description' => 'Mystery', 'category' => 'nonsense'],
        ],
    ]);

    $result = scanner("```json\n{$answer}\n```")->scan(['abc'])[0];

    expect($result['supplier_name'])->toBe('Bidfood')
        ->and($result['currency'])->toBe('EUR')
        ->and($result['gross_total'])->toBe(109.0)
        ->and($result['confidence'])->toBe('medium')
        ->and($result['invoice_date'])->toBe('2026-11-03')
        ->and($result['lines'])->toHaveCount(2)
        ->and($result['lines'][0]['line_total'])->toBe(19.0)
        ->and($result['lines'][0]['normalized_item'])->toBe('kipfilet')
        ->and($result['lines'][1]['category'])->toBe('other');
});

test('an unreadable answer raises a client-safe exception', function () {
    scanner('sorry, I cannot read this')->scan(['abc']);
})->throws(FoodWasteScanException::class);

test('a draft whose lines do not add up is re-read once and the closer answer wins', function () {
    $wrong = json_encode(['gross_total' => 13.16, 'lines' => [
        ['description' => 'Uien', 'amount' => 4.26, 'category' => 'veg'],
        ['description' => 'Noten', 'amount' => 12.74, 'category' => 'dry_goods'],
    ]]);
    $right = json_encode(['gross_total' => 13.16, 'lines' => [
        ['description' => 'Bospeen', 'amount' => 1.49, 'category' => 'veg'],
        ['description' => 'Korting', 'amount' => -0.20, 'category' => 'other'],
        ['description' => 'Uien', 'amount' => 1.09, 'category' => 'veg'],
        ['description' => 'Noten', 'amount' => 2.86, 'category' => 'dry_goods'],
        ['description' => 'Havermout', 'amount' => 2.74, 'category' => 'dry_goods'],
        ['description' => 'Protein bar', 'amount' => 5.18, 'category' => 'prepared'],
    ]]);

    $client = Mockery::mock(AiVisionClient::class);
    $client->shouldReceive('send')->twice()->andReturn($wrong, $right);

    $result = (new ReceiptScanner($client))->scan(['abc'])[0];

    expect($result['lines'])->toHaveCount(6)
        ->and(round(array_sum(array_column($result['lines'], 'line_total')), 2))->toBe(13.16);
});

test('a consistent draft is not re-read', function () {
    $good = json_encode(['gross_total' => 10, 'lines' => [['description' => 'A', 'amount' => 10, 'category' => 'veg']]]);

    $client = Mockery::mock(AiVisionClient::class);
    $client->shouldReceive('send')->once()->andReturn($good);

    expect((new ReceiptScanner($client))->scan(['abc'])[0]['lines'])->toHaveCount(1);
});

test('the first draft is kept when the re-read is worse or fails', function () {
    $first = json_encode(['gross_total' => 10, 'lines' => [['description' => 'A', 'amount' => 9, 'category' => 'veg']]]);

    $client = Mockery::mock(AiVisionClient::class);
    $client->shouldReceive('send')->twice()->andReturn($first, 'not json');

    expect((new ReceiptScanner($client))->scan(['abc'])[0]['lines'][0]['line_total'])->toBe(9.0);
});

test('one photo with several receipts returns one draft per receipt', function () {
    $answer = json_encode(['receipts' => [
        ['supplier_name' => 'PLUS', 'gross_total' => 10, 'lines' => [['description' => 'A', 'amount' => 10, 'category' => 'veg']]],
        ['supplier_name' => 'Jumbo', 'gross_total' => 5, 'lines' => [['description' => 'B', 'amount' => 5, 'category' => 'dairy']]],
        'garbage',
        ['supplier_name' => 'No lines key'],
    ]]);

    $drafts = scanner($answer)->scan(['abc']);

    expect($drafts)->toHaveCount(2)
        ->and($drafts[0]['supplier_name'])->toBe('PLUS')
        ->and($drafts[1]['supplier_name'])->toBe('Jumbo');
});

test('a retry is used only when it keeps every document and is closer overall', function () {
    $first = json_encode(['receipts' => [
        ['supplier_name' => 'A', 'gross_total' => 10, 'lines' => [['description' => 'x', 'amount' => 10]]],
        ['supplier_name' => 'B', 'gross_total' => 5, 'lines' => [['description' => 'y', 'amount' => 9]]],
    ]]);
    $dropsOne = json_encode(['receipts' => [
        ['supplier_name' => 'B', 'gross_total' => 5, 'lines' => [['description' => 'y', 'amount' => 5]]],
    ]]);

    $client = Mockery::mock(AiVisionClient::class);
    $client->shouldReceive('send')->twice()->andReturn($first, $dropsOne);

    expect((new ReceiptScanner($client))->scan(['abc']))->toHaveCount(2);
});
