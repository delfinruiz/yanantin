<?php

namespace Database\Factories;

use App\Models\Response;
use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ResponseFactory extends Factory
{
    protected $model = Response::class;

    public function definition(): array
    {
        return [
            'question_id' => Question::factory(),
            'user_id' => User::factory(),
            'value' => (string) $this->faker->numberBetween(1, 5),
        ];
    }
}

