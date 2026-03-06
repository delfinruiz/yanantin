<?php

namespace App\Policies;

use App\Models\JobOffer;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class JobOfferPolicy
{
    use HandlesAuthorization;
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:JobOffer');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, JobOffer $jobOffer): bool
    {
        return $user->can('View:JobOffer');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('Create:JobOffer');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, JobOffer $jobOffer): bool
    {
        // Verificar permisos estándar
        if ($user->can('Update:JobOffer')) {
            return true;
        }

        // Permitir edición si hay una solicitud de cambio APROBADA para esta oferta y el usuario es el solicitante
        $hasApprovedRequest = $jobOffer->changeRequests()
            ->where('requester_id', $user->id)
            ->where('status', 'approved')
            ->exists();

        if ($hasApprovedRequest) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, JobOffer $jobOffer): bool
    {
        return $user->can('Delete:JobOffer');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, JobOffer $jobOffer): bool
    {
        return $user->can('Restore:JobOffer');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, JobOffer $jobOffer): bool
    {
        return $user->can('ForceDelete:JobOffer');
    }
}
