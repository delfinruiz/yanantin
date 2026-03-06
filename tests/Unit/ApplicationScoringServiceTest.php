<?php

namespace Tests\Unit;

use App\Models\JobApplication;
use App\Models\JobOffer;
use App\Models\JobOfferRequirement;
use App\Services\ApplicationScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationScoringServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ApplicationScoringService $service;

    protected function setUp(): void
    {
        parent::setUp();
        // Resolver desde el contenedor para que inyecte dependencias automáticamente (incluyendo OpenAiCandidateAnalysisService)
        // O mockear si queremos aislarlo, pero resolverlo es más rápido ahora.
        $this->service = app(ApplicationScoringService::class);
    }

    public function test_it_marks_application_as_not_eligible_if_experience_is_insufficient()
    {
        // 1. Crear JobOffer con requisito de experiencia (5 años)
        $offer = JobOffer::factory()->create();
        JobOfferRequirement::create([
            'job_offer_id' => $offer->id,
            'category' => 'Experiencia laboral',
            'type' => 'Obligatorio',
            'level' => '5+ años',
        ]);

        // 2. Crear Application con CV snapshot (3 años)
        $application = JobApplication::factory()->create([
            'job_offer_id' => $offer->id,
            'cv_snapshot' => [
                'work_experience' => [
                    ['start_date' => now()->subYears(3)->format('Y-m-d'), 'end_date' => now()->format('Y-m-d')]
                ]
            ]
        ]);

        // 3. Ejecutar servicio
        $this->service->processApplication($application);

        // 4. Assert
        $application->refresh();
        $this->assertEquals('not_eligible', $application->eligibility_status);
        $this->assertEquals(0, $application->score);
        $this->assertEquals('Experiencia insuficiente', $application->rejection_reason);
    }

    public function test_it_marks_application_as_eligible_if_requirements_met()
    {
        $offer = JobOffer::factory()->create();
        // Requisito: 2 años
        JobOfferRequirement::create([
            'job_offer_id' => $offer->id,
            'category' => 'Experiencia laboral',
            'type' => 'Obligatorio',
            'level' => '2 años',
        ]);

        // Candidato: 3 años
        $application = JobApplication::factory()->create([
            'job_offer_id' => $offer->id,
            'cv_snapshot' => [
                'work_experience' => [
                    ['start_date' => now()->subYears(3)->format('Y-m-d'), 'end_date' => now()->format('Y-m-d')]
                ],
                'education' => [['level' => 'Tecnico']], // Default required is tecnico
            ]
        ]);

        $this->service->processApplication($application);

        $application->refresh();
        $this->assertEquals('eligible', $application->eligibility_status);
        $this->assertGreaterThan(0, $application->score); // Debe tener puntos por experiencia extra (1 año = 5 pts)
    }

    public function test_it_calculates_score_correctly()
    {
        $offer = JobOffer::factory()->create();
        
        // Requisitos: 2 años exp, PHP deseable
        JobOfferRequirement::create(['job_offer_id' => $offer->id, 'category' => 'Experiencia laboral', 'type' => 'Obligatorio', 'level' => '2 años']);
        JobOfferRequirement::create(['job_offer_id' => $offer->id, 'category' => 'Habilidad técnica', 'type' => 'Deseable', 'evidence' => 'PHP']);
        JobOfferRequirement::create(['job_offer_id' => $offer->id, 'category' => 'Habilidad técnica', 'type' => 'Deseable', 'evidence' => 'Laravel']);

        // Candidato: 4 años exp (+2 extra = 10 pts), PHP y Laravel (100% deseables = 40 pts)
        $application = JobApplication::factory()->create([
            'job_offer_id' => $offer->id,
            'cv_snapshot' => [
                'work_experience' => [
                    ['start_date' => now()->subYears(4)->format('Y-m-d'), 'end_date' => now()->format('Y-m-d')]
                ],
                'technical_skills' => ['PHP', 'Laravel'],
                'education' => [['level' => 'Tecnico']],
            ]
        ]);

        $this->service->processApplication($application);

        $application->refresh();
        
        // Expected:
        // Experience: 2 extra years * 5 = 10 pts
        // Skills: 2/2 matched = 40 pts
        // Education: Equal = 0 pts
        // Total = 50 pts
        
        $this->assertEquals(50, $application->score);
    }
}
