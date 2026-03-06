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
        Schema::table('job_offers', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('hierarchical_level')->nullable();
            $table->string('criticality_level')->nullable();
            $table->string('work_modality')->nullable();
            $table->integer('vacancies_count')->default(1);
            $table->date('estimated_start_date')->nullable();
            $table->string('cost_center')->nullable();
            $table->string('opening_reason')->nullable();
            $table->text('mission')->nullable();
            $table->text('organizational_impact')->nullable();
            $table->json('key_results')->nullable();
        });

        Schema::create('job_offer_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_offer_id')->constrained('job_offers')->cascadeOnDelete();
            $table->string('category'); // Experiencia laboral, Área funcional, etc.
            $table->string('type'); // Obligatorio, Deseable
            $table->string('level')->nullable();
            $table->integer('weight')->nullable(); // Para deseables
            $table->text('evidence')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_offer_requirements');

        Schema::table('job_offers', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn([
                'department_id',
                'hierarchical_level',
                'criticality_level',
                'work_modality',
                'vacancies_count',
                'estimated_start_date',
                'cost_center',
                'opening_reason',
                'mission',
                'organizational_impact',
                'key_results',
            ]);
        });
    }
};
