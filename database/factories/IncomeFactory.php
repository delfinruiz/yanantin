<?php

namespace Database\Factories;

use App\Models\IncomeType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Income>
 */
class IncomeFactory extends Factory
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
            'income_type_id' => IncomeType::factory(),
            'year' => 2025,
            'month' => fake()->numberBetween(1, 12),
            'amount' => fake()->numberBetween(50000, 2000000),
            'notes' => fake()->sentence(),
        ];
    }
}
