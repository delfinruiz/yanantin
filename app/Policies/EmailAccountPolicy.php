<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\EmailAccount;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmailAccountPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:EmailAccount');
    }

    public function view(AuthUser $authUser, EmailAccount $emailAccount): bool
    {
        return $authUser->can('View:EmailAccount');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:EmailAccount');
    }

    public function update(AuthUser $authUser, EmailAccount $emailAccount): bool
    {
        return $authUser->can('Update:EmailAccount');
    }

    public function delete(AuthUser $authUser, EmailAccount $emailAccount): bool
    {
        return $authUser->can('Delete:EmailAccount');
    }

    public function restore(AuthUser $authUser, EmailAccount $emailAccount): bool
    {
        return $authUser->can('Restore:EmailAccount');
    }

    public function forceDelete(AuthUser $authUser, EmailAccount $emailAccount): bool
    {
        return $authUser->can('ForceDelete:EmailAccount');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:EmailAccount');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:EmailAccount');
    }

    public function replicate(AuthUser $authUser, EmailAccount $emailAccount): bool
    {
        return $authUser->can('Replicate:EmailAccount');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:EmailAccount');
    }

}