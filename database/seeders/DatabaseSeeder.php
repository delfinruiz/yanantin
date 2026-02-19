<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\User;
use Database\Factories\ItemFactory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        /*User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);*/

        // Llamar al seeder de tareas
        /*$this->call(TaskSeeder::class);*/
/*
        \App\Models\User::factory(3)->create();
        \App\Models\ExpenseCategory::factory(10)->create();
        \App\Models\IncomeType::factory(10)->create();
        \App\Models\Expense::factory(20)->create();
        \App\Models\Income::factory(20)->create();
*/

            $this->call([
                RoleSeeder::class,
                AbsenceSeeder::class,
                InformaticCategorySeeder::class,
                StatusInventorySeeder::class,
                LocationSeeder::class,
                StatusSeeder::class,
                PermissionsTaskSeeder::class,
                ContractTypeSeeder::class,
    ]);

    Item::factory(50)->create();

    }
}
