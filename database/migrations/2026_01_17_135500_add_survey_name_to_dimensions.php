<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('dimensions', function (Blueprint $table) {
            $table->string('survey_name')->nullable()->after('id');
            $table->unique('survey_name');
        });
    }

    public function down(): void
    {
        Schema::table('dimensions', function (Blueprint $table) {
            $table->dropUnique(['survey_name']);
            $table->dropColumn('survey_name');
        });
    }
};

