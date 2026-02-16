<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_ranges', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('min_percentage');
            $table->unsignedInteger('max_percentage');
            $table->timestamps();
            $table->unique(['min_percentage', 'max_percentage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_ranges');
    }
};

