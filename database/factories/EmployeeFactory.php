<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'position' => fake()->randomElement(['Serveur', 'Cuisinier', 'Barman', 'Caissier', 'Manager']),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->optional()->safeEmail(),
            'hire_date' => fake()->dateTimeBetween('-3 years', 'now')->format('Y-m-d'),
            'salary' => fake()->randomFloat(2, 3000, 15000),
            'is_active' => true,
            'created_by' => User::factory(),
        ];
    }
}
