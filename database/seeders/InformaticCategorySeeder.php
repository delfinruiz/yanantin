<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InformaticCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Category::insert([
        ['name' => 'Notebook'],
        ['name' => 'Desktop'],
        ['name' => 'Monitor'],
        ['name' => 'Teclado'],
        ['name' => 'Mouse'],
        ['name' => 'Impresora'],
        ['name' => 'Router'],
        ['name' => 'Servidor'],
    ]);
    }
}
