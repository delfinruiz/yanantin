<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\BonusRule;
use Illuminate\Auth\Access\HandlesAuthorization;

class BonusRulePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:BonusRule');
    }

    public function view(AuthUser $authUser, BonusRule $bonusRule): bool
    {
        return $authUser->can('View:BonusRule');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:BonusRule');
    }

    public function update(AuthUser $authUser, BonusRule $bonusRule): bool
    {
        return $authUser->can('Update:BonusRule');
    }

    public function delete(AuthUser $authUser, BonusRule $bonusRule): bool
    {
        return $authUser->can('Delete:BonusRule');
    }

    public function restore(AuthUser $authUser, BonusRule $bonusRule): bool
    {
        return $authUser->can('Restore:BonusRule');
    }

    public function forceDelete(AuthUser $authUser, BonusRule $bonusRule): bool
    {
        return $authUser->can('ForceDelete:BonusRule');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:BonusRule');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:BonusRule');
    }

    public function replicate(AuthUser $authUser, BonusRule $bonusRule): bool
    {
        return $authUser->can('Replicate:BonusRule');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:BonusRule');
    }

}