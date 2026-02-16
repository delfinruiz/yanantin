<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_cycle_id')->constrained('evaluation_cycles')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->decimal('final_score', 5, 2)->nullable();
            $table->unsignedBigInteger('performance_range_id')->nullable(); // FK opcional para evitar orden de migraciones
            $table->decimal('bonus_amount', 12, 2)->nullable();
            $table->json('details')->nullable();
            $table->timestamp('computed_at')->nullable();
            $table->timestamps();
            $table->unique(['evaluation_cycle_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_results');
    }
};
