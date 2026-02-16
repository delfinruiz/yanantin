<?php

namespace App\Filament\Resources\Cargos\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

use Filament\Tables\Columns\TextColumn;

class CargosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('cargos.fields.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('hierarchy_level')
                    ->label(__('cargos.fields.hierarchy_level'))
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('description')
                    ->label(__('cargos.fields.description'))
                    ->limit(50)
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                DeleteAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('hierarchy_level', 'asc');
    }
}
