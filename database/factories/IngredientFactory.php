<?php

namespace Database\Factories;

use App\Models\Ingredient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ingredient>
 */
class IngredientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'unit' => fake()->randomElement(['kg', 'L', 'pièce', 'g']),
            'current_stock' => fake()->randomFloat(3, 10, 100),
            'minimum_stock' => 5,
            'unit_cost' => fake()->randomFloat(4, 1, 50),
            'is_active' => true,
        ];
    }
}
