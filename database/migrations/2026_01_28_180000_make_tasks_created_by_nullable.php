<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // 1. Eliminar la restricción de clave foránea existente
            $table->dropForeign(['created_by']);

            // 2. Modificar la columna para permitir valores nulos
            $table->unsignedBigInteger('created_by')->nullable()->change();

            // 3. Agregar la nueva restricción con ON DELETE SET NULL
            $table->foreign('created_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['created_by']);

            // Nota: Revertir a no-nullable puede fallar si hay registros con null.
            // Por seguridad en el down, solo restauramos la restricción restrict
            // asumiendo que se limpiaron los datos o aceptando que quede nullable.
            // Para ser estrictos:
            // $table->unsignedBigInteger('created_by')->nullable(false)->change();

            $table->foreign('created_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('restrict');
        });
    }
};
