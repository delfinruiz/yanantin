<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_interviews', function (Blueprint $table) {
            if (! Schema::hasColumn('job_interviews', 'ai_score')) {
                $table->decimal('ai_score', 5, 2)->nullable()->after('score');
            }

            if (! Schema::hasColumn('job_interviews', 'ai_score_generated_at')) {
                $table->timestamp('ai_score_generated_at')->nullable()->after('ai_score');
            }

            if (! Schema::hasColumn('job_interviews', 'ai_score_version')) {
                $table->unsignedInteger('ai_score_version')->nullable()->after('ai_score_generated_at');
            }

            if (! Schema::hasColumn('job_interviews', 'ai_score_source_hash')) {
                $table->string('ai_score_source_hash', 64)->nullable()->after('ai_score_version');
            }
        });
    }

    public function down(): void
    {
        Schema::table('job_interviews', function (Blueprint $table) {
            if (Schema::hasColumn('job_interviews', 'ai_score_source_hash')) {
                $table->dropColumn('ai_score_source_hash');
            }

            if (Schema::hasColumn('job_interviews', 'ai_score_version')) {
                $table->dropColumn('ai_score_version');
            }

            if (Schema::hasColumn('job_interviews', 'ai_score_generated_at')) {
                $table->dropColumn('ai_score_generated_at');
            }

            if (Schema::hasColumn('job_interviews', 'ai_score')) {
                $table->dropColumn('ai_score');
            }
        });
    }
};

