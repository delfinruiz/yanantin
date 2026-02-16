<?php

namespace App\Http\Livewire\Nominas;

use App\Models\EvaluationCycle;
use App\Models\EmployeeObjective;
use App\Models\EvaluationResult;
use Livewire\Component;

class EvaluationsPanel extends Component
{
    public $ownerRecord;

    public function render()
    {
        $user = $this->ownerRecord?->user;
        $cycle = EvaluationCycle::query()->orderByDesc('starts_at')->first();
        $objectives = collect();
        $result = null;

        if ($user && $cycle) {
            $objectives = EmployeeObjective::query()
                ->where('evaluation_cycle_id', $cycle->id)
                ->where('user_id', $user->id)
                ->get();
            $result = EvaluationResult::query()
                ->where('evaluation_cycle_id', $cycle->id)
                ->where('user_id', $user->id)
                ->first();
        }

        return view('livewire.nominas.evaluations-panel', [
            'cycle' => $cycle,
            'objectives' => $objectives,
            'result' => $result,
        ]);
    }
}

