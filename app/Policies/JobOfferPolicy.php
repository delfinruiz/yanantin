<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\JobOffer;
use Illuminate\Auth\Access\HandlesAuthorization;

class JobOfferPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:JobOffer');
    }

    public function view(AuthUser $authUser, JobOffer $jobOffer): bool
    {
        return $authUser->can('View:JobOffer');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:JobOffer');
    }

    public function update(AuthUser $authUser, JobOffer $jobOffer): bool
    {
        return $authUser->can('Update:JobOffer');
    }

    public function delete(AuthUser $authUser, JobOffer $jobOffer): bool
    {
        return $authUser->can('Delete:JobOffer');
    }

    public function restore(AuthUser $authUser, JobOffer $jobOffer): bool
    {
        return $authUser->can('Restore:JobOffer');
    }

    public function forceDelete(AuthUser $authUser, JobOffer $jobOffer): bool
    {
        return $authUser->can('ForceDelete:JobOffer');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:JobOffer');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:JobOffer');
    }

    public function replicate(AuthUser $authUser, JobOffer $jobOffer): bool
    {
        return $authUser->can('Replicate:JobOffer');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:JobOffer');
    }

}