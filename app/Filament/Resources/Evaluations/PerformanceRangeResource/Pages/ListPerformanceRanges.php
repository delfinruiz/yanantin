<?php

namespace App\Filament\Resources\Evaluations\PerformanceRangeResource\Pages;

use App\Filament\Resources\Evaluations\PerformanceRangeResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListPerformanceRanges extends ListRecords
{
    protected static string $resource = PerformanceRangeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }
}

