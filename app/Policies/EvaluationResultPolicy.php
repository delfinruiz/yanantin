<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\EvaluationResult;
use Illuminate\Auth\Access\HandlesAuthorization;

class EvaluationResultPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:EvaluationResult');
    }

    public function view(AuthUser $authUser, EvaluationResult $evaluationResult): bool
    {
        return $authUser->can('View:EvaluationResult');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:EvaluationResult');
    }

    public function update(AuthUser $authUser, EvaluationResult $evaluationResult): bool
    {
        return $authUser->can('Update:EvaluationResult');
    }

    public function delete(AuthUser $authUser, EvaluationResult $evaluationResult): bool
    {
        return $authUser->can('Delete:EvaluationResult');
    }

    public function restore(AuthUser $authUser, EvaluationResult $evaluationResult): bool
    {
        return $authUser->can('Restore:EvaluationResult');
    }

    public function forceDelete(AuthUser $authUser, EvaluationResult $evaluationResult): bool
    {
        return $authUser->can('ForceDelete:EvaluationResult');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:EvaluationResult');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:EvaluationResult');
    }

    public function replicate(AuthUser $authUser, EvaluationResult $evaluationResult): bool
    {
        return $authUser->can('Replicate:EvaluationResult');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:EvaluationResult');
    }

}