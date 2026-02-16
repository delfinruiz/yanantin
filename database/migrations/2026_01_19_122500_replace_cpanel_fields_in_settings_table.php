<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['cpanel_api_token', 'cpanel_client_key', 'cpanel_client_secret']);
            $table->string('cpanel_host')->nullable()->after('company_name');
            $table->string('cpanel_username')->nullable()->after('cpanel_host');
            $table->string('cpanel_token')->nullable()->after('cpanel_username');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['cpanel_host', 'cpanel_username', 'cpanel_token']);
            $table->string('cpanel_api_token')->nullable()->after('company_name');
            $table->string('cpanel_client_key')->nullable()->after('cpanel_api_token');
            $table->string('cpanel_client_secret')->nullable()->after('cpanel_client_key');
        });
    }
};
