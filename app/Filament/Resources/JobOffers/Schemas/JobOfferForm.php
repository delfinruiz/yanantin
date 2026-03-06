<?php

namespace App\Filament\Resources\JobOffers\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class JobOfferForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Identidad del Cargo')
                    ->collapsible()
                    ->schema([
                        TextInput::make('title')
                            ->label('Denominación del cargo')
                            ->required()
                            ->maxLength(255),
                        DatePicker::make('deadline')
                            ->label('Fecha límite de postulación')
                            ->native(false),
                        Select::make('department_id')
                            ->label('Área organizacional')
                            ->relationship('department', 'name')
                            ->required()
                            ->createOptionForm([
                                TextInput::make('name')->required()->label('Nombre del Departamento'),
                            ]),
                        Select::make('hierarchical_level')
                            ->label('Nivel jerárquico')
                            ->options([
                                'Operativo' => 'Operativo',
                                'Analista' => 'Analista',
                                'Especialista' => 'Especialista',
                                'Jefatura' => 'Jefatura',
                                'Gerencia' => 'Gerencia',
                                'Dirección' => 'Dirección',
                            ])
                            ->required(),
                        Select::make('criticality_level')
                            ->label('Nivel de criticidad')
                            ->options([
                                'Operativo' => 'Operativo',
                                'Táctico' => 'Táctico',
                                'Estratégico' => 'Estratégico',
                            ])
                            ->required(),
                        Select::make('work_modality')
                            ->label('Modalidad')
                            ->options([
                                'Presencial' => 'Presencial',
                                'Híbrido' => 'Híbrido',
                                'Remoto' => 'Remoto',
                            ])
                            ->required(),
                        Select::make('country')
                            ->label('País')
                            ->options([
                                'Argentina' => 'Argentina',
                                'Bolivia' => 'Bolivia',
                                'Brasil' => 'Brasil',
                                'Chile' => 'Chile',
                                'Colombia' => 'Colombia',
                                'Costa Rica' => 'Costa Rica',
                                'Cuba' => 'Cuba',
                                'Ecuador' => 'Ecuador',
                                'El Salvador' => 'El Salvador',
                                'Guatemala' => 'Guatemala',
                                'Haití' => 'Haití',
                                'Honduras' => 'Honduras',
                                'México' => 'México',
                                'Nicaragua' => 'Nicaragua',
                                'Panamá' => 'Panamá',
                                'Paraguay' => 'Paraguay',
                                'Perú' => 'Perú',
                                'República Dominicana' => 'República Dominicana',
                                'Uruguay' => 'Uruguay',
                                'Venezuela' => 'Venezuela',
                            ])
                            ->searchable()
                            ->required(),
                        TextInput::make('city')
                            ->label('Ciudad')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('vacancies_count')
                            ->label('Número de posiciones requeridas')
                            ->numeric()
                            ->default(1)
                            ->required(),
                        Select::make('contract_type')
                            ->label('Tipo de contrato')
                            ->options([
                                'Indefinido' => 'Indefinido',
                                'Fijo' => 'Fijo',
                                'Proyecto' => 'Proyecto',
                                'Temporal' => 'Temporal',
                            ])
                            ->required(),
                        DatePicker::make('estimated_start_date')
                            ->label('Fecha estimada de inicio')
                            ->native(false),
                        TextInput::make('cost_center')
                            ->label('Centro de costo (interno)'),
                        Select::make('opening_reason')
                            ->label('Justificación de apertura')
                            ->options([
                                'Reemplazo' => 'Reemplazo',
                                'Expansión' => 'Expansión',
                                'Nueva campaña' => 'Nueva campaña',
                            ]),
                        TextInput::make('salary')
                            ->label('Salario')
                            ->numeric()
                            ->prefix('$')
                            ->nullable(),
                        FileUpload::make('image')
                            ->label('Imagen de la oferta')
                            ->image()
                            ->directory('job-offers')
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label('Oferta Activa (Publicada)')
                            ->helperText('Al activar, la oferta será visible para los candidatos.')
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('Descripción Estratégica')
                    ->collapsible()
                    ->schema([
                        Textarea::make('mission')
                            ->label('Misión del cargo')
                            ->rows(3)
                            ->required()
                            ->columnSpanFull(),
                        Textarea::make('organizational_impact')
                            ->label('Impacto organizacional esperado')
                            ->rows(3)
                            ->columnSpanFull(),
                        Repeater::make('key_results')
                            ->label('Resultados clave que debe producir')
                            ->schema([
                                TextInput::make('result')->label('Resultado')->required()
                            ])
                            ->columnSpanFull(),
                        MarkdownEditor::make('description')
                            ->label('Descripción detallada / Responsabilidades')
                            ->columnSpanFull(),
                        Textarea::make('benefits')
                            ->label('Beneficios y Propuesta de Valor')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),

                Section::make('Requisitos del Cargo')
                    ->collapsible()
                    ->schema([
                        Repeater::make('jobOfferRequirements')
                            ->relationship()
                            ->label('Requisitos')
                            ->schema([
                                Select::make('category')
                                    ->label('Categoría')
                                    ->options([
                                        'Experiencia laboral' => 'Experiencia laboral',
                                        'Área funcional' => 'Área funcional',
                                        'Sector' => 'Sector',
                                        'Subsector' => 'Subsector',
                                        'Educación' => 'Educación',
                                        'Idioma' => 'Idioma',
                                        'Habilidad blanda' => 'Habilidad blanda',
                                        'Habilidad técnica' => 'Habilidad técnica',
                                    ])
                                    ->required(),
                                Select::make('type')
                                    ->label('Tipo')
                                    ->options([
                                        'Obligatorio' => 'Obligatorio',
                                        'Deseable' => 'Deseable',
                                    ])
                                    ->required()
                                    ->live(),
                                TextInput::make('level')
                                    ->label('Nivel requerido')
                                    ->placeholder('Ej: Avanzado, 3 años, etc.'),
                                TextInput::make('weight')
                                    ->label('Peso ponderado (%)')
                                    ->numeric()
                                    ->suffix('%')
                                    ->hidden(fn (Get $get) => $get('type') !== 'Deseable')
                                    ->required(fn (Get $get) => $get('type') === 'Deseable'),
                                TextInput::make('evidence')
                                    ->label('Evidencia verificable')
                                    ->placeholder('Ej: Certificado, Portafolio, etc.')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->defaultItems(1)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
