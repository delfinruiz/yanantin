<?php

namespace App\Filament\Resources\AbsenceRequests\Schemas;

use App\Models\AbsenceType;
use App\Models\EmployeeProfile;
use App\Services\AbsenceService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

use Filament\Forms\Components\Hidden;

class AbsenceRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detalles de la Solicitud')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Hidden::make('employee_profile_id')
                                    ->default(fn () => Auth::user()?->employeeProfile?->id)
                                    ->dehydrated()
                                    ->required(),

                                Select::make('absence_type_id')
                                    ->relationship('type', 'name')
                                    ->label('Tipo de Ausencia')
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        self::updateDays($get, $set);
                                    }),

                                TextInput::make('current_vacation_balance')
                                    ->label('Saldo Vacaciones')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->default(function () {
                                        $user = Auth::user();
                                        return $user?->employeeProfile ? (new AbsenceService())->getVacationBalance($user->employeeProfile) : 0;
                                    })
                                    ->afterStateHydrated(function (TextInput $component, $record) {
                                        // Si estamos editando un registro existente
                                        if ($record && $record->employee) {
                                            $balance = (new AbsenceService())->getVacationBalance($record->employee);
                                            $component->state($balance);
                                            return;
                                        }
                                        // Si estamos creando (y el default no se disparó por alguna razón o recarga)
                                        $user = Auth::user();
                                        $balance = $user?->employeeProfile ? (new AbsenceService())->getVacationBalance($user->employeeProfile) : 0;
                                        $component->state($balance);
                                    })
                                    ->suffix('días'),

                                DatePicker::make('start_date')
                                    ->label('Fecha Inicio')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        self::updateDays($get, $set);
                                    })
                                    ->rule(function (Get $get, $record) {
                                        return function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                            $start = $value;
                                            $end = $get('end_date');
                                            $employeeId = $get('employee_profile_id');
                                            
                                            if ($start && $end && $employeeId) {
                                                $service = new AbsenceService();
                                                $employee = EmployeeProfile::find($employeeId);
                                                
                                                // Pass record ID to exclude it if we are editing
                                                $excludeId = $record?->id;

                                                if ($employee && $service->hasOverlap($employee, $start, $end, $excludeId)) {
                                                     $fail('Existe una solicitud superpuesta en estas fechas.');
                                                }
                                            }
                                        };
                                    }),

                                DatePicker::make('end_date')
                                    ->label('Fecha Término')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        self::updateDays($get, $set);
                                    }),

                                TextInput::make('days_requested')
                                    ->label('Días Calculados')
                                    ->required()
                                    ->numeric()
                                    ->readOnly()
                                    ->helperText('Calculado automáticamente excluyendo fines de semana y feriados.')
                                    ->rule(function (Get $get) {
                                        return function (string $attribute, $value, \Closure $fail) use ($get) {
                                            $typeId = $get('absence_type_id');
                                            $employeeId = $get('employee_profile_id');
                                            
                                            if ($typeId && $employeeId) {
                                                $type = AbsenceType::find($typeId);
                                                if ($type && $type->is_vacation) {
                                                     $employee = EmployeeProfile::find($employeeId);
                                                     $service = new AbsenceService();
                                                     $balance = $service->getVacationBalance($employee);
                                                     if ($value > $balance) {
                                                          $fail("Saldo insuficiente. Disponible: $balance días.");
                                                     }
                                                }
                                            }
                                        };
                                    }),
                            ]),

                        Textarea::make('reason')
                            ->label('Motivo')
                            ->required()
                            ->columnSpanFull(),

                        FileUpload::make('attachments')
                            ->label('Documentos Adjuntos')
                            ->disk('public')
                            ->directory('absence-attachments')
                            ->multiple()
                            ->columnSpanFull(),
                    ]),

                Section::make('Aprobación')
                    ->visible(fn ($record) => $record !== null) // Only visible on edit/view
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('status')
                                    ->options([
                                        'pending' => 'Pendiente',
                                        'approved_supervisor' => 'Aprobado por Supervisor',
                                        'approved_hr' => 'Aprobado por RRHH',
                                        'rejected' => 'Rechazado',
                                    ])
                                    ->disabled(), // Status changes via Actions, not manual edit usually
                                
                                // Placeholder for layout
                            ]),
                            
                        Textarea::make('supervisor_comment')
                            ->label('Comentario Supervisor')
                            ->disabled(function () {
                                /** @var \App\Models\User|null $user */
                                $user = Auth::user();
                                $isSupervisor = $user?->hasRole(['Supervisores', 'Gerencia']) || ($user && $user->supervisedDepartments()->exists());
                                return !$isSupervisor;
                            }),
                        
                        Textarea::make('hr_comment')
                            ->label('Comentario RRHH')
                            ->disabled(function () {
                                /** @var \App\Models\User|null $user */
                                $user = Auth::user();
                                return !($user?->hasRole(['Recursos Humanos']) ?? false);
                            }),
                    ]),
            ]);
    }

    protected static function updateDays(Get $get, Set $set): void
    {
        $start = $get('start_date');
        $end = $get('end_date');
        $typeId = $get('absence_type_id');

        if (!$start || !$end) {
            $set('days_requested', 0);
            return;
        }

        $service = new AbsenceService();
        $days = $service->calculateBusinessDays($start, $end);
        
        $set('days_requested', $days);
    }
}

