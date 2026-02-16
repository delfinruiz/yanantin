<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_objectives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_cycle_id')->constrained('evaluation_cycles')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('strategic_objective_id')->nullable()->constrained('strategic_objectives')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['quantitative', 'qualitative']);
            $table->decimal('target_value', 12, 2)->nullable();
            $table->decimal('weight', 5, 2)->default(0); // porcentaje 0-100
            $table->string('status')->default('pending_approval');
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->unique(['evaluation_cycle_id', 'user_id', 'title']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_objectives');
    }
};

