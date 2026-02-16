<?php

namespace App\Filament\Resources\Nominas\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EvaluationResultsRelationManager extends RelationManager
{
    protected static string $relationship = 'evaluationResults';
    
    protected static ?string $title = 'Historial de Evaluaciones';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-clock';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Read-only view mainly
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('cycle.name')
                    ->label(__('nominas.evaluations_tab.cycle'))
                    ->sortable()
                    ->searchable(),

                TextColumn::make('final_score')
                    ->label(__('nominas.evaluations_tab.final_score'))
                    ->suffix('%')
                    ->sortable(),

                TextColumn::make('range.name')
                    ->label(__('nominas.evaluations_tab.classification'))
                    ->badge()
                    ->color(fn ($record) => match($record->range?->name) {
                        'Sobresaliente', 'Destacado' => 'success',
                        'Competente' => 'primary',
                        'En Desarrollo', 'Bajo Esperado' => 'warning',
                        'Insuficiente' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('bonus_amount')
                    ->label(__('nominas.evaluations_tab.bonus'))
                    ->money('CLP')
                    ->sortable(),
                
                TextColumn::make('computed_at')
                    ->label(__('nominas.field.date'))
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->defaultSort('computed_at', 'desc')
            ->filters([
                //
            ])
            ->headerActions([
                // Read only
            ])
            ->recordActions([
                // Read only
            ])
            ->toolbarActions([
                // Read only
            ]);
    }
}
