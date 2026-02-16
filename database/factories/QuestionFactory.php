<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\Survey;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionFactory extends Factory
{
    protected $model = Question::class;

    public function definition(): array
    {
        $types = ['text', 'bool', 'scale_5', 'scale_10', 'likert', 'multi'];
        $type = $this->faker->randomElement($types);

        return [
            'survey_id' => Survey::factory(),
            'content' => $this->faker->sentence(8) . '?',
            'type' => $type,
            'item' => $this->faker->randomElement(['Condiciones de trabajo', 'Relaciones', 'Comunicación interna']),
            'required' => $this->faker->boolean(70),
            'options' => $type === 'multi' ? ['A', 'B', 'C'] : ($type === 'likert' ? ['Nunca','Casi nunca','A veces','Casi siempre','Siempre'] : null),
            'order' => $this->faker->numberBetween(1, 50),
        ];
    }
}

