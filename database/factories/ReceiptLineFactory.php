<?php

namespace Database\Factories;

use App\Models\Receipt;
use App\Models\ReceiptLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReceiptLine>
 */
class ReceiptLineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'receipt_id' => Receipt::factory(),
            'description' => fake()->words(2, true),
            'normalized_item' => fake()->word(),
            'quantity' => 2,
            'unit' => 'kg',
            'unit_price' => 5,
            'line_total' => 10,
            'category' => 'veg',
            'price_per_kg' => 5,
        ];
    }
}
