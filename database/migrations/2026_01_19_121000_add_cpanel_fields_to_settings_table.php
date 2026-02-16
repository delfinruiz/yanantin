<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('cpanel_api_token')->nullable()->after('company_name');
            $table->string('cpanel_client_key')->nullable()->after('cpanel_api_token');
            $table->string('cpanel_client_secret')->nullable()->after('cpanel_client_key');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'cpanel_api_token',
                'cpanel_client_key',
                'cpanel_client_secret',
            ]);
        });
    }
};
