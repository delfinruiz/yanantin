<?php

namespace Tests\Unit;

use App\Models\Response;
use App\Models\Survey;
use App\Models\Question;
use App\Models\Dimension;
use App\Services\SurveyStatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurveyStatsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_normalization_scale_5_and_kpi_percentage()
    {
        $survey = Survey::create(['title' => 'Test', 'description' => 'd']);
        Dimension::create(['survey_name' => 'Test', 'item' => 'Dim A', 'kpi_target' => 80, 'weight' => 2]);

        $q = Question::create(['survey_id' => $survey->id, 'item' => 'Dim A', 'type' => 'scale_5', 'content' => 'q']);
        Response::create(['question_id' => $q->id, 'user_id' => 1, 'value' => '4']);

        $svc = new SurveyStatsService();
        $stats = $svc->dimensionStats($survey);
        $dim = $stats['Dim A'];

        $this->assertEquals(80.0, $dim['avg']); // 4/5 = 0.8 => 80
        $this->assertEquals(100.0, $dim['compliance_pct']); // 80 / 80 = 1 -> 100%
    }

    public function test_normalization_likert_and_weighted_avg()
    {
        $survey = Survey::create(['title' => 'Test', 'description' => 'd']);
        Dimension::create(['survey_name' => 'Test', 'item' => 'Dim L', 'kpi_target' => 85, 'weight' => 1]);
        Dimension::create(['survey_name' => 'Test', 'item' => 'Dim S', 'kpi_target' => 90, 'weight' => 3]);

        $q1 = Question::create(['survey_id' => $survey->id, 'item' => 'Dim L', 'type' => 'likert', 'content' => 'q1']);
        Response::create(['question_id' => $q1->id, 'user_id' => 1, 'value' => '5']); // ((5-1)/4)*100 = 100

        $q2 = Question::create(['survey_id' => $survey->id, 'item' => 'Dim S', 'type' => 'scale_10', 'content' => 'q2']);
        Response::create(['question_id' => $q2->id, 'user_id' => 1, 'value' => '7']); // 70

        $svc = new SurveyStatsService();
        $stats = $svc->dimensionStats($survey);
        $weighted = $svc->weightedAvg($stats);

        // Weighted: (100*1 + 70*3) / (1+3) = (100 + 210)/4 = 77.5
        $this->assertEquals(77.5, $weighted);
    }

    public function test_boolean_and_non_numeric_treatment()
    {
        $survey = Survey::create(['title' => 'Test', 'description' => 'd']);
        Dimension::create(['survey_name' => 'Test', 'item' => 'Dim B', 'kpi_target' => 80]);

        $q = Question::create(['survey_id' => $survey->id, 'item' => 'Dim B', 'type' => 'bool', 'content' => 'q']);
        Response::create(['question_id' => $q->id, 'user_id' => 1, 'value' => 'si']);
        Response::create(['question_id' => $q->id, 'user_id' => 2, 'value' => 'no']);

        $svc = new SurveyStatsService();
        $stats = $svc->dimensionStats($survey);
        $dim = $stats['Dim B'];

        $this->assertNull($dim['avg']); // bool excluded from numeric avg
        $this->assertEquals(50.0, $dim['bool_yes_pct']); // 1 de 2 = 50%
    }
}
