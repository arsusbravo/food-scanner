<?php

use App\Support\PriceBasis;

test('a plain line is one item at the line amount', function () {
    expect(PriceBasis::compute('Bospeen', 1.49))->toMatchArray(['base_unit' => 'pc', 'base_price' => 1.49, 'base_quantity' => 1.0]);
});

test('a multiplier line uses the printed unit price and count', function () {
    expect(PriceBasis::compute('2X PROTEIN BAR', 5.18, 2.0, 'pc', 2.59))->toMatchArray(['base_unit' => 'pc', 'base_price' => 2.59, 'base_quantity' => 2.0]);
});

test('without a printed unit price the total is divided by the count', function () {
    expect(PriceBasis::compute('Lenzen', 31.98, 2.0, 'pc'))->toMatchArray(['base_price' => 15.99, 'base_quantity' => 2.0]);
});

test('a unit price that disagrees with the printed total is not trusted', function () {
    // 3 x 2.50 = 7.50, but the line says 5.00: use 5.00 / 3.
    expect(PriceBasis::compute('Item', 5.0, 3.0, 'pc', 2.5)['base_price'])->toBe(1.6667);
});

test('weighed lines are priced per kg or litre', function (string $unit, float $quantity, ?float $unitPrice, float $total, string $baseUnit, float $price, float $baseQuantity) {
    expect(PriceBasis::compute('Bananen', $total, $quantity, $unit, $unitPrice))
        ->toMatchArray(['base_unit' => $baseUnit, 'base_price' => $price, 'base_quantity' => $baseQuantity]);
})->with([
    'kg with printed price' => ['kg', 0.534, 1.99, 1.06, 'kg', 1.99, 0.534],
    'kg from total' => ['kg', 2.0, null, 21.0, 'kg', 10.5, 2.0],
    'grams' => ['g', 500.0, null, 4.0, 'kg', 8.0, 0.5],
    'litres' => ['l', 2.0, 1.5, 3.0, 'l', 1.5, 2.0],
    'millilitres' => ['ml', 750.0, null, 3.0, 'l', 4.0, 0.75],
    'centilitres' => ['cl', 75.0, null, 6.0, 'l', 8.0, 0.75],
]);

test('discounts, deposits and empty amounts are not price observations', function (?float $total) {
    expect(PriceBasis::compute('Korting', $total)['base_price'])->toBeNull();
})->with([[-1.0], [0.0], [null]]);

test('pack sizes are read from the product text', function (string $text, ?float $size, ?string $unit) {
    expect(PriceBasis::packSize($text))->toBe([$size, $unit]);
})->with([
    '250 g' => ['Boter 250 g', 0.25, 'kg'],
    'gr' => ['Kaas 100gr', 0.1, 'kg'],
    'kg comma' => ['Aardappelen 2,5 kg', 2.5, 'kg'],
    'litre' => ['Melk 1L', 1.0, 'l'],
    'cl' => ['Wijn 75cl', 0.75, 'l'],
    'ml' => ['Cola 330 ml', 0.33, 'l'],
    'no size' => ['Bospeen', null, null],
    'multipack is ambiguous' => ['Bier 6x330ml', null, null],
    'absurd size' => ['Item 900 kg', null, null],
]);

test('the size bucket separates pack sizes', function () {
    expect(PriceBasis::bucket(0.25, 'kg'))->toBe('0.25kg')
        ->and(PriceBasis::bucket(1.0, 'l'))->toBe('1l')
        ->and(PriceBasis::bucket(null, null))->toBe('none');
});
