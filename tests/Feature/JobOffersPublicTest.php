<?php

use App\Models\JobOffer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
uses(Tests\TestCase::class);

uses(RefreshDatabase::class);

it('lists only active offers with pagination', function () {
    JobOffer::factory()->count(1)->create(); // inactive
    JobOffer::factory()->count(12)->active()->create();

    $response = \Pest\Laravel\get('/trabaja-con-nosotros');
    $response->assertOk();
    $response->assertSee('Trabaja con Nosotros');
    $response->assertSee('?page=2', false);
});

it('filters by search term', function () {
    JobOffer::factory()->active()->create(['title' => 'Desarrollador Laravel']);
    JobOffer::factory()->active()->create(['title' => 'Diseñador UX']);

    \Pest\Laravel\get('/trabaja-con-nosotros?q=Laravel')
        ->assertOk()
        ->assertSee('Desarrollador Laravel')
        ->assertDontSee('Diseñador UX');
});

it('returns full details via modal endpoint', function () {
    $offer = JobOffer::factory()->active()->create(['title' => 'QA Engineer']);
    \Pest\Laravel\getJson('/trabaja-con-nosotros/oferta/'.$offer->id)
        ->assertOk()
        ->assertJsonFragment(['title' => 'QA Engineer']);
});

it('returns 404 for inactive offers in modal endpoint', function () {
    $offer = JobOffer::factory()->create(['title' => 'Hidden']);
    \Pest\Laravel\getJson('/trabaja-con-nosotros/oferta/'.$offer->id)->assertNotFound();
});

it('allows public users into dashboard and rejects internal', function () {
    $publicUser = User::factory()->create(['is_internal' => false]);

    \Pest\Laravel\actingAs($publicUser);
    \Pest\Laravel\get('/mi-panel')->assertOk();

    $internalUser = User::factory()->create(['is_internal' => true]);
    \Pest\Laravel\actingAs($internalUser);
    \Pest\Laravel\get('/mi-panel')->assertForbidden();
});
