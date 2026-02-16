<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\EvaluationCycle;
use Illuminate\Auth\Access\HandlesAuthorization;

class EvaluationCyclePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:EvaluationCycle');
    }

    public function view(AuthUser $authUser, EvaluationCycle $evaluationCycle): bool
    {
        return $authUser->can('View:EvaluationCycle');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:EvaluationCycle');
    }

    public function update(AuthUser $authUser, EvaluationCycle $evaluationCycle): bool
    {
        return $authUser->can('Update:EvaluationCycle');
    }

    public function delete(AuthUser $authUser, EvaluationCycle $evaluationCycle): bool
    {
        return $authUser->can('Delete:EvaluationCycle');
    }

    public function restore(AuthUser $authUser, EvaluationCycle $evaluationCycle): bool
    {
        return $authUser->can('Restore:EvaluationCycle');
    }

    public function forceDelete(AuthUser $authUser, EvaluationCycle $evaluationCycle): bool
    {
        return $authUser->can('ForceDelete:EvaluationCycle');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:EvaluationCycle');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:EvaluationCycle');
    }

    public function replicate(AuthUser $authUser, EvaluationCycle $evaluationCycle): bool
    {
        return $authUser->can('Replicate:EvaluationCycle');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:EvaluationCycle');
    }

}