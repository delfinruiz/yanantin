<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\ContractType;

class ContractTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            'Indefinido',
            'Plazo Fijo',
            'Plazo Fijo (Reemplazo)',
            'Plazo Fijo (Proyecto)',
            'Por Obra o Faena',
            'Part-Time',
            'Honorarios',
            'Práctica Profesional',
        ];

        foreach ($types as $type) {
            ContractType::firstOrCreate(['name' => $type]);
        }
    }
}
