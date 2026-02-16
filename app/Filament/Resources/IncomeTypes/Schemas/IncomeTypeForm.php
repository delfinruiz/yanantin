<?php

namespace App\Filament\Resources\IncomeTypes\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class IncomeTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('user_id')
                    ->default(Auth::id()),
                TextInput::make('name')
                    ->label(__('name'))
                    ->columnSpanFull()
                    ->required(),
                Textarea::make('description')
                    ->label(__('description'))      
                    ->columnSpanFull(),
            ]);
    }
}
