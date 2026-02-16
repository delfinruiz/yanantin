<?php

namespace App\Filament\Resources\Nominas\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Model;

class EvaluationsRelationManager extends RelationManager
{
    protected static string $relationship = 'evaluationObjectives';

    protected static ?string $title = null;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('nominas.evaluations_tab.title');
    }

    protected static string|\BackedEnum|null $icon = 'heroicon-o-chart-bar';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('cycle.name')
                    ->label(__('nominas.evaluations_tab.cycle'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true), // Oculto porque usaremos agrupación

                TextColumn::make('title')
                    ->label(__('nominas.evaluations_tab.objective'))
                    ->searchable()
                    ->description(fn ($record) => $record->parent 
                        ? '↳ Alineado a: ' . $record->parent->title 
                        : $record->description)
                    ->wrap(),
                
                TextColumn::make('type')
                    ->label(__('nominas.evaluations_tab.type'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? __('nominas.options.evaluation_type.' . \Illuminate\Support\Str::lower($state)) : '')
                    ->color('primary'),

                TextColumn::make('weight')
                    ->label(__('nominas.evaluations_tab.weight'))
                    ->suffix('%')
                    ->numeric(2),

                TextColumn::make('progress_percentage')
                    ->label('Avance')
                    ->suffix('%')
                    ->numeric(2)
                    ->badge()
                    ->color(fn ($state) => $state >= 100 ? 'success' : ($state > 0 ? 'warning' : 'gray')),

                TextColumn::make('execution_status')
                    ->label('Ejecución')
                    ->badge()
                    ->colors([
                        'gray' => 'pending',
                        'warning' => 'in_progress',
                        'success' => 'completed',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'pending' => 'Pendiente',
                        'in_progress' => 'En Progreso',
                        'completed' => 'Completado',
                        default => $state,
                    }),

                TextColumn::make('status')
                    ->label(__('nominas.evaluations_tab.status'))
                    ->badge()
                    ->colors([
                        'gray' => 'draft',
                        'warning' => 'pending_approval',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'draft' => 'Borrador',
                        'pending_approval' => 'Por Aprobar',
                        'approved' => 'Aprobado',
                        'rejected' => 'Rechazado',
                        default => $state,
                    }),
            ])
            ->filters([
                SelectFilter::make('evaluation_cycle_id')
                    ->label(__('nominas.evaluations_tab.filter_cycle'))
                    ->relationship('cycle', 'name'),
            ])
            ->groups([
                \Filament\Tables\Grouping\Group::make('cycle.name')
                    ->label(__('nominas.evaluations_tab.cycle')),
            ])
            ->defaultGroup('cycle.name')
            ->headerActions([
                // Actions to manage objectives if needed
            ])
            ->recordActions([
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                // Tables\Actions\BulkActionGroup::make([
                //    Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
    }
}
