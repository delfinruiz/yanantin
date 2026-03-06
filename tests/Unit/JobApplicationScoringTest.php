<?php

namespace Tests\Unit;

use App\Models\JobApplication;
use App\Models\JobOffer;
use App\Models\JobOfferRequirement;
use App\Services\ApplicationScoringService;
use App\Services\OpenAiCandidateAnalysisService;
use Mockery;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class JobApplicationScoringTest extends TestCase
{
    // No usamos RefreshDatabase para poder consultar los datos reales existentes si fuera necesario,
    // pero para pruebas unitarias puras deberíamos mockear o crear datos en memoria.
    // El usuario pidió verificar con la oferta ID 1 y el postulante Juan Astorga.
    // Esto sugiere una prueba de integración con la base de datos real.
    
    // Si queremos probar con datos reales sin borrarlos, NO debemos usar RefreshDatabase.
    
    public function test_scoring_calculation_with_real_data()
    {
        // Buscar la oferta ID 1
        $jobOffer = JobOffer::find(1);
        
        if (!$jobOffer) {
            $this->markTestSkipped('Job Offer ID 1 not found in database.');
            return;
        }

        // Buscar la postulación de Juan Astorga para esta oferta
        // Asumimos que el email es el identificador único o el nombre
        $application = JobApplication::where('job_offer_id', 1)
            ->where('applicant_name', 'like', '%Juan Astorga%')
            ->first();

        if (!$application) {
            // Intentar buscar por email si el nombre no coincide exacto
            $application = JobApplication::where('job_offer_id', 1)
                ->where('applicant_email', 'like', '%ivanruizdelfin%') // Email visto en la captura
                ->first();
        }

        if (!$application) {
            $this->markTestSkipped('Application for Juan Astorga not found.');
            return;
        }

        // Imprimir datos para depuración
        echo "\n--- Debug Info ---\n";
        echo "Job Offer: " . $jobOffer->title . "\n";
        
        $requirements = $jobOffer->jobOfferRequirements()
            ->whereIn('category', ['Habilidad técnica', 'Habilidad blanda', 'Idioma'])
            ->where('type', 'Obligatorio')
            ->get();
            
        echo "Obligatory Requirements:\n";
        foreach ($requirements as $req) {
            echo "- Type: {$req->category}, Evidence: '{$req->evidence}'\n";
        }

        echo "\nCandidate Snapshot Skills:\n";
        $snapshot = $application->cv_snapshot;
        $technical = $snapshot['technical_skills'] ?? [];
        $soft = $snapshot['soft_skills'] ?? [];
        $languages = $snapshot['languages'] ?? [];
        
        foreach ($technical as $s) echo "- Technical: " . json_encode($s) . "\n";
        foreach ($soft as $s) echo "- Soft: " . json_encode($s) . "\n";
        foreach ($languages as $l) echo "- Language: " . json_encode($l) . "\n";

        // Instanciar servicio (mockeando el servicio de IA para no gastar tokens ni depender de él)
        $aiService = Mockery::mock(OpenAiCandidateAnalysisService::class);
        $aiService->shouldReceive('analyzeApplication')->andReturn(['summary' => 'Mock Analysis']);
        
        $service = new ApplicationScoringService($aiService);

        // Forzar recalculo (limpiando estado previo)
        $application->eligibility_status = null;
        $service->processApplication($application);
        
        echo "\n--- Result ---\n";
        echo "Eligibility: " . $application->eligibility_status . "\n";
        echo "Score: " . $application->score . "\n";
        echo "Rejection Reason: " . $application->rejection_reason . "\n";
        
        if ($application->auto_decision_log) {
            echo "Log: " . json_encode($application->auto_decision_log, JSON_PRETTY_PRINT) . "\n";
        }

        // Aserciones básicas
        // El usuario espera que sea elegible, así que probamos eso
        // Pero si falla, veremos el output con la razón.
        $this->assertEquals('eligible', $application->eligibility_status, "Application should be eligible. Reason: " . $application->rejection_reason);
        $this->assertGreaterThan(0, $application->score, "Score should be greater than 0");
    }
}
