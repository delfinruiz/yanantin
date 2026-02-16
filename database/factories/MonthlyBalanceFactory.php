<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MonthlyBalance>
 */
class MonthlyBalanceFactory extends Factory
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
            'year' => 2025,
            'month' => fake()->numberBetween(1, 12),
            'total_income' => fake()->numberBetween(100000, 2000000),
            'total_expense' => fake()->numberBetween(50000, 1000000),
            'balance' => 0,
            'calculated_at' => now(),
            'notes' => fake()->sentence(),
        ];
    }
}
