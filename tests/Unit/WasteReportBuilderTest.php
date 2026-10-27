<?php

use App\Support\WasteReportBuilder;
use Illuminate\Support\Collection;

function aggregateRows(): Collection
{
    return collect([
        (object) ['category' => 'protein', 'reason' => 'spoilage', 'total_kg' => 2, 'entry_count' => 1],
        (object) ['category' => 'protein', 'reason' => 'expiry', 'total_kg' => 1, 'entry_count' => 1],
        (object) ['category' => 'dairy', 'reason' => 'expiry', 'total_kg' => 5, 'entry_count' => 2],
    ]);
}

test('buildSummary computes cost and co2e per category', function () {
    $builder = new WasteReportBuilder;
    $summary = $builder->buildSummary(aggregateRows(), ['protein' => 10, 'dairy' => '2.5']);

    expect($summary['protein']['cost'])->toBe(30.0)
        ->and($summary['protein']['co2e'])->toBe(45.0)
        ->and($summary['dairy']['cost'])->toBe(12.5)
        ->and($summary['veg']['cost'])->toBeNull()
        ->and($summary['veg']['co2e'])->toBe(0.0);
});

test('buildSummary leaves cost null without prices', function () {
    $summary = (new WasteReportBuilder)->buildSummary(aggregateRows());

    expect(collect($summary)->pluck('cost')->filter()->all())->toBe([]);
});

test('totals sums cost and co2e and returns null cost when unpriced', function () {
    $builder = new WasteReportBuilder;

    $priced = $builder->totals($builder->buildSummary(aggregateRows(), ['protein' => 10]));
    expect($priced)->toBe(['cost' => 30.0, 'co2e' => 85.0]);

    $unpriced = $builder->totals($builder->buildSummary(aggregateRows()));
    expect($unpriced['cost'])->toBeNull();
});
