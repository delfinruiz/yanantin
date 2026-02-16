<?php

namespace Database\Seeders;

use App\Models\AbsenceType;
use App\Models\Holiday;
use Illuminate\Database\Seeder;

class AbsenceSeeder extends Seeder
{
    public function run(): void
    {
        // Absence Types
        $types = [
            [
                'name' => 'Vacaciones',
                'slug' => 'vacaciones',
                'color' => 'success',
                'is_vacation' => true,
                'requires_approval' => true,
                'allows_half_day' => true,
            ],
            [
                'name' => 'Licencia Médica',
                'slug' => 'licencia-medica',
                'color' => 'warning',
                'is_vacation' => false,
                'requires_approval' => true,
                'allows_half_day' => false,
            ],
            [
                'name' => 'Permiso Administrativo',
                'slug' => 'permiso-administrativo',
                'color' => 'info',
                'is_vacation' => false, // Depending on policy, usually doesn't deduct from vacation but has its own quota
                'requires_approval' => true,
                'allows_half_day' => true,
            ],
            [
                'name' => 'Día Administrativo',
                'slug' => 'dia-administrativo',
                'color' => 'primary',
                'is_vacation' => false,
                'requires_approval' => true,
                'allows_half_day' => true,
            ],
            [
                'name' => 'Ausencia Sin Goce de Sueldo',
                'slug' => 'sin-goce-sueldo',
                'color' => 'danger',
                'is_vacation' => false,
                'requires_approval' => true,
                'allows_half_day' => false,
            ],
        ];

        foreach ($types as $type) {
            AbsenceType::updateOrCreate(['slug' => $type['slug']], $type);
        }

        // Holidays (Chilean examples, should be configurable)
        $holidays = [
            ['name' => 'Año Nuevo', 'date' => '2025-01-01', 'is_recurring' => true],
            ['name' => 'Viernes Santo', 'date' => '2025-04-18', 'is_recurring' => false], // Changes every year
            ['name' => 'Sábado Santo', 'date' => '2025-04-19', 'is_recurring' => false],
            ['name' => 'Día del Trabajo', 'date' => '2025-05-01', 'is_recurring' => true],
            ['name' => 'Día de las Glorias Navales', 'date' => '2025-05-21', 'is_recurring' => true],
            ['name' => 'Día Nacional de los Pueblos Indígenas', 'date' => '2025-06-20', 'is_recurring' => false],
            ['name' => 'San Pedro y San Pablo', 'date' => '2025-06-29', 'is_recurring' => false],
            ['name' => 'Día de la Virgen del Carmen', 'date' => '2025-07-16', 'is_recurring' => true],
            ['name' => 'Asunción de la Virgen', 'date' => '2025-08-15', 'is_recurring' => true],
            ['name' => 'Independencia Nacional', 'date' => '2025-09-18', 'is_recurring' => true],
            ['name' => 'Día de las Glorias del Ejército', 'date' => '2025-09-19', 'is_recurring' => true],
            ['name' => 'Encuentro de Dos Mundos', 'date' => '2025-10-12', 'is_recurring' => false],
            ['name' => 'Día de las Iglesias Evangélicas', 'date' => '2025-10-31', 'is_recurring' => false],
            ['name' => 'Día de Todos los Santos', 'date' => '2025-11-01', 'is_recurring' => true],
            ['name' => 'Inmaculada Concepción', 'date' => '2025-12-08', 'is_recurring' => true],
            ['name' => 'Navidad', 'date' => '2025-12-25', 'is_recurring' => true],
        ];

        foreach ($holidays as $holiday) {
            Holiday::updateOrCreate(['date' => $holiday['date']], $holiday);
        }
    }
}
