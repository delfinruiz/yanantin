<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\AbsenceRequest;
use Illuminate\Auth\Access\HandlesAuthorization;

class AbsenceRequestPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AbsenceRequest');
    }

    public function view(AuthUser $authUser, AbsenceRequest $absenceRequest): bool
    {
        return $authUser->can('View:AbsenceRequest');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AbsenceRequest');
    }

    public function update(AuthUser $authUser, AbsenceRequest $absenceRequest): bool
    {
        return $authUser->can('Update:AbsenceRequest');
    }

    public function delete(AuthUser $authUser, AbsenceRequest $absenceRequest): bool
    {
        return $authUser->can('Delete:AbsenceRequest');
    }

    public function restore(AuthUser $authUser, AbsenceRequest $absenceRequest): bool
    {
        return $authUser->can('Restore:AbsenceRequest');
    }

    public function forceDelete(AuthUser $authUser, AbsenceRequest $absenceRequest): bool
    {
        return $authUser->can('ForceDelete:AbsenceRequest');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AbsenceRequest');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AbsenceRequest');
    }

    public function replicate(AuthUser $authUser, AbsenceRequest $absenceRequest): bool
    {
        return $authUser->can('Replicate:AbsenceRequest');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AbsenceRequest');
    }

}