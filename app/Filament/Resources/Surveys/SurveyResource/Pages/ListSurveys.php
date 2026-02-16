<?php

namespace App\Filament\Resources\Surveys\SurveyResource\Pages;

use App\Filament\Resources\Surveys\SurveyResource;
use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use App\Models\Dimension;
use Filament\Notifications\Notification;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select as FormSelect;
use Filament\Schemas\Components\Utilities\Get;

class ListSurveys extends ListRecords
{
    protected static string $resource = SurveyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('dimensions_catalog')
                ->label(__('surveys.catalog.label'))
                ->color('gray')
                ->modalHeading(__('surveys.catalog.modal_heading'))
                ->modalContent(fn () => view('filament.modals.dimensions-table'))
                ->modalSubmitAction(false)
                ->modalCancelAction(false)
                ->modalWidth('5xl'),
        ];
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }
}
