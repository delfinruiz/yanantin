<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\EvaluationCycle;
use App\Models\StrategicObjective;
use App\Models\ObjectiveCheckin;
use App\Models\EvaluationResult;
use App\Models\PerformanceRange;
use App\Models\BonusRule;
use App\Policies\EvaluationCyclePolicy;
use App\Policies\StrategicObjectivePolicy;
use App\Policies\ObjectiveCheckinPolicy;
use App\Policies\EvaluationResultPolicy;
use App\Policies\PerformanceRangePolicy;
use App\Policies\BonusRulePolicy;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        EvaluationCycle::class => EvaluationCyclePolicy::class,
        StrategicObjective::class => StrategicObjectivePolicy::class,
        ObjectiveCheckin::class => ObjectiveCheckinPolicy::class,
        EvaluationResult::class => EvaluationResultPolicy::class,
        PerformanceRange::class => PerformanceRangePolicy::class,
        BonusRule::class => BonusRulePolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        Gate::before(function ($user, $ability) {
            return $user->hasRole('super_admin') ? true : null;
        });
    }
}

