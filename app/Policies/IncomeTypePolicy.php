<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\IncomeType;
use Illuminate\Auth\Access\HandlesAuthorization;

class IncomeTypePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:IncomeType');
    }

    public function view(AuthUser $authUser, IncomeType $incomeType): bool
    {
        return $authUser->can('View:IncomeType');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:IncomeType');
    }

    public function update(AuthUser $authUser, IncomeType $incomeType): bool
    {
        return $authUser->can('Update:IncomeType');
    }

    public function delete(AuthUser $authUser, IncomeType $incomeType): bool
    {
        return $authUser->can('Delete:IncomeType');
    }

    public function restore(AuthUser $authUser, IncomeType $incomeType): bool
    {
        return $authUser->can('Restore:IncomeType');
    }

    public function forceDelete(AuthUser $authUser, IncomeType $incomeType): bool
    {
        return $authUser->can('ForceDelete:IncomeType');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:IncomeType');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:IncomeType');
    }

    public function replicate(AuthUser $authUser, IncomeType $incomeType): bool
    {
        return $authUser->can('Replicate:IncomeType');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:IncomeType');
    }

}