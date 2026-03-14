<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('responses', function (Blueprint $table) {
            if (Schema::hasColumn('responses', 'job_interview_id')) {
                return;
            }

            $table->foreignId('job_interview_id')
                ->nullable()
                ->after('question_id')
                ->constrained('job_interviews')
                ->cascadeOnDelete();

            $table->index('job_interview_id');
            $table->index(['job_interview_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::table('responses', function (Blueprint $table) {
            if (! Schema::hasColumn('responses', 'job_interview_id')) {
                return;
            }

            $table->dropForeign(['job_interview_id']);
            $table->dropColumn('job_interview_id');
        });
    }
};

