<?php

namespace App\Filament\Resources\JobOffers;

use App\Filament\Resources\JobOffers\Pages\CreateJobOffer;
use App\Filament\Resources\JobOffers\Pages\EditJobOffer;
use App\Filament\Resources\JobOffers\Pages\ListJobOffers;
use App\Filament\Resources\JobOffers\Pages\ListJobApplications;
use App\Filament\Resources\JobOffers\Schemas\JobOfferForm;
use App\Filament\Resources\JobOffers\Tables\JobOffersTable;
use App\Models\JobOffer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class JobOfferResource extends Resource
{
    protected static ?string $model = JobOffer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Briefcase;

    public static function getNavigationGroup(): ?string
    {
        return 'Gestión Laboral';
    }

    public static function getNavigationLabel(): string
    {
        return __('job_offers.navigation_label') ?: 'Ofertas Laborales';
    }

    public static function getModelLabel(): string
    {
        return __('job_offers.model_label') ?: 'Oferta Laboral';
    }

    public static function getPluralModelLabel(): string
    {
        return __('job_offers.plural_model_label') ?: 'Ofertas Laborales';
    }

    public static function getNavigationSort(): ?int
    {
        return 5;
    }

    public static function form(Schema $schema): Schema
    {
        return JobOfferForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return JobOffersTable::configure($table);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Gate::allows('viewAny', JobOffer::class);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJobOffers::route('/'),
            'create' => CreateJobOffer::route('/create'),
            'edit' => EditJobOffer::route('/{record}/edit'),
            'applications' => ListJobApplications::route('/{record}/applications'),
        ];
    }
}
