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

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Scale;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.my_apps');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.labels.my_finances');
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
