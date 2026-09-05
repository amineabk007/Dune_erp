<?php

namespace Database\Factories;

use App\Models\RestaurantTable;
use App\Models\Zone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RestaurantTable>
 */
class RestaurantTableFactory extends Factory
{
    public function definition(): array
    {
        return [
            'zone_id' => Zone::factory(),
            'name' => 'T'.fake()->unique()->numberBetween(1, 999),
            'capacity' => fake()->numberBetween(2, 10),
            'status' => 'available',
        ];
    }
}
