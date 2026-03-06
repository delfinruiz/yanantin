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
        Schema::table('job_applications', function (Blueprint $table) {
            $table->string('eligibility_status')->nullable()->after('status'); // eligible, not_eligible
            $table->decimal('score', 5, 2)->nullable()->after('eligibility_status');
            $table->string('rejection_reason')->nullable()->after('score');
            $table->json('auto_decision_log')->nullable()->after('rejection_reason');
            $table->timestamp('auto_processed_at')->nullable()->after('auto_decision_log');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropColumn([
                'eligibility_status',
                'score',
                'rejection_reason',
                'auto_decision_log',
                'auto_processed_at',
            ]);
        });
    }
};
