<?php

namespace Database\Factories;

use App\Models\DishDescription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DishDescription>
 */
class DishDescriptionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'dish_name' => 'Pad Thai',
            'origin' => 'Thailand',
            'title' => 'Street-Style Pad Thai',
            'description' => 'Rice noodles wok-fried with tamarind, peanuts and lime.',
            'confidence' => 'high',
            'locale' => 'en',
        ];
    }
}
