<?php

namespace Database\Factories;

use App\Models\Survey;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SurveyFactory extends Factory
{
    protected $model = Survey::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->optional()->paragraph(),
            'active' => false,
            'deadline' => $this->faker->optional()->dateTimeBetween('+1 days', '+1 month'),
            'creator_id' => User::factory(),
        ];
    }
}

