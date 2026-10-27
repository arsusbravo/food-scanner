<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WasteEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WasteEntry>
 */
class WasteEntryFactory extends Factory
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
            'category' => fake()->randomElement(['protein', 'veg', 'dairy', 'prepared']),
            'item_name' => fake()->word(),
            'weight_kg' => fake()->randomFloat(2, 0.1, 5),
            'reason' => fake()->randomElement(['spoilage', 'overproduction', 'expiry', 'prep_waste', 'other']),
            'logged_at' => now(),
            'source' => 'manual',
        ];
    }
}
