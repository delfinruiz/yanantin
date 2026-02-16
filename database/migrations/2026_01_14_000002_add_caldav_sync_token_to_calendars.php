<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('calendars', function (Blueprint $table) {
            if (!Schema::hasColumn('calendars', 'caldav_sync_token')) {
                $table->string('caldav_sync_token')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('calendars', function (Blueprint $table) {
            if (Schema::hasColumn('calendars', 'caldav_sync_token')) {
                $table->dropColumn('caldav_sync_token');
            }
        });
    }
};

