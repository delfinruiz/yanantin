<?php

namespace App\Policies;

use App\Models\JobOfferChangeRequest;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class JobOfferChangeRequestPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:JobOfferChangeRequest');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, JobOfferChangeRequest $jobOfferChangeRequest): bool
    {
        return $user->can('View:JobOfferChangeRequest');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('Create:JobOfferChangeRequest');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, JobOfferChangeRequest $jobOfferChangeRequest): bool
    {
        return false; // Nadie puede editar una solicitud, solo verla o aprobar/rechazar
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, JobOfferChangeRequest $jobOfferChangeRequest): bool
    {
        return $user->can('Delete:JobOfferChangeRequest');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, JobOfferChangeRequest $jobOfferChangeRequest): bool
    {
        return $user->can('Restore:JobOfferChangeRequest');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, JobOfferChangeRequest $jobOfferChangeRequest): bool
    {
        return $user->can('ForceDelete:JobOfferChangeRequest');
    }
}
