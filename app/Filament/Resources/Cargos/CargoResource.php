<?php

namespace App\Filament\Resources\Cargos;

use App\Filament\Resources\Cargos\Pages\CreateCargo;
use App\Filament\Resources\Cargos\Pages\EditCargo;
use App\Filament\Resources\Cargos\Pages\ListCargos;
use App\Filament\Resources\Cargos\Schemas\CargoForm;
use App\Filament\Resources\Cargos\Tables\CargosTable;
use App\Models\Cargo;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CargoResource extends Resource
{
    protected static ?string $model = Cargo::class;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.human_resources');
    }

    protected static ?int $navigationSort = 9;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getModelLabel(): string
    {
        return __('cargos.title_singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('cargos.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('cargos.navigation_label');
    }

    public static function form(Schema $schema): Schema
    {
        return CargoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CargosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCargos::route('/'),
        //    'create' => CreateCargo::route('/create'),
        //    'edit' => EditCargo::route('/{record}/edit'),
        ];
    }
}
