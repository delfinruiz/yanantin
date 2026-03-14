<?php

namespace App\Filament\Resources\Surveys\SurveyResource\Pages;

use App\Filament\Resources\Surveys\SurveyResource;
use App\Models\Dimension;
use App\Models\Survey;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Mail;
use App\Mail\SurveyAssignedMail;
use App\Services\MailGuardService;
use Filament\Actions\DeleteAction;
use Filament\Support\Enums\Width;

class EditSurvey extends EditRecord
{
    protected static string $resource = SurveyResource::class;

    protected array $originalAssignedUserIds = [];
    protected bool $originalActive = false;
    public bool $isSilentSaving = false;
    public bool $pendingPublishNotification = false;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        /** @var Survey $survey */
        $survey = $this->record;

        $totalWeight = (float) Dimension::query()
            ->where('survey_name', $survey->title)
            ->sum('weight');

        $rounded = round($totalWeight, 2);

        if (abs($rounded - 100.0) > 0.001) {
            Notification::make()
                ->title('No se puede editar la encuesta')
                ->body('La sumatoria de pesos de dimensiones es ' . $rounded . '%. Debe ser 100% para editar.')
                ->danger()
                ->send();

            $this->redirect($this->getResource()::getUrl('index'));
            return;
        }
    }

    public function silentSave(): void
    {
        if ((bool) $this->record?->active) {
            return;
        }

        $this->isSilentSaving = true;
        $this->save();
        $this->isSilentSaving = false;
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->disabled(function (Survey $record): bool {
                    if ($record->is_interview) {
                        return \App\Models\JobInterview::where('survey_id', $record->id)->exists();
                    }

                    return $record->questions()->whereHas('responses')->exists();
                })
                ->tooltip(function (Survey $record): ?string {
                    if ($record->is_interview) {
                        return \App\Models\JobInterview::where('survey_id', $record->id)->exists()
                            ? 'No se puede borrar porque ya fue utilizada en entrevistas.'
                            : null;
                    }

                    return $record->questions()->whereHas('responses')->exists()
                        ? __('No se puede borrar porque tiene respuestas')
                        : null;
                }),
        ];
    }

    public bool $shouldRedirect = false;

    public function saveAndRedirect(): void
    {
        $this->shouldRedirect = true;
        $this->save();
        $this->shouldRedirect = false;
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->action('saveAndRedirect')
                ->submit(null),
            $this->getCancelFormAction(),
        ];
    }

    protected function getRedirectUrl(): ?string
    {
        if ($this->shouldRedirect) {
            return $this->getResource()::getUrl('index');
        }
        return null;
    }


    // dimensiones gestionadas desde catálogo global; sin acciones inline

    protected function beforeSave(): void
    {
        /** @var Survey $record */
        $record = $this->record;

        $this->originalActive = (bool) $record->active;
        $this->originalAssignedUserIds = $record->users()->pluck('users.id')->all();
    }

    protected function afterSave(): void
    {
        /** @var Survey $record */
        $record = $this->record;
        $state = $this->form->getRawState();

        if ((bool) ($state['public_enabled'] ?? false)) {
            $record->public_enabled = true;
            $record->ensurePublicToken();
        } else {
            $record->public_enabled = false;
            $record->save();
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

        if ($this->isSilentSaving) {
            if ((! $this->originalActive) && (bool) $record->active) {
                $this->pendingPublishNotification = true;
            }
            return;
        }

        if ((bool) $record->is_interview) {
            return;
        }

        $assignAll = (bool) ($state['assign_all'] ?? false);
        $assignPublicRole = (bool) ($state['assign_public_role'] ?? false);
        $deptIds = $state['departments'] ?? $record->departments()->pluck('departments.id')->all();

        $targetUserIds = collect();

        if ($assignAll) {
            // Asignar a todos los internos
            $targetUserIds = $targetUserIds->merge(User::where('is_internal', true)->pluck('id'));
            $record->departments()->detach();
        } else {
            $record->departments()->sync($deptIds ?? []);
            if (!empty($deptIds)) {
                $deptUserIds = User::where('is_internal', true)
                    ->whereHas('departments', fn ($q) => $q->whereIn('departments.id', $deptIds))
                    ->pluck('id');
                $targetUserIds = $targetUserIds->merge($deptUserIds);
            }
        }

        if ($assignPublicRole) {
            // Asignar rol public
            $publicUserIds = User::role('public')->pluck('id');
            $targetUserIds = $targetUserIds->merge($publicUserIds);
        }

        $targetIds = $targetUserIds->unique()->values()->all();
        $existingIds = $this->originalAssignedUserIds;

        $toDetach = array_values(array_diff($existingIds, $targetIds));
        $toAttach = array_values(array_diff($targetIds, $existingIds));

        if (! empty($toDetach)) {
            $record->users()->detach($toDetach);
        }

        if (! empty($toAttach)) {
            $payload = collect($toAttach)->mapWithKeys(fn ($id) => [$id => ['assigned_at' => now()]])->all();
            $record->users()->attach($payload);
        }

        if (! $record->active) {
            return;
        }

        $newlyActivated = (((! $this->originalActive) && (bool) $record->active) || $this->pendingPublishNotification);

        $notifyUserIds = $newlyActivated
            ? $record->users()->pluck('users.id')->all()
            : $toAttach;

        if (empty($notifyUserIds)) {
            return;
        }

        $recipients = User::whereIn('id', $notifyUserIds)->get();

        Notification::make()
            ->title($newlyActivated ? __('surveys.notifications.new_assigned') : __('surveys.notifications.active_updated'))
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

        $this->pendingPublishNotification = false;
    }
}
