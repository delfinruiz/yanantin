<?php

namespace App\Livewire;

use Filament\Widgets\StatsOverviewWidget\Stat;

class HacerStatsWidget extends PrincipalMarcadoresWidget
{
    protected int|string|array $columnSpan = 4;
    protected static ?int $sort = 4;

    protected function getStats(): array
    {
        return [
            Stat::make(__('dashboard.stats.do.label'), $this->countByStatusForUser('pending'))
                ->description(__('dashboard.stats.do.description'))
                ->descriptionIcon('heroicon-o-puzzle-piece')
                ->color(fn () => $this->countByStatusForUser('pending') > 0 ? 'danger' : 'success'),
        ];
    }
}

