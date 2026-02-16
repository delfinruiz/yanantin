<?php

namespace App\Filament\Resources\Events\EventResource\Pages;

use App\Filament\Resources\Events\EventResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class CreateEvent extends CreateRecord
{
    protected static string $resource = EventResource::class;

    protected function afterCreate(): void
    {
        $event = $this->record;
        $calendar = $event->calendar;

        if ($calendar->is_public && $calendar->manager_user_id === Auth::id()) {
            $users = User::where('id', '!=', Auth::id())->get();
            
            foreach ($users as $user) {
                Notification::make()
                    ->title(__('events.notification.public_new_title'))
                    ->body(__('events.notification.public_new_body', [
                        'manager' => Auth::user()->name,
                        'title' => $event->title,
                        'calendar' => $calendar->name,
                    ]))
                    ->info()
                    ->sendToDatabase($user);
            }
        }

        foreach ($event->sharedWith as $user) {
            if ($user->id === Auth::id()) continue;

            Notification::make()
                ->title(__('events.notification.shared_with_you_title'))
                ->body(__('events.notification.shared_with_you_body', [
                    'title' => $event->title,
                    'calendar' => $calendar->name,
                ]))
                ->success()
                ->sendToDatabase($user);
        }
    }
}
