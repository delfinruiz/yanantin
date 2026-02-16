<?php

namespace App\Filament\Resources\ExpenseCategories\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class ExpenseCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('user_id')
                    ->default(Auth::id()),
                TextInput::make('name')
                    ->label(__('name'))
                    ->required()
                    ->maxLength(100)
                    ->columnSpanFull(),
                Textarea::make('description')
                    ->label(__('description'))
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
            ]);
    }
}
