<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\PerformanceRange;
use Illuminate\Auth\Access\HandlesAuthorization;

class PerformanceRangePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PerformanceRange');
    }

    public function view(AuthUser $authUser, PerformanceRange $performanceRange): bool
    {
        return $authUser->can('View:PerformanceRange');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PerformanceRange');
    }

    public function update(AuthUser $authUser, PerformanceRange $performanceRange): bool
    {
        return $authUser->can('Update:PerformanceRange');
    }

    public function delete(AuthUser $authUser, PerformanceRange $performanceRange): bool
    {
        return $authUser->can('Delete:PerformanceRange');
    }

    public function restore(AuthUser $authUser, PerformanceRange $performanceRange): bool
    {
        return $authUser->can('Restore:PerformanceRange');
    }

    public function forceDelete(AuthUser $authUser, PerformanceRange $performanceRange): bool
    {
        return $authUser->can('ForceDelete:PerformanceRange');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:PerformanceRange');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PerformanceRange');
    }

    public function replicate(AuthUser $authUser, PerformanceRange $performanceRange): bool
    {
        return $authUser->can('Replicate:PerformanceRange');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PerformanceRange');
    }

}