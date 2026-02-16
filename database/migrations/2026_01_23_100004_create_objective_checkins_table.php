<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('objective_checkins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_objective_id')->constrained('employee_objectives')->restrictOnDelete();
            $table->unsignedInteger('period_index')->nullable();
            $table->date('period_date')->nullable();
            $table->decimal('numeric_value', 12, 2)->nullable();
            $table->text('narrative')->nullable();
            $table->text('activities')->nullable();
            $table->json('evidence_paths')->nullable();
            $table->string('review_status')->default('pending_review');
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_comment')->nullable();
            $table->timestamps();
            $table->unique(['employee_objective_id', 'period_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('objective_checkins');
    }
};

