<?php

namespace App\Filament\Resources\AbsenceTypes;

use App\Filament\Resources\AbsenceTypes\Pages\CreateAbsenceType;
use App\Filament\Resources\AbsenceTypes\Pages\EditAbsenceType;
use App\Filament\Resources\AbsenceTypes\Pages\ListAbsenceTypes;
use App\Filament\Resources\AbsenceTypes\Schemas\AbsenceTypeForm;
use App\Filament\Resources\AbsenceTypes\Tables\AbsenceTypesTable;
use App\Models\AbsenceType;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AbsenceTypeResource extends Resource
{
    protected static ?string $model = AbsenceType::class;

    public static function getModelLabel(): string
    {
        return __('absence_types.title_singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('absence_types.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('absence_types.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.human_resources');
    }

    protected static ?int $navigationSort = 101;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return AbsenceTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AbsenceTypesTable::configure($table);
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
            'index' => ListAbsenceTypes::route('/'),
        //    'create' => CreateAbsenceType::route('/create'),
        //    'edit' => EditAbsenceType::route('/{record}/edit'),
        ];
    }
}
