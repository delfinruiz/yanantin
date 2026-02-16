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
        Schema::table('absence_requests', function (Blueprint $table) {
            // Drop existing foreign keys
            $table->dropForeign(['hr_user_id']);
            $table->dropForeign(['supervisor_id']);

            // Re-add foreign keys with ON DELETE SET NULL
            $table->foreign('hr_user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('supervisor_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('absence_requests', function (Blueprint $table) {
            $table->dropForeign(['hr_user_id']);
            $table->dropForeign(['supervisor_id']);

            // Revert to original constraints (restrict/no action default)
            $table->foreign('hr_user_id')
                ->references('id')
                ->on('users');

            $table->foreign('supervisor_id')
                ->references('id')
                ->on('users');
        });
    }
};
