<?php

namespace App\Filament\Resources\Evaluations;

use App\Filament\Resources\Evaluations\EvaluationResultResource\Pages\ListEvaluationResults;
use App\Models\EvaluationResult;
use App\Services\Evaluations\EvaluationCalculator;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\Action as TableAction;
use Filament\Actions\BulkAction;
use Filament\Support\Icons\Heroicon;

class EvaluationResultResource extends Resource
{
    protected static ?string $model = EvaluationResult::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    public static function getNavigationGroup(): ?string
    {
        return __('evaluations.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('evaluations.evaluation_result.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('evaluations.evaluation_result.plural');
    }

    public static function getNavigationSort(): ?int
    {
        return 4;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('evaluations.evaluation_result.title'))->schema([
                \Filament\Forms\Components\Select::make('evaluation_cycle_id')
                    ->label(__('evaluations.evaluation_result.fields.evaluation_cycle_id'))
                    ->relationship('cycle', 'name')
                    ->required(),
                \Filament\Forms\Components\Select::make('user_id')
                    ->label(__('evaluations.evaluation_result.fields.user_id'))
                    ->relationship('user', 'name')
                    ->required()
                    ->searchable(),
                \Filament\Forms\Components\TextInput::make('final_score')
                    ->label(__('evaluations.evaluation_result.fields.final_score'))
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100),
                \Filament\Forms\Components\TextInput::make('bonus_amount')
                    ->label(__('evaluations.evaluation_result.fields.bonus_amount'))
                    ->numeric()
                    ->minValue(0),
            ])->columns(2)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cycle.name')
                    ->label(__('evaluations.evaluation_result.fields.evaluation_cycle_id'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('evaluations.evaluation_result.fields.user_id'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('final_score')
                    ->label(__('evaluations.evaluation_result.fields.final_score'))
                    ->sortable()
                    ->suffix('%'),
                Tables\Columns\TextColumn::make('range.name')
                    ->label(__('evaluations.evaluation_result.fields.range'))
                    ->badge()
                    ->color(fn ($record) => match($record->range?->name) {
                        'Sobresaliente', 'Destacado', 'Excelente' => 'success',
                        'Competente', 'Cumple', 'Satisfactorio' => 'info',
                        'En Desarrollo', 'Bajo Esperado', 'Regular' => 'warning',
                        'Insuficiente', 'Incumple', 'No Cumple' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('bonus_amount')
                    ->label(__('evaluations.evaluation_result.fields.bonus_amount'))
                    ->money('USD'),
                Tables\Columns\TextColumn::make('computed_at')
                    ->label(__('evaluations.evaluation_result.fields.computed_at'))
                    ->dateTime(),
            ])
            ->groups([
                Tables\Grouping\Group::make('cycle.name')
                    ->label(__('evaluations.evaluation_result.fields.evaluation_cycle_id'))
                    ->collapsible(),
            ])
            ->defaultGroup('cycle.name')
            ->groupsOnly(false)
            ->collapsedGroupsByDefault(true)
            ->filters([
                Tables\Filters\SelectFilter::make('evaluation_cycle_id')
                    ->label(__('evaluations.evaluation_result.fields.evaluation_cycle_id'))
                    ->relationship('cycle', 'name'),
            ])
            ->recordActions([
                TableAction::make('recompute')
                    ->label(__('evaluations.evaluation_result.actions.recompute'))
                    ->action(function (EvaluationResult $record) {
                        $calc = new EvaluationCalculator();
                        $calc->computeForEmployee($record->cycle, $record->user);
                    })
                    ->requiresConfirmation(),
            ])
            ->toolbarActions([
                BulkAction::make('recomputeSelected')
                    ->label('Recalcular seleccionados')
                    ->action(function (array $records) {
                        $calc = new EvaluationCalculator();
                        foreach ($records as $record) {
                            /** @var EvaluationResult $r */
                            $r = EvaluationResult::find($record);
                            if ($r) {
                                $calc->computeForEmployee($r->cycle, $r->user);
                            }
                        }
                    }),
                BulkAction::make('exportCsv')
                    ->label('Exportar CSV')
                    ->action(function (array $records) {
                        $rows = EvaluationResult::query()->whereIn('id', $records)->get();
                        $csv = implode("\n", collect([
                            'ciclo,empleado,puntaje,rango,bono,calculado_en',
                        ])->merge($rows->map(function ($r) {
                            return sprintf(
                                '%s,%s,%s,%s,%s,%s',
                                $r->cycle?->name,
                                $r->user?->name,
                                $r->final_score,
                                $r->range?->name,
                                $r->bonus_amount,
                                optional($r->computed_at)->toDateTimeString()
                            );
                        }))->all());
                        return response()->streamDownload(function () use ($csv) {
                            echo $csv;
                        }, 'evaluation_results.csv', [
                            'Content-Type' => 'text/csv',
                        ]);
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEvaluationResults::route('/'),
        ];
    }
}
