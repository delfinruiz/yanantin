<?php

namespace App\Filament\Resources\Expenses\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ExpensesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('category.name')
                    ->label(__('expense_category'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('month')
                    //mostrar mes de acuerdo al numero con traduccion
                    ->formatStateUsing(function (string $state): string {
                        $mes = date('F', mktime(0, 0, 0, $state));
                        return __($mes);
                    })
                    ->label(__('month'))
                    ->sortable(),
                TextColumn::make('year')
                    ->label(__('year'))
                    ->sortable(),
                TextColumn::make('amount')
                    ->label(__('amount'))
                    ->numeric()
                    ->sortable(),
                ToggleColumn::make('status')
                    ->label(__('status_payment')),
                TextColumn::make('notes')
                    ->label(__('notes')),
                TextColumn::make('created_at')
                    ->label(__('created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //por defecto anio actual
                SelectFilter::make('year')
                    ->label(__('year'))
                    ->options(function () {
                        $years = [];
                        for ($i = 2020; $i <= date('Y'); $i++) {
                            $years[$i] = $i;
                        }
                        return $years;
                    })
                    ->default(date('Y')),
                //por defecto mes actual con traduccion __('month')
                SelectFilter::make('month')
                    ->label(__('month'))
                    ->options([
                        1 => __('January'),
                        2 => __('February'),
                        3 => __('March'),
                        4 => __('April'),
                        5 => __('May'),
                        6 => __('June'),
                        7 => __('July'),
                        8 => __('August'),
                        9 => __('September'),
                        10 => __('October'),
                        11 => __('November'),
                        12 => __('December'),
                    ])
                    ->default(date('n'))
            ])
            ->recordActions([
                EditAction::make()
                    ->label(__('edit')),
                DeleteAction::make()
                    ->label(__('delete')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label(__('delete')),
                ]),
            ]);
    }
}
