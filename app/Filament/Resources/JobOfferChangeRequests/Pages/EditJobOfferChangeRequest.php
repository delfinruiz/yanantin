<?php

namespace App\Filament\Resources\JobOfferChangeRequests\Pages;

use App\Filament\Resources\JobOfferChangeRequests\JobOfferChangeRequestResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditJobOfferChangeRequest extends EditRecord
{
    protected static string $resource = JobOfferChangeRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // DeleteAction::make(), // Deshabilitado para auditoría
        ];
    }
}
