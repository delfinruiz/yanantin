<?php

namespace App\Filament\Resources\Cargos\Schemas;

use Filament\Schemas\Schema;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class CargoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('cargos.fields.name'))
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                TextInput::make('hierarchy_level')
                    ->label(__('cargos.fields.hierarchy_level'))
                    ->numeric()
                    ->minValue(1)
                    ->default(99)
                    ->required()
                    ->helperText('Ej: 1 (CEO), 2 (Directivos), 3 (Gerentes)'),
                Textarea::make('description')
                    ->label(__('cargos.fields.description'))
                    ->rows(3)
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ]);
    }
}
