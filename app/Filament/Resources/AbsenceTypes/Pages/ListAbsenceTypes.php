<?php

namespace App\Filament\Resources\AbsenceTypes\Pages;

use App\Filament\Resources\AbsenceTypes\AbsenceTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListAbsenceTypes extends ListRecords
{
    protected static string $resource = AbsenceTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }
}
