<?php

namespace App\Support;

/**
 * Works out the price observation of one receipt line: what one item (or one
 * kg / litre for weighed lines) cost, using only numbers printed on the
 * receipt. Plain supermarket lines are one item at the line amount.
 */
class PriceBasis
{
    /**
     * @return array{base_unit: string|null, base_price: float|null, base_quantity: float|null, pack_size: float|null, pack_unit: string|null}
     */
    public static function compute(string $description, ?float $lineTotal, ?float $quantity = null, ?string $unit = null, ?float $unitPrice = null): array
    {
        $none = ['base_unit' => null, 'base_price' => null, 'base_quantity' => null, 'pack_size' => null, 'pack_unit' => null];

        // Discounts, deposits and returns are not price observations.
        if ($lineTotal === null || $lineTotal <= 0) {
            return $none;
        }

        [$packSize, $packUnit] = self::packSize($description);

        $weighed = match ($unit) {
            'kg' => ['kg', 1.0],
            'g' => ['kg', 0.001],
            'l' => ['l', 1.0],
            'ml' => ['l', 0.001],
            'cl' => ['l', 0.01],
            default => null,
        };

        if ($weighed !== null && $quantity !== null && $quantity > 0) {
            [$baseUnit, $factor] = $weighed;
            $amount = $quantity * $factor;
            $fromTotal = $lineTotal / $amount;
            // A printed price per kg/l is only trusted when it agrees with the printed total.
            $price = $unitPrice !== null && $unitPrice > 0 && $factor === 1.0 && self::agrees($unitPrice * $amount, $lineTotal)
                ? $unitPrice
                : $fromTotal;

            return ['base_unit' => $baseUnit, 'base_price' => round($price, 4), 'base_quantity' => round($amount, 3), 'pack_size' => $packSize, 'pack_unit' => $packUnit];
        }

        $count = $quantity !== null && $quantity > 0 ? $quantity : 1.0;
        $price = $unitPrice !== null && $unitPrice > 0 && self::agrees($unitPrice * $count, $lineTotal)
            ? $unitPrice
            : $lineTotal / $count;

        return ['base_unit' => 'pc', 'base_price' => round($price, 4), 'base_quantity' => round($count, 3), 'pack_size' => $packSize, 'pack_unit' => $packUnit];
    }

    /**
     * Pack size written in the product text ("250 g", "1L", "75cl"), as kg or
     * litres. Multipacks such as "6x330ml" are left out because the total is
     * ambiguous.
     *
     * @return array{0: float|null, 1: string|null}
     */
    public static function packSize(string $description): array
    {
        $text = mb_strtolower($description);

        if (preg_match('/\d\s*[x×]\s*\d/u', $text)) {
            return [null, null];
        }

        if (! preg_match('/(\d+(?:[.,]\d+)?)\s*(kg|gram|gr|g|ml|cl|ltr|liter|litre|l)\b/u', $text, $match)) {
            return [null, null];
        }

        $value = (float) str_replace(',', '.', $match[1]);

        [$size, $unit] = match ($match[2]) {
            'kg' => [$value, 'kg'],
            'g', 'gr', 'gram' => [$value / 1000, 'kg'],
            'ml' => [$value / 1000, 'l'],
            'cl' => [$value / 100, 'l'],
            default => [$value, 'l'],
        };

        return $size > 0 && $size <= 50 ? [round($size, 3), $unit] : [null, null];
    }

    /**
     * Identifies a size on a product, so 250 g and 500 g are not mixed up.
     */
    public static function bucket(?float $packSize, ?string $packUnit): string
    {
        return $packSize !== null && $packUnit !== null ? rtrim(rtrim(number_format($packSize, 3, '.', ''), '0'), '.').$packUnit : 'none';
    }

    private static function agrees(float $computed, float $printed): bool
    {
        return abs($computed - $printed) <= max(0.05, 0.02 * $printed);
    }
}
