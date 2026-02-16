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
        // 1. Add progress tracking fields to strategic_objectives
        Schema::table('strategic_objectives', function (Blueprint $table) {
            $table->decimal('current_value', 10, 2)->default(0)->after('target_value');
            $table->decimal('progress_percentage', 5, 2)->default(0)->after('current_value');
            $table->date('due_date')->nullable()->after('description'); // Optional due date override? Or strictly cycle? User wants integration.
            $table->string('execution_status')->default('pending')->after('status'); // pending, in_progress, completed, cancelled
        });

        // 2. Drop old checkins table and create new one linked to strategic_objectives
        // We drop because the FK is different and we assume data migration is not required/possible directly due to ID mismatch
        Schema::dropIfExists('objective_checkins');

        Schema::create('objective_checkins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('strategic_objective_id')->constrained('strategic_objectives')->onDelete('cascade');
            $table->integer('period_index'); // 1, 2, 3... corresponding to cycle periods
            $table->date('period_date')->nullable();
            $table->decimal('numeric_value', 10, 2)->nullable();
            $table->text('narrative')->nullable();
            $table->text('activities')->nullable();
            $table->json('evidence_paths')->nullable();
            $table->string('review_status')->default('pending_review'); // pending_review, approved, rejected
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_comment')->nullable();
            $table->timestamps();
        });

        // 3. Drop employee_objectives table
        Schema::dropIfExists('employee_objectives');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Not easily reversible as we dropped tables, but we can try to restore structure
        Schema::table('strategic_objectives', function (Blueprint $table) {
            $table->dropColumn(['current_value', 'progress_percentage', 'due_date', 'execution_status']);
        });

        Schema::dropIfExists('objective_checkins');
        
        // Recreate old tables (simplified)
        Schema::create('employee_objectives', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
    }
};
