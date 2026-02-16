<?php

namespace App\Filament\Resources\AbsenceRequests\Pages;

use App\Filament\Resources\AbsenceRequests\AbsenceRequestResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAbsenceRequest extends EditRecord
{
    protected static string $resource = AbsenceRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Eliminar Solicitud'),
        ];
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();
        
        try {
            $service = new \App\Services\AbsenceService();
            $service->notifySupervisors($record, 'updated');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error sending update notifications: ' . $e->getMessage());
        }
    }
}
