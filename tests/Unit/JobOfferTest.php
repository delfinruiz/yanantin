<?php

use App\Models\JobOffer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('scope active returns non expired and active offers', function () {
    $active = JobOffer::factory()->active()->create(['deadline' => now()->addDay()]);
    $expired = JobOffer::factory()->active()->create(['deadline' => now()->subDay()]);
    $inactive = JobOffer::factory()->create();

    $ids = JobOffer::query()->active()->pluck('id')->all();

    expect($ids)->toContain($active->id)
        ->and($ids)->not->toContain($expired->id)
        ->and($ids)->not->toContain($inactive->id);
});

