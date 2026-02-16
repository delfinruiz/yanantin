<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Asignar un valor por defecto a los registros existentes que son nulos
        DB::table('cargos')->whereNull('hierarchy_level')->update(['hierarchy_level' => 99]);

        Schema::table('cargos', function (Blueprint $table) {
            $table->integer('hierarchy_level')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cargos', function (Blueprint $table) {
            $table->integer('hierarchy_level')->nullable()->change();
        });
    }
};
