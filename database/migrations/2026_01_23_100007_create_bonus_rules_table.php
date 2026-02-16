<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bonus_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('performance_range_id')->constrained('performance_ranges')->restrictOnDelete();
            $table->decimal('percentage', 5, 2)->nullable();
            $table->decimal('fixed_amount', 12, 2)->nullable();
            $table->string('base_type')->default('percentage'); // percentage|fixed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bonus_rules');
    }
};

