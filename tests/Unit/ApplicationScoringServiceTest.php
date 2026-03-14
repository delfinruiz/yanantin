<?php

namespace Tests\Unit;

use App\Models\JobApplication;
use App\Models\JobOffer;
use App\Models\JobOfferRequirement;
use App\Services\ApplicationScoringService;
use App\Services\OpenAiCandidateAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationScoringServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ApplicationScoringService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mock(OpenAiCandidateAnalysisService::class, function ($mock) {
            $mock->shouldReceive('analyzeApplication')->andReturn(['summary' => 'Mock Analysis']);
        });

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

    public function test_it_awards_superior_education_points_when_candidate_has_higher_level(): void
    {
        $offer = JobOffer::factory()->create();

        JobOfferRequirement::create([
            'job_offer_id' => $offer->id,
            'category' => 'Experiencia laboral',
            'type' => 'Obligatorio',
            'level' => '1 año',
        ]);
        JobOfferRequirement::create([
            'job_offer_id' => $offer->id,
            'category' => 'Educación',
            'type' => 'Obligatorio',
            'level' => 'Técnico',
        ]);

        $application = JobApplication::factory()->create([
            'job_offer_id' => $offer->id,
            'cv_snapshot' => [
                'work_experience' => [
                    ['start_date' => now()->subYears(2)->format('Y-m-d'), 'end_date' => now()->format('Y-m-d')],
                ],
                'education' => [['level' => 'Universitario']],
            ],
        ]);

        $this->service->processApplication($application);

        $application->refresh();
        $this->assertEquals('eligible', $application->eligibility_status);
        $this->assertEquals(15, $application->score);
    }

    public function test_it_marks_application_not_eligible_when_required_education_is_university_and_candidate_is_technical(): void
    {
        $offer = JobOffer::factory()->create();

        JobOfferRequirement::create([
            'job_offer_id' => $offer->id,
            'category' => 'Experiencia laboral',
            'type' => 'Obligatorio',
            'level' => '1 año',
        ]);
        JobOfferRequirement::create([
            'job_offer_id' => $offer->id,
            'category' => 'Educación',
            'type' => 'Obligatorio',
            'level' => 'Universitario',
        ]);

        $application = JobApplication::factory()->create([
            'job_offer_id' => $offer->id,
            'cv_snapshot' => [
                'work_experience' => [
                    ['start_date' => now()->subYears(2)->format('Y-m-d'), 'end_date' => now()->format('Y-m-d')],
                ],
                'education' => [['level' => 'Técnico']],
            ],
        ]);

        $this->service->processApplication($application);

        $application->refresh();
        $this->assertEquals('not_eligible', $application->eligibility_status);
        $this->assertEquals('Nivel educativo insuficiente', $application->rejection_reason);
    }

    public function test_it_marks_application_not_eligible_when_required_language_level_is_not_met(): void
    {
        $offer = JobOffer::factory()->create();

        JobOfferRequirement::create([
            'job_offer_id' => $offer->id,
            'category' => 'Idioma',
            'type' => 'Obligatorio',
            'level' => 'Intermedio',
            'evidence' => 'Español',
        ]);

        $application = JobApplication::factory()->create([
            'job_offer_id' => $offer->id,
            'cv_snapshot' => [
                'education' => [['level' => 'Técnico']],
                'languages' => [
                    ['language' => 'Español', 'level' => 'Básico'],
                ],
            ],
        ]);

        $this->service->processApplication($application);

        $application->refresh();
        $this->assertEquals('not_eligible', $application->eligibility_status);
        $this->assertEquals('Nivel de idioma insuficiente', $application->rejection_reason);
    }

    public function test_it_awards_language_bonus_when_candidate_exceeds_required_language_level(): void
    {
        $offer = JobOffer::factory()->create();

        JobOfferRequirement::create([
            'job_offer_id' => $offer->id,
            'category' => 'Idioma',
            'type' => 'Obligatorio',
            'level' => 'Intermedio',
            'evidence' => 'Español',
        ]);

        $application = JobApplication::factory()->create([
            'job_offer_id' => $offer->id,
            'cv_snapshot' => [
                'education' => [['level' => 'Técnico']],
                'languages' => [
                    ['language' => 'Español', 'level' => 'Nativo'],
                ],
            ],
        ]);

        $this->service->processApplication($application);

        $application->refresh();
        $this->assertEquals('eligible', $application->eligibility_status);
        $this->assertEquals(20, $application->score);
    }

    public function test_it_awards_language_bonus_when_candidate_meets_required_language_level(): void
    {
        $offer = JobOffer::factory()->create();

        JobOfferRequirement::create([
            'job_offer_id' => $offer->id,
            'category' => 'Idioma',
            'type' => 'Obligatorio',
            'level' => 'Intermedio',
            'evidence' => 'Español',
        ]);

        $application = JobApplication::factory()->create([
            'job_offer_id' => $offer->id,
            'cv_snapshot' => [
                'education' => [['level' => 'Técnico']],
                'languages' => [
                    ['language' => 'Español', 'level' => 'Intermedio'],
                ],
            ],
        ]);

        $this->service->processApplication($application);

        $application->refresh();
        $this->assertEquals('eligible', $application->eligibility_status);
        $this->assertEquals(20, $application->score);
    }

    public function test_it_logs_skills_check_as_passed_when_there_are_no_required_skills(): void
    {
        $offer = JobOffer::factory()->create();

        $application = JobApplication::factory()->create([
            'job_offer_id' => $offer->id,
            'cv_snapshot' => [
                'education' => [['level' => 'Técnico']],
            ],
        ]);

        $this->service->processApplication($application);

        $application->refresh();
        $this->assertEquals('eligible', $application->eligibility_status);
        $this->assertEquals(0, data_get($application->auto_decision_log, 'skills_check.required_count'));
        $this->assertEquals(0, data_get($application->auto_decision_log, 'skills_check.missing_count'));
        $this->assertTrue((bool) data_get($application->auto_decision_log, 'skills_check.passed'));
    }

    public function test_it_matches_desirable_skills_when_requirement_evidence_has_commas(): void
    {
        $offer = JobOffer::factory()->create();

        JobOfferRequirement::create([
            'job_offer_id' => $offer->id,
            'category' => 'Experiencia laboral',
            'type' => 'Obligatorio',
            'level' => '1 año',
        ]);
        JobOfferRequirement::create([
            'job_offer_id' => $offer->id,
            'category' => 'Habilidad técnica',
            'type' => 'Deseable',
            'evidence' => 'PHP, Laravel',
        ]);

        $application = JobApplication::factory()->create([
            'job_offer_id' => $offer->id,
            'cv_snapshot' => [
                'work_experience' => [
                    ['start_date' => now()->subYears(2)->format('Y-m-d'), 'end_date' => now()->format('Y-m-d')],
                ],
                'technical_skills' => [
                    ['software' => 'PHP'],
                    ['software' => 'Laravel'],
                ],
                'education' => [['level' => 'Técnico']],
            ],
        ]);

        $this->service->processApplication($application);

        $application->refresh();
        $this->assertEquals('eligible', $application->eligibility_status);
        $this->assertEquals(45, $application->score);
        $this->assertEquals(2, data_get($application->auto_decision_log, 'scoring_skills.matched_count'));
        $this->assertEquals(40, data_get($application->auto_decision_log, 'scoring_skills.points'));
    }
}
