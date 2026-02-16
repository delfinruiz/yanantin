<?php

namespace App\Filament\Resources\Dimensions;

use App\Filament\Resources\Dimensions\DimensionResource\Pages\CreateDimension;
use App\Filament\Resources\Dimensions\DimensionResource\Pages\EditDimension;
use App\Filament\Resources\Dimensions\DimensionResource\Pages\ListDimensions;
use App\Models\Dimension;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;

class DimensionResource extends Resource
{
    protected static ?string $model = Dimension::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.human_resources');
    }

    public static function getNavigationLabel(): string
    {
        return __('Catálogo de dimensiones');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                Grid::make(2)->schema([
                    Forms\Components\TextInput::make('item')
                        ->label('Dimensión / Ítem')
                        ->required()
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('kpi_target')
                        ->label('Meta (KPI)')
                        ->numeric()
                        ->required()
                        ->default(10),
                    Forms\Components\TextInput::make('weight')
                        ->label('Peso')
                        ->numeric()
                        ->nullable(),
                ]),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('item')->label('Dimensión')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('kpi_target')->label('Meta')->sortable(),
                Tables\Columns\TextColumn::make('weight')->label('Peso')->sortable(),
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
            'index' => ListDimensions::route('/'),
            'create' => CreateDimension::route('/create'),
            'edit' => EditDimension::route('/{record}/edit'),
        ];
    }
}
