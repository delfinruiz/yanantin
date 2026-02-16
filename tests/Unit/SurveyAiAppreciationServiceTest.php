<?php

namespace Tests\Unit;

use App\Models\Survey;
use App\Models\Question;
use App\Models\Response;
use App\Services\SurveyAiAppreciationService;
use Tests\TestCase;

class SurveyAiAppreciationServiceTest extends TestCase
{
    public function test_build_report_contains_key_sections()
    {
        $survey = Survey::create([
            'title' => 'Satisfacción',
            'description' => 'Evaluación de satisfacción',
            'active' => true,
        ]);

        $q1 = Question::create(['survey_id' => $survey->id, 'content' => '¿Cómo califica el servicio?', 'type' => 'scale_5', 'required' => true, 'order' => 1]);
        $q2 = Question::create(['survey_id' => $survey->id, 'content' => '¿Recomendaría el producto?', 'type' => 'bool', 'required' => true, 'order' => 2]);

        Response::create(['question_id' => $q1->id, 'user_id' => null, 'guest_email' => 'guest@example.com', 'value' => 4]);
        Response::create(['question_id' => $q2->id, 'user_id' => null, 'guest_email' => 'guest@example.com', 'value' => 1]);

        $service = app(SurveyAiAppreciationService::class);
        $report = $service->buildReport($survey);

        $this->assertStringContainsString('Participantes:', $report);
        $this->assertStringContainsString('Promedio global:', $report);
        $this->assertStringContainsString('Resumen por tipo de pregunta:', $report);
    }
}
