<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_ai_appreciations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained()->cascadeOnDelete();
            $table->longText('content');
            $table->string('provider')->default('openai');
            $table->string('model')->nullable();
            $table->unsignedInteger('usage_tokens')->nullable();
            $table->unsignedInteger('reasoning_tokens')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->unique('survey_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_ai_appreciations');
    }
};
