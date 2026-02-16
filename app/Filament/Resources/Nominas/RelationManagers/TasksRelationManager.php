<?php

namespace App\Filament\Resources\Nominas\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Model;

class TasksRelationManager extends RelationManager
{
    protected static string $relationship = 'tasks';

    protected static ?string $title = null;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('nominas.tasks.title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('id')
                    ->label(__('nominas.tasks.column_id'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('title')
                    ->label(__('nominas.tasks.column_title'))
                    ->searchable()
                    ->wrap(),
                TextColumn::make('creator.name')
                    ->label(__('nominas.tasks.column_creator'))
                    ->searchable(),
                TextColumn::make('due_date')
                    ->label(__('nominas.tasks.column_due_date'))
                    ->date('d-m-Y')
                    ->sortable(),
                TextColumn::make('status.title')
                     ->label(__('nominas.tasks.column_status'))
                     ->badge()
                     ->color(fn ($state) => match ($state) {
                         'Completado' => 'success',
                         'Pendiente' => 'warning',
                         'En Progreso' => 'info',
                         'Cancelado' => 'danger',
                         default => 'gray',
                     }),
                TextColumn::make('rating')
                    ->label(__('nominas.tasks.column_rating'))
                    ->sortable()
                    ->formatStateUsing(function ($state) {
                        if (!$state) return '-';
                        $stars = str_repeat('★', $state) . str_repeat('☆', 5 - $state);
                        return $stars . " ({$state})";
                    })
                    ->color('warning'),
            ])
            ->filters([
                SelectFilter::make('rating')
                    ->label(__('nominas.tasks.filter_rating'))
                    ->options(__('nominas.tasks.rating_options')),
                SelectFilter::make('status_id')
                    ->label(__('nominas.tasks.filter_status'))
                    ->relationship('status', 'title'),
            ])
            ->defaultSort('due_date', 'desc');
    }
}
