<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('absence_requests')) {
            try {
                DB::statement('ALTER TABLE `absence_requests` DROP FOREIGN KEY `absence_requests_hr_user_id_foreign`');
            } catch (\Throwable $e) {}
            try {
                DB::statement('ALTER TABLE `absence_requests` DROP FOREIGN KEY `absence_requests_supervisor_id_foreign`');
            } catch (\Throwable $e) {}

            try {
                DB::statement('ALTER TABLE `absence_requests` ADD CONSTRAINT `absence_requests_hr_user_id_foreign` FOREIGN KEY (`hr_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL');
            } catch (\Throwable $e) {}
            try {
                DB::statement('ALTER TABLE `absence_requests` ADD CONSTRAINT `absence_requests_supervisor_id_foreign` FOREIGN KEY (`supervisor_id`) REFERENCES `users`(`id`) ON DELETE SET NULL');
            } catch (\Throwable $e) {}
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('absence_requests')) {
            try {
                DB::statement('ALTER TABLE `absence_requests` DROP FOREIGN KEY `absence_requests_hr_user_id_foreign`');
            } catch (\Throwable $e) {}
            try {
                DB::statement('ALTER TABLE `absence_requests` DROP FOREIGN KEY `absence_requests_supervisor_id_foreign`');
            } catch (\Throwable $e) {}

            try {
                DB::statement('ALTER TABLE `absence_requests` ADD CONSTRAINT `absence_requests_hr_user_id_foreign` FOREIGN KEY (`hr_user_id`) REFERENCES `users`(`id`)');
            } catch (\Throwable $e) {}
            try {
                DB::statement('ALTER TABLE `absence_requests` ADD CONSTRAINT `absence_requests_supervisor_id_foreign` FOREIGN KEY (`supervisor_id`) REFERENCES `users`(`id`)');
            } catch (\Throwable $e) {}
        }
    }
};
