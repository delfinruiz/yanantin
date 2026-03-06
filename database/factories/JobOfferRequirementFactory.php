<?php

namespace Database\Factories;

use App\Models\JobOfferRequirement;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\JobOfferRequirement>
 */
class JobOfferRequirementFactory extends Factory
{
    protected $model = JobOfferRequirement::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = ['Experiencia laboral', 'Área funcional', 'Sector', 'Educación', 'Idioma', 'Habilidad blanda', 'Habilidad técnica'];
        $types = ['Obligatorio', 'Deseable'];
        $levels = ['Básico', 'Intermedio', 'Avanzado', 'Nativo', '2 años', '5+ años'];
        
        $type = Arr::random($types);
        
        return [
            'category' => Arr::random($categories),
            'type' => $type,
            'level' => Arr::random($levels),
            'weight' => $type === 'Deseable' ? random_int(10, 30) : null,
            'evidence' => $this->faker->sentence(),
        ];
    }
}
