<?php

namespace App\Filament\Resources\Nominas\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Checkbox;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Carbon\Carbon;
use Illuminate\Support\Str;

use Filament\Actions\Action;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use App\Services\AbsenceService;
use Filament\Schemas\Components\Livewire;
use App\Models\EvaluationCycle;
use App\Models\EvaluationResult;

use App\Filament\Resources\Nominas\RelationManagers\AbsenceRequestsRelationManager;
use App\Filament\Resources\Nominas\RelationManagers\VacationLedgersRelationManager;
use App\Filament\Resources\Nominas\RelationManagers\TasksRelationManager;
use Filament\Notifications\Notification;
use App\Models\VacationLedger;

class NominaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('nominas.section.user_details'))
                    ->collapsible()
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('user_name')
                                ->label(__('nominas.field.name'))
                                ->afterStateHydrated(function (TextInput $component, $record) {
                                    $component->state($record->user->name);
                                })
                                ->disabled()
                                ->dehydrated(false),
                            TextInput::make('user_email')
                                ->label(__('nominas.field.email'))
                                ->afterStateHydrated(function (TextInput $component, $record) {
                                    $component->state($record->user->email);
                                })
                                ->disabled()
                                ->dehydrated(false),
                            TextInput::make('user_roles')
                                ->label(__('nominas.field.roles'))
                                ->formatStateUsing(fn ($record) => $record?->user->roles->pluck('name')->join(', '))
                                ->afterStateHydrated(function (TextInput $component, $record) {
                                    $component->state($record->user->roles->pluck('name')->join(', '));
                                })
                                ->disabled()
                                ->dehydrated(false),
                            Select::make('department_id')
                                ->label(__('nominas.field.department'))
                                ->options(\App\Models\Department::pluck('name', 'id'))
                                ->searchable()
                                ->preload()
                                ->afterStateHydrated(fn ($component, $record) => $component->state($record->user->departments->first()?->id))
                                ->dehydrated(true)
                                ->required(),
                            Select::make('cargo_id')
                                ->label(__('nominas.field.position'))
                                ->relationship('cargo', 'name')
                                ->createOptionForm([
                                    TextInput::make('name')
                                        ->required()
                                        ->unique('cargos', 'name'),
                                ])
                                ->searchable()
                                ->preload()
                                ->required(),
                            Select::make('contract_type_id')
                                ->label(__('nominas.field.contract_type'))
                                ->relationship('contractType', 'name')
                                ->searchable()
                                ->preload()
                                ->live()
                                ->required()
                                ->createOptionForm([
                                    TextInput::make('name')
                                        ->required()
                                        ->unique('contract_types', 'name'),
                                ]),
                            Select::make('vacation_type_id')
                                ->label(__('nominas.field.vacation_regime'))
                                ->relationship('vacationType', 'name', fn ($query) => $query->where('is_vacation', true))
                                ->searchable()
                                ->preload()
                                ->required()
                                ->helperText(__('nominas.field.vacation_regime_helper')),
                            Select::make('reports_to')
                                ->label('Jefe Directo / Reporta a')
                                ->relationship('boss', 'name')
                                ->searchable()
                                ->preload()
                                ->required(fn ($record) => ! ($record?->user?->hasRole('super_admin') ?? false))
                                ->helperText('Persona que aprobará sus objetivos y solicitudes. (Opcional para CEO/Super Admin)'),
                        ]),
                    ]),

                Section::make(__('nominas.section.employee_profile'))
                    ->collapsible()
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)->schema([
                                    TextInput::make('rut')
                                        ->label(__('nominas.field.rut'))
                                        ->required()
                                        ->maxLength(20),
                                    DatePicker::make('birth_date')
                                        ->label(__('nominas.field.birth_date'))
                                        ->required()
                                        ->native(false)
                                        ->displayFormat('d-m-Y')
                                        ->live(onBlur: true)
                                        ->maxDate(now())
                                        ->afterStateUpdated(function ($state, Set $set) {
                                            $set('age', $state ? Carbon::parse($state)->age . ' años' : null);
                                        }),
                                    TextInput::make('age')
                                        ->label(__('nominas.field.age'))
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->afterStateHydrated(function (TextInput $component, $record) {
                                            $component->state($record?->birth_date ? Carbon::parse($record->birth_date)->age . ' años' : null);
                                        }),
                                    Select::make('gender')
                                        ->label(__('nominas.field.gender'))
                                        ->options([
                                            'masculino' =>(__('nominas.options.gender.masculino')),
                                            'femenino' =>(__('nominas.options.gender.femenino')),
                                            'no_binario' =>(__('nominas.options.gender.no_binario')),
                                            'otro' =>(__('nominas.options.gender.otro')),
                                            'prefiere_no_informar' =>(__('nominas.options.gender.prefiere_no_informar')),
                                        ])
                                        ->required(),
                                    DatePicker::make('contract_date')
                                        ->label(__('nominas.field.contract_date'))
                                        ->required(),
                                    DatePicker::make('contract_end_date')
                                        ->label(__('nominas.field.contract_end_date'))
                                        ->disabled(function (Get $get) {
                                            $contractTypeId = $get('contract_type_id');
                                            if (! $contractTypeId) {
                                                return true;
                                            }
                                            $type = \App\Models\ContractType::find($contractTypeId);
                                            // Comparación insensible a mayúsculas y espacios
                                            return ! ($type && Str::contains(Str::lower($type->name), 'plazo fijo'));
                                        })
                                        ->required(function (Get $get) {
                                            $contractTypeId = $get('contract_type_id');
                                            if (! $contractTypeId) {
                                                return false;
                                            }
                                            $type = \App\Models\ContractType::find($contractTypeId);
                                            return $type && Str::contains(Str::lower($type->name), 'plazo fijo');
                                        }),
                                    Select::make('health_insurance')
                                        ->label(__('nominas.field.health_insurance'))
                                        ->options([
                                            'fonasa' =>(__('nominas.options.health_insurance.fonasa')),
                                            'isapre' =>(__('nominas.options.health_insurance.isapre')),
                                            'other' =>(__('nominas.options.health_insurance.other')),
                                        ])
                                        ->required(),
                                    Select::make('labor_inclusion')
                                        ->label(__('nominas.field.labor_inclusion'))
                                        ->options(__('nominas.options.labor_inclusion'))
                                        ->required(),
                                    Checkbox::make('disability')
                                        ->label(__('nominas.field.disability'))
                                        ->inline(false)
                                        ->hidden(),
                                    TextInput::make('address')
                                        ->label(__('nominas.field.address'))
                                        ->maxLength(255)
                                        ->required()
                                        ->columnSpanFull(),
                                    TextInput::make('profession')
                                        ->label(__('nominas.field.profession'))
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('phone')
                                        ->label(__('nominas.field.phone'))
                                        ->tel()
                                        ->required(),
                                    TextInput::make('emergency_contact_name')
                                        ->label(__('nominas.field.emergency_contact_name'))
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('emergency_phone')
                                        ->label(__('nominas.field.emergency_phone'))
                                        ->tel()
                                        ->required(),
                                    
                                    Fieldset::make(__('nominas.section.bank_details'))
                                        ->schema([
                                            TextInput::make('bank_name')
                                                ->label(__('nominas.field.bank_name'))
                                                ->maxLength(255),
                                            Select::make('account_type')
                                                ->label(__('nominas.field.account_type'))
                                                ->options(__('nominas.options.account_type')),
                                            TextInput::make('account_number')
                                                ->label(__('nominas.field.account_number'))
                                                ->maxLength(255),
                                        ])
                                        ->columns(3)
                                        ->columnSpanFull(),
                                ]),
                    ]),

            
            Tabs::make('Categories')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make(__('nominas.section.medical_licenses'))
                            ->schema([
                                Repeater::make('medical_licenses')
                                    ->label(__('nominas.section.medical_licenses'))
                                    ->relationship('medicalLicenses')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            Select::make('absence_type')
                                                ->label(__('nominas.field.absence_type'))
                                                ->options(__('nominas.options.absence_type'))
                                                ->required(),

                                            DatePicker::make('start_date')
                                                ->label(__('nominas.field.start_date'))
                                                ->required()
                                                ->live()
                                                ->afterStateUpdated(function (Get $get, Set $set) {
                                                    self::calculateDuration($get, $set);
                                                }),

                                            DatePicker::make('end_date')
                                                ->label(__('nominas.field.end_date'))
                                                ->required()
                                                ->live()
                                                ->afterStateUpdated(function (Get $get, Set $set) {
                                                    self::calculateDuration($get, $set);
                                                })
                                                ->rule(function (Get $get) {
                                                    return function (string $attribute, $value, \Closure $fail) use ($get) {
                                                        $startDate = $get('start_date');
                                                        if (!$startDate || !$value) return;

                                                        $start = Carbon::parse($startDate);
                                                        $end = Carbon::parse($value);

                                                        if ($end->lt($start)) {
                                                            $fail(__('nominas.validation.end_date_after_start_date'));
                                                            return;
                                                        }
                                                    };
                                                }),
                                        ]),

                                        Grid::make(3)->schema([
                                            TextInput::make('duration_days')
                                                ->label(__('nominas.field.duration_days'))
                                                ->numeric()
                                                ->readOnly()
                                                ->required(),

                                            TextInput::make('reason')
                                                ->label(__('nominas.field.reason'))
                                                ->required()
                                                ->maxLength(255)
                                                ->placeholder(__('nominas.placeholder.example_surgery'))
                                                ->columnSpan(2),
                                        ]),

                                        Textarea::make('diagnosis')
                                            ->label(__('nominas.field.diagnosis'))
                                            ->rows(2)
                                            ->columnSpanFull(),

                                        TextInput::make('code')
                                            ->label(__('nominas.field.code'))
                                            ->placeholder(__('nominas.placeholder.optional')),

                                        Section::make(__('nominas.section.professional_id'))
                                            ->schema([
                                                Grid::make(4)->schema([
                                                    TextInput::make('professional_lastname_father')
                                                        ->label(__('nominas.field.professional_lastname_father')),
                                                    TextInput::make('professional_lastname_mother')
                                                        ->label(__('nominas.field.professional_lastname_mother')),
                                                    TextInput::make('professional_names')
                                                        ->label(__('nominas.field.professional_names'))
                                                        ->columnSpan(2),
                                                ]),
                                                Grid::make(3)->schema([
                                                    TextInput::make('professional_rut')
                                                        ->label(__('nominas.field.professional_rut')),
                                                    TextInput::make('professional_specialty')
                                                        ->label(__('nominas.field.professional_specialty')),
                                                    Select::make('professional_type')
                                                        ->label(__('nominas.field.professional_type'))
                                                        ->options(__('nominas.options.professional_type')),
                                                ]),
                                                Grid::make(3)->schema([
                                                    TextInput::make('professional_registry_code')
                                                        ->label(__('nominas.field.professional_registry_code')),
                                                    TextInput::make('professional_email')
                                                        ->label(__('nominas.field.professional_email'))
                                                        ->email(),
                                                    TextInput::make('professional_phone')
                                                        ->label(__('nominas.field.professional_phone'))
                                                        ->tel(),
                                                ]),
                                            ])
                                            ->collapsible(),

                                        FileUpload::make('attachments')
                                            ->label(__('nominas.field.attachments'))
                                            ->disk('public')
                                            ->directory('medical-licenses')
                                            ->visibility('public')
                                            ->multiple()
                                            ->downloadable()
                                            ->openable()
                                            ->previewable()
                                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                                            ->maxSize(10240) // 10MB
                                            ->columnSpanFull()
                                            ->helperText(__('nominas.field.attachments_helper')),
                                    ])
                                    ->columns(1)
                                    ->itemLabel(function (array $state): ?string {
                                        $label = $state['reason'] ?? __('nominas.field.default_license_label');
                                        if (isset($state['start_date']) && isset($state['end_date'])) {
                                            $start = Carbon::parse($state['start_date'])->format('d-m-Y');
                                            $end = Carbon::parse($state['end_date'])->format('d-m-Y');
                                            return "{$label} ({$start} / {$end})";
                                        }
                                        return $label;
                                    })
                                    ->collapsed(),
                            ]),

                        Tab::make(__('nominas.section.vacations'))
                            ->schema([
                                TextInput::make('vacation_balance')
                                    ->label(__('nominas.field.vacation_balance'))
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->hintAction(
                                        Action::make('recalculate')
                                            ->icon('heroicon-m-arrow-path')
                                            ->label(__('nominas.action.recalculate_balance'))
                                            ->requiresConfirmation()
                                            ->modalHeading(__('nominas.action.recalculate_heading'))
                                            ->modalDescription(__('nominas.action.recalculate_description'))
                                            ->action(function ($record, Set $set) {
                                                if (!$record || !$record->contract_date) {
                                                    Notification::make()
                                                        ->title('Error')
                                                        ->body(__('nominas.notification.error_no_contract_date'))
                                                        ->danger()
                                                        ->send();
                                                    return;
                                                }

                                                $service = new AbsenceService();
                                                
                                                // 1. Calcular devengado teórico
                                                $theoretical = $service->calculateTheoreticalAccrual($record);
                                                
                                                // 2. Calcular saldo real actual en ledger (suma de abonos - usos)
                                                // Ojo: getVacationBalance es (abonos - usos)
                                                // Pero queremos saber cuánto se ha "abonado" vs "teórico".
                                                // Si simplemente comparamos saldo final teórico vs saldo final real, asumimos que los usos están bien registrados.
                                                // El problema es que faltan los "abonos" (accruals).
                                                
                                                // Enfoque seguro:
                                                // Calcular cuánto se ha abonado históricamente (type=accrual o adjustment positivo)
                                                // $totalAccrued = $record->vacationLedgers()->where('days', '>', 0)->sum('days'); 
                                                // NO, porque los ajustes pueden ser negativos.
                                                
                                                // Mejor enfoque simplificado para "Inicialización":
                                                // Si el saldo actual es 0 (o muy bajo) y el teórico es alto (ej 60), agregamos la diferencia.
                                                // Pero si ya gastó vacaciones sin registrarlas, le regalaremos días.
                                                // Asumimos que si está en 0 es porque FALTA la carga inicial.
                                                
                                                $currentBalance = $service->getVacationBalance($record);
                                                
                                                if ($currentBalance >= $theoretical) {
                                                    Notification::make()
                                                        ->title(__('nominas.notification.no_changes'))
                                                        ->body(__('nominas.notification.no_changes_body', ['current' => $currentBalance, 'theoretical' => $theoretical]))
                                                        ->warning()
                                                        ->send();
                                                    return;
                                                }

                                                $diff = $theoretical - $currentBalance;

                                                if ($diff > 0) {
                                                    VacationLedger::create([
                                                        'employee_profile_id' => $record->id,
                                                        'days' => $diff,
                                                        'type' => 'adjustment',
                                                        'description' => 'Ajuste automático por recálculo histórico',
                                                        'created_at' => now(),
                                                    ]);

                                                    $newBalance = $service->getVacationBalance($record);
                                                    $set('vacation_balance', $newBalance . ' días hábiles');
                                                    
                                                    Notification::make()
                                                        ->title(__('nominas.notification.balance_updated'))
                                                        ->body(__('nominas.notification.balance_updated_body', ['diff' => $diff, 'new_balance' => $newBalance]))
                                                        ->success()
                                                        ->send();
                                                }
                                            })
                                    )
                                    ->afterStateHydrated(function (TextInput $component, $record) {
                                        if (!$record) {
                                            $component->state('0 días');
                                        } else {
                                            $service = new AbsenceService();
                                            $balance = $service->getVacationBalance($record);
                                            $component->state($balance . ' días hábiles');
                                        }
                                    }),

                                Livewire::make(AbsenceRequestsRelationManager::class)
                                    ->data(fn ($record) => [
                                        'ownerRecord' => $record,
                                        'pageClass' => \App\Filament\Resources\Nominas\Pages\EditNomina::class,
                                    ])
                                    ->key('absence-requests-manager')
                                    ->hidden(fn ($record) => $record === null),
                                
                                Livewire::make(VacationLedgersRelationManager::class)
                                    ->data(fn ($record) => [
                                        'ownerRecord' => $record,
                                        'pageClass' => \App\Filament\Resources\Nominas\Pages\EditNomina::class,
                                    ])
                                    ->key('vacation-ledgers-manager')
                                    ->hidden(fn ($record) => $record === null),
                            ]),
                        Tab::make(__('nominas.section.children'))
                            ->schema([
                                Repeater::make('children')
                                    ->label(__('nominas.section.children'))
                                    ->schema([
                                        Grid::make(2)->schema([
                                            TextInput::make('name')
                                                ->label(__('nominas.field.name'))
                                                ->required(),
                                            TextInput::make('rut')
                                                ->label(__('nominas.field.rut')),
                                            DatePicker::make('birth_date')
                                                ->label(__('nominas.field.birth_date'))
                                                ->required()
                                                ->native(false)
                                                ->maxDate(now())
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function ($state, Set $set) {
                                                    $set('age', $state ? Carbon::parse($state)->age . ' ' . __('nominas.field.years') : null);
                                                }),
                                            TextInput::make('age')
                                                ->label(__('nominas.field.age'))
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->afterStateHydrated(function (TextInput $component, Get $get) {
                                                    $birthDate = $get('birth_date');
                                                    if ($birthDate) {
                                                        $component->state(Carbon::parse($birthDate)->age . ' ' . __('nominas.field.years'));
                                                    }
                                                }),
                                            Checkbox::make('is_dependent')
                                                ->label(__('nominas.field.is_dependent'))
                                                ->inline(false),
                                            Checkbox::make('has_disability')
                                                ->label(__('nominas.field.has_disability'))
                                                ->inline(false),
                                        ]),

                                        Section::make(__('nominas.section.mother_data'))
                                            ->schema([
                                                Grid::make(2)->schema([
                                                    TextInput::make('mother_name')->label(__('nominas.field.mother_name')),
                                                    TextInput::make('mother_rut')->label(__('nominas.field.mother_rut')),
                                                ])
                                            ])->collapsible()->compact(),
                                    ])
                                    ->itemLabel(fn (array $state): ?string => ($state['name'] ?? null) . (isset($state['birth_date']) ? ' - ' . Carbon::parse($state['birth_date'])->age . ' ' . __('nominas.field.years') : ''))
                                    ->addActionLabel(__('nominas.action.add_child'))
                                    ->reorderable(false)
                                    ->collapsible()
                                    ->collapsed(),
                            ]),
                        Tab::make(__('nominas.section.evaluations'))
                            ->schema([
                                Section::make('Ciclo Vigente')
                                    ->description('Información del ciclo de evaluación más reciente.')
                                    ->schema([
                                        Grid::make(3)->schema([
                                            TextInput::make('cycle_name')
                                                ->label('Ciclo')
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->formatStateUsing(fn () => EvaluationCycle::query()->orderByDesc('starts_at')->first()?->name ?? 'N/A'),
                                            TextInput::make('cycle_start')
                                                ->label('Inicio')
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->formatStateUsing(fn () => EvaluationCycle::query()->orderByDesc('starts_at')->first()?->starts_at?->format('d/m/Y') ?? 'N/A'),
                                            TextInput::make('cycle_end')
                                                ->label('Fin')
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->formatStateUsing(fn () => EvaluationCycle::query()->orderByDesc('starts_at')->first()?->ends_at?->format('d/m/Y') ?? 'N/A'),
                                        ]),
                                    ]),

                                Section::make('Resultados (Ciclo Vigente)')
                                    ->schema([
                                        Grid::make(3)->schema([
                                            TextInput::make('final_score')
                                                ->label('Puntaje Final')
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->formatStateUsing(function ($record) {
                                                    if (!$record || !$record->user) return 'N/A';
                                                    $user = $record->user;
                                                    $cycle = EvaluationCycle::query()->orderByDesc('starts_at')->first();
                                                    $score = EvaluationResult::where('user_id', $user->id)
                                                        ->where('evaluation_cycle_id', $cycle?->id)
                                                        ->first()?->final_score;
                                                    return $score !== null ? $score . '%' : 'Pendiente';
                                                }),
                                            TextInput::make('classification')
                                                ->label('Clasificación')
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->formatStateUsing(function ($record) {
                                                    if (!$record || !$record->user) return 'N/A';
                                                    $user = $record->user;
                                                    $cycle = EvaluationCycle::query()->orderByDesc('starts_at')->first();
                                                    return EvaluationResult::where('user_id', $user->id)
                                                        ->where('evaluation_cycle_id', $cycle?->id)
                                                        ->first()?->range?->name ?? 'Sin Clasificar';
                                                }),
                                            TextInput::make('bonus')
                                                ->label('Bono')
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->formatStateUsing(function ($record) {
                                                    if (!$record || !$record->user) return 'N/A';
                                                    $user = $record->user;
                                                    $cycle = EvaluationCycle::query()->orderByDesc('starts_at')->first();
                                                    $amount = EvaluationResult::where('user_id', $user->id)
                                                        ->where('evaluation_cycle_id', $cycle?->id)
                                                        ->first()?->bonus_amount;
                                                    return $amount ? '$' . number_format($amount, 0) : 'N/A';
                                                }),
                                        ]),
                                    ]),

                                Livewire::make(\App\Filament\Resources\Nominas\RelationManagers\EvaluationsRelationManager::class)
                                    ->data(fn ($record) => [
                                        'ownerRecord' => $record,
                                        'pageClass' => \App\Filament\Resources\Nominas\Pages\EditNomina::class,
                                    ])
                                    ->key('evaluations-manager')
                                    ->hidden(fn ($record) => $record === null),

                                Section::make('Histórico de Resultados')
                                    ->schema([
                                        Livewire::make(\App\Filament\Resources\Nominas\RelationManagers\EvaluationResultsRelationManager::class)
                                            ->data(fn ($record) => [
                                                'ownerRecord' => $record,
                                                'pageClass' => \App\Filament\Resources\Nominas\Pages\EditNomina::class,
                                            ])
                                            ->key('evaluation-results-manager')
                                            ->hidden(fn ($record) => $record === null),
                                    ])
                                    ->collapsible()
                                    ->collapsed(),
                            ]),
                        Tab::make(__('nominas.section.tasks'))
                            ->schema([
                                Livewire::make(TasksRelationManager::class)
                                    ->data(fn ($record) => [
                                        'ownerRecord' => $record,
                                        'pageClass' => \App\Filament\Resources\Nominas\Pages\EditNomina::class,
                                    ])
                                    ->key('tasks-manager')
                                    ->hidden(fn ($record) => $record === null),
                            ]),
                        Tab::make(__('nominas.section.trainings'))
                            ->schema([
                                Repeater::make('trainings')
                                    ->label(__('nominas.field.trainings_repeater'))
                                    ->schema([
                                        Grid::make(2)->schema([
                                            TextInput::make('name')
                                                ->label(__('nominas.field.training_name'))
                                                ->required()
                                                ->columnSpanFull(),
                                            TextInput::make('institution')
                                                ->label(__('nominas.field.institution')),
                                            DatePicker::make('date')
                                                ->label(__('nominas.field.date'))
                                                ->native(false),
                                            TextInput::make('hours')
                                                ->label(__('nominas.field.hours'))
                                                ->numeric(),
                                            Select::make('status')
                                                ->label(__('nominas.field.status'))
                                                ->options(__('nominas.options.training_status'))
                                                ->default('completed'),
                                        ]),
                                        Textarea::make('description')
                                            ->label(__('nominas.field.description'))
                                            ->rows(2)
                                            ->columnSpanFull(),
                                        FileUpload::make('certificate')
                                            ->label(__('nominas.field.certificate'))
                                            ->directory('trainings_certificates')
                                            ->downloadable()
                                            ->openable()
                                            ->columnSpanFull(),
                                    ])
                                    ->itemLabel(fn (array $state): ?string => ($state['name'] ?? null) . (isset($state['institution']) ? ' - ' . $state['institution'] : ''))
                                    ->addActionLabel(__('nominas.field.training_add'))
                                    ->collapsible()
                                    ->collapsed()
                                    ->reorderable(false),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }

    protected static function calculateDuration(Get $get, Set $set): void
    {
        $start = $get('start_date');
        $end = $get('end_date');

        if ($start && $end) {
            $startDate = Carbon::parse($start);
            $endDate = Carbon::parse($end);
            
            if ($endDate->gte($startDate)) {
                $diff = $startDate->diffInDays($endDate) + 1; // Inclusive
                $set('duration_days', $diff);
            } else {
                $set('duration_days', 0);
            }
        }
    }
}
