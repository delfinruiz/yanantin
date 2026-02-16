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

    public static function canAccess(array $parameters = []): bool
    {
        return \Illuminate\Support\Facades\Auth::check();
    }

    protected function authorizeAccess(): void
    {
        $userId = Auth::id();
        $isAssigned = $this->record->users()->where('users.id', $userId)->exists();
        Log::info('RespondSurvey.authorizeAccess', [
            'user_id' => $userId,
            'survey_id' => $this->record->id,
            'is_assigned' => $isAssigned,
            'route' => request()?->path(),
        ]);
        abort_unless($isAssigned, 403);
    }

    public function mount(Survey $record): void
    {
        $this->record = $record;
        $userId = Auth::id();
        $pendingRequired = $this->record->questions()
            ->where('required', true)
            ->whereDoesntHave('responses', fn ($q) => $q->where('user_id', $userId))
            ->exists();
        if (! $pendingRequired) {
            Notification::make()
                ->title('Encuesta ya contestada')
                ->warning()
                ->send();
            $this->redirect(SurveyResource::getUrl('index'));
            return;
        }
        $this->form->fill($this->getInitialState());
    }

    protected function getInitialState(): array
    {
        $state = [];
        foreach ($this->record->questions as $q) {
            $existing = Response::where('question_id', $q->id)->where('user_id', Auth::id())->first();
            $state['q_'.$q->id] = $existing ? $existing->value : null;
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
        foreach ($this->record->questions as $q) {
            $key = 'q_'.$q->id;
            $val = $this->form->getState()[$key] ?? null;
            if ($q->required && ($val === null || $val === '')) {
                Notification::make()->title('Por favor responda todas las preguntas requeridas.')->danger()->send();
                return;
            }
            if ($val !== null) {
                Response::updateOrCreate(
                    ['question_id' => $q->id, 'user_id' => Auth::id()],
                    ['value' => is_array($val) ? json_encode($val) : (string) $val]
                );
            }
        }
        Notification::make()->title('Respuestas guardadas')->success()->send();
    }
}
