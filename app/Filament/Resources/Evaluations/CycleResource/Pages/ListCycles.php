<?php

namespace App\Filament\Resources\Evaluations\CycleResource\Pages;

use App\Filament\Resources\Evaluations\CycleResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListCycles extends ListRecords
{
    protected static string $resource = CycleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }

    //ancho de la tabla
    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }
}

