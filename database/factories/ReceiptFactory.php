<?php

namespace Database\Factories;

use App\Models\Receipt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Receipt>
 */
class ReceiptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'supplier_name' => fake()->company(),
            'invoice_number' => fake()->bothify('INV-####'),
            'invoice_date' => now()->toDateString(),
            'currency' => 'EUR',
            'net_total' => 100,
            'vat_total' => 9,
            'gross_total' => 109,
            'confidence' => 'high',
            'status' => Receipt::STATUS_CONFIRMED,
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => Receipt::STATUS_DRAFT]);
    }
}
