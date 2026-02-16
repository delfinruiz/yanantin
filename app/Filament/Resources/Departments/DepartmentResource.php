<?php

namespace App\Filament\Resources\Departments;

use App\Filament\Resources\Departments\DepartmentResource\Pages\CreateDepartment;
use App\Filament\Resources\Departments\DepartmentResource\Pages\EditDepartment;
use App\Filament\Resources\Departments\DepartmentResource\Pages\ListDepartments;
use App\Models\Department;
use App\Models\User;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;

class DepartmentResource extends Resource
{
    protected static ?string $model = Department::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.human_resources');
    }

    public static function getNavigationLabel(): string
    {
        return __('departments.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('departments.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('departments.plural_model_label');
    }

    public static function getNavigationSort(): ?int
    {
        return 4;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Forms\Components\TextInput::make('name')
                    ->label(__('departments.fields.name'))
                    ->required(),
                Forms\Components\Textarea::make('description')
                    ->label(__('departments.fields.description')),
                Forms\Components\Select::make('supervisors')
                    ->label('Supervisores')
                    ->relationship('supervisors', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('departments.columns.name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label(__('departments.columns.description'))
                    ->wrap()
                    ->toggleable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('supervisors.name')
                    ->label('Supervisores')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->label(__('departments.columns.created_at'))
                    ->sortable(),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDepartments::route('/'),
    //        'create' => CreateDepartment::route('/create'),
    //        'edit' => EditDepartment::route('/{record}/edit'),
        ];
    }
}
