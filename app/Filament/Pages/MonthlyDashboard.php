<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

use Filament\Support\Enums\Width;

class MonthlyDashboard extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.monthly-dashboard';

    // Ocultar de la navegación para evitar redirección automática si no es deseado
    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Scale;

        //funcion personalizar titulo del menu
    public static function getNavigationLabel(): string
    {
        return __('monthly_dashboard');
    }

    public function getTitle(): string
    {
        return __('monthly_dashboard');
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

}
