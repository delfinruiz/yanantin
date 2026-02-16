<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('token_cpanel')->nullable()->after('company_name');
            $table->string('token_ai')->nullable()->after('token_cpanel');
            $table->string('token_zadarma')->nullable()->after('token_ai');
            $table->string('token_sms')->nullable()->after('token_zadarma');
            $table->string('token_email_marketing')->nullable()->after('token_sms');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'token_cpanel',
                'token_ai',
                'token_zadarma',
                'token_sms',
                'token_email_marketing',
            ]);
        });
    }
};
