<?php

namespace App\Livewire;

use App\Filament\Pages\MonthlyDashboard;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PagarStatsWidget extends PrincipalMarcadoresWidget
{
    protected int|string|array $columnSpan = 4;
    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        return [
            Stat::make(__('dashboard.stats.pay.label'), $this->countByStatusForUserExpense('0'))
                ->description(__('dashboard.stats.pay.description'))
                ->descriptionIcon('heroicon-o-credit-card')
                ->color(fn () => $this->countByStatusForUserExpense('0') > 0 ? 'danger' : 'success')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ])
                ->url(MonthlyDashboard::getUrl() . '#expenses'),
        ];
    }
}

