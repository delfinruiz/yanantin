<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Enums\Width;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use App\Filament\Widgets\CompanyHappinessBarWidget;
use App\Filament\Widgets\DailyMoodWidget;
use App\Livewire\PrincipalMarcadoresWidget;
use App\Livewire\GraficoResumenAnualWidgetPrincipal;

class Dashboard extends BaseDashboard
{
    use HasPageShield;

    // cambiar nombre de la página
    public static function getNavigationLabel(): string
    {
        return __('dashboard.navigation_label');
    }

    //full width
    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    //cambiar titulo de la página
    public function getTitle(): string
    {
        return __('dashboard.title');
    }

    public function getWidgets(): array
    {
        return [
            CompanyHappinessBarWidget::class,
            DailyMoodWidget::class,
            PrincipalMarcadoresWidget::class,
            GraficoResumenAnualWidgetPrincipal::class,

        ];
    }
}
