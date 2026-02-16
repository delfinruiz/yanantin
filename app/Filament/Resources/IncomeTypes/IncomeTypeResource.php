<?php

namespace App\Filament\Resources\IncomeTypes;

use App\Filament\Resources\IncomeTypes\Pages\CreateIncomeType;
use App\Filament\Resources\IncomeTypes\Pages\EditIncomeType;
use App\Filament\Resources\IncomeTypes\Pages\ListIncomeTypes;
use App\Filament\Resources\IncomeTypes\Schemas\IncomeTypeForm;
use App\Filament\Resources\IncomeTypes\Tables\IncomeTypesTable;
use App\Models\IncomeType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class IncomeTypeResource extends Resource
{
    protected static ?string $model = IncomeType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'Tipo de Ingreso';

    //cambiar label modelo
    public static function getLabel(): string
    {
        return __('income_type');
    }

    //no registrar la navegacion en el sidebar
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Schema $schema): Schema
    {
        return IncomeTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IncomeTypesTable::configure($table);
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
            'index' => ListIncomeTypes::route('/'),
            //'create' => CreateIncomeType::route('/create'),
            //'edit' => EditIncomeType::route('/{record}/edit'),
        ];
    }
}
