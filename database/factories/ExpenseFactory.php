<?php

namespace Database\Factories;

use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Expense>
 */
class ExpenseFactory extends Factory
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
            'expense_category_id' => ExpenseCategory::factory(),
            'year' => 2025,
            'month' => fake()->numberBetween(1, 12),
            'amount' => fake()->numberBetween(1000, 500000),
            'notes' => fake()->sentence(),
        ];
    }
}
