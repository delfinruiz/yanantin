<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\StrategicObjective;
use Illuminate\Auth\Access\HandlesAuthorization;

class StrategicObjectivePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:StrategicObjective');
    }

    public function view(AuthUser $authUser, StrategicObjective $strategicObjective): bool
    {
        return $authUser->can('View:StrategicObjective');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:StrategicObjective');
    }

    public function update(AuthUser $authUser, StrategicObjective $strategicObjective): bool
    {
        return $authUser->can('Update:StrategicObjective');
    }

    public function delete(AuthUser $authUser, StrategicObjective $strategicObjective): bool
    {
        return $authUser->can('Delete:StrategicObjective');
    }

    public function restore(AuthUser $authUser, StrategicObjective $strategicObjective): bool
    {
        return $authUser->can('Restore:StrategicObjective');
    }

    public function forceDelete(AuthUser $authUser, StrategicObjective $strategicObjective): bool
    {
        return $authUser->can('ForceDelete:StrategicObjective');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:StrategicObjective');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:StrategicObjective');
    }

    public function replicate(AuthUser $authUser, StrategicObjective $strategicObjective): bool
    {
        return $authUser->can('Replicate:StrategicObjective');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:StrategicObjective');
    }

}