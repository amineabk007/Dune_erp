<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'created_by' => User::factory(),
            'reserved_at' => fake()->dateTimeBetween('now', '+1 week'),
            'guests' => fake()->numberBetween(2, 8),
            'status' => 'pending',
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
