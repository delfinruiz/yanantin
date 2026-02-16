<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionsTaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('permissions_task')->insertOrIgnore([
            ['id' => 1, 'title' => 'view'],
            ['id' => 2, 'title' => 'edit'],
        ]);
    }
}
