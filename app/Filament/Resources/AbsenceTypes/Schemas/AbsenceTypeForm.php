<?php

namespace App\Filament\Resources\AbsenceTypes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AbsenceTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información General')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required(),
                        TextInput::make('slug')
                            ->label('Slug')
                            ->required(),
                        Select::make('color')
                            ->label('Color')
                            ->options([
                                'success' => 'Verde (Éxito)',
                                'danger' => 'Rojo (Peligro)',
                                'warning' => 'Amarillo (Advertencia)',
                                'info' => 'Azul (Información)',
                                'primary' => 'Primario',
                                'gray' => 'Gris',
                            ]),
                        TextInput::make('max_days_allowed')
                            ->label('Días Máximos Permitidos')
                            ->numeric(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                
                Section::make('Configuración')
                    ->schema([
                        Toggle::make('is_vacation')
                            ->label('Es Vacaciones')
                            ->live()
                            ->required(),
                        Toggle::make('requires_approval')
                            ->label('Requiere Aprobación')
                            ->required(),
                        Toggle::make('allows_half_day')
                            ->label('Permite Medio Día')
                            ->required(),
                        TextInput::make('accrual_days_per_year')
                            ->label('Días Acumulados por Año')
                            ->numeric()
                            ->default(15)
                            ->visible(fn (callable $get) => $get('is_vacation'))
                            ->required(fn (callable $get) => $get('is_vacation'))
                            ->helperText('Días que se acumulan anualmente para este tipo de vacaciones (ej. 15)')
                            ->columnSpanFull(),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ]);
    }
}
