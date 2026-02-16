<?php

namespace App\Filament\Resources\IncomeTypes\Pages;

use App\Filament\Resources\IncomeTypes\IncomeTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditIncomeType extends EditRecord
{
    protected static string $resource = IncomeTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
