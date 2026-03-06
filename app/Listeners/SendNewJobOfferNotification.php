<?php

namespace App\Listeners;

use App\Events\JobOfferPublished;
use App\Notifications\NewJobOfferNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Spatie\Permission\Models\Role;

class SendNewJobOfferNotification implements ShouldQueue
{
    public function handle(JobOfferPublished $event): void
    {
        try {
            $role = Role::where('name', 'public')->first();
            if (! $role) {
                return;
            }
            $users = $role->users()->limit(100)->get();
            foreach ($users as $user) {
                $user->notify(new NewJobOfferNotification($event->jobOffer));
            }
        } catch (\Throwable $e) {
            // Silenciar para no bloquear publicación en caso de error
        }
    }
}

