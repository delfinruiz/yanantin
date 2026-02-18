<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('happiness_suggestions', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('suggestion');
            $table->json('context')->nullable();
            $table->string('model')->nullable();
            $table->timestamps();
            $table->unique(['date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('happiness_suggestions');
    }
};

