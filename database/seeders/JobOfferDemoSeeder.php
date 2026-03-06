<?php

namespace Database\Seeders;

use App\Models\JobOffer;
use Illuminate\Database\Seeder;

class JobOfferDemoSeeder extends Seeder
{
    public function run(): void
    {
        JobOffer::factory()->count(30)->active()->create();
        JobOffer::factory()->count(10)->create();
    }
}
