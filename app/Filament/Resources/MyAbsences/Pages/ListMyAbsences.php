<?php

namespace App\Filament\Resources\MyAbsences\Pages;

use App\Filament\Resources\MyAbsences\MyAbsenceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListMyAbsences extends ListRecords
{
    protected static string $resource = MyAbsenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nueva Solicitud'),
        ];
    }

    //ancho completo
    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }
}
