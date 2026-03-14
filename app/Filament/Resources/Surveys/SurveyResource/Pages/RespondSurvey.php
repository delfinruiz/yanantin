<?php

namespace App\Filament\Resources\Surveys\SurveyResource\Pages;

use App\Filament\Resources\Surveys\SurveyResource;
use App\Models\Response;
use App\Models\Survey;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RespondSurvey extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = SurveyResource::class;

    protected string $view = 'filament.pages.respond-survey';

    public Survey $record;

    public ?array $data = [];
    
    public ?string $interviewId = null;

    public static function canAccess(array $parameters = []): bool
    {
        return \Illuminate\Support\Facades\Auth::check();
    }

    protected function authorizeAccess(): void
    {
        $userId = Auth::id();
        $interviewId = request()->query('interview_id');

        if ($interviewId) {
            $interview = \App\Models\JobInterview::find($interviewId);
            $allowed = $interview
                && (int) $interview->survey_id === (int) $this->record->id
                && (int) $interview->interviewer_id === (int) $userId;
            abort_unless($allowed, 403);
            return;
        }

        $isAssigned = $this->record->users()->where('users.id', $userId)->exists();
        abort_unless($isAssigned, 403);
    }

    public function mount(Survey $record): void
    {
        $this->record = $record;
        $this->interviewId = request()->query('interview_id');
        $this->form->fill($this->getInitialState());
    }

    protected function getInitialState(): array
    {
        $state = [];
        foreach ($this->record->questions as $q) {
            $existing = Response::query()
                ->where('question_id', $q->id)
                ->when(
                    $this->interviewId,
                    fn ($query) => $query->forInterview((int) $this->interviewId),
                    fn ($query) => $query->withoutInterview()->where('user_id', Auth::id())
                )
                ->first();
            $value = $existing ? $existing->value : null;
            $state['q_'.$q->id] = $value === 'Sin Respuesta' ? null : $value;
        }
        return $state;
    }

    public function form(Schema $schema): Schema
    {
        $components = [];
        $grouped = $this->record->questions->groupBy('item');
        foreach ($grouped as $item => $questions) {
            $components[] = \Filament\Schemas\Components\Section::make($item)->schema(
                $questions->map(function ($q) {
                    $key = 'q_'.$q->id;
                    switch ($q->type) {
                        case 'text':
                            return Textarea::make($key)->label($q->content)->required($q->required);
                        case 'bool':
                        case 'boolean':
                        case 'true_false':
                        case 'vf':
                            return Radio::make($key)
                                ->label($q->content)
                                ->options([
                                    'si' => 'Sí',
                                    'no' => 'No',
                                ])
                                ->columns(2)
                                ->required($q->required);
                        case 'scale_5':
                            return Select::make($key)->label($q->content)->options([
                                '0' => '0','1' => '1','2' => '2','3' => '3','4' => '4','5' => '5',
                            ])->required($q->required);
                        case 'scale_10':
                            $opts = [];
                            for ($i=0; $i<=10; $i++) $opts[(string)$i] = (string)$i;
                            return Select::make($key)->label($q->content)->options($opts)->required($q->required);
                        case 'likert':
                            $options = $q->options ?: [
                                '1' => 'Nunca',
                                '2' => 'Casi nunca',
                                '3' => 'A veces',
                                '4' => 'Casi siempre',
                                '5' => 'Siempre',
                            ];
                            return Select::make($key)->label($q->content)->options($options)->required($q->required);
                        case 'multi':
                            $options = $q->options ?: [];
                            return Select::make($key)->label($q->content)->options($options)->multiple()->required($q->required);
                        default:
                            return TextInput::make($key)->label($q->content)->required($q->required);
                    }
                })->values()->all()
            )->columnSpanFull();
        }

        return $schema->schema($components)->model($this->record)->statePath('data');
    }

    public function submit(): void
    {
        try {
            $this->form->validate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            Notification::make()
                ->title('Hay preguntas obligatorias sin responder.')
                ->danger()
                ->send();
            return;
        }

        foreach ($this->record->questions as $q) {
            $key = 'q_'.$q->id;
            $val = $this->form->getState()[$key] ?? null;

            if (is_string($val) && trim($val) === '') {
                $val = null;
            }

            if (is_array($val) && empty($val)) {
                $val = null;
            }

            if (! $q->required && $val === null) {
                $val = 'Sin Respuesta';
            }

            if ($val !== null) {
                $attributes = [
                    'question_id' => $q->id,
                    'user_id' => Auth::id(),
                ];

                if ($this->interviewId) {
                    if (Response::hasJobInterviewIdColumn()) {
                        $attributes['job_interview_id'] = (int) $this->interviewId;
                    }
                } elseif (Response::hasJobInterviewIdColumn()) {
                    $attributes['job_interview_id'] = null;
                }

                Response::updateOrCreate($attributes, [
                    'value' => is_array($val) ? json_encode($val) : (string) $val,
                ]);
            }
        }
        Notification::make()->title('Respuestas guardadas')->success()->send();
        
        if ($this->interviewId) {
            $interview = \App\Models\JobInterview::find($this->interviewId);
            if ($interview && $interview->status === 'scheduled') {
                $survey = $interview->survey()->with('questions')->first();
                $score = null;

                if ($survey) {
                    $questions = $survey->questions;
                    $qIds = $questions->pluck('id')->all();

                    $responsesByQid = Response::query()
                        ->forInterview((int) $interview->id)
                        ->whereIn('question_id', $qIds)
                        ->get(['question_id', 'value'])
                        ->keyBy('question_id');

                    if ($responsesByQid->isEmpty()) {
                        Response::backfillInterviewResponses($interview, $qIds);

                        $responsesByQid = Response::query()
                            ->forInterview((int) $interview->id)
                            ->whereIn('question_id', $qIds)
                            ->get(['question_id', 'value'])
                            ->keyBy('question_id');
                    }

                    $dimensionsCatalog = \App\Models\Dimension::query()
                        ->where('survey_name', $survey->title)
                        ->get()
                        ->keyBy('item');

                    $dimensions = $questions
                        ->map(fn ($q) => (string) ($q->item ?: 'General'))
                        ->unique()
                        ->values()
                        ->all();

                    $byDim = [];
                    foreach ($questions as $q) {
                        $dim = (string) ($q->item ?: 'General');
                        $raw = $responsesByQid->get($q->id)?->value;

                        if ($raw === null || (is_string($raw) && trim($raw) === '') || $raw === 'Sin Respuesta') {
                            continue;
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
                            continue;
                        }

                        $byDim[$dim] ??= [];
                        $byDim[$dim][] = $normalized;
                    }

                    $rows = [];
                    foreach ($dimensions as $dim) {
                        $values = $byDim[$dim] ?? [];
                        $avg = count($values) ? (array_sum($values) / count($values)) : null;
                        $dimRow = $dimensionsCatalog->get($dim);
                        $weight = $dimRow?->weight;

                        $rows[] = [
                            'avg' => $avg,
                            'weight' => $weight,
                        ];
                    }

                    $avgs = array_values(array_filter(array_map(fn ($r) => $r['avg'], $rows), fn ($v) => is_numeric($v)));
                    $globalAvg = count($avgs) ? (array_sum($avgs) / count($avgs)) : null;

                    $weightedDen = 0.0;
                    $weightedSum = 0.0;
                    foreach ($rows as $row) {
                        if (! is_numeric($row['avg']) || ! is_numeric($row['weight'])) {
                            continue;
                        }
                        $w = (float) $row['weight'];
                        if ($w <= 0) {
                            continue;
                        }
                        $weightedDen += $w;
                        $weightedSum += ((float) $row['avg']) * $w;
                    }
                    $weightedAvg = $weightedDen > 0 ? ($weightedSum / $weightedDen) : null;

                    $score = $weightedAvg ?? $globalAvg;
                    if ($score !== null) {
                        $score = round((float) $score, 2);
                    }
                }

                $interview->update([
                    'status' => 'completed',
                    'score' => $score,
                ]);
                
                Notification::make()
                    ->title('Entrevista finalizada')
                    ->success()
                    ->send();
                    
                // Redirigir a Mis Entrevistas
                $this->redirect(\App\Filament\Pages\MyInterviews::getUrl());
                return;
            }
        }
    }
}
