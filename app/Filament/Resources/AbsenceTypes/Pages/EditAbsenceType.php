<?php

namespace App\Filament\Resources\AbsenceTypes\Pages;

use App\Filament\Resources\AbsenceTypes\AbsenceTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAbsenceType extends EditRecord
{
    protected static string $resource = AbsenceTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
