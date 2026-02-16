<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\User; // Asegúrate de importar el modelo User
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Obtener los IDs de los usuarios existentes
        // Esto es crucial, ya que las claves foráneas deben ser válidas.
        $userIds = User::pluck('id')->toArray();

        // Si no hay usuarios, detenemos el seeder o creamos algunos
        if (empty($userIds)) {
            echo "¡Advertencia! No hay usuarios. Ejecuta 'php artisan db:seed --class=UserSeeder' primero.\n";
            return;
        }

        // 2. Definir una lista de tareas para el ejemplo
        $tasks = [
            [
                'title' => 'Implementar autenticación de usuarios',
                'description' => 'Configurar rutas, controladores y vistas para login/registro.',
                'observation' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Diseñar estructura de base de datos',
                'description' => 'Finalizar esquema de tareas, estados y permisos.',
                'observation' => 'Pendiente la revisión final de las migraciones.',
                'created_at' => now()->subDays(5),
                'updated_at' => now()->subDays(1),
            ],
            [
                'title' => 'Crear componente Livewire de Tareas',
                'description' => 'Desarrollar la tabla de tareas con Filament.',
                'observation' => 'Incluir filtros por estado y asignado.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Agrega más tareas aquí...
        ];

        // 3. Iterar y crear las tareas con IDs de usuario aleatorios
        foreach ($tasks as $taskData) {
            // Seleccionar IDs de usuario aleatorios de la lista
            $creatorId = $userIds[array_rand($userIds)];
            $ownerId = $userIds[array_rand($userIds)];
            // assigned_to puede ser nulo o un ID de usuario
            $assignedId = (rand(0, 1) ? $userIds[array_rand($userIds)] : null);

            Task::create(array_merge($taskData, [
                'created_by' => $creatorId,
                'user_id' => $ownerId, // Propietario de la tarea
                'assigned_to' => $assignedId,
            ]));
        }
    }
}