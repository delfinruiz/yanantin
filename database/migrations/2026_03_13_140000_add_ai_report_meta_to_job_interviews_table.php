<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_interviews', function (Blueprint $table) {
            if (! Schema::hasColumn('job_interviews', 'ai_report_version')) {
                $table->unsignedInteger('ai_report_version')->nullable()->after('ai_report_generated_at');
            }

            if (! Schema::hasColumn('job_interviews', 'ai_report_source_hash')) {
                $table->string('ai_report_source_hash', 64)->nullable()->after('ai_report_version');
            }
        });
    }

    public function down(): void
    {
        Schema::table('job_interviews', function (Blueprint $table) {
            if (Schema::hasColumn('job_interviews', 'ai_report_source_hash')) {
                $table->dropColumn('ai_report_source_hash');
            }

            if (Schema::hasColumn('job_interviews', 'ai_report_version')) {
                $table->dropColumn('ai_report_version');
            }
        });
    }
};

