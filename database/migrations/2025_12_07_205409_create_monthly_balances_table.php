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
        Schema::create('monthly_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('year');
            $table->tinyInteger('month');
            $table->integer('total_income')->default(0);
            $table->integer('total_expense')->default(0);
            $table->integer('balance')->default(0);
            $table->timestamp('calculated_at')->nullable();
            $table->text('notes')->nullable();

            // Índice único del diagrama
            $table->unique(['user_id', 'year', 'month']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_balances');
    }
};
