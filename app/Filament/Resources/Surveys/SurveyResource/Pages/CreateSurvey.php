<?php

namespace App\Filament\Resources\Surveys\SurveyResource\Pages;

use App\Filament\Resources\Surveys\SurveyResource;
use App\Models\Survey;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\SurveyAssignedMail;
use App\Services\MailGuardService;
use App\Models\Dimension;
use Filament\Support\Enums\Width;

class CreateSurvey extends CreateRecord
{
    protected static string $resource = SurveyResource::class;

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }


    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['creator_id'] = Auth::id();
        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var Survey $record */
        $record = $this->record;
        if ((bool) ($this->data['public_enabled'] ?? false)) {
            $record->public_enabled = true;
            $record->ensurePublicToken();
        }
        $assignAll = $this->data['assign_all'] ?? false;
        $deptIds = $this->data['departments'] ?? $record->departments()->pluck('departments.id')->all();

        if ($assignAll) {
            $userIds = User::pluck('id')->all();
            $payload = collect($userIds)->mapWithKeys(fn ($id) => [$id => ['assigned_at' => now()]])->all();
            $record->users()->syncWithoutDetaching($payload);
        } elseif (!empty($deptIds)) {
            $userIds = User::whereHas('departments', fn ($q) => $q->whereIn('departments.id', $deptIds))->pluck('id')->all();
            $record->departments()->sync($deptIds);
            $payload = collect($userIds)->mapWithKeys(fn ($id) => [$id => ['assigned_at' => now()]])->all();
            $record->users()->syncWithoutDetaching($payload);
        }

        if ($record->active) {
            $recipientIds = $record->users()->pluck('users.id')->all();
            if (!empty($recipientIds)) {
                $recipients = User::whereIn('id', $recipientIds)->get();
                Notification::make()
                    ->title(__('surveys.notifications.new_assigned'))
                    ->body($record->title)
                    ->success()
                    ->sendToDatabase($recipients);
                $guard = app(MailGuardService::class);
                foreach ($recipients as $user) {
                    if ($user->email && $guard->canSend($user->email)) {
                        try {
                            Mail::to($user->email)->send(new SurveyAssignedMail($record));
                        } catch (\Throwable $e) {
                        }
                    }
                }
            }
        }

        // sincronizar orden según la posición en el Repeater
        $qs = $this->form->getState()['questions'] ?? [];
        $i = 0;
        foreach ($qs as $row) {
            $id = $row['id'] ?? null;
            if ($id) {
                $record->questions()->where('id', $id)->update(['order' => $i]);
                $i++;
            }
        }
    }

    public function saveDimensionsInline(): void
    {
        $dims = $this->data['dimensions'] ?? [];
        $record = $this->record;
        if (! $record?->id) {
            $state = $this->form->getState();
            $record = Survey::create([
                'title' => $state['title'] ?? __('surveys.defaults.title'),
                'description' => $state['description'] ?? null,
                'active' => (bool) ($state['active'] ?? false),
                'is_public' => (bool) ($state['is_public'] ?? false),
                'deadline' => $state['deadline'] ?? null,
                'creator_id' => \Illuminate\Support\Facades\Auth::id(),
            ]);
            $this->record = $record;
            $this->data['survey_id'] = $record->id;
        }
        $valid = collect($dims)->filter(function ($d) {
            return trim($d['item'] ?? '') !== '';
        });
        if ($valid->isEmpty()) {
            Notification::make()
                ->title('Agrega al menos una dimensión con nombre')
                ->danger()
                ->send();
            return;
        }
        foreach ($valid as $d) {
            $item = trim($d['item'] ?? '');
            if ($item === '') continue;
            Dimension::updateOrCreate(
                ['survey_name' => $record->title, 'item' => $item],
                [
                    'kpi_target' => (float) ($d['kpi_target'] ?? 10),
                    'weight' => isset($d['weight']) ? (float) $d['weight'] : null,
                ]
            );
        }
        $saved = Dimension::where('survey_name', $record->title)->pluck('item', 'item')->toArray();
        $this->data['dimension_items'] = json_encode($saved);
        Notification::make()
            ->title('Dimensiones guardadas')
            ->success()
            ->send();
    }
}
