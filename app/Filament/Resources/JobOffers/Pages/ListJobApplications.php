<?php

namespace App\Filament\Resources\JobOffers\Pages;

use App\Filament\Resources\JobOffers\JobOfferResource;
use App\Models\JobApplication;
use App\Models\User;
use App\Models\Survey;
use App\Services\ApplicationScoringService;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables\Columns\ImageColumn;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Actions as InfolistActions;
use Filament\Actions\Action as InfolistAction;
use Filament\Support\Enums\FontWeight;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Support\Enums\Width;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Html;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Support\Enums\TextSize;
use App\Models\Response;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Facades\Filament;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use App\Models\JobInterview;
use App\Models\Dimension;
use App\Services\AiProviderService;

class ListJobApplications extends Page implements HasTable, HasSchemas, HasForms, HasActions
{
    use InteractsWithTable;
    use InteractsWithForms;
    use InteractsWithActions;

    /**
     * @param  array<string, mixed>  $parameters
     */
    public static function canAccess(array $parameters = []): bool
    {
        /** @var \Illuminate\Contracts\Auth\Access\Authorizable|null $user */
        $user = Filament::auth()->user();

        return $user?->can('ViewAny:JobOffer') ?? false;
    }

    protected static string $resource = JobOfferResource::class;

    protected string $view = 'filament.resources.job-offers.pages.list-job-applications';

    public JobOfferResource $resourceInstance;
    public $record; // The JobOffer record

    public function mount($record)
    {
        $this->record = $this->resolveRecord($record);
    }

    public function getTitle(): string | Htmlable
    {
        return 'Postulaciones - ' . $this->record->title;
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    protected function resolveRecord($key): Model
    {
        return JobOfferResource::resolveRecordRouteBinding($key);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                JobApplication::query()
                    ->where('job_offer_id', $this->record->id)
                    ->with('user')
                    ->orderByRaw("FIELD(eligibility_status, 'eligible', 'not_eligible') DESC")
                    ->orderByDesc('score')
            )
            ->defaultSort('score', 'desc')
            ->columns([
                ImageColumn::make('applicant_avatar')
                    ->label('Avatar')
                    ->circular()
                    ->state(function (JobApplication $record): string {
                        $avatarUrl = $record->user?->getFilamentAvatarUrl();
                        if ($avatarUrl) {
                            return $avatarUrl;
                        }

                        $name = $record->applicant_name ?: ($record->user?->name ?? 'Usuario');

                        return 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&color=7F9CF5&background=EBF4FF';
                    }),
                Tables\Columns\TextColumn::make('applicant_name')
                    ->label('Candidato')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->description(fn (JobApplication $record) => $record->applicant_email),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha Postulación')
                    ->dateTime('d M, Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('eligibility_status')
                    ->label('Elegibilidad')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'eligible' => 'success',
                        'not_eligible' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'eligible' => 'Elegible',
                        'not_eligible' => 'No elegible',
                        default => 'Pendiente',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('score')
                    ->label('Score')
                    ->badge()
                    ->numeric(2)
                    ->sortable()
                    ->color(function ($state) {
                        $value = (float) $state;
                        return $value >= 70 ? 'success' : ($value >= 50 ? 'warning' : 'danger');
                    })
                    ->weight(FontWeight::Bold),
                Tables\Columns\TextColumn::make('ai_analysis.qualitative_score')
                    ->label('Score IA')
                    ->badge()
                    ->color(function ($state) {
                        $value = (float) $state;
                        return $value >= 70 ? 'success' : ($value >= 50 ? 'warning' : 'danger');
                    })
                    ->formatStateUsing(fn ($state) => $state ? "{$state}%" : '-')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'submitted', 'pending' => 'info',
                        'reviewed' => 'warning',
                        'interview' => 'info',
                        'testing' => 'warning',
                        'finalist' => 'success',
                        'hired' => 'success',
                        'rejected' => 'danger',
                        'cancelled' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'reviewed' => 'En revisión',
                        'interview' => 'Entrevista',
                        'testing' => 'Pruebas',
                        'finalist' => 'Finalista',
                        'hired' => 'Contratada',
                        'rejected' => 'Rechazada',
                        'cancelled' => 'Cancelada',
                        default => ucfirst($state),
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'reviewed' => 'En revisión',
                        'interview' => 'Entrevista',
                        'testing' => 'Pruebas',
                        'finalist' => 'Finalista',
                        'hired' => 'Contratada',
                        'rejected' => 'Rechazada',
                    ]),
            ])
            ->filtersTriggerAction(fn (Action $action) => $action->label('Filtrar')->icon('heroicon-o-funnel'))
            ->deferFilters(false)
            ->recordActions([
                ViewAction::make()
                    ->label('Ver Ficha')
                    ->modalHeading('Ficha del postulante')
                    ->modalCancelActionLabel('Cerrar')
                    ->modalWidth('7xl')
                    ->infolist(fn (Schema $schema) => $this->candidateInfolist($schema)),
                Action::make('analysis')
                    ->label('Ver Análisis')
                    ->icon('heroicon-o-chart-bar')
                    ->color('primary')
                    ->modalWidth('5xl')
                    ->visible(fn (JobApplication $record) => $record->auto_decision_log !== null)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->infolist(fn (Schema $schema) => $this->scoringAnalysisInfolist($schema)),
                ActionGroup::make([
                    Action::make('change_status')
                        ->label('Cambiar Estado')
                        ->icon('heroicon-o-pencil-square')
                        ->color('info')
                        ->fillForm(fn (JobApplication $record) => ['status' => $record->status])
                        ->schema([
                            Select::make('status')
                                ->label('Nuevo Estado')
                                ->options([
                                    'reviewed' => 'En revisión',
                                    'interview' => 'Entrevista',
                                    'testing' => 'Pruebas',
                                    'finalist' => 'Finalista',
                                    'hired' => 'Contratada',
                                    'rejected' => 'Rechazada',
                                ])
                                ->required(),
                            Textarea::make('comment')
                                ->label('Comentario / Motivo')
                                ->rows(3)
                                ->placeholder('Ingrese un comentario sobre el cambio de estado...')
                                ->required(),
                        ])
                        ->action(function (JobApplication $record, array $data) {
                            $previousStatus = $record->status;
                            $record->update([
                                'status' => $data['status'],
                            ]);

                            $candidateUser = $record->user;
                            if (! $candidateUser && filled($record->applicant_email)) {
                                $candidateUser = User::query()->where('email', $record->applicant_email)->first();
                            }

                            $statusLabels = [
                                'submitted' => 'Recibida',
                                'reviewed' => 'En revisión',
                                'interview' => 'Entrevista',
                                'testing' => 'Pruebas',
                                'finalist' => 'Finalista',
                                'hired' => 'Contratada',
                                'rejected' => 'Rechazada',
                            ];

                            $fromLabel = $statusLabels[$previousStatus] ?? $previousStatus;
                            $toLabel = $statusLabels[$data['status']] ?? $data['status'];

                            if ($candidateUser) {
                                Notification::make()
                                    ->title('Actualización de postulación')
                                    ->body("Tu postulación a \"{$record->jobOffer?->title}\" cambió de \"{$fromLabel}\" a \"{$toLabel}\".\n\nComentario: {$data['comment']}")
                                    ->icon('heroicon-o-briefcase')
                                    ->info()
                                    ->sendToDatabase($candidateUser);
                            }

                            Notification::make()
                                ->title('Estado actualizado')
                                ->body("La postulación ha pasado a estado: {$toLabel}")
                                ->success()
                                ->send();
                        }),
                    Action::make('recalculate')
                        ->label('Recalcular')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->action(function (JobApplication $record) {
                            $service = app(ApplicationScoringService::class);

                            Notification::make()
                                ->title('Recalculando...')
                                ->info()
                                ->send();

                            $record->update([
                                'eligibility_status' => null,
                                'ai_analysis' => null,
                            ]);

                            $service->processApplication($record);

                            Notification::make()
                                ->title('Puntaje y análisis IA recalculados')
                                ->success()
                                ->send();
                        }),
                ])
                    ->label('Acciones')
                    ->icon('heroicon-o-ellipsis-vertical')
                    ->color('gray'),
            ])
            ->headerActions([
                Action::make('recalculate_all')
                    ->label('Recalcular Todos')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription('Esta acción recalculará el puntaje de TODAS las postulaciones y ejecutará el análisis de IA. Esto puede tomar varios minutos dependiendo de la cantidad de registros. ¿Desea continuar?')
                    ->action(function () {
                        $service = app(ApplicationScoringService::class);
                        
                        set_time_limit(300);
                        
                        $applications = JobApplication::where('job_offer_id', $this->record->id)->get();
                        $count = 0;

                        Notification::make()
                            ->title('Proceso iniciado en segundo plano')
                            ->info()
                            ->send();

                        foreach ($applications as $app) {
                            $app->update([
                                'eligibility_status' => null,
                                'ai_analysis' => null,
                            ]);
                            $service->processApplication($app);
                            $count++;
                        }

                        Notification::make()
                            ->title("Se recalcularon {$count} postulaciones con análisis IA")
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public function scoringAnalysisInfolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Resumen de Elegibilidad')
                    ->schema([
                        TextEntry::make('eligibility_status')
                            ->label('Estado')
                            ->badge()
                            ->color(fn ($state) => $state === 'eligible' ? 'success' : 'danger')
                            ->formatStateUsing(fn ($state) => $state === 'eligible' ? 'Elegible' : 'No Elegible'),
                        TextEntry::make('score')
                            ->label('Puntaje Total')
                            ->weight(FontWeight::Bold)
                            ->size(TextSize::Large),
                        TextEntry::make('rejection_reason')
                            ->label('Motivo de Rechazo')
                            ->visible(fn ($record) => $record->eligibility_status === 'not_eligible')
                            ->color('danger'),
                    ])->columns(3),

                Section::make('Detalle del Análisis Automático')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('auto_decision_log.experience_check.required')
                                    ->label('Experiencia Requerida (años)')
                                    ->columnSpan(1),
                                TextEntry::make('auto_decision_log.experience_check.candidate')
                                    ->label('Experiencia Candidato (años)')
                                    ->columnSpan(1),
                                TextEntry::make('auto_decision_log.experience_check.passed')
                                    ->label('Experiencia Aprobada')
                                    ->formatStateUsing(fn ($state) => $state ? 'Sí' : 'No')
                                    ->badge()
                                    ->color(fn ($state) => $state ? 'success' : 'danger')
                                    ->columnSpan(1),
                                TextEntry::make('auto_decision_log.education_check.required')
                                    ->label('Educación Requerida')
                                    ->columnSpan(1),
                                TextEntry::make('auto_decision_log.education_check.candidate')
                                    ->label('Educación Candidato')
                                    ->columnSpan(1),
                                TextEntry::make('auto_decision_log.education_check.passed')
                                    ->label('Educación Aprobada')
                                    ->formatStateUsing(fn ($state) => $state ? 'Sí' : 'No')
                                    ->badge()
                                    ->color(fn ($state) => $state ? 'success' : 'danger')
                                    ->columnSpan(1),
                                TextEntry::make('auto_decision_log.skills_check.required_count')
                                    ->label('Habilidades Requeridas')
                                    ->columnSpan(1),
                                TextEntry::make('auto_decision_log.skills_check.missing_count')
                                    ->label('Habilidades Faltantes')
                                    ->columnSpan(1),
                                TextEntry::make('auto_decision_log.skills_check.passed')
                                    ->label('Habilidades Aprobadas')
                                    ->formatStateUsing(fn ($state) => $state ? 'Sí' : 'No')
                                    ->badge()
                                    ->color(fn ($state) => $state ? 'success' : 'danger')
                                    ->columnSpan(1),
                            ]),
                        
                        Section::make('Desglose de Puntaje')
                            ->description('Detalle de puntos obtenidos por criterios adicionales')
                            ->compact()
                            ->visible(fn ($record) => $record->eligibility_status === 'eligible')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        TextEntry::make('auto_decision_log.scoring_experience.extra_years')
                                            ->label('Años Extra Experiencia'),
                                        TextEntry::make('auto_decision_log.scoring_experience.points')
                                            ->label('Puntos Experiencia'),
                                        TextEntry::make('auto_decision_log.scoring_skills.matched_count')
                                            ->label('Habilidades Deseables'),
                                        TextEntry::make('auto_decision_log.scoring_skills.points')
                                            ->label('Puntos Habilidades'),
                                        TextEntry::make('auto_decision_log.scoring_languages.met_count')
                                            ->label('Idiomas que cumplen o superan el nivel'),
                                        TextEntry::make('auto_decision_log.scoring_languages.superior_count')
                                            ->label('Idiomas sobre el nivel'),
                                        TextEntry::make('auto_decision_log.scoring_languages.points')
                                            ->label('Puntos Idioma'),
                                        TextEntry::make('auto_decision_log.scoring_education.is_superior')
                                            ->label('Educación Superior'),
                                        TextEntry::make('auto_decision_log.scoring_education.points')
                                            ->label('Puntos Educación'),
                                    ]),
                            ]),
                    ]),

                Section::make('Análisis de Inteligencia Artificial')
                    ->schema([
                        TextEntry::make('ai_analysis.qualitative_score')
                            ->label('Score Cualitativo (IA)')
                            ->badge()
                            ->color(fn ($state) => $state >= 70 ? 'success' : ($state >= 40 ? 'warning' : 'danger'))
                            ->weight(FontWeight::Bold),
                        TextEntry::make('ai_analysis.summary')
                            ->label('Resumen Ejecutivo')
                            ->columnSpanFull(),
                        TextEntry::make('ai_analysis.strengths')
                            ->label('Fortalezas Detectadas')
                            ->listWithLineBreaks()
                            ->bulleted(),
                        TextEntry::make('ai_analysis.weaknesses')
                            ->label('Debilidades / Riesgos')
                            ->listWithLineBreaks()
                            ->bulleted()
                            ->color('danger'),
                        TextEntry::make('ai_analysis.interview_questions')
                            ->label('Preguntas Sugeridas para Entrevista')
                            ->listWithLineBreaks()
                            ->bulleted()
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => ! empty($record->ai_analysis))
                    ->columns(2),
            ]);
    }

    public function candidateInfolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Ficha del Candidato')
                    ->tabs([
                        Tab::make('Currículum')
                            ->icon('heroicon-m-document-text')
                            ->schema([
                                ViewEntry::make('cv_snapshot')
                                    ->hiddenLabel()
                                    ->view('filament.components.cv-snapshot-viewer'),
                            ]),
                        Tab::make('Archivos')
                            ->icon('heroicon-m-paper-clip')
                            ->schema([
                                ViewEntry::make('candidate_files')
                                    ->hiddenLabel()
                                    ->view('filament.components.job-application-files-tab'),
                            ]),
                        Tab::make('Entrevistas')
                            ->icon('heroicon-m-chat-bubble-left-right')
                            ->schema([
                                InfolistActions::make([
                                    InfolistAction::make('schedule_interview')
                                        ->label('Agendar Entrevista')
                                        ->icon('heroicon-o-chat-bubble-left-right')
                                        ->color('warning')
                                        ->schema([
                                            Select::make('interviewer_id')
                                                ->label('Entrevistador')
                                                ->options(fn () => User::query()
                                                    ->whereDoesntHave('roles', fn ($query) => $query->whereIn('name', ['super_admin', 'public']))
                                                    ->orderBy('name')
                                                    ->pluck('name', 'id')
                                                    ->toArray())
                                                ->required()
                                                ->searchable(),
                                            Select::make('survey_id')
                                                ->label('Plantilla de Entrevista')
                                                ->options(Survey::where('is_interview', true)->where('active', true)->pluck('title', 'id'))
                                                ->required()
                                                ->searchable(),
                                            \Filament\Forms\Components\DateTimePicker::make('scheduled_at')
                                                ->label('Fecha y Hora')
                                                ->required()
                                                ->native(false),
                                            Textarea::make('comments')
                                                ->label('Notas')
                                                ->columnSpanFull(),
                                        ])
                                        ->action(function (JobApplication $record, array $data) {
                                            try {
                                                $record->interviews()->create([
                                                    'interviewer_id' => $data['interviewer_id'],
                                                    'survey_id' => $data['survey_id'],
                                                    'scheduled_at' => $data['scheduled_at'],
                                                    'comments' => $data['comments'] ?? null,
                                                    'status' => 'scheduled',
                                                ]);
                                            } catch (\Illuminate\Validation\ValidationException $e) {
                                                Notification::make()
                                                    ->title('No se pudo agendar la entrevista')
                                                    ->body(collect($e->errors())->flatten()->first() ?: 'El entrevistador no está disponible en ese horario.')
                                                    ->danger()
                                                    ->send();

                                                return;
                                            }

                                            $survey = Survey::find($data['survey_id']);
                                            if ($survey && ! $survey->users()->where('users.id', $data['interviewer_id'])->exists()) {
                                                $survey->users()->attach($data['interviewer_id']);
                                            }

                                            // Notificar al entrevistador
                                            $interviewer = User::find($data['interviewer_id']);
                                            if ($interviewer) {
                                                Notification::make()
                                                    ->title('Nueva Entrevista Agendada')
                                                    ->body("Se te ha asignado una entrevista con {$record->applicant_name} para el cargo {$record->jobOffer->title}.\nFecha: " . \Carbon\Carbon::parse($data['scheduled_at'])->format('d/m/Y H:i'))
                                                    ->icon('heroicon-o-chat-bubble-left-right')
                                                    ->info()
                                                    ->actions([
                                                        \Filament\Actions\Action::make('view')
                                                            ->label('Ver Entrevistas')
                                                            ->url(\App\Filament\Pages\MyInterviews::getUrl())
                                                            ->markAsRead(),
                                                    ])
                                                    ->sendToDatabase($interviewer);
                                            }

                                            Notification::make()
                                                ->title('Entrevista agendada correctamente')
                                                ->success()
                                                ->send();
                                        }),
                                ])->alignment(\Filament\Support\Enums\Alignment::End),
                                
                                Html::make('interviews_empty_state')
                                    ->content(fn () => new HtmlString('<div class="flex items-center justify-center py-10 text-sm text-gray-500 dark:text-gray-400">Sin entrevistas agendadas</div>'))
                                    ->visible(fn (JobApplication $record) => ! $record->interviews()->exists()),

                                RepeatableEntry::make('interviews')
                                    ->hiddenLabel()
                                    ->visible(fn (JobApplication $record) => $record->interviews()->exists())
                                    ->state(function (JobApplication $record) {
                                        return $record->interviews()
                                            ->with(['interviewer', 'survey'])
                                            ->orderByDesc('scheduled_at')
                                            ->get()
                                            ->map(function ($interview) {
                                                $score = $interview->score;

                                                if (($score === null) && (($interview->status ?? null) === 'completed')) {
                                                    $computed = $this->buildInterviewReport($interview);
                                                    $score = $computed['score'] ?? null;
                                                }

                                                return [
                                                    'id' => $interview->id,
                                                    'scheduled_at' => $interview->scheduled_at,
                                                    'survey' => [
                                                        'title' => $interview->survey?->title,
                                                    ],
                                                    'interviewer' => [
                                                        'name' => $interview->interviewer?->name,
                                                    ],
                                                    'status' => $interview->status,
                                                    'score' => $score,
                                                    'ai_score' => $interview->ai_score,
                                                    'actions' => $interview->id . '|' . ($interview->status ?? ''),
                                                ];
                                            })
                                            ->values()
                                            ->all();
                                    })
                                    ->table([
                                        TableColumn::make('Fecha'),
                                        TableColumn::make('Tipo'),
                                        TableColumn::make('Entrevistador'),
                                        TableColumn::make('Estado')->alignCenter(),
                                        TableColumn::make('Acciones')->alignCenter()->width('1%'),
                                        TableColumn::make('Score')->alignCenter()->width('1%'),
                                        TableColumn::make('Score IA')->alignCenter()->width('1%'),
                                    ])
                                    ->schema([
                                        TextEntry::make('scheduled_at')
                                            ->label('Fecha')
                                            ->hiddenLabel()
                                            ->dateTime('d M, Y H:i'),
                                        TextEntry::make('survey.title')
                                            ->label('Tipo')
                                            ->hiddenLabel(),
                                        TextEntry::make('interviewer.name')
                                            ->label('Entrevistador')
                                            ->hiddenLabel(),
                                        TextEntry::make('status')
                                            ->label('Estado')
                                            ->hiddenLabel()
                                            ->badge()
                                            ->color(fn (string $state): string => match ($state) {
                                                'scheduled' => 'warning',
                                                'completed' => 'success',
                                                'cancelled' => 'danger',
                                                default => 'gray',
                                            })
                                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                                'scheduled' => 'Pendiente',
                                                'completed' => 'Realizada',
                                                'cancelled' => 'Cancelada',
                                                default => ucfirst($state),
                                            }),
                                        TextEntry::make('actions')
                                            ->label('Acciones')
                                            ->hiddenLabel()
                                            ->placeholder('')
                                            ->formatStateUsing(fn () => null)
                                            ->alignCenter()
                                            ->suffixActions([
                                                function (string $state) {
                                                    [$interviewId, $status] = array_pad(explode('|', $state, 2), 2, null);
                                                    $interviewId = (int) $interviewId;

                                                    return Action::make('view_interview_report')
                                                        ->tooltip('Ver resultados')
                                                        ->icon('heroicon-o-eye')
                                                        ->iconButton()
                                                        ->color('primary')
                                                        ->visible(($status ?? null) === 'completed')
                                                        ->modalHeading('Reporte de entrevista')
                                                        ->modalCancelActionLabel('Cerrar')
                                                        ->modalSubmitAction(false)
                                                        ->modalWidth('7xl')
                                                        ->closeModalByClickingAway(false)
                                                        ->modalContent(fn () => $this->renderInterviewReportHtml($interviewId));
                                                },
                                                function (string $state) {
                                                    [$interviewId, $status] = array_pad(explode('|', $state, 2), 2, null);
                                                    $interviewId = (int) $interviewId;

                                                    return Action::make('download_interview_responses')
                                                        ->tooltip('Bajar respuestas')
                                                        ->icon('heroicon-o-arrow-down-tray')
                                                        ->iconButton()
                                                        ->color('gray')
                                                        ->visible(($status ?? null) === 'completed')
                                                        ->url(fn () => route('job_interviews.responses.export', ['interview' => $interviewId]))
                                                        ->openUrlInNewTab();
                                                },
                                            ]),
                                        TextEntry::make('score')
                                            ->label('Puntaje')
                                            ->hiddenLabel()
                                            ->numeric(2)
                                            ->placeholder('—')
                                            ->alignCenter(),
                                        TextEntry::make('ai_score')
                                            ->label('Score IA')
                                            ->hiddenLabel()
                                            ->numeric(2)
                                            ->placeholder('—')
                                            ->alignCenter(),
                                    ])
                                    ->contained(false),
                            ]),
                        Tab::make('Evaluaciones')
                            ->icon('heroicon-m-clipboard-document-check')
                            ->schema([
                                TextEntry::make('evaluaciones_placeholder')
                                    ->hiddenLabel()
                                    ->state('Próximamente: Resultados de pruebas técnicas y psicométricas.'),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }

    protected function renderInterviewReportHtml(int $interviewId): HtmlString
    {
        if ($interviewId <= 0) {
            return new HtmlString('<div class="p-4 text-sm text-gray-500 dark:text-gray-400">Entrevista no encontrada.</div>');
        }

        $interview = JobInterview::query()
            ->with(['jobApplication.jobOffer', 'interviewer', 'survey.questions'])
            ->find($interviewId);

        if (! $interview || $interview->status !== 'completed') {
            return new HtmlString('<div class="p-4 text-sm text-gray-500 dark:text-gray-400">Aún no hay resultados para esta entrevista.</div>');
        }

        $report = $this->buildInterviewReport($interview);
        $ai = $this->ensureInterviewAiReport($interview, $report);
        $aiScore = $this->ensureInterviewAiScore($interview, $report);

        $title = e($interview->survey?->title ?? 'Entrevista');
        $candidate = e($interview->jobApplication?->applicant_name ?? '—');
        $offer = e($interview->jobApplication?->jobOffer?->title ?? '—');
        $date = e(optional($interview->scheduled_at)->format('d/m/Y H:i'));
        $interviewer = e($interview->interviewer?->name ?? '—');

        $html = '<div class="space-y-6">';
        $html .= '<div class="rounded-lg border border-gray-200 dark:border-gray-800 p-4 bg-white dark:bg-gray-900">';
        $html .= '<div class="text-sm text-gray-600 dark:text-gray-300">Candidato: <span class="font-medium text-gray-900 dark:text-gray-100">' . $candidate . '</span></div>';
        $html .= '<div class="text-sm text-gray-600 dark:text-gray-300">Oferta: <span class="font-medium text-gray-900 dark:text-gray-100">' . $offer . '</span></div>';
        $html .= '<div class="text-sm text-gray-600 dark:text-gray-300">Tipo: <span class="font-medium text-gray-900 dark:text-gray-100">' . $title . '</span></div>';
        $html .= '<div class="text-sm text-gray-600 dark:text-gray-300">Entrevistador: <span class="font-medium text-gray-900 dark:text-gray-100">' . $interviewer . '</span></div>';
        $html .= '<div class="text-sm text-gray-600 dark:text-gray-300">Fecha: <span class="font-medium text-gray-900 dark:text-gray-100">' . $date . '</span></div>';
        $html .= '</div>';

        $html .= '<div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">';
        $html .= '<table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">';
        $html .= '<thead class="bg-gray-50 dark:bg-gray-800">';
        $html .= '<tr>';
        $html .= '<th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Dimensión</th>';
        $html .= '<th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Peso</th>';
        $html .= '<th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Meta</th>';
        $html .= '<th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Resultado</th>';
        $html .= '<th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Cumplimiento</th>';
        $html .= '<th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Rating</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody class="divide-y divide-gray-200 dark:divide-gray-800">';

        foreach ($report['dimensions'] as $row) {
            $html .= '<tr>';
            $html .= '<td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100">' . e($row['name']) . '</td>';
            $html .= '<td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-200">' . e($row['weight_label']) . '</td>';
            $html .= '<td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-200">' . e($row['kpi_label']) . '</td>';
            $html .= '<td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-200">' . e($row['avg_label']) . '</td>';
            $html .= '<td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-200">' . e($row['compliance_label']) . '</td>';
            $html .= '<td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-200">' . e($row['rating'] ?? '—') . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';

        $qualitative = $report['qualitative'] ?? [];
        if (is_array($qualitative) && ! empty($qualitative)) {
            $html .= '<div class="rounded-lg border border-gray-200 dark:border-gray-800 p-4 bg-white dark:bg-gray-900">';
            $html .= '<div class="text-sm font-semibold text-gray-900 dark:text-gray-100">Respuestas cualitativas</div>';
            $html .= '<div class="mt-3 space-y-4">';

            foreach ($qualitative as $dimName => $items) {
                if (empty($items) || ! is_array($items)) {
                    continue;
                }

                $html .= '<div class="rounded-md border border-gray-100 dark:border-gray-800">';
                $html .= '<div class="px-3 py-2 text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-gray-800">';
                $html .= e((string) $dimName);
                $html .= '</div>';
                $html .= '<div class="divide-y divide-gray-100 dark:divide-gray-800">';

                foreach ($items as $it) {
                    $q = e((string) ($it['question'] ?? ''));
                    $a = e((string) ($it['answer'] ?? ''));
                    if ($q === '' && $a === '') {
                        continue;
                    }
                    $html .= '<div class="px-3 py-2">';
                    if ($q !== '') {
                        $html .= '<div class="text-sm text-gray-900 dark:text-gray-100">' . $q . '</div>';
                    }
                    if ($a !== '') {
                        $html .= '<div class="mt-1 text-sm text-gray-700 dark:text-gray-200 whitespace-pre-line">' . $a . '</div>';
                    }
                    $html .= '</div>';
                }

                $html .= '</div></div>';
            }

            $html .= '</div></div>';
        }

        $html .= '<div class="grid grid-cols-1 md:grid-cols-3 gap-4">';
        $html .= '<div class="rounded-lg border border-gray-200 dark:border-gray-800 p-4 bg-white dark:bg-gray-900">';
        $html .= '<div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Promedio global</div>';
        $html .= '<div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">' . e($report['global_avg'] ?? '—') . '</div>';
        $html .= '</div>';
        $html .= '<div class="rounded-lg border border-gray-200 dark:border-gray-800 p-4 bg-white dark:bg-gray-900">';
        $html .= '<div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Promedio ponderado</div>';
        $html .= '<div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">' . e($report['weighted_avg'] ?? '—') . '</div>';
        $html .= '</div>';
        $html .= '<div class="rounded-lg border border-gray-200 dark:border-gray-800 p-4 bg-white dark:bg-gray-900">';
        $html .= '<div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Puntaje entrevista</div>';
        $html .= '<div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">' . e($report['score_label'] ?? '—') . '</div>';
        $html .= '</div>';
        $html .= '</div>';

        $aiScoreValue = $aiScore['score'] ?? null;
        if ($aiScoreValue !== null) {
            $html .= '<div class="rounded-lg border border-gray-200 dark:border-gray-800 p-4 bg-white dark:bg-gray-900">';
            $html .= '<div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Score IA</div>';
            $html .= '<div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">' . e(number_format((float) $aiScoreValue, 2)) . '</div>';
            $html .= '</div>';
        }

        $html .= '<div class="rounded-lg border border-gray-200 dark:border-gray-800 p-4 bg-white dark:bg-gray-900">';
        $html .= '<div class="text-sm font-semibold text-gray-900 dark:text-gray-100">Apreciación IA</div>';
        if ($ai['content'] ?? null) {
            $html .= '<div class="mt-2 text-sm text-gray-700 dark:text-gray-200 whitespace-pre-line">' . e((string) $ai['content']) . '</div>';
        } else {
            $html .= '<div class="mt-2 text-sm text-gray-500 dark:text-gray-400">' . e((string) ($ai['message'] ?? 'Sin apreciación.')) . '</div>';
        }
        $html .= '</div>';

        $html .= '</div>';

        return new HtmlString($html);
    }

    protected function buildInterviewReport(JobInterview $interview): array
    {
        $survey = $interview->survey;
        $questions = $survey?->questions ?? collect();
        $qIds = $questions->pluck('id')->all();

        $responsesByQid = Response::query()
            ->forInterview($interview->id)
            ->whereIn('question_id', $qIds)
            ->get(['question_id', 'value'])
            ->keyBy('question_id');

        if ($responsesByQid->isEmpty()) {
            Response::backfillInterviewResponses($interview, $qIds);

            $responsesByQid = Response::query()
                ->forInterview($interview->id)
                ->whereIn('question_id', $qIds)
                ->get(['question_id', 'value'])
                ->keyBy('question_id');
        }

        $dimensionsCatalog = Dimension::query()
            ->where('survey_name', $survey?->title)
            ->get()
            ->keyBy('item');

        $dimensions = $questions
            ->map(fn ($q) => (string) ($q->item ?: 'General'))
            ->unique()
            ->values()
            ->all();

        $byDim = [];
        $qualitative = [];
        foreach ($questions as $q) {
            $dim = (string) ($q->item ?: 'General');
            $raw = $responsesByQid->get($q->id)?->value;
            if ($raw === null || (is_string($raw) && trim($raw) === '') || $raw === 'Sin Respuesta') {
                continue;
            }

            $rawValue = $raw;
            if (is_string($rawValue) && $rawValue !== '' && str_starts_with(trim($rawValue), '[')) {
                $decoded = json_decode($rawValue, true);
                if (is_array($decoded)) {
                    $rawValue = implode(', ', array_map('strval', $decoded));
                }
            }

            $normalized = null;
            $type = $q->type;

            if ($type === 'scale_10' && is_numeric($raw)) {
                $normalized = ((float) $raw / 10.0) * 100.0;
            } elseif ($type === 'scale_5' && is_numeric($raw)) {
                $normalized = ((float) $raw / 5.0) * 100.0;
            } elseif ($type === 'likert' && is_numeric($raw)) {
                $v = (float) $raw;
                $normalized = (($v - 1.0) / 4.0) * 100.0;
            } elseif (($type === 'bool' || $type === 'boolean' || $type === 'true_false' || $type === 'vf') && is_string($raw)) {
                $normalized = strtolower($raw) === 'si' ? 100.0 : 0.0;
            } elseif (is_numeric($raw)) {
                $normalized = ((float) $raw / 10.0) * 100.0;
            }

            if ($normalized === null) {
                $qualitative[$dim] ??= [];
                $qualitative[$dim][] = [
                    'question' => (string) $q->content,
                    'answer' => (string) $rawValue,
                ];
                continue;
            }

            $byDim[$dim] ??= [];
            $byDim[$dim][] = $normalized;
        }

        $rows = [];
        foreach ($dimensions as $dim) {
            $values = $byDim[$dim] ?? [];
            $avg = count($values) ? round(array_sum($values) / count($values), 2) : null;
            $dimRow = $dimensionsCatalog->get($dim);
            $kpi = $dimRow?->kpi_target;
            $weight = $dimRow?->weight;
            $compliance = ($avg !== null && $kpi !== null && $kpi > 0) ? min(100.0, round(($avg / $kpi) * 100.0, 2)) : null;
            $rating = null;
            if ($compliance !== null) {
                $rating = match (true) {
                    $compliance < 55 => 'Deficiente',
                    $compliance < 70 => 'Regular',
                    $compliance < 85 => 'Bueno',
                    default => 'Excelente',
                };
            }

            $rows[] = [
                'name' => $dim,
                'weight' => $weight,
                'kpi' => $kpi,
                'avg' => $avg,
                'compliance' => $compliance,
                'rating' => $rating,
                'weight_label' => $weight !== null ? number_format((float) $weight, 0) . '%' : '—',
                'kpi_label' => $kpi !== null ? number_format((float) $kpi, 0) : '—',
                'avg_label' => $avg !== null ? number_format((float) $avg, 2) : '—',
                'compliance_label' => $compliance !== null ? number_format((float) $compliance, 2) . '%' : '—',
            ];
        }

        $globalAvg = ! empty($rows)
            ? round(collect($rows)->pluck('avg')->filter()->avg(), 2)
            : null;

        $weightedAvg = null;
        $weightedDen = 0.0;
        $weightedSum = 0.0;
        foreach ($rows as $row) {
            if (! isset($row['avg']) || ! is_numeric($row['avg']) || ! isset($row['weight']) || ! is_numeric($row['weight'])) {
                continue;
            }
            $w = (float) $row['weight'];
            if ($w <= 0) {
                continue;
            }
            $weightedDen += $w;
            $weightedSum += ((float) $row['avg']) * $w;
        }
        if ($weightedDen > 0) {
            $weightedAvg = round($weightedSum / $weightedDen, 2);
        }

        $score = $weightedAvg ?? $globalAvg;

        return [
            'dimensions' => $rows,
            'global_avg' => $globalAvg !== null ? number_format((float) $globalAvg, 2) : null,
            'weighted_avg' => $weightedAvg !== null ? number_format((float) $weightedAvg, 2) : null,
            'score_label' => $score !== null ? number_format((float) $score, 2) : null,
            'score' => $score !== null ? round((float) $score, 2) : null,
            'qualitative' => $qualitative,
        ];
    }

    protected function ensureInterviewAiReport(JobInterview $interview, array $report): array
    {
        $version = 2;
        $hash = hash('sha256', json_encode([
            'dimensions' => $report['dimensions'] ?? [],
            'global_avg' => $report['global_avg'] ?? null,
            'weighted_avg' => $report['weighted_avg'] ?? null,
            'qualitative' => $report['qualitative'] ?? [],
        ]));

        if (! empty($interview->ai_report) && (int) ($interview->ai_report_version ?? 0) === $version && ($interview->ai_report_source_hash ?? null) === $hash) {
            return [
                'content' => $interview->ai_report,
            ];
        }

        $provider = app(AiProviderService::class);
        if (! $provider->hasToken()) {
            return [
                'message' => 'Token IA no configurado.',
            ];
        }

        try {
            $candidate = $interview->jobApplication?->applicant_name ?? 'candidato';
            $offer = $interview->jobApplication?->jobOffer?->title ?? 'oferta';
            $title = $interview->survey?->title ?? 'entrevista';

            $lines = [];
            foreach (($report['dimensions'] ?? []) as $d) {
                $lines[] = ($d['name'] ?? '-') . ': resultado=' . ($d['avg_label'] ?? '—') . ' meta=' . ($d['kpi_label'] ?? '—') . ' peso=' . ($d['weight_label'] ?? '—') . ' cumplimiento=' . ($d['compliance_label'] ?? '—') . ' rating=' . ($d['rating'] ?? '—');
            }

            $qualitativeLines = [];
            foreach (($report['qualitative'] ?? []) as $dim => $items) {
                foreach (($items ?? []) as $it) {
                    $q = trim((string) ($it['question'] ?? ''));
                    $a = trim((string) ($it['answer'] ?? ''));
                    if ($q === '' && $a === '') {
                        continue;
                    }
                    $qualitativeLines[] = "{$dim} | {$q}: {$a}";
                }
            }

            $prompt = "Genera una apreciación breve de la entrevista.\n"
                . "Candidato: {$candidate}\n"
                . "Oferta: {$offer}\n"
                . "Tipo: {$title}\n"
                . "Promedio global: " . ($report['global_avg'] ?? 'N/A') . "\n"
                . "Promedio ponderado: " . ($report['weighted_avg'] ?? 'N/A') . "\n"
                . "Detalle por dimensión:\n- " . implode("\n- ", $lines) . "\n\n"
                . (empty($qualitativeLines) ? '' : ("Respuestas cualitativas:\n- " . implode("\n- ", $qualitativeLines) . "\n\n"))
                . "Entrega ÚNICAMENTE: una conclusión final y 3-5 sugerencias accionables para el entrevistador. Sin títulos ni secciones.";

            $ai = $provider->text()
                ->using('openai', 'gpt-5-nano')
                ->withPrompt($prompt);

            $response = $ai->asText();
            $content = $response->text;

            $interview->forceFill([
                'ai_report' => $content,
                'ai_report_generated_at' => now(),
                'ai_report_version' => $version,
                'ai_report_source_hash' => $hash,
            ])->save();

            return [
                'content' => $content,
            ];
        } catch (\Throwable) {
            return [
                'message' => 'No se pudo generar la apreciación IA.',
            ];
        }
    }

    protected function ensureInterviewAiScore(JobInterview $interview, array $report): array
    {
        $version = 1;
        $hash = hash('sha256', json_encode([
            'dimensions' => $report['dimensions'] ?? [],
            'global_avg' => $report['global_avg'] ?? null,
            'weighted_avg' => $report['weighted_avg'] ?? null,
            'qualitative' => $report['qualitative'] ?? [],
        ]));

        if (
            $interview->ai_score !== null &&
            (int) ($interview->ai_score_version ?? 0) === $version &&
            ($interview->ai_score_source_hash ?? null) === $hash
        ) {
            return [
                'score' => (float) $interview->ai_score,
            ];
        }

        $provider = app(AiProviderService::class);
        if (! $provider->hasToken()) {
            return [
                'message' => 'Token IA no configurado.',
            ];
        }

        try {
            $candidate = $interview->jobApplication?->applicant_name ?? 'candidato';
            $offer = $interview->jobApplication?->jobOffer?->title ?? 'oferta';
            $title = $interview->survey?->title ?? 'entrevista';

            $lines = [];
            foreach (($report['dimensions'] ?? []) as $d) {
                $lines[] = ($d['name'] ?? '-') . ': resultado=' . ($d['avg_label'] ?? '—') . ' meta=' . ($d['kpi_label'] ?? '—') . ' peso=' . ($d['weight_label'] ?? '—') . ' cumplimiento=' . ($d['compliance_label'] ?? '—') . ' rating=' . ($d['rating'] ?? '—');
            }

            $qualitativeLines = [];
            foreach (($report['qualitative'] ?? []) as $dim => $items) {
                foreach (($items ?? []) as $it) {
                    $q = trim((string) ($it['question'] ?? ''));
                    $a = trim((string) ($it['answer'] ?? ''));
                    if ($q === '' && $a === '') {
                        continue;
                    }
                    $qualitativeLines[] = "{$dim} | {$q}: {$a}";
                }
            }

            $prompt = "Asigna un puntaje numérico de 0 a 100 (con máximo 2 decimales) para el desempeño del candidato en esta entrevista.\n"
                . "Candidato: {$candidate}\n"
                . "Oferta: {$offer}\n"
                . "Tipo: {$title}\n"
                . "Promedio global (numérico): " . ($report['global_avg'] ?? 'N/A') . "\n"
                . "Promedio ponderado (numérico): " . ($report['weighted_avg'] ?? 'N/A') . "\n"
                . "Detalle por dimensión:\n- " . implode("\n- ", $lines) . "\n\n"
                . (empty($qualitativeLines) ? '' : ("Respuestas cualitativas:\n- " . implode("\n- ", $qualitativeLines) . "\n\n"))
                . "Responde ÚNICAMENTE con el número (ej: 78.50). Sin texto adicional.";

            $ai = $provider->text()
                ->using('openai', 'gpt-5-nano')
                ->withPrompt($prompt);

            $response = $ai->asText();
            $text = trim((string) $response->text);

            preg_match('/-?\d+(?:\.\d+)?/', $text, $m);
            $score = isset($m[0]) ? (float) $m[0] : null;

            if ($score === null) {
                return [
                    'message' => 'No se pudo calcular el score IA.',
                ];
            }

            $score = max(0, min(100, $score));
            $score = round($score, 2);

            $interview->forceFill([
                'ai_score' => $score,
                'ai_score_generated_at' => now(),
                'ai_score_version' => $version,
                'ai_score_source_hash' => $hash,
            ])->save();

            return [
                'score' => $score,
            ];
        } catch (\Throwable) {
            return [
                'message' => 'No se pudo calcular el score IA.',
            ];
        }
    }
}
