<?php

namespace App\Filament\Resources\Surveys\SurveyResource\Pages;

use App\Filament\Resources\Surveys\SurveyResource;
use App\Models\Survey;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Mail;
use App\Mail\SurveyAssignedMail;
use App\Services\MailGuardService;
use Filament\Actions\Action;
use Illuminate\Support\Facades\DB;
use Filament\Support\Enums\Width;

class EditSurvey extends EditRecord
{
    protected static string $resource = SurveyResource::class;

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }


    // dimensiones gestionadas desde catálogo global; sin acciones inline

    protected function afterSave(): void
    {
        /** @var Survey $record */
        $record = $this->record;
        if ((bool) ($this->data['public_enabled'] ?? false)) {
            $record->public_enabled = true;
            $record->ensurePublicToken();
        } else {
            $record->public_enabled = false;
            $record->save();
        }
        $assignAll = $this->data['assign_all'] ?? false;
        $deptIds = $this->data['departments'] ?? $record->departments()->pluck('departments.id')->all();

        if ($assignAll) {
            $userIds = User::pluck('id')->all();
            $payload = collect($userIds)->mapWithKeys(fn ($id) => [$id => ['assigned_at' => now()]])->all();
            $record->users()->sync($payload);
            $record->departments()->detach();
        } else {
            $record->departments()->sync($deptIds ?? []);
            $userIds = [];
            if (!empty($deptIds)) {
                $userIds = User::whereHas('departments', fn ($q) => $q->whereIn('departments.id', $deptIds))->pluck('id')->all();
            }
            $payload = collect($userIds)->mapWithKeys(fn ($id) => [$id => ['assigned_at' => now()]])->all();
            $record->users()->sync($payload);
        }

        if ($record->active) {
            $recipientIds = $record->users()->pluck('users.id')->all();
            if (!empty($recipientIds)) {
                $recipients = User::whereIn('id', $recipientIds)->get();
                Notification::make()
                    ->title(__('surveys.notifications.active_updated'))
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
}
