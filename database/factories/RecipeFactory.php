<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Recipe;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Recipe>
 */
class RecipeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'yield_quantity' => 1,
            'instructions' => fake()->optional()->paragraph(),
        ];
    }
}
