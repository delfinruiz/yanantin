<?php

namespace App\Observers;

use App\Models\User;
use App\Models\EmailAccount;
use Wirechat\Wirechat\Models\Participant;
use App\Models\Calendar;
use Wirechat\Wirechat\Models\Group;
use Wirechat\Wirechat\Enums\ParticipantRole as WirechatParticipantRole;

class UserObserver
{
    public function created(User $user): void
    {
        if (!Calendar::where('is_personal', true)->where('user_id', $user->id)->exists()) {
            Calendar::create([
                'name' => 'Calendario de ' . ($user->name ?? 'Usuario'),
                'is_public' => false,
                'is_personal' => true,
                'user_id' => $user->id,
                'created_by' => $user->id,
            ]);
        }

        try {
            $group = Group::where('name', 'General')->first();
            if ($group && $group->conversation) {
                $participant = Participant::withoutGlobalScopes()
                    ->where('conversation_id', $group->conversation->id)
                    ->where('participantable_id', $user->id)
                    ->where('participantable_type', $user->getMorphClass())
                    ->first();
                if ($participant && $participant->isRemovedByAdmin()) {
                    return;
                }
                if (! $participant) {
                    Participant::create([
                        'conversation_id' => $group->conversation->id,
                        'participantable_id' => $user->id,
                        'participantable_type' => $user->getMorphClass(),
                        'role' => WirechatParticipantRole::PARTICIPANT,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            // silent
        }
    }

    /**
     * Handle the User "deleting" event.
     */
    public function deleting(User $user): void
    {
        // 1. Desasignar cuenta de correo
        // Aunque existe ON DELETE SET NULL en la BD para user_id,
        // necesitamos limpiar assigned_at para mantener consistencia.
        $emailAccount = EmailAccount::where('user_id', $user->id)->first();
        if ($emailAccount) {
            $emailAccount->assigned_at = null;
            // Opcional: anticipar el deslinkeo
            // $emailAccount->user_id = null; 
            $emailAccount->save();
        }

        // 2. Eliminar de grupos de chat
        // La tabla participants no tiene cascade delete para el usuario (polimórfica).
        Participant::where('participantable_id', $user->id)
            ->where('participantable_type', $user->getMorphClass())
            ->delete();
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        if ($user->isDirty('email')) {
            /** @var \App\Models\EmailAccount|null $emailAccount */
            $emailAccount = EmailAccount::where('user_id', $user->id)->first();
            
            if ($emailAccount) {
                if ($emailAccount->email !== $user->email) {
                    $newEmail = $user->email;
                    if (filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                        $domain = substr(strrchr($newEmail, '@'), 1);
                        
                        // Usamos updateQuietly por precaución, aunque EmailAccount no tiene observers conocidos
                        // Si updateQuietly no existe (Laravel antiguo), usar saveQuietly o sin events
                        // Laravel 9+ tiene updateQuietly? No, tiene saveQuietly.
                        // update() dispara eventos.
                        // Mejor:
                        $emailAccount->email = $newEmail;
                        $emailAccount->domain = $domain;
                        $emailAccount->saveQuietly();
                    }
                }
            }
        }
    }
}
