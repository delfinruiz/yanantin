<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\AbsenceType;
use Illuminate\Auth\Access\HandlesAuthorization;

class AbsenceTypePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AbsenceType');
    }

    public function view(AuthUser $authUser, AbsenceType $absenceType): bool
    {
        return $authUser->can('View:AbsenceType');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AbsenceType');
    }

    public function update(AuthUser $authUser, AbsenceType $absenceType): bool
    {
        return $authUser->can('Update:AbsenceType');
    }

    public function delete(AuthUser $authUser, AbsenceType $absenceType): bool
    {
        return $authUser->can('Delete:AbsenceType');
    }

    public function restore(AuthUser $authUser, AbsenceType $absenceType): bool
    {
        return $authUser->can('Restore:AbsenceType');
    }

    public function forceDelete(AuthUser $authUser, AbsenceType $absenceType): bool
    {
        return $authUser->can('ForceDelete:AbsenceType');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AbsenceType');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AbsenceType');
    }

    public function replicate(AuthUser $authUser, AbsenceType $absenceType): bool
    {
        return $authUser->can('Replicate:AbsenceType');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AbsenceType');
    }

}