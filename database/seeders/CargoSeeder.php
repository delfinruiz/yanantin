<?php

namespace Database\Seeders;

use App\Models\Cargo;
use Illuminate\Database\Seeder;

class CargoSeeder extends Seeder
{
    public function run(): void
    {
        $cargos = [
            [
                'name' => 'CEO',
                'hierarchy_level' => 1,
                'description' => null,
            ],
            [
                'name' => 'Directivo Tecnologia',
                'hierarchy_level' => 2,
                'description' => null,
            ],
            [
                'name' => 'Directivo Finanzas',
                'hierarchy_level' => 2,
                'description' => null,
            ],
            [
                'name' => 'Directivo Marketing',
                'hierarchy_level' => 2,
                'description' => null,
            ],
            [
                'name' => 'Directivo I + D',
                'hierarchy_level' => 2,
                'description' => null,
            ],
            [
                'name' => 'Directivo Gestión Humana',
                'hierarchy_level' => 2,
                'description' => null,
            ],
            [
                'name' => 'Gerente Tecnologia',
                'hierarchy_level' => 3,
                'description' => null,
            ],
            [
                'name' => 'Gerente Finanzas',
                'hierarchy_level' => 3,
                'description' => null,
            ],
            [
                'name' => 'Gerente Marketing',
                'hierarchy_level' => 3,
                'description' => null,
            ],
            [
                'name' => 'Gerente I + D',
                'hierarchy_level' => 3,
                'description' => null,
            ],
            [
                'name' => 'Gerente Gestión Humana',
                'hierarchy_level' => 3,
                'description' => null,
            ],
        ];

        foreach ($cargos as $cargo) {
            Cargo::query()->firstOrCreate(
                ['name' => $cargo['name']],
                [
                    'description' => $cargo['description'],
                    'hierarchy_level' => $cargo['hierarchy_level'],
                ],
            );
        }
    }
}

