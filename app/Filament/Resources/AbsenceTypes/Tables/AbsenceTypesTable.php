<?php

namespace App\Filament\Resources\AbsenceTypes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AbsenceTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable(),
                TextColumn::make('color')
                    ->label('Color')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'success' => 'Verde',
                        'danger' => 'Rojo',
                        'warning' => 'Amarillo',
                        'info' => 'Azul',
                        'primary' => 'Primario',
                        'gray' => 'Gris',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'success' => 'success',
                        'danger' => 'danger',
                        'warning' => 'warning',
                        'info' => 'info',
                        'primary' => 'primary',
                        'gray' => 'gray',
                        default => 'gray',
                    })
                    ->searchable(),
                IconColumn::make('is_vacation')
                    ->label('Es Vacaciones')
                    ->boolean(),
                IconColumn::make('requires_approval')
                    ->label('Requiere Aprobación')
                    ->boolean(),
                TextColumn::make('max_days_allowed')
                    ->label('Días Máximos')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('allows_half_day')
                    ->label('Permite Medio Día')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Creado el')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Actualizado el')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            ]);
    }
}
