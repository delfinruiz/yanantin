<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('file_item_shares', function (Blueprint $table) {
            $table->boolean('requires_ack')->default(false)->after('permission');
            $table->string('ack_code', 16)->nullable()->after('requires_ack');
            $table->timestamp('ack_code_expires_at')->nullable()->after('ack_code');
            $table->timestamp('ack_completed_at')->nullable()->after('ack_code_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('file_item_shares', function (Blueprint $table) {
            $table->dropColumn(['requires_ack', 'ack_code', 'ack_code_expires_at', 'ack_completed_at']);
        });
    }
};
