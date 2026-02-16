<?php

namespace App\Filament\Resources\AbsenceRequests\Pages;

use App\Filament\Resources\AbsenceRequests\AbsenceRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListAbsenceRequests extends ListRecords
{
    protected static string $resource = AbsenceRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            /*
            CreateAction::make()
                ->label('Nueva Solicitud')
                ->after(function ($record) {
                    try {
                        $service = new \App\Services\AbsenceService();
                        $service->notifySupervisors($record, 'created');
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error('Error sending creation notifications: ' . $e->getMessage());
                    }
                }),
            */
        ];
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }
}
