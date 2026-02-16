<?php

namespace App\Filament\Resources\Nominas\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use pxlrbt\FilamentExcel\Actions\ExportAction;
use pxlrbt\FilamentExcel\Actions\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;

class NominasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('data_status')
                    ->label(__('nominas.column.data_status'))
                    ->state(function ($record) {
                        $fields = [
                            'rut',
                            'birth_date',
                            'health_insurance',
                            'address',
                            'profession',
                            'phone',
                            'emergency_contact_name',
                            'emergency_phone',
                        ];

                        foreach ($fields as $field) {
                            if (empty($record->$field)) {
                                return false;
                            }
                        }
                        return true;
                    })
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                TextColumn::make('user.name')
                    ->label(__('nominas.field.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.email')
                    ->label(__('nominas.field.email'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.departments.name')
                    ->label(__('departments.plural_model_label'))
                    ->badge()
                    ->searchable(),
                TextColumn::make('cargo.name')
                    ->label('Cargo')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('boss.name')
                    ->label('Jefe Directo')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('rut')
                    ->label(__('nominas.field.rut'))
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                     ExportBulkAction::make()
                        ->label(__('nominas.action.export_selected'))
                        ->exports([
                            ExcelExport::make()
                                ->fromTable()
                                ->withFilename('nominas_seleccionadas_' . date('Y-m-d'))
                                ->withColumns([
                                    Column::make('user.name')->heading(__('nominas.field.name')),
                                    Column::make('user.email')->heading(__('nominas.field.email')),
                                    Column::make('user.departments.name')->heading(__('departments.plural_model_label')),
                                    Column::make('boss.name')->heading('Jefe Directo'),
                                    Column::make('rut')->heading(__('nominas.field.rut')),
                                    Column::make('birth_date')
                                        ->heading(__('nominas.field.birth_date'))
                                        ->getStateUsing(fn ($record) => $record->birth_date ? \Carbon\Carbon::parse($record->birth_date)->format('Y-m-d') : ''),
                                    Column::make('age')
                                        ->heading(__('nominas.field.age'))
                                        ->getStateUsing(fn ($record) => $record->birth_date ? \Carbon\Carbon::parse($record->birth_date)->age : ''),
                                    Column::make('health_insurance')->heading(__('nominas.field.health_insurance')),
                                    Column::make('labor_inclusion')->heading(__('nominas.field.labor_inclusion')),
                                    Column::make('address')->heading(__('nominas.field.address')),
                                    Column::make('profession')->heading(__('nominas.field.profession')),
                                    Column::make('cargo.name')->heading('Cargo'),
                                    Column::make('emergency_contact_name')->heading(__('nominas.field.emergency_contact_name')),
                                    Column::make('emergency_phone')->heading(__('nominas.field.emergency_phone')),
                                ])
                        ]),
                ]),
            ]);
    }
}
