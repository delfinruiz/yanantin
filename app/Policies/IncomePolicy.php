<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Income;
use Illuminate\Auth\Access\HandlesAuthorization;

class IncomePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Income');
    }

    public function view(AuthUser $authUser, Income $income): bool
    {
        return $authUser->can('View:Income');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Income');
    }

    public function update(AuthUser $authUser, Income $income): bool
    {
        return $authUser->can('Update:Income');
    }

    public function delete(AuthUser $authUser, Income $income): bool
    {
        return $authUser->can('Delete:Income');
    }

    public function restore(AuthUser $authUser, Income $income): bool
    {
        return $authUser->can('Restore:Income');
    }

    public function forceDelete(AuthUser $authUser, Income $income): bool
    {
        return $authUser->can('ForceDelete:Income');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Income');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Income');
    }

    public function replicate(AuthUser $authUser, Income $income): bool
    {
        return $authUser->can('Replicate:Income');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Income');
    }

}