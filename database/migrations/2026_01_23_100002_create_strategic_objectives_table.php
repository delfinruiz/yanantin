<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('strategic_objectives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_cycle_id')->constrained('evaluation_cycles')->restrictOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['quantitative', 'qualitative']);
            $table->decimal('target_value', 12, 2)->nullable();
            $table->string('unit')->nullable();
            $table->decimal('weight', 5, 2)->default(0); // porcentaje 0-100
            $table->foreignId('parent_id')->nullable()->constrained('strategic_objectives')->nullOnDelete();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('strategic_objectives');
    }
};

