<?php

namespace App\Filament\Resources\Evaluations\StrategicObjectiveResource\Pages;

use App\Filament\Resources\Evaluations\StrategicObjectiveResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use App\Models\EvaluationCycle;
use App\Models\StrategicObjective;

class ListStrategicObjectives extends ListRecords
{
    protected static string $resource = StrategicObjectiveResource::class;

    public function getTabs(): array
    {
        $tabs = [
            'all' => Tab::make('Todos'),
            'pending_approval' => Tab::make('Por Aprobar')
                ->icon('heroicon-m-check-badge')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('status', 'pending_approval')
                    ->whereHas('owner.employeeProfile', fn ($q) => $q->where('reports_to', Auth::id()))
                )
                ->badge(StrategicObjective::query()
                    ->where('status', 'pending_approval')
                    ->whereHas('owner.employeeProfile', fn ($q) => $q->where('reports_to', Auth::id()))
                    ->count()),
            'checkin_reviews' => Tab::make('Revisiones de Avance')
                ->icon('heroicon-m-clipboard-document-check')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->whereHas('checkins', fn ($q) => $q->where('review_status', 'pending_review'))
                    ->whereHas('owner.employeeProfile', fn ($q) => $q->where('reports_to', Auth::id()))
                )
                ->badge(StrategicObjective::query()
                    ->whereHas('checkins', fn ($q) => $q->where('review_status', 'pending_review'))
                    ->whereHas('owner.employeeProfile', fn ($q) => $q->where('reports_to', Auth::id()))
                    ->count())
                ->badgeColor('danger'),
        ];

        return $tabs;
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make()
                ->visible(function () {
                    // Verificar si existe al menos un ciclo activo en periodo de definición
                    return EvaluationCycle::query()
                        ->where('is_published', true)
                        ->where(function ($q) {
                            $now = now();
                            $q->whereDate('definition_starts_at', '<=', $now)
                              ->whereDate('definition_ends_at', '>=', $now);
                        })
                        ->exists();
                }),
        ];
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }
}

