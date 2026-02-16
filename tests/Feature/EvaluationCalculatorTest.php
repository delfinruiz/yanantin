<?php

namespace Tests\Feature;

use App\Models\BonusRule;
use App\Models\EvaluationCycle;
use App\Models\StrategicObjective;
use App\Models\ObjectiveCheckin;
use App\Models\PerformanceRange;
use App\Models\User;
use App\Services\Evaluations\EvaluationCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvaluationCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_quantitative_compliance_and_final_score(): void
    {
        $cycle = EvaluationCycle::create([
            'name' => 'Ciclo Prueba',
            'followup_periods_count' => 3,
            'status' => 'closed',
        ]);

        $user = User::factory()->create();

        $obj = StrategicObjective::create([
            'evaluation_cycle_id' => $cycle->id,
            'owner_user_id' => $user->id,
            'title' => 'Ventas',
            'type' => 'quantitative',
            'target_value' => 100,
            'weight' => 100,
            'status' => 'approved',
        ]);

        ObjectiveCheckin::create([
            'strategic_objective_id' => $obj->id,
            'period_index' => 1,
            'numeric_value' => 80,
            'review_status' => 'approved',
        ]);
        // Trigger updateProgress manually or rely on model events if factories/creates fire them. 
        // In tests, if using create(), events fire.
        
        ObjectiveCheckin::create([
            'strategic_objective_id' => $obj->id,
            'period_index' => 2,
            'numeric_value' => 120,
            'review_status' => 'approved',
        ]);
        
        // Refresh to get updated progress
        $obj->refresh();

        PerformanceRange::create([
            'name' => 'Bueno',
            'min_percentage' => 70,
            'max_percentage' => 90,
        ]);
        PerformanceRange::create([
            'name' => 'Excelente',
            'min_percentage' => 90,
            'max_percentage' => 100,
        ]);

        $calc = new EvaluationCalculator();
        $result = $calc->computeForEmployee($cycle, $user);

        $this->assertNotNull($result);
        $this->assertEquals(100.0, $result->final_score); // 120/100 clamped to 100
        $this->assertEquals('Excelente', $result->range?->name);
    }
}

