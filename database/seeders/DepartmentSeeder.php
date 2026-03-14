<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            [
                'name' => 'Gestión Humana',
                'description' => 'Administra el ciclo de vida del colaborador: reclutamiento y selección, inducción, capacitación, desempeño, clima laboral, compensaciones y cumplimiento normativo.',
            ],
            [
                'name' => 'Marketing',
                'description' => 'Define y ejecuta la estrategia de marca y comunicación: investigación de mercado, campañas, contenidos, posicionamiento, generación de demanda y análisis de resultados.',
            ],
            [
                'name' => 'Tecnología',
                'description' => 'Diseña, desarrolla y mantiene las plataformas y sistemas: arquitectura, infraestructura, seguridad, soporte técnico, operación y continuidad de servicios digitales.',
            ],
            [
                'name' => 'I + D',
                'description' => 'Impulsa la innovación mediante investigación y desarrollo: prototipado, validación de hipótesis, mejora continua, automatización y adopción de nuevas tecnologías.',
            ],
            [
                'name' => 'Finanzas',
                'description' => 'Gestiona la salud financiera de la organización: presupuestos, contabilidad, tesorería, control de gestión, análisis financiero, cumplimiento tributario y reportabilidad.',
            ],
        ];

        foreach ($departments as $department) {
            Department::query()->firstOrCreate(
                ['name' => $department['name']],
                ['description' => $department['description']],
            );
        }
    }
}

