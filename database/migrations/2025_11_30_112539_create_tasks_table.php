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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            
            // Claves foráneas para los usuarios
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');

            // Campos de la tarea
            $table->string('title');
            $table->text('description');
            $table->text('observation')->nullable();
            $table->integer('rating')->default(0);

            // relacion entre statuses y tasks
            $table->foreignId('status_id')->constrained('status')->onDelete('restrict')->default(1); 

            // relacion entre tasks y permissions_tasks
            $table->foreignId('permissions_id')->constrained('permissions_task')->onDelete('restrict')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
