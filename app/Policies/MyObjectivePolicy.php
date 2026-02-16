<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\MyObjective;
use Illuminate\Auth\Access\HandlesAuthorization;

class MyObjectivePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MyObjective');
    }

    public function view(AuthUser $authUser, MyObjective $myObjective): bool
    {
        return $authUser->can('View:MyObjective');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MyObjective');
    }

    public function update(AuthUser $authUser, MyObjective $myObjective): bool
    {
        return $authUser->can('Update:MyObjective');
    }

    public function delete(AuthUser $authUser, MyObjective $myObjective): bool
    {
        return $authUser->can('Delete:MyObjective');
    }

    public function restore(AuthUser $authUser, MyObjective $myObjective): bool
    {
        return $authUser->can('Restore:MyObjective');
    }

    public function forceDelete(AuthUser $authUser, MyObjective $myObjective): bool
    {
        return $authUser->can('ForceDelete:MyObjective');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MyObjective');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MyObjective');
    }

    public function replicate(AuthUser $authUser, MyObjective $myObjective): bool
    {
        return $authUser->can('Replicate:MyObjective');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MyObjective');
    }

}