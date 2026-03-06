<?php

namespace App\Filament\Resources\JobOfferChangeRequests\Pages;

use App\Filament\Resources\JobOfferChangeRequests\JobOfferChangeRequestResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;

class CreateJobOfferChangeRequest extends CreateRecord
{
    protected static string $resource = JobOfferChangeRequestResource::class;

    protected function afterCreate(): void
    {
        /** @var \App\Models\JobOfferChangeRequest $record */
        $record = $this->record;

        /** @var \App\Models\User $user */
        $user = $record->requester; // El solicitante

        // 1. Intentar notificar a la Jefatura Directa
        $recipients = collect();
        
        if ($user->employeeProfile?->boss) {
            $recipients->push($user->employeeProfile->boss);
        } 
        
        // 2. Si no tiene jefe directo, notificar a Super Admin y RRHH como respaldo
        if ($recipients->isEmpty()) {
            $admins = \App\Models\User::role(['super_admin', 'recursos_humanos'])->get();
            $recipients = $recipients->merge($admins);
        }

        // 3. Filtrar: No notificar al mismo usuario que creó la solicitud
        $recipients = $recipients->unique('id')->reject(fn ($u) => $u->id === $user->id);

        if ($recipients->isNotEmpty()) {
            Notification::make()
                ->title('Nueva Solicitud de Modificación')
                ->body("{$user->name} ha solicitado modificar la oferta laboral: {$record->jobOffer->title}")
                ->actions([
                    Action::make('view')
                        ->label('Ver Solicitud')
                        ->url(JobOfferChangeRequestResource::getUrl('edit', ['record' => $record])),
                ])
                ->sendToDatabase($recipients->all());
        }
    }
}
