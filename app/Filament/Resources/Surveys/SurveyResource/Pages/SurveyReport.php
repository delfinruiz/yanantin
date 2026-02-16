<?php

namespace App\Filament\Resources\Surveys\SurveyResource\Pages;

use App\Filament\Resources\Surveys\SurveyResource;
use App\Models\Response;
use App\Models\Survey;
use App\Services\SurveyStatsService;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SurveyReport extends Page
{
    protected static string $resource = SurveyResource::class;

    protected string $view = 'filament.pages.survey-report';

    public static function canAccess(array $parameters = []): bool
    {
        return \Illuminate\Support\Facades\Auth::check();
    }

    public Survey $record;

    public array $stats = [];

    public function mount(Survey $record): void
    {
        $this->record = $record;
        $this->stats = $this->computeStats();
    }

    protected function computeStats(): array
    {
        $service = app(SurveyStatsService::class);
        $result = $service->dimensionStats($this->record);
        $globalAvg = $service->globalAvg($result);
        $respondentNames = [];
        if ($this->record->is_public) {
            $qIds = $this->record->questions()->pluck('id');
            $uIds = Response::whereIn('question_id', $qIds)->distinct()->pluck('user_id');
            $respondentNames = \App\Models\User::whereIn('id', $uIds)->pluck('name')->all();
        }
        return [
            'dimensions' => $result,
            'global_avg' => $globalAvg ? round($globalAvg, 2) : null,
            'participants' => $this->record->users()->count(),
            'respondents' => $respondentNames,
        ];
    }
}
