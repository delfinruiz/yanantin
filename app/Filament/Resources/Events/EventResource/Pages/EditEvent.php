<?php

namespace App\Filament\Resources\Events\EventResource\Pages;

use App\Filament\Resources\Events\EventResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class EditEvent extends EditRecord
{
    protected static string $resource = EventResource::class;

    protected array $originalSharedIds = [];

    protected function beforeSave(): void
    {
        // Guardar IDs originales antes de guardar cambios
        $this->originalSharedIds = $this->record->sharedWith()->pluck('users.id')->toArray();
    }

    protected function afterSave(): void
    {
        $event = $this->record;
        
        // Obtener nuevos IDs compartidos
        $currentSharedIds = $event->sharedWith()->pluck('users.id')->toArray();
        $newIds = array_diff($currentSharedIds, $this->originalSharedIds);

        if (!empty($newIds)) {
            $users = User::whereIn('id', $newIds)->get();
            foreach ($users as $user) {
                if ($user->id === Auth::id()) continue;

                Notification::make()
                    ->title(__('events.shared_title'))
                    ->body(__('events.shared_body', ['title' => $event->title, 'calendar' => $event->calendar->name]))
                    ->success()
                    ->sendToDatabase($user);
            }
        }
    }
}
