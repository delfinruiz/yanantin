<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\JobOfferChangeRequest;
use Illuminate\Auth\Access\HandlesAuthorization;

class JobOfferChangeRequestPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:JobOfferChangeRequest');
    }

    public function view(AuthUser $authUser, JobOfferChangeRequest $jobOfferChangeRequest): bool
    {
        return $authUser->can('View:JobOfferChangeRequest');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:JobOfferChangeRequest');
    }

    public function update(AuthUser $authUser, JobOfferChangeRequest $jobOfferChangeRequest): bool
    {
        return $authUser->can('Update:JobOfferChangeRequest');
    }

    public function delete(AuthUser $authUser, JobOfferChangeRequest $jobOfferChangeRequest): bool
    {
        return $authUser->can('Delete:JobOfferChangeRequest');
    }

    public function restore(AuthUser $authUser, JobOfferChangeRequest $jobOfferChangeRequest): bool
    {
        return $authUser->can('Restore:JobOfferChangeRequest');
    }

    public function forceDelete(AuthUser $authUser, JobOfferChangeRequest $jobOfferChangeRequest): bool
    {
        return $authUser->can('ForceDelete:JobOfferChangeRequest');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:JobOfferChangeRequest');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:JobOfferChangeRequest');
    }

    public function replicate(AuthUser $authUser, JobOfferChangeRequest $jobOfferChangeRequest): bool
    {
        return $authUser->can('Replicate:JobOfferChangeRequest');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:JobOfferChangeRequest');
    }

}