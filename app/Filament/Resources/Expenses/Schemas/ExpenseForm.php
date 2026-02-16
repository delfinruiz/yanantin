<?php

namespace App\Filament\Resources\Expenses\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class ExpenseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                //mostrar en una seccion en tres columnas
                Section::make(__('expense'))
                    ->columnSpanFull()
                    ->columns(3)
                    ->components([
                        Select::make('expense_category_id')
                            ->label(__('expense_category'))
                            ->relationship('category', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->disabledOn('edit'),
                        Select::make('month')
                            //mostrar mes de acuerdo al numero con traduccion
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
                            ->label(__('month'))
                            ->required()
                            ->disabledOn('edit'),
                        TextInput::make('amount')
                            ->label(__('amount'))
                            ->required()
                            ->numeric(),
                        Textarea::make('notes')
                            ->label(__('notes'))
                            ->columnSpanFull(),
                        Hidden::make('user_id')
                            ->default(Auth::id())
                            ->required(),
                        Hidden::make('year')
                            ->default(date('Y')),
                    ]),
            ]);
    }
}
