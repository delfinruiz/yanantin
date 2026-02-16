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
        Schema::table('absence_types', function (Blueprint $table) {
            $table->float('accrual_days_per_year')->default(15)->after('is_vacation')->comment('Días de vacaciones acumulados por año');
        });

        Schema::table('employee_profiles', function (Blueprint $table) {
            $table->foreignId('vacation_type_id')->nullable()->after('contract_type_id')->constrained('absence_types')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_profiles', function (Blueprint $table) {
            $table->dropForeign(['vacation_type_id']);
            $table->dropColumn('vacation_type_id');
        });

        Schema::table('absence_types', function (Blueprint $table) {
            $table->dropColumn('accrual_days_per_year');
        });
    }
};
