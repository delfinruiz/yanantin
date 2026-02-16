<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('dimensions', function (Blueprint $table) {
            $table->dropUnique(['survey_name']);
            $table->unique(['survey_name', 'item'], 'dimensions_survey_name_item_unique');
        });
    }

    public function down(): void
    {
        Schema::table('dimensions', function (Blueprint $table) {
            $table->dropUnique('dimensions_survey_name_item_unique');
            $table->unique('survey_name', 'dimensions_survey_name_unique');
        });
    }
};
