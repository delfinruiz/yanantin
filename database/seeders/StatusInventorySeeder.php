<?php

namespace Database\Seeders;

use App\Models\StatusInventory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StatusInventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        StatusInventory::insert([
        ['name' => 'Disponible', 'color' => 'success'],
        ['name' => 'Asignado', 'color' => 'info'],
        ['name' => 'En Reparación', 'color' => 'warning'],
        ['name' => 'Dado de Baja', 'color' => 'danger'],
    ]);
    }
}
