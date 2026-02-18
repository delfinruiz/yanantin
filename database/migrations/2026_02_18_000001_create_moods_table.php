<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('moods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date')->index();
            $table->string('mood'); // sad|med_sad|neutral|med_happy|happy
            $table->unsignedTinyInteger('score')->default(0); // 0..100
            $table->text('message')->nullable(); // Mensaje personal de IA
            $table->string('message_model')->nullable();
            $table->timestamp('message_generated_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moods');
    }
};

