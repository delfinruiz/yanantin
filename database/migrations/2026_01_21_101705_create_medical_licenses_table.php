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
        Schema::create('medical_licenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('absence_type'); // licencia médica/enfermedad, maternidad, accidente
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('duration_days');
            $table->string('reason'); // Motivo/Descripción breve
            $table->text('diagnosis')->nullable(); // Descripción médica adicional
            $table->string('code')->nullable();
            $table->json('attachments')->nullable(); // Rutas de archivos adjuntos
            $table->string('status')->default('active'); // active, cancelled, etc.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_licenses');
    }
};
