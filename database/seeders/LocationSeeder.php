<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $bodega = Location::create(['name' => 'Bodega Central']);

        Location::create([
            'name' => 'Rack A',
            'parent_id' => $bodega->id
        ]);
    }
}
