<?php

namespace App\Filament\Resources\Incomes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class IncomesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type.name')
                    ->label(__('income_type'))
                    ->sortable(),
                TextColumn::make('year')
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
                TextColumn::make('amount')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
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
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
