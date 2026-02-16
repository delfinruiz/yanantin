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
        Schema::table('responses', function (Blueprint $table) {
            $table->string('guest_email')->nullable()->after('user_id');
            $table->string('guest_name')->nullable()->after('guest_email');
            $table->foreignId('user_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('responses', function (Blueprint $table) {
            // Cannot easily revert nullable change without knowing previous state exactly or data issues
            // But we can drop the new columns
            $table->dropColumn(['guest_email', 'guest_name']);
            // We assume user_id was not nullable. 
            // But if we have null values now, this will fail.
            // $table->foreignId('user_id')->nullable(false)->change(); 
        });
    }
};
