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
use App\Models\Survey;
use Illuminate\Support\Facades\DB;

class ListSurveys extends ListRecords
{
    protected static string $resource = SurveyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('dimensions_catalog')
                ->label('1. ' . __('surveys.catalog.label'))
                ->color('info')
                ->icon('heroicon-o-rectangle-stack')
                ->modalHeading(__('surveys.catalog.modal_heading'))
                ->modalContent(fn () => view('filament.modals.dimensions-table'))
                ->modalSubmitAction(false)
                ->modalCancelAction(false)
                ->modalWidth('5xl'),
            Action::make('create_survey_step')
                ->label('2. Estructurar Encuesta')
                ->color('success')
                ->icon('heroicon-o-document-plus')
                ->modalHeading('Estructurar Nueva Encuesta')
                ->modalDescription('Selecciona el nombre de la encuesta para comenzar. Una vez creada, podrás añadir preguntas y se guardarán automáticamente.')
                ->schema([
                    FormSelect::make('title')
                        ->label(__('surveys.fields.select_survey'))
                        ->required()
                        ->options(function () {
                            $existingTitles = \App\Models\Survey::pluck('title')->toArray();
                            return \App\Models\Dimension::query()
                                ->whereNotNull('survey_name')
                                ->whereNotIn('survey_name', $existingTitles)
                                ->select('survey_name', DB::raw('SUM(weight) as total_weight'))
                                ->groupBy('survey_name')
                                ->orderBy('survey_name')
                                ->get()
                                ->mapWithKeys(function ($dim) {
                                    $label = $dim->survey_name;
                                    $weight = $dim->total_weight ?? 0;
                                    if ($weight > 0) {
                                        $label .= ' (Peso total: ' . number_format($weight, 0) . '%)';
                                    }
                                    return [$dim->survey_name => $label];
                                })
                                ->toArray();
                        })
                        ->placeholder(__('surveys.fields.no_surveys_in_catalog'))
                        ->disabled(fn () => \App\Models\Dimension::whereNotNull('survey_name')->count() === 0),
                ])
                ->action(function (array $data) {
                    $survey = Survey::create([
                        'title' => $data['title'],
                        'active' => false,
                        'is_public' => false,
                    ]);

                    return redirect(SurveyResource::getUrl('edit', ['record' => $survey]));
                }),
        ];
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }
}
