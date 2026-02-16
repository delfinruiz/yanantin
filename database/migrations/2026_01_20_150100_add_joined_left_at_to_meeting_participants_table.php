<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meeting_participants', function (Blueprint $table) {
            if (!Schema::hasColumn('meeting_participants', 'joined_at')) {
                $table->timestamp('joined_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('meeting_participants', 'left_at')) {
                $table->timestamp('left_at')->nullable()->after('joined_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('meeting_participants', function (Blueprint $table) {
            if (Schema::hasColumn('meeting_participants', 'joined_at')) {
                $table->dropColumn('joined_at');
            }
            if (Schema::hasColumn('meeting_participants', 'left_at')) {
                $table->dropColumn('left_at');
            }
        });
    }
};
