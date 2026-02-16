<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use pxlrbt\FilamentExcel\Actions\ExportBulkAction;

class UsersTable
{

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('name'))
                    ->searchable(),
                TextColumn::make('email')
                    ->label(__('email address'))
                    ->searchable(),
                TextColumn::make('departments.name')
                    ->label(__('departments'))
                    ->badge()
                    ->separator(',')
                    ->searchable(),
                TextColumn::make('roles.name')
                    ->label(__('roles'))
                    ->badge()
                    ->separator(',')
                    ->searchable(),
                TextColumn::make('type')
                    ->label(__('type'))
                    ->badge()
                    ->color(fn (User $record): string => $record->emailAccount()->exists() ? 'success' : 'warning')
                    ->getStateUsing(fn (User $record): string => $record->emailAccount()->exists() ? (__('internal_user')) : __('external_user')),
                TextColumn::make('email_verified_at')
                    ->label(__('email verified'))
                    ->badge()
                    ->color(fn (User $record): string => $record->email_verified_at ? 'success' : 'danger')
                    //modificar el record para que muestre verified o not verified
                    ->getStateUsing(fn (User $record): string => $record->email_verified_at ? __('verified') : __('not verified'))
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('Created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('Updated_at'))
                    ->dateTime()    
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->modalWidth('7xl'),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                    BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ExportBulkAction::make()
                ]),
            ]);
    }
}
