<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Enums\Width;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use App\Filament\Widgets\CompanyHappinessBarWidget;
use App\Filament\Widgets\DailyMoodWidget;
use App\Livewire\PagarStatsWidget;
use App\Livewire\HacerStatsWidget;
use App\Livewire\GraficoResumenAnualWidgetPrincipal;
use App\Filament\Widgets\TodaysBirthdays;


class Dashboard extends BaseDashboard
{
    use HasPageShield;

    // cambiar nombre de la página
    public static function getNavigationLabel(): string
    {
        return __('dashboard.navigation_label');
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    public function getColumns(): int | array
    {
        return 12;
    }

    public function getTitle(): string
    {
        return __('dashboard.title');
    }

    public function getWidgets(): array
    {
        return [
            CompanyHappinessBarWidget::class,
            DailyMoodWidget::class,
            PagarStatsWidget::class,
            HacerStatsWidget::class,
            TodaysBirthdays::class,
            GraficoResumenAnualWidgetPrincipal::class,
        ];
    }
}
