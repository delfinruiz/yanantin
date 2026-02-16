<?php

namespace App\Services\Evaluations;

use App\Models\BonusRule;
use App\Models\EvaluationCycle;
use App\Models\EvaluationResult;
use App\Models\StrategicObjective;
use App\Models\PerformanceRange;
use App\Models\User;
use Illuminate\Support\Collection;

class EvaluationCalculator
{
    public function computeForEmployee(EvaluationCycle $cycle, User $user): EvaluationResult
    {
        $objectives = StrategicObjective::query()
            ->where('evaluation_cycle_id', $cycle->id)
            ->where('owner_user_id', $user->id)
            ->where('status', 'approved')
            ->with('checkins')
            ->get();

        $details = [];
        $totalWeighted = 0.0;

        /** @var StrategicObjective $obj */
        foreach ($objectives as $obj) {
            // Ensure progress is up-to-date with latest logic (e.g. only approved check-ins)
            $obj->updateProgress(); 
            
            $weight = (float) $obj->weight;
            // Use the pre-calculated progress_percentage from the model
            $compliance = (float) $obj->progress_percentage; 
            
            $details[] = [
                'objective_id' => $obj->id,
                'title' => $obj->title,
                'type' => $obj->type,
                'weight' => $weight,
                'compliance' => $compliance,
            ];
            $totalWeighted += ($compliance * $weight) / 100.0;
        }

        $final = round($totalWeighted, 2);
        $range = $this->findRangeForScore($final, $cycle);
        $bonusAmount = $this->computeBonusAmount($range, $final);

        $result = EvaluationResult::query()
            ->firstOrNew([
                'evaluation_cycle_id' => $cycle->id,
                'user_id' => $user->id,
            ]);

        $result->final_score = $final;
        $result->performance_range_id = $range?->id;
        $result->bonus_amount = $bonusAmount;
        $result->details = $details;
        $result->computed_at = now();
        $result->save();

        return $result;
    }

    // computeCompliance method is no longer needed as we rely on StrategicObjective::updateProgress
    // but we can keep findRangeForScore and computeBonusAmount


    protected function findRangeForScore(float $score, ?EvaluationCycle $cycle = null): ?PerformanceRange
    {
        $query = PerformanceRange::query()
            ->where('min_percentage', '<=', $score)
            ->where('max_percentage', '>=', $score);

        if ($cycle) {
            $query->where(function ($q) use ($cycle) {
                $q->where('evaluation_cycle_id', $cycle->id)
                  ->orWhereNull('evaluation_cycle_id'); // Fallback to global if needed
            })->orderByDesc('evaluation_cycle_id'); // Prefer specific cycle
        } else {
             $query->whereNull('evaluation_cycle_id');
        }

        return $query->first();
    }

    protected function computeBonusAmount(?PerformanceRange $range, float $finalScore): ?float
    {
        if (!$range) {
            return null;
        }
        $rule = BonusRule::query()->where('performance_range_id', $range->id)->first();
        if (!$rule) {
            return null;
        }
        if ($rule->base_type === 'fixed' && $rule->fixed_amount) {
            return (float) $rule->fixed_amount;
        }
        // porcentaje sin base definida: devolver null para no alterar nómina
        return null;
    }
}

