<?php

namespace App\Filament\Resources\Evaluations;

use App\Filament\Resources\Evaluations\BonusRuleResource\Pages\CreateBonusRule;
use App\Filament\Resources\Evaluations\BonusRuleResource\Pages\EditBonusRule;
use App\Filament\Resources\Evaluations\BonusRuleResource\Pages\ListBonusRules;
use App\Models\BonusRule;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

use Filament\Schemas\Components\Utilities\Get;

class BonusRuleResource extends Resource
{
    protected static ?string $model = BonusRule::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

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
        return __('evaluations.bonus_rule.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('evaluations.bonus_rule.plural');
    }

    public static function getNavigationSort(): ?int
    {
        return 6;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('evaluations.bonus_rule.title'))->schema([
                \Filament\Forms\Components\Select::make('performance_range_id')
                    ->label(__('evaluations.bonus_rule.fields.performance_range_id'))
                    ->relationship('range', 'name')->required(),
                \Filament\Forms\Components\Select::make('base_type')
                    ->label(__('evaluations.bonus_rule.fields.base_type'))
                    ->options([
                        'percentage' => __('evaluations.bonus_rule.enums.percentage'),
                        'fixed' => __('evaluations.bonus_rule.enums.fixed'),
                    ])
                    ->required()
                    ->live(),
                \Filament\Forms\Components\TextInput::make('percentage')
                    ->label(__('evaluations.bonus_rule.fields.percentage'))
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->visible(fn (Get $get) => $get('base_type') === 'percentage')
                    ->required(fn (Get $get) => $get('base_type') === 'percentage'),
                \Filament\Forms\Components\TextInput::make('fixed_amount')
                    ->label(__('evaluations.bonus_rule.fields.fixed_amount'))
                    ->numeric()
                    ->minValue(0)
                    ->visible(fn (Get $get) => $get('base_type') === 'fixed')
                    ->required(fn (Get $get) => $get('base_type') === 'fixed'),
            ])->columns(2)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('range.name')
                    ->label(__('evaluations.bonus_rule.fields.performance_range_id')),
                Tables\Columns\TextColumn::make('base_type')
                    ->label(__('evaluations.bonus_rule.fields.base_type'))
                    ->badge(),
                Tables\Columns\TextColumn::make('percentage')
                    ->label(__('evaluations.bonus_rule.fields.percentage'))
                    ->suffix('%'),
                Tables\Columns\TextColumn::make('fixed_amount')
                    ->label(__('evaluations.bonus_rule.fields.fixed_amount'))
                    ->numeric(2),
            ])
            ->recordActions([
                EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBonusRules::route('/'),
        //    'create' => CreateBonusRule::route('/create'),
        //    'edit' => EditBonusRule::route('/{record}/edit'),
        ];
    }
}
