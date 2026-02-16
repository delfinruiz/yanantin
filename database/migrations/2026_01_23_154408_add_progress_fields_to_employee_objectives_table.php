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
        Schema::table('employee_objectives', function (Blueprint $table) {
            $table->decimal('current_value', 12, 2)->default(0)->after('target_value');
            $table->decimal('progress_percentage', 5, 2)->default(0)->after('current_value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_objectives', function (Blueprint $table) {
            $table->dropColumn(['current_value', 'progress_percentage']);
        });
    }
};
