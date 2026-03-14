<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

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
                DepartmentSeeder::class,
                CargoSeeder::class,
            ]);

        $admin = User::query()->find(1);
        if (! $admin) {
            $admin = User::query()->firstOrCreate(
                ['email' => 'ivanruizdelfin@gmail.com'],
                [
                    'name' => 'Administrador',
                    'password' => Hash::make('12345678'),
                    'is_internal' => false,
                    'email_verified_at' => now(),
                ],
            );
        }

        if (! $admin->is_internal) {
            $admin->forceFill(['is_internal' => true])->save();
        }

        if (! $admin->email_verified_at) {
            $admin->forceFill(['email_verified_at' => now()])->save();
        }

        $roleName = config('filament-shield.super_admin.name', 'super_admin');
        $superAdminRole = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        $admin->syncRoles([$superAdminRole]);

    Item::factory(50)->create();

    }
}
