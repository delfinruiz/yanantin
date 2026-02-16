<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StatusSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('status')->insertOrIgnore([
            ['id' => 1, 'title' => 'Pending'],
            ['id' => 2, 'title' => 'Completed'],
            ['id' => 3, 'title' => 'In_Progress'],
        ]);
    }
}
