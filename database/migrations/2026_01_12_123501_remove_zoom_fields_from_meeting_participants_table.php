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
        Schema::table('meeting_participants', function (Blueprint $table) {
            $table->dropColumn(['zoom_registrant_id', 'zoom_join_url']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meeting_participants', function (Blueprint $table) {
            $table->string('zoom_registrant_id')->nullable();
            $table->text('zoom_join_url')->nullable();
        });
    }
};
