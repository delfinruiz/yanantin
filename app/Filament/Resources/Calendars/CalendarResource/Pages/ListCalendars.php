<?php

namespace App\Filament\Resources\Calendars\CalendarResource\Pages;

use App\Filament\Resources\Calendars\CalendarResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListCalendars extends ListRecords
{
    protected static string $resource = CalendarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('calendars_admin.button.new'))
                ->modalWidth('5xl'),
        ];
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }
}
