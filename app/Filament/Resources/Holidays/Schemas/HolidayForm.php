<?php

namespace App\Filament\Resources\Holidays\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class HolidayForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre')
                    ->required(),
                DatePicker::make('date')
                    ->label('Fecha')
                    ->required(),
                Toggle::make('is_recurring')
                    ->label('Es Recurrente')
                    ->required(),
            ]);
    }
}
