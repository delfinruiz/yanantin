<?php

namespace App\Policies;

use App\Models\ObjectiveCheckin;
use App\Models\User;

class ObjectiveCheckinPolicy
{
    protected function isHR(User $user): bool
    {
        return $user->hasRole('super_admin') || $user->hasRole('recursos humanos');
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ObjectiveCheckin $checkin): bool
    {
        // Acceso si es Admin/RH
        if ($this->isHR($user)) return true;

        $objective = $checkin->objective;
        if (!$objective) return false;

        // Acceso si es el dueño del objetivo
        if ($objective->owner_user_id === $user->id) return true;

        // Acceso si es supervisor (Dueño del objetivo padre o Jefe directo)
        $supervisor = $objective->parent ? $objective->parent->owner : null;
        if (!$supervisor && $objective->owner && $objective->owner->employeeProfile) {
            $supervisor = $objective->owner->employeeProfile->boss;
        }

        return $supervisor && $supervisor->id === $user->id;
    }

    public function create(User $user): bool
    {
        return true; 
    }

    public function update(User $user, ObjectiveCheckin $checkin): bool
    {
        // Misma lógica que view para simplicidad, o restringir más
        return $this->view($user, $checkin);
    }

    public function delete(User $user, ObjectiveCheckin $checkin): bool
    {
        return $this->view($user, $checkin);
    }
}

