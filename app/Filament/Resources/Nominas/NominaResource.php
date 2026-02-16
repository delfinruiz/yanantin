<?php

namespace App\Filament\Resources\Nominas;

use App\Filament\Resources\Nominas\Pages\EditNomina;
use App\Filament\Resources\Nominas\Pages\ListNominas;
use App\Filament\Resources\Nominas\Schemas\NominaForm;
use App\Filament\Resources\Nominas\Tables\NominasTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Models\EmployeeProfile;
use UnitEnum;

class NominaResource extends Resource
{
    protected static ?string $model = EmployeeProfile::class;
    
    protected static ?string $slug = 'nominas';

    public static function getModelLabel(): string
    {
        return __('nominas.title_singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('nominas.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('nominas.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.human_resources');
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getNavigationSort(): ?int
    {
        return 10;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    public static function form(Schema $schema): Schema
    {
        return NominaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NominasTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            // RelationManagers\MedicalLicensesRelationManager::class,
            // RelationManagers\AbsenceRequestsRelationManager::class,
            // RelationManagers\VacationLedgersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNominas::route('/'),
            'edit' => EditNomina::route('/{record}/edit'),
        ];
    }
}
