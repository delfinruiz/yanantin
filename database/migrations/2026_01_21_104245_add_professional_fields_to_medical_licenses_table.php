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
        Schema::table('medical_licenses', function (Blueprint $table) {
            $table->string('professional_lastname_father')->nullable()->after('status');
            $table->string('professional_lastname_mother')->nullable()->after('professional_lastname_father');
            $table->string('professional_names')->nullable()->after('professional_lastname_mother');
            $table->string('professional_rut')->nullable()->after('professional_names');
            $table->string('professional_specialty')->nullable()->after('professional_rut');
            $table->string('professional_type')->nullable()->after('professional_specialty'); // 1=Medico, 2=Dentista, 3=Matrona
            $table->string('professional_registry_code')->nullable()->after('professional_type');
            $table->string('professional_email')->nullable()->after('professional_registry_code');
            $table->string('professional_phone')->nullable()->after('professional_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('medical_licenses', function (Blueprint $table) {
            $table->dropColumn([
                'professional_lastname_father',
                'professional_lastname_mother',
                'professional_names',
                'professional_rut',
                'professional_specialty',
                'professional_type',
                'professional_registry_code',
                'professional_email',
                'professional_phone',
            ]);
        });
    }
};
