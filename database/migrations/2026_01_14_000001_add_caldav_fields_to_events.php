<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (!Schema::hasColumn('events', 'caldav_uid')) {
                $table->string('caldav_uid')->nullable()->index();
            }
            if (!Schema::hasColumn('events', 'caldav_etag')) {
                $table->string('caldav_etag')->nullable();
            }
            if (!Schema::hasColumn('events', 'caldav_last_sync_at')) {
                $table->timestamp('caldav_last_sync_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (Schema::hasColumn('events', 'caldav_uid')) {
                $table->dropColumn('caldav_uid');
            }
            if (Schema::hasColumn('events', 'caldav_etag')) {
                $table->dropColumn('caldav_etag');
            }
            if (Schema::hasColumn('events', 'caldav_last_sync_at')) {
                $table->dropColumn('caldav_last_sync_at');
            }
        });
    }
};

