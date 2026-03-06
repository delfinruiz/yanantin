<?php

namespace Database\Factories;

use App\Models\JobOffer;
use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

class JobOfferFactory extends Factory
{
    protected $model = JobOffer::class;

    public function definition(): array
    {
        $titles = ['Desarrollador Backend', 'Analista de Datos', 'Diseñador UX/UI', 'QA Engineer', 'Project Manager', 'Product Owner', 'DevOps Engineer', 'SRE'];
        $contracts = ['Indefinido', 'Fijo', 'Proyecto', 'Temporal'];
        $modalities = ['Presencial', 'Híbrido', 'Remoto'];
        $levels = ['Operativo', 'Analista', 'Especialista', 'Jefatura', 'Gerencia', 'Dirección'];
        $criticalities = ['Operativo', 'Táctico', 'Estratégico'];
        $openingReasons = ['Reemplazo', 'Expansión', 'Nueva campaña'];
        
        $countries = ['Chile', 'Argentina', 'Perú', 'Colombia', 'México', 'Uruguay'];
        $cities = [
            'Chile' => ['Santiago', 'Valparaíso', 'Concepción'],
            'Argentina' => ['Buenos Aires', 'Córdoba', 'Mendoza'],
            'Perú' => ['Lima', 'Arequipa', 'Cusco'],
            'Colombia' => ['Bogotá', 'Medellín', 'Cali'],
            'México' => ['Ciudad de México', 'Guadalajara', 'Monterrey'],
            'Uruguay' => ['Montevideo', 'Punta del Este'],
        ];

        $selectedCountry = Arr::random($countries);
        $selectedCity = Arr::random($cities[$selectedCountry]);

        return [
            'title' => Arr::random($titles),
            'department_id' => Department::inRandomOrder()->first()?->id ?? Department::factory(),
            'hierarchical_level' => Arr::random($levels),
            'criticality_level' => Arr::random($criticalities),
            'work_modality' => Arr::random($modalities),
            'vacancies_count' => random_int(1, 5),
            'estimated_start_date' => now()->addDays(random_int(15, 60)),
            'cost_center' => 'CC-' . random_int(100, 999),
            'opening_reason' => Arr::random($openingReasons),
            
            // Descripción Estratégica
            'mission' => $this->faker->paragraph(3),
            'organizational_impact' => $this->faker->paragraph(2),
            'key_results' => [
                ['result' => 'Aumentar la eficiencia operativa en un 20%'],
                ['result' => 'Reducir la tasa de errores en producción'],
                ['result' => 'Liderar la migración a la nube'],
            ],
            'description' => $this->faker->paragraph(5),
            'benefits' => $this->faker->paragraph(4),
            
            // Ubicación
            'location' => "$selectedCity, $selectedCountry", // Mantener por compatibilidad legacy si es necesario
            'city' => $selectedCity,
            'country' => $selectedCountry,
            
            'contract_type' => Arr::random($contracts),
            'salary' => rand(0, 1) ? null : random_int(800000, 4500000),
            
            'published_at' => null,
            'deadline' => rand(0, 1) ? now()->addDays(random_int(1, 90)) : null,
            'is_active' => false,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'is_active' => true,
            'published_at' => now(),
        ]);
    }
}
