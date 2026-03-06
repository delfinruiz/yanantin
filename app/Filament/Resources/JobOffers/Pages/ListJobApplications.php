<?php

namespace App\Filament\Resources\JobOffers\Pages;

use App\Filament\Resources\JobOffers\JobOfferResource;
use App\Models\CandidateProfile;
use App\Models\JobApplication;
use App\Models\User;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Actions;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Support\Enums\FontWeight;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Support\Enums\Width;
use App\Services\ApplicationScoringService;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Support\Enums\TextSize;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;

use App\Services\OpenAiCandidateAnalysisService;

class ListJobApplications extends Page implements HasTable, HasInfolists, HasForms
{
    use InteractsWithTable;
    use InteractsWithInfolists;
    use InteractsWithForms;

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
                    ->orderByRaw("FIELD(eligibility_status, 'eligible', 'not_eligible') DESC") // Priorizar elegibles
                    ->orderByDesc('score') // Luego por puntaje
            )
            ->defaultSort('score', 'desc')
            ->columns([
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
                    ->numeric(2)
                    ->sortable()
                    ->color(fn ($state) => $state >= 70 ? 'success' : ($state >= 40 ? 'warning' : 'danger'))
                    ->weight(FontWeight::Bold),
                Tables\Columns\TextColumn::make('ai_analysis.qualitative_score')
                    ->label('Score IA')
                    ->badge()
                    ->color(fn ($state) => $state >= 70 ? 'success' : ($state >= 40 ? 'warning' : 'danger'))
                    ->formatStateUsing(fn ($state) => $state ? "{$state}%" : '-')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'submitted', 'pending' => 'info',
                        'reviewed' => 'warning',
                        'hired' => 'success',
                        'rejected' => 'danger',
                        'cancelled' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'submitted', 'pending' => 'Recibida',
                        'reviewed' => 'En revisión',
                        'hired' => 'Seleccionado',
                        'rejected' => 'Descartado',
                        'cancelled' => 'Cancelada',
                        default => ucfirst($state),
                    }),
            ])
            ->actions([
                ViewAction::make()
                    ->label('Ver Ficha')
                    ->modalWidth('7xl')
                    ->infolist(fn (Schema $schema) => $this->candidateInfolist($schema)),
                Action::make('analysis')
                    ->label('Ver Análisis')
                    ->icon('heroicon-o-chart-bar')
                    ->color('primary')
                    ->modalWidth('5xl')
                    ->visible(fn (JobApplication $record) => $record->auto_decision_log !== null)
                    ->modalSubmitAction(false) // Ocultar botón "Enviar" ya que es solo lectura
                    ->modalCancelActionLabel('Cerrar') // Renombrar "Cancelar" a "Cerrar"
                    ->infolist(fn (Schema $schema) => $this->scoringAnalysisInfolist($schema)),
                Action::make('change_status')
                    ->label('Cambiar Estado')
                    ->icon('heroicon-o-pencil-square')
                    ->color('info')
                    ->fillForm(fn (JobApplication $record) => ['status' => $record->status])
                    ->form([
                        Select::make('status')
                            ->label('Nuevo Estado')
                            ->options([
                                'submitted' => 'Recibida',
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
                            ->placeholder('Ingrese un comentario sobre el cambio de estado...'),
                    ])
                    ->action(function (JobApplication $record, array $data) {
                        $record->update([
                            'status' => $data['status'],
                        ]);
                        
                        // Aquí se podría guardar el comentario en un historial si existiera la tabla
                        // Por ahora solo notificamos
                        
                        Notification::make()
                            ->title('Estado actualizado')
                            ->body("La postulación ha pasado a estado: " . ucfirst($data['status']))
                            ->success()
                            ->send();
                    }),
                Action::make('recalculate')
                    ->label('Recalcular')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->action(function (JobApplication $record, ApplicationScoringService $service) {
                        Notification::make()
                            ->title('Recalculando...')
                            ->info()
                            ->send();

                        // Forzar nulo para que procese todo desde cero
                        $record->eligibility_status = null;
                        $record->ai_analysis = null; // Limpiar análisis previo
                        
                        $service->processApplication($record);
                        
                        Notification::make()
                            ->title('Puntaje y análisis IA recalculados')
                            ->success()
                            ->send();
                    }),
            ])
            ->headerActions([
                Action::make('recalculate_all')
                    ->label('Recalcular Todos')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription('Esta acción recalculará el puntaje de TODAS las postulaciones y ejecutará el análisis de IA. Esto puede tomar varios minutos dependiendo de la cantidad de registros. ¿Desea continuar?')
                    ->action(function (ApplicationScoringService $service) {
                        // Aumentar tiempo de ejecución para proceso largo
                        set_time_limit(300);
                        
                        $count = 0;
                        $applications = JobApplication::where('job_offer_id', $this->record->id)->get();
                        
                        Notification::make()
                            ->title('Proceso iniciado en segundo plano')
                            ->info()
                            ->send();

                        foreach ($applications as $app) {
                            $app->eligibility_status = null;
                            $app->ai_analysis = null; // Resetear análisis IA para forzar nuevo
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
            ->schema([
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
                        Grid::make(3) // Usar 3 columnas para mejor distribución
                            ->schema([
                                KeyValueEntry::make('auto_decision_log.experience_check')
                                    ->label('Validación de Experiencia')
                                    ->keyLabel('Criterio')
                                    ->valueLabel('Resultado')
                                    ->columnSpan(1)
                                    ->state(fn ($record) => [
                                        'Requerido (años)' => $record->auto_decision_log['experience_check']['required'] ?? '-',
                                        'Candidato (años)' => $record->auto_decision_log['experience_check']['candidate'] ?? '-',
                                        'Estado' => ($record->auto_decision_log['experience_check']['passed'] ?? false) ? 'Aprobado' : 'Rechazado',
                                    ]),
                                KeyValueEntry::make('auto_decision_log.education_check')
                                    ->label('Validación de Educación')
                                    ->keyLabel('Criterio')
                                    ->valueLabel('Resultado')
                                    ->columnSpan(1)
                                    ->state(fn ($record) => [
                                        'Nivel Requerido' => ucfirst($record->auto_decision_log['education_check']['required'] ?? '-'),
                                        'Nivel Candidato' => ucfirst($record->auto_decision_log['education_check']['candidate'] ?? '-'),
                                        'Estado' => ($record->auto_decision_log['education_check']['passed'] ?? false) ? 'Aprobado' : 'Rechazado',
                                    ]),
                                KeyValueEntry::make('auto_decision_log.skills_check')
                                    ->label('Habilidades Obligatorias')
                                    ->keyLabel('Métrica')
                                    ->valueLabel('Valor')
                                    ->columnSpan(1)
                                    ->state(fn ($record) => [
                                        'Total Requeridas' => $record->auto_decision_log['skills_check']['required_count'] ?? 0,
                                        'Faltantes' => $record->auto_decision_log['skills_check']['missing_count'] ?? 0,
                                        'Estado' => ($record->auto_decision_log['skills_check']['passed'] ?? false) ? 'Aprobado' : 'Rechazado',
                                    ]),
                            ]),
                        
                        Section::make('Desglose de Puntaje')
                            ->description('Detalle de puntos obtenidos por criterios adicionales')
                            ->compact()
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        KeyValueEntry::make('auto_decision_log.scoring_experience')
                                            ->label('Experiencia Extra')
                                            ->state(fn ($record) => [
                                                'Años Extra' => $record->auto_decision_log['scoring_experience']['extra_years'] ?? 0,
                                                'Puntos' => ($record->auto_decision_log['scoring_experience']['points'] ?? 0) . ' pts',
                                            ]),
                                        KeyValueEntry::make('auto_decision_log.scoring_skills')
                                            ->label('Habilidades Deseables')
                                            ->state(fn ($record) => [
                                                'Coincidencias' => ($record->auto_decision_log['scoring_skills']['matched_count'] ?? 0) . ' / ' . ($record->auto_decision_log['scoring_skills']['total_desirable'] ?? 0),
                                                'Puntos' => ($record->auto_decision_log['scoring_skills']['points'] ?? 0) . ' pts',
                                            ]),
                                        KeyValueEntry::make('auto_decision_log.scoring_education')
                                            ->label('Educación Superior')
                                            ->state(fn ($record) => [
                                                'Es Superior' => ($record->auto_decision_log['scoring_education']['is_superior'] ?? false) ? 'Sí' : 'No',
                                                'Puntos' => ($record->auto_decision_log['scoring_education']['points'] ?? 0) . ' pts',
                                            ]),
                                    ]),
                            ])
                            ->visible(fn ($record) => $record->eligibility_status === 'eligible'),
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
            ->schema([
                Tabs::make('Ficha del Candidato')
                    ->tabs([
                        Tab::make('Currículum')
                            ->icon('heroicon-m-document-text')
                            ->schema([
                                ViewEntry::make('cv_snapshot')
                                    ->hiddenLabel()
                                    ->view('filament.components.cv-snapshot-viewer'),
                            ]),
                        Tab::make('Entrevistas')
                            ->icon('heroicon-m-chat-bubble-left-right')
                            ->schema([
                                TextEntry::make('entrevistas_placeholder')
                                    ->hiddenLabel()
                                    ->state('Próximamente: Gestión de entrevistas y notas.'),
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
}
