<?php

namespace Database\Factories;

use App\Models\ProductLink;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductLink>
 */
class ProductLinkFactory extends Factory
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
            'item_key' => fake()->unique()->word(),
            'canonical_key' => fake()->unique()->word(),
        ];
    }
}
