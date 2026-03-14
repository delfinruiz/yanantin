<?php

namespace App\Filament\Pages;

use App\Models\JobApplication;
use App\Models\JobOffer;
use App\Models\CandidateProfile;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Html;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Components\Section as FormSection;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\ViewField;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Illuminate\Support\HtmlString;

class PublicCandidateDashboard extends Page implements HasTable, HasInfolists, HasForms
{
    use InteractsWithTable;
    use InteractsWithInfolists;
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-briefcase';
    protected static string|\UnitEnum|null $navigationGroup = 'Mis Aplicaciones';
    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.pages.public-dashboard';

    public ?array $curriculumData = [];
    public bool $isFinished = false; // Propiedad para controlar el estado del Wizard
    
    // Propiedad para controlar la pestaña activa (reemplaza a los tabs generados por schema)
    public string $activeTab = 'offers';
    
    // queryString para mantener el estado en la URL (opcional pero recomendado)
    protected $queryString = [
        'activeTab' => ['except' => 'offers'],
    ];

    public function mount(): void
    {
        $this->fillCurriculumForm();
    }

    protected function fillCurriculumForm(): void
    {
        $profile = CandidateProfile::where('user_id', Auth::id())->first();
        if ($profile) {
            $data = $profile->toArray();
            $data['name'] = Auth::user()->name;
            $data['email'] = Auth::user()->email;
            
            // Set has_no_experience checkbox state based on stored data
            if (isset($data['work_experience']) && empty($data['work_experience'])) {
                $data['has_no_experience'] = true;
            }

            // Calculate age if birth_date exists
            if (isset($data['birth_date'])) {
                $data['age'] = Carbon::parse($data['birth_date'])->age;
            }
            
            $this->curriculumForm->fill($data);
        } else {
            $this->curriculumForm->fill([
                'name' => Auth::user()->name,
                'email' => Auth::user()->email,
                'phone' => Auth::user()->phone, 
            ]);
        }
    }

    public function getForms(): array
    {
        return [
            'curriculumForm',
        ];
    }

    public function curriculumForm(Schema $schema): Schema
    {
        $steps = [
            Step::make('Perfil Personal y Académico')
                ->afterValidation(function () {
                    $this->saveCurriculum(false, true, false);
                })
                ->schema([
                    FormSection::make('Información Personal')
                        ->collapsible()
                        ->schema([
                            TextInput::make('name')
                                ->label('Nombre')
                                ->readOnly(),
                            TextInput::make('email')
                                ->label('Email')
                                ->readOnly(),
                            TextInput::make('phone')
                                ->label('Número celular')
                                ->tel()
                                ->required(),
                            Select::make('country')
                                ->label('País')
                                ->options(fn () => JobOffer::query()
                                    ->whereNotNull('country')
                                    ->distinct()
                                    ->pluck('country', 'country')
                                    ->toArray())
                                ->required()
                                ->searchable()
                                ->live(),
                            Select::make('city')
                                ->label('Ciudad actual')
                                ->options(function (Get $get) {
                                    $country = $get('country');
                                    $query = JobOffer::query()
                                        ->whereNotNull('city')
                                        ->distinct();
                                        
                                    if ($country) {
                                        $query->where('country', $country);
                                    }
                                    
                                    return $query->pluck('city', 'city')->toArray();
                                })
                                ->required()
                                ->searchable(),
                            TextInput::make('rut')
                                ->label('Rut / DNI')
                                ->required(),
                            DatePicker::make('birth_date')
                                ->label('Fecha de nacimiento')
                                ->required()
                                ->live()
                                ->maxDate(now()->subYears(18))
                                ->native(false)
                                ->displayFormat('d/m/Y')
                                ->validationMessages([
                                    'max_date' => 'Debes ser mayor de 18 años para continuar.',
                                ])
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if ($state) {
                                        $age = Carbon::parse($state)->age;
                                        $set('age', $age);
                                    }
                                }),
                            TextInput::make('age')
                                ->label('Edad')
                                ->disabled()
                                ->dehydrated(false),
                            Select::make('relocation_availability')
                                ->label('Disponibilidad para traslado')
                                ->options([
                                    1 => 'Sí',
                                    0 => 'No',
                                ])
                                ->default(0),
                            Select::make('modality_availability')
                                ->label('Disponibilidad de modalidad')
                                ->options([
                                    'Presencial' => 'Presencial',
                                    'Remoto' => 'Remoto',
                                    'Híbrido' => 'Híbrido',
                                ])
                                ->required(),
                            TextInput::make('portfolio_url')
                                ->label('Portafolio (URL)')
                                ->url()
                                ->suffixIcon('heroicon-m-globe-alt'),
                            TextInput::make('linkedin_url')
                                ->label('LinkedIn (URL)')
                                ->url()
                                ->suffixIcon('heroicon-m-link'),
                        ])->columns(2),

                    FormSection::make('Formación Académica')
                        ->collapsible()
                        ->schema([
                            Repeater::make('education')
                                ->label('Formación Académica')
                                ->reorderable(false)
                                ->collapsible()
                                ->collapsed()
                                ->itemLabel(fn (array $state): ?string => $state['institution'] ?? 'Nueva Formación')
                                ->minItems(1)
                                ->validationMessages([
                                    'min' => 'Debe agregar al menos una formación académica.',
                                ])
                                ->components([
                                    Select::make('level')
                                        ->label('Nivel de formación')
                                        ->options([
                                            'Secundaria' => 'Secundaria',
                                            'Técnico' => 'Técnico',
                                            'Universitario' => 'Universitario',
                                            'Postgrado' => 'Postgrado',
                                            'Doctorado' => 'Doctorado',
                                        ])
                                        ->required(),
                                    TextInput::make('institution')
                                        ->label('Institución')
                                        ->required(),
                                    TextInput::make('title')
                                        ->label('Título obtenido')
                                        ->required(),
                                    Select::make('status')
                                        ->label('Estado')
                                        ->options([
                                            'En curso' => 'En curso',
                                            'Finalizado' => 'Finalizado',
                                        ])
                                        ->required(),
                                    DatePicker::make('start_date')
                                        ->label('Fecha inicio')
                                        ->required()
                                        ->native(false)
                                        ->displayFormat('d/m/Y'),
                                    DatePicker::make('end_date')
                                        ->label('Fecha fin')
                                        ->native(false)
                                        ->displayFormat('d/m/Y'),
                                    Textarea::make('certifications')
                                        ->label('Certificaciones asociadas'),
                                    FileUpload::make('attachment')
                                        ->label('Adjuntar certificados o diplomas')
                                        ->disk('public')
                                        ->directory('candidate-attachments')
                                        ->visibility('public'),
                                ])
                                ->columns(2)
                                ->columnSpanFull(),
                        ]),
                ]),

            Step::make('Experiencia Laboral')
                ->afterValidation(function () {
                    $this->saveCurriculum(false, true, false);
                })
                ->components([
                    Checkbox::make('has_no_experience')
                        ->label('No tengo experiencia laboral')
                        ->live()
                        ->dehydrated(false)
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state) {
                                $set('work_experience', []);
                            }
                        }),
                    Repeater::make('work_experience')
                        ->label('Experiencia Laboral')
                        ->visible(fn (Get $get) => ! $get('has_no_experience'))
                        ->reorderable(false)
                        ->collapsible()
                        ->collapsed()
                        ->itemLabel(fn (array $state): ?string => $state['company'] ?? 'Nueva Experiencia')
                        ->validationMessages([
                            'min' => 'Debe agregar al menos una experiencia laboral o marcar que no tiene experiencia.',
                        ])
                        ->rules([
                            function (Get $get) {
                                return function (string $attribute, $value, \Closure $fail) use ($get) {
                                    if ($get('has_no_experience')) {
                                        return;
                                    }
                                    
                                    if (empty($value)) {
                                        $fail('Debe agregar al menos una experiencia laboral o marcar que no tiene experiencia.');
                                        return;
                                    }

                                    // Check for overlaps
                                    $experiences = collect($value)->sortBy('start_date')->values();
                                    
                                    for ($i = 0; $i < $experiences->count() - 1; $i++) {
                                        $current = $experiences[$i];
                                        $next = $experiences[$i+1];
                                        
                                        if (empty($current['end_date'])) continue; // Current is ongoing

                                        $currentEnd = Carbon::parse($current['end_date']);
                                        $nextStart = Carbon::parse($next['start_date']);

                                        if ($nextStart->lt($currentEnd)) {
                                            // Overlap detected
                                            // Check if justification exists in the overlapping item (next)
                                            if (empty($next['overlap_justification'])) {
                                                $fail("Se detectó un traslape laboral entre '{$current['company']}' y '{$next['company']}'. Debe proporcionar una justificación en el campo correspondiente.");
                                            }
                                        }
                                    }
                                };
                            },
                        ])
                        ->components([
                            TextInput::make('company')
                                ->label('Empresa')
                                ->required(),
                            TextInput::make('sector')
                                ->label('Sector')
                                ->required(),
                            TextInput::make('company_size')
                                ->label('Tamaño de empresa (nº trabajadores)')
                                ->numeric(),
                            TextInput::make('functional_area')
                                ->label('Área funcional')
                                ->required(),
                            TextInput::make('position')
                                ->label('Cargo desempeñado')
                                ->required(),
                            Select::make('contract_type')
                                ->label('Tipo de contrato')
                                ->options([
                                    'Indefinido' => 'Indefinido',
                                    'Plazo Fijo' => 'Plazo Fijo',
                                    'Por Obra o Faena' => 'Por Obra o Faena',
                                    'Honorarios' => 'Honorarios',
                                    'Práctica' => 'Práctica',
                                    'Part-time' => 'Part-time',
                                ]),
                            DatePicker::make('start_date')
                                ->label('Fecha inicio')
                                ->required(),
                            DatePicker::make('end_date')
                                ->label('Fecha fin'),
                            Textarea::make('functions')
                                ->label('Funciones principales')
                                ->required(),
                            Textarea::make('achievements')
                                ->label('Logros cuantificables'),
                            Textarea::make('kpis')
                                ->label('KPIs cumplidos'),
                            Textarea::make('exit_reason')
                                ->label('Motivo de salida'),
                            TextInput::make('overlap_justification')
                                ->label('Justificación de traslape laboral')
                                ->helperText('Requerido si las fechas se superponen con otra experiencia.'),
                        ])
                        ->columns(2)
                        ->columnSpanFull()
                        ->validationAttribute('Experiencia Laboral'),
                ]),

            Step::make('Habilidades e Idiomas')
                ->afterValidation(function () {
                    $this->saveCurriculum(false, true, false);
                })
                ->components([
                    FormSection::make('Idiomas')
                        ->collapsible()
                        ->schema([
                            Repeater::make('languages')
                                ->label('Idiomas')
                                ->reorderable(false)
                                ->collapsible()
                                ->collapsed()
                                ->itemLabel(fn (array $state): ?string => $state['language'] ?? 'Nuevo Idioma')
                                ->minItems(1)
                                ->validationMessages([
                                    'min' => 'Debe agregar al menos un idioma.',
                                ])
                                ->components([
                                    Select::make('language')
                                        ->label('Idioma')
                                        ->options([
                                            'Español' => 'Español',
                                            'Inglés' => 'Inglés',
                                            'Portugués' => 'Portugués',
                                            'Francés' => 'Francés',
                                            'Alemán' => 'Alemán',
                                            'Italiano' => 'Italiano',
                                            'Chino Mandarín' => 'Chino Mandarín',
                                            'Japonés' => 'Japonés',
                                        ])
                                        ->searchable()
                                        ->required(),
                                    Select::make('level')
                                        ->label('Nivel')
                                        ->options([
                                            'Básico' => 'Básico',
                                            'Intermedio' => 'Intermedio',
                                            'Avanzado' => 'Avanzado',
                                            'Nativo' => 'Nativo',
                                        ])
                                        ->required(),
                                    FileUpload::make('attachment')
                                ->label('Adjuntar Certificación (opcional)')
                                ->disk('public')
                                ->directory('candidate-attachments')
                                ->visibility('public'),
                                ])
                                ->columns(2)
                                ->columnSpanFull(),
                        ]),
                    
                    FormSection::make('Habilidades Técnicas')
                        ->description('Incluya software, metodologías, herramientas o conocimientos técnicos específicos (Ej: Excel Avanzado, Gestión de Proyectos, Python, Maquinaria pesada, etc.)')
                        ->collapsible()
                        ->schema([
                            Repeater::make('technical_skills')
                                ->label('Habilidades Técnicas')
                                ->reorderable(false)
                                ->collapsible()
                                ->collapsed()
                                ->itemLabel(fn (array $state): ?string => $state['software'] ?? 'Nueva Habilidad Técnica')
                                ->minItems(1)
                                ->validationMessages([
                                    'min' => 'Debe agregar al menos una habilidad técnica.',
                                ])
                                ->components([
                                    TextInput::make('software')
                                        ->label('Nombre de la Habilidad')
                                        ->required(),
                                    Select::make('level')
                                        ->label('Nivel')
                                        ->options([
                                            'Básico' => 'Básico',
                                            'Intermedio' => 'Intermedio',
                                            'Avanzado' => 'Avanzado',
                                        ])
                                        ->required(),
                                    TextInput::make('methodologies')
                                        ->label('Metodologías'),
                                    TextInput::make('tools')
                                        ->label('Herramientas específicas'),
                                    TextInput::make('certifications')
                                        ->label('Certificaciones técnicas'),
                                ])
                                ->columns(2)
                                ->columnSpanFull(),
                        ]),

                    FormSection::make('Habilidades Blandas')
                        ->description('Seleccione las competencias interpersonales que mejor lo describan y proporcione un ejemplo breve de cómo las ha aplicado.')
                        ->collapsible()
                        ->schema([
                            Repeater::make('soft_skills')
                                ->label('Autoevaluación de Habilidades Blandas')
                                ->reorderable(false)
                                ->collapsible()
                                ->collapsed()
                                ->itemLabel(fn (array $state): ?string => $state['skill'] ?? 'Nueva Habilidad Blanda')
                                ->minItems(1)
                                ->validationMessages([
                                    'min' => 'Debe agregar al menos una habilidad blanda.',
                                ])
                                ->components([
                                    Select::make('skill')
                                        ->label('Habilidad')
                                        ->options([
                                            'Liderazgo' => 'Liderazgo',
                                            'Pensamiento crítico' => 'Pensamiento crítico',
                                            'Adaptabilidad' => 'Adaptabilidad',
                                            'Trabajo en equipo' => 'Trabajo en equipo',
                                            'Comunicación' => 'Comunicación',
                                        ])
                                        ->required(),
                                    Select::make('level')
                                        ->label('Nivel (1-5)')
                                        ->options([
                                            1 => '1 - Bajo',
                                            2 => '2 - Regular',
                                            3 => '3 - Medio',
                                            4 => '4 - Alto',
                                            5 => '5 - Excelente',
                                        ])
                                        ->required(),
                                    Textarea::make('evidence')
                                        ->label('Evidencia o ejemplo breve')
                                        ->required(),
                                ])
                                ->columns(1)
                                ->columnSpanFull()
                                ->defaultItems(0)
                                ->addable(true)
                                ->deletable(true),
                        ]),
                ]),

            Step::make('Información Final')
                ->id('final-info')
                ->afterValidation(function () {
                     $this->saveCurriculum(true, false, true);
                })
                ->components([
                    FormSection::make('Información Complementaria')
                        ->collapsible()
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    TextInput::make('salary_expectation')
                                        ->label('Aspiración salarial')
                                        ->numeric()
                                        ->prefix('$')
                                        ->required()
                                        ->validationMessages([
                                            'required' => 'El campo aspiración salarial es obligatorio.',
                                        ]),
                                    Select::make('currency')
                                        ->label('Tipo de Moneda')
                                        ->options([
                                            'CLP' => 'Peso Chileno (CLP)',
                                            'USD' => 'Dólar Estadounidense (USD)',
                                            'ARS' => 'Peso Argentino (ARS)',
                                            'BRL' => 'Peso Argentino (BRL)',
                                            'COP' => 'Peso Colombiano (COP)',
                                            'MXN' => 'Peso Mexicano (MXN)',
                                            'PEN' => 'Peso Peruano (PEN)',
                                            'BOB' => 'Sol Peruano (BOB)',
                                            'UYU' => 'Sol Peruano (UYU)',
                                            'PYG' => 'Guaraní Paraguayo (PYG)',
                                            'VES' => 'Bolívar Venezolano (VES)',
                                        ])
                                        ->searchable()
                                        ->required()
                                        ->validationMessages([
                                            'required' => 'El campo tipo de moneda es obligatorio.',
                                        ]),
                                ]),
                            Checkbox::make('immediate_availability')
                                ->label('Disponibilidad inmediata'),
                            Repeater::make('references')
                                ->label('Referencias laborales')
                                ->reorderable(false)
                                ->collapsible()
                                ->collapsed()
                                ->itemLabel(fn (array $state): ?string => $state['name'] ?? 'Nueva Referencia')
                                ->components([
                                    TextInput::make('name')
                                        ->label('Nombre contacto')
                                        ->required(),
                                    TextInput::make('company')
                                        ->label('Empresa')
                                        ->required(),
                                    TextInput::make('phone')
                                        ->label('Teléfono')
                                        ->tel()
                                        ->required(),
                                    TextInput::make('email')
                                        ->label('Email')
                                        ->email(),
                                ])
                                ->columns(2)
                                ->columnSpanFull(),
                        ]),

                    FormSection::make('Confirmación Final')
                        ->collapsible()
                        ->schema([
                            Checkbox::make('veracity_declaration')
                                ->label('Declaro que la información proporcionada es verídica.')
                                ->accepted(),
                            Checkbox::make('ai_authorization')
                                ->label('Autorizo el uso de IA para el análisis de mi perfil.')
                                ->accepted(),
                            Checkbox::make('automated_evaluation_consent')
                                ->label('Doy mi consentimiento para la evaluación automatizada.')
                                ->accepted(),
                        ]),
                ]),
            Step::make('Completado')
                ->id('completado')
                ->icon('heroicon-o-check-circle')
                ->schema([
                    ViewField::make('success_message')
                        ->hiddenLabel()
                        ->view('filament.pages.public-dashboard-tabs.curriculum-success'),
                ]),
        ];

        return $schema
            ->components([
                Wizard::make($steps)
                    ->startOnStep($this->isFinished ? 5 : 1)
                    ->submitAction(new \Illuminate\Support\HtmlString('')),
            ])
            ->statePath('curriculumData')
            ->model(CandidateProfile::class);
    }

    public function submitCurriculum(): void
    {
        $this->saveCurriculum(true, false, true);
    }

    public function saveCurriculum($notify = true, $skipValidation = false, $final = false): void
    {
        if ($skipValidation) {
            $data = $this->curriculumForm->getRawState();
            
            // Fix for FileUpload persistence in partial saves:
            // Recursively dehydrate all components to process file uploads
            $this->dehydrateComponents($this->curriculumForm->getComponents(withHidden: true), $data);
            
            // Manually call dehydrateState on the Repeater components at the root level (or inside sections)
            // because dehydrateComponents logic might be missing the context needed for Repeaters to process their children properly
            // when called in this "detached" way.
            // Actually, Filament's Repeater dehydrateState() expects the state array to be passed by reference and modifies it.
            // But getRawState() returns a copy. 
            // The recursive dehydrateComponents is trying to fix this, but maybe the Repeater logic needs the full context.
            
            // Let's try to explicitly force the repeaters to dehydrate their state into our $data array.
            // We need to find the repeaters and call dehydrateState on them with the full $data.
            
            // The issue is likely that $data passed to dehydrateState needs to be the full form state, 
            // and the component knows where to look based on its state path.
            
            // Also call container-level hooks if any
            $this->curriculumForm->callBeforeStateDehydrated($data);
            $this->curriculumForm->mutateDehydratedState($data);
        } else {
            // Full validation and dehydration
            $data = $this->curriculumForm->getState();
        }
        
        // Remove read-only/calculated fields that shouldn't be saved directly
        // Age is calculated, name and email come from User model
        // Also remove 'has_no_experience' as it is a UI helper field, not in the model
        // NOTE: currency field should be saved! Ensure it is fillable in the model.
        $dataToSave = collect($data)
            ->except(['age', 'name', 'email', 'has_no_experience'])
            ->toArray();

        // Ensure work_experience is explicitly empty array if has_no_experience is true
        // This handles the case where getRawState might return null or inconsistent state
        if (isset($data['has_no_experience']) && $data['has_no_experience']) {
            $dataToSave['work_experience'] = [];
        }

        $profile = CandidateProfile::updateOrCreate(
            ['user_id' => Auth::id()],
            $dataToSave
        );

        if ($notify) {
            Notification::make()
                ->title('Curriculum guardado exitosamente')
                ->success()
                ->send();
        }

        // Marcar como finalizado para mostrar el paso 5 y reiniciar el wizard en el último paso
        // Solo si es el guardado final (submit)
        if ($final) {
            $this->isFinished = true;
        }
    }

    protected function dehydrateComponents(array $components, array &$data): void
    {
        foreach ($components as $component) {
            $component->dehydrateState($data);
            
            if (method_exists($component, 'mutateDehydratedState')) {
                 $component->mutateDehydratedState($data);
            }
            
            if (method_exists($component, 'getChildComponents')) {
                 if (! $component instanceof \Filament\Forms\Components\Repeater) {
                     $this->dehydrateComponents($component->getChildComponents(), $data);
                 } else {
                    // For Repeaters, we must explicitly dehydrate the items
                    // Standard dehydrateState on the Repeater component itself MIGHT NOT recurse into items 
                    // if the component tree isn't fully hydrated in this context.
                    // We need to iterate over the repeater's state items and manually dehydrate the child components for each item.
                    
                    $statePath = $component->getStatePath();
                    // Extract the repeater data from the full data array using dot notation or direct access if simple
                    // Since we are at root, $data is the full array.
                    // But getStatePath returns full path including form name usually? No, just component path.
                    // Let's assume $data matches the structure.
                    
                    // Simple approach: Let Filament handle it by ensuring the component is bound to the form
                    // The issue might be that getRawState() returns data, but the components aren't "hydrated" with that data
                    // so dehydrateState() operates on empty/default values?
                    
                    // Let's try to get the containers for each item and dehydrate them
                     $repeaterState = [];
                     foreach ($component->getChildComponentContainers() as $uuid => $container) {
                         $container->callBeforeStateDehydrated();
                         $containerData = $container->getRawState(); // Use getRawState to avoid validation in rows
                         $container->dehydrateState($containerData); 
                         // ComponentContainer does not have mutateDehydratedState method
                         $repeaterState[$uuid] = $containerData;
                     }
                     
                     // Update the main data array with the dehydrated repeater state
                     // We assume the repeater is at the root level of the form schema (inside sections/wizard)
                     // which means its name corresponds to the key in the data array.
                     $data[$component->getName()] = $repeaterState;
                 }
            }
        }
    }

    public static function getNavigationLabel(): string
    {
        return 'Mi Panel';
    }

    public function getTitle(): string | HtmlString
    {
        return 'Mi Panel';
    }

    public function getSubheading(): string | HtmlString | null
    {
        return 'Este es tu panel como candidato. Aquí verás tus datos y las ofertas disponibles.';
    }

    public function getActions(): array
    {
        return [
            $this->reviewCv(),
        ];
    }

    public function openReviewModal(): void
    {
        // En lugar de una acción, usamos una redirección o un componente diferente si el conflicto con Tabs persiste.
        // Pero intentemos mover el modal FUERA del contexto de los tabs.
        // Las acciones de página se renderizan normalmente fuera del contenido principal, pero aquí estamos dentro de un Page custom.
        
        // Vamos a probar lanzar un evento de navegador para abrir un modal global si es posible,
        // o simplemente montar la acción.
        $this->mountAction('reviewCv');
    }

    public function reviewCv(): Action
    {
        return Action::make('reviewCv')
            ->label('Revisar mis datos')
            ->modalHeading('Mi Curriculum Vitae')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar')
            ->modalWidth('7xl')
            ->closeModalByClickingAway(false)
            ->extraModalWindowAttributes(['class' => 'review-cv-modal-window']) // Clase para depuración si es necesario
            ->modalContent(function () {
                $user = Auth::user();
                $profile = CandidateProfile::where('user_id', $user->id)->first();
                if (!$profile) return null;
                return view('filament.components.cv-summary', ['profile' => $profile, 'user' => $user]);
            });
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download_cv')
                ->label('Descargar CV')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    $user = Auth::user();
                    $profile = CandidateProfile::where('user_id', $user->id)->first();

                    if (!$profile) {
                        Notification::make()
                            ->title('Perfil no encontrado')
                            ->body('Primero debes completar tu perfil.')
                            ->danger()
                            ->send();
                        return;
                    }

                    if (!$this->checkProfileCompleteness($profile)) {
                        Notification::make()
                            ->title('Perfil incompleto')
                            ->body('Por favor completa todas las secciones de tu currículum antes de descargar el PDF.')
                            ->danger()
                            ->send();
                        return;
                    }

                    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.cv', [
                        'user' => $user,
                        'profile' => $profile,
                    ]);
                    $pdf->setPaper('a4', 'portrait');
                    $pdf->setOption('dpi', 96);
                    $pdf->setOption('defaultFont', 'DejaVu Sans');
                    $pdf->setOption('isHtml5ParserEnabled', true);
                    $pdf->setOption('isRemoteEnabled', true);
                    $pdf->setOption('isPhpEnabled', false);
                    $pdf->setOption('margin_top', 10);
                    $pdf->setOption('margin_right', 10);
                    $pdf->setOption('margin_bottom', 12);
                    $pdf->setOption('margin_left', 10);

                    return response()->streamDownload(function () use ($pdf) {
                        echo $pdf->output();
                    }, 'curriculum-' . \Illuminate\Support\Str::slug($user->name) . '.pdf');
                }),
        ];
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();

        return $user?->is_internal === false;
    }

    protected function checkProfileCompleteness(?CandidateProfile $profile): bool
    {
        if (!$profile) return false;

        // 1. Personal Info
        if (empty($profile->phone) || empty($profile->rut) || empty($profile->city) || empty($profile->country)) return false;

        // 2. Education (At least one)
        if (empty($profile->education) || count($profile->education) < 1) return false;

        // 3. Work Experience 
        // Allowed to be empty if user checked "No experience" (which saves empty array)
        // So we don't enforce count > 0 here, assuming wizard validation handled the "one or the other" logic.
        
        // 4. Skills & Languages
        if (empty($profile->languages) || count($profile->languages) < 1) return false;
        if (empty($profile->technical_skills) || count($profile->technical_skills) < 1) return false;
        if (empty($profile->soft_skills) || count($profile->soft_skills) < 1) return false;

        // 5. Final Info
        if (empty($profile->salary_expectation) || empty($profile->currency)) return false;
        if (!$profile->veracity_declaration || !$profile->ai_authorization || !$profile->automated_evaluation_consent) return false;

        return true;
    }

    // protected $listeners = ['openPostularModal'];

    // public function openPostularModal($recordKey)
    // {
    //     \Illuminate\Support\Facades\Log::info('openPostularModal event received', ['recordKey' => $recordKey]);
    //     $this->mountTableAction('postular_modal', $recordKey);
    // }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                JobOffer::query()
                    ->active()
                    ->orderByDesc('published_at')
            )
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('city')
                    ->label('Ciudad')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('country')
                    ->label('País')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('contract_type')
                    ->label('Tipo de contrato')
                    ->badge()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Descripción')
                    ->limit(80)
                    ->wrap()
                    ->toggleable(),
            ])
            ->defaultPaginationPageOption(6)
            ->emptyStateHeading('No hay publicaciones recientes')
            ->recordActions([
                ViewAction::make()
                    ->label('Ver detalles')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(false)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->extraModalFooterActions([
                        Action::make('postular_modal')
                            ->label('Postular Ahora')
                            ->color('primary')
                            ->icon('heroicon-o-paper-airplane')
                            ->requiresConfirmation()
                            ->modalHeading('Revisar y Enviar Postulación')
                            ->modalSubmitActionLabel('Enviar Postulación')
                            ->mountUsing(function (Action $action) {
                                $user = Auth::user();
                                $profile = CandidateProfile::where('user_id', $user->id)->first();

                                // Check completeness
                                if (!$this->checkProfileCompleteness($profile)) {
                                    Notification::make()
                                        ->title('Perfil incompleto')
                                        ->body('Por favor completa todas las secciones de tu currículum antes de postular.')
                                        ->danger()
                                        ->send();
                                    
                                    $action->cancel();
                                }
                            })
                            ->modalWidth('7xl')
                            ->modalContent(function () {
                                $user = Auth::user();
                                $profile = CandidateProfile::where('user_id', $user->id)->first();
                                if (!$profile) return null;
                                return view('filament.components.cv-summary', ['profile' => $profile, 'user' => $user]);
                            })
                            ->before(function (Action $action, JobOffer $record) {
                                $user = Auth::user();
                                
                                // Check duplicate
                                if (JobApplication::where('user_id', $user->id)->where('job_offer_id', $record->id)->exists()) {
                                     Notification::make()
                                        ->title('Ya has postulado a esta oferta')
                                        ->warning()
                                        ->send();
                                     $action->cancel();
                                }
                            })
                            ->action(function (JobOffer $record) {
                                 $user = Auth::user();
                                 $profile = CandidateProfile::where('user_id', $user->id)->first();
                                 
                                 JobApplication::create([
                                     'user_id' => $user->id,
                                     'job_offer_id' => $record->id,
                                     'cv_snapshot' => $profile->toArray(),
                                     'applicant_name' => $user->name,
                                     'applicant_email' => $user->email,
                                     'status' => 'submitted',
                                     'submitted_at' => now(),
                                 ]);

                                 Notification::make()
                                    ->title('Postulación enviada exitosamente')
                                    ->success()
                                    ->send();

                                 $this->dispatch('applicationSubmitted');
                             }),
                    ])
                    ->modalWidth('4xl')
                    ->schema($this->getJobOfferDetailsSchema()),
            ]);
    }

    public function getJobOfferDetailsSchema(): array
    {
        return [
            TextEntry::make('header')
                ->hiddenLabel()
                ->columnSpanFull()
                ->html()
                ->state(function (JobOffer $record) {
                    $location = e($record->city) . ', ' . e($record->country);
                    $deadline = $record->deadline ? ' Postula hasta ' . $record->deadline->format('d M, Y') : '';
                    $published = $record->published_at ? ' Publicado ' . $record->published_at->format('d M, Y') : '';
                    
                    $badge = $record->contract_type 
                        ? '<span class="px-3 py-1 rounded-full bg-blue-50 text-blue-600 text-xs font-medium dark:bg-blue-900/30 dark:text-blue-400">' . e($record->contract_type) . '</span>' 
                        : '';

                    return '
                        <div class="mb-4">
                            <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Detalle de la oferta</div>
                            <div class="flex justify-between items-start gap-4">
                                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">' . e($record->title) . '</h2>
                                ' . $badge . '
                            </div>
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-2 text-sm text-gray-500 dark:text-gray-400">
                                <span>' . $location . '</span>
                                ' . ($deadline ? '<span class="text-gray-300 dark:text-gray-600 px-2">&bull;</span><span>' . $deadline . '</span>' : '') . '
                                ' . ($published ? '<span class="text-gray-300 dark:text-gray-600 px-2">&bull;</span><span>' . $published . '</span>' : '') . '
                            </div>
                        </div>
                    ';
                }),

            TextEntry::make('description')
                ->label('Descripción')
                ->markdown()
                ->columnSpanFull(),

            TextEntry::make('requirements')
                ->label('Requisitos')
                ->columnSpanFull()
                ->html()
                ->state(function (JobOffer $record) {
                    if ($record->jobOfferRequirements->isEmpty()) {
                        return '<span class="text-gray-500 dark:text-gray-400">—</span>';
                    }
                    
                    $html = '<ul class="list-disc pl-5 space-y-1 text-sm text-gray-900 dark:text-white">';
                    foreach ($record->jobOfferRequirements as $req) {
                        $html .= '<li><strong>' . e($req->category) . '</strong> (' . e($req->type) . '): ' . e($req->evidence ?? $req->level) . '</li>';
                    }
                    $html .= '</ul>';
                    
                    return $html;
                }),

            TextEntry::make('benefits')
                ->label('Beneficios')
                ->markdown()
                ->placeholder('—')
                ->columnSpanFull(),
        ];
    }

    public function getTabsSchema(): Schema
    {
        return Schema::make($this)->components([
            Tabs::make('Mi Panel')
                ->tabs([
                    Tab::make('Ofertas Laborales')
                        ->components([
                            Html::make(fn () => view('filament.pages.public-dashboard-tabs.offers', ['component' => $this])->render()),
                        ]),
                    Tab::make('Mi Curriculum')
                        ->components([
                            Html::make(fn () => view('filament.pages.public-dashboard-tabs.curriculum', ['component' => $this])->render()),
                        ]),
                    Tab::make('Mis Postulaciones')
                        ->components([
                            Html::make(fn () => view('filament.pages.public-dashboard-tabs.my-applications')->render()),
                        ]),
                ])
                ->activeTab(1),
        ]);
    }
}
