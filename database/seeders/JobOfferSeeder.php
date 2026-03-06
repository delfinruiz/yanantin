<?php

namespace Database\Seeders;

use App\Models\JobOffer;
use App\Models\JobOfferRequirement;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class JobOfferSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Limpiar tablas
        Schema::disableForeignKeyConstraints();
        JobOfferRequirement::truncate();
        JobOffer::truncate();
        Schema::enableForeignKeyConstraints();

        // 2. Generar 20 ofertas variadas

        // Grupo 1: Activas y Publicadas (Vigentes) - 10 ofertas
        // Ofertas que están visibles en el portal
        JobOffer::factory(10)
            ->active() // Sets is_active=true, published_at=now()
            ->state([
                'deadline' => now()->addDays(random_int(15, 60)), // Vencen en el futuro
            ])
            ->create()
            ->each(function (JobOffer $jobOffer) {
                JobOfferRequirement::factory(random_int(3, 6))->create([
                    'job_offer_id' => $jobOffer->id,
                ]);
            });

        // Grupo 2: Activas pero Vencidas (Histórico) - 5 ofertas
        // Ofertas que se publicaron pero ya pasó su fecha límite
        JobOffer::factory(5)
            ->state([
                'is_active' => true,
                'published_at' => now()->subDays(random_int(30, 90)),
                'deadline' => now()->subDays(random_int(1, 10)), // Ya venció
            ])
            ->create()
            ->each(function (JobOffer $jobOffer) {
                JobOfferRequirement::factory(random_int(3, 5))->create([
                    'job_offer_id' => $jobOffer->id,
                ]);
            });

        // Grupo 3: Borradores / Inactivas (No publicadas) - 5 ofertas
        // Ofertas en preparación, no visibles
        JobOffer::factory(5)
            ->state([
                'is_active' => false,
                'published_at' => null,
                'deadline' => null,
            ])
            ->create()
            ->each(function (JobOffer $jobOffer) {
                JobOfferRequirement::factory(random_int(2, 4))->create([
                    'job_offer_id' => $jobOffer->id,
                ]);
            });
    }
}
