<?php

namespace App\Filament\Resources\Evaluations;

use App\Filament\Resources\Evaluations\PerformanceRangeResource\Pages\CreatePerformanceRange;
use App\Filament\Resources\Evaluations\PerformanceRangeResource\Pages\EditPerformanceRange;
use App\Filament\Resources\Evaluations\PerformanceRangeResource\Pages\ListPerformanceRanges;
use App\Models\PerformanceRange;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PerformanceRangeResource extends Resource
{
    protected static ?string $model = PerformanceRange::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function getNavigationGroup(): ?string
    {
        return __('evaluations.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('evaluations.performance_range.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('evaluations.performance_range.plural');
    }

    public static function getNavigationSort(): ?int
    {
        return 5;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('evaluations.performance_range.title'))->schema([
                \Filament\Forms\Components\TextInput::make('name')
                    ->label(__('evaluations.performance_range.fields.name'))
                    ->required()->maxLength(100),
                \Filament\Forms\Components\TextInput::make('min_percentage')
                    ->label(__('evaluations.performance_range.fields.min_percentage'))
                    ->numeric()->minValue(0)->maxValue(100)->required(),
                \Filament\Forms\Components\TextInput::make('max_percentage')
                    ->label(__('evaluations.performance_range.fields.max_percentage'))
                    ->numeric()->minValue(0)->maxValue(100)->required(),
            ])->columns(3)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('evaluations.performance_range.fields.name'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('min_percentage')
                    ->label(__('evaluations.performance_range.fields.min_percentage'))
                    ->suffix('%'),
                Tables\Columns\TextColumn::make('max_percentage')
                    ->label(__('evaluations.performance_range.fields.max_percentage'))
                    ->suffix('%'),
            ])
            ->recordActions([
                EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPerformanceRanges::route('/'),
         //   'create' => CreatePerformanceRange::route('/create'),
          //  'edit' => EditPerformanceRange::route('/{record}/edit'),
        ];
    }
}
