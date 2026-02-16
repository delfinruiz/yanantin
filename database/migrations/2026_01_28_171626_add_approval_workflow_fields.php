<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Jerarquía: Quién es el jefe directo de quién
        Schema::table('employee_profiles', function (Blueprint $table) {
            $table->foreignId('reports_to')->nullable()
                  ->comment('Jefe directo del empleado')
                  ->constrained('users')
                  ->nullOnDelete();
        });

        // 2. Estado de Aprobación en Objetivos Estratégicos
        Schema::table('strategic_objectives', function (Blueprint $table) {
            $table->string('status')->default('draft')
                  ->comment('draft, pending_approval, approved, rejected');
            
            $table->text('rejection_reason')->nullable();
            
            $table->foreignId('approved_by')->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employee_profiles', function (Blueprint $table) {
            $table->dropForeign(['reports_to']);
            $table->dropColumn('reports_to');
        });

        Schema::table('strategic_objectives', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['status', 'rejection_reason', 'approved_by']);
        });
    }
};
