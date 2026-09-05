<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true).' — événement privé',
            'event_date' => fake()->dateTimeBetween('now', '+2 months'),
            'guest_count' => fake()->numberBetween(10, 150),
            'total_amount' => fake()->randomFloat(2, 2000, 30000),
            'status' => 'pending',
            'created_by' => User::factory(),
        ];
    }
}
