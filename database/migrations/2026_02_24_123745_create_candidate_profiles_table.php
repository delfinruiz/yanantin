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
        Schema::create('candidate_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            // Personal Info
            $table->string('phone')->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('rut')->nullable();
            $table->date('birth_date')->nullable();
            $table->boolean('relocation_availability')->default(false);
            $table->string('modality_availability')->nullable(); // Presencial, Remoto, Híbrido

            // JSON fields for complex data
            $table->json('education')->nullable();
            $table->json('work_experience')->nullable();
            $table->json('languages')->nullable();
            $table->json('technical_skills')->nullable();
            $table->json('soft_skills')->nullable();
            $table->json('references')->nullable();
            
            // Complementary Info
            $table->string('salary_expectation')->nullable();
            $table->boolean('immediate_availability')->default(false);
            $table->string('portfolio_url')->nullable();
            $table->string('linkedin_url')->nullable();

            // Confirmations
            $table->boolean('veracity_declaration')->default(false);
            $table->boolean('ai_authorization')->default(false);
            $table->boolean('automated_evaluation_consent')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidate_profiles');
    }
};
