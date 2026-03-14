<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_interviews', function (Blueprint $table) {
            if (! Schema::hasColumn('job_interviews', 'ai_report')) {
                $table->longText('ai_report')->nullable()->after('comments');
            }

            if (! Schema::hasColumn('job_interviews', 'ai_report_generated_at')) {
                $table->timestamp('ai_report_generated_at')->nullable()->after('ai_report');
            }
        });
    }

    public function down(): void
    {
        Schema::table('job_interviews', function (Blueprint $table) {
            if (Schema::hasColumn('job_interviews', 'ai_report_generated_at')) {
                $table->dropColumn('ai_report_generated_at');
            }

            if (Schema::hasColumn('job_interviews', 'ai_report')) {
                $table->dropColumn('ai_report');
            }
        });
    }
};

