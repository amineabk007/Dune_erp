<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category' => fake()->randomElement(Expense::CATEGORIES),
            'description' => fake()->sentence(4),
            'amount' => fake()->randomFloat(2, 50, 5000),
            'expense_date' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'paid_via' => fake()->randomElement(Expense::PAID_VIA),
            'created_by' => User::factory(),
        ];
    }
}
