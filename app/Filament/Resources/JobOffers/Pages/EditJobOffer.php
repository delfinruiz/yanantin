<?php

namespace App\Filament\Resources\JobOffers\Pages;

use App\Filament\Resources\JobOffers\JobOfferResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

use Filament\Notifications\Notification;
use Filament\Actions\Action;
use App\Models\JobOfferChangeRequest;
use App\Models\JobOfferHistory;
use App\Jobs\RecalculateOfferScores;
use Illuminate\Support\Facades\Auth;

class EditJobOffer extends EditRecord
{
    protected static string $resource = JobOfferResource::class;

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function beforeFill(): void
    {
        // Bloquear edición si hay postulantes y no hay solicitud aprobada
        if ($this->record->jobApplications()->count() > 0) {
            $hasApprovedRequest = JobOfferChangeRequest::where('job_offer_id', $this->record->id)
                ->where('status', 'approved')
                ->exists();

            if (! $hasApprovedRequest) {
                Notification::make()
                    ->title('Edición Bloqueada')
                    ->body('Esta oferta tiene postulantes activos. Debe solicitar autorización para modificarla.')
                    ->warning()
                    ->persistent()
                    ->send();
            }
        }
    }

    protected function afterSave(): void
    {
        // Verificar si se editaron cambios aprobados
        $approvedRequest = JobOfferChangeRequest::where('job_offer_id', $this->record->id)
            ->where('status', 'approved')
            ->first();

        if ($approvedRequest) {
            // 1. Guardar historial
            JobOfferHistory::create([
                'job_offer_id' => $this->record->id,
                'changed_by_id' => Auth::id(),
                'change_request_id' => $approvedRequest->id,
                'snapshot_data' => $this->record->toArray(), // Guardamos el estado NUEVO como referencia
                'change_reason' => $approvedRequest->reason,
                'created_at' => now(),
            ]);

            // 2. Marcar solicitud como completada
            $approvedRequest->update(['status' => 'completed']);

            // 3. Disparar Job de recálculo
            RecalculateOfferScores::dispatch($this->record);

            Notification::make()
                ->title('Cambios aplicados y recálculo iniciado')
                ->body('Se ha iniciado el proceso de re-evaluación de candidatos en segundo plano.')
                ->success()
                ->persistent()
                ->send();
        }
    }

    protected function getFormActions(): array
    {
        // Verificar si está bloqueada
        $isLocked = false;
        if ($this->record->jobApplications()->count() > 0) {
            $hasApprovedRequest = JobOfferChangeRequest::where('job_offer_id', $this->record->id)
                ->where('status', 'approved')
                ->exists();
            
            $isLocked = ! $hasApprovedRequest;
        }

        if ($isLocked) {
            return [
                Action::make('request_changes')
                    ->label('Solicitar Modificación')
                    ->icon('heroicon-o-lock-closed')
                    ->color('warning')
                    ->schema([
                        \Filament\Forms\Components\Textarea::make('reason')
                            ->label('Motivo del Cambio')
                            ->required()
                            ->placeholder('Ej: Bajar exigencia de experiencia, corregir error crítico...'),
                        \Filament\Forms\Components\Textarea::make('justification')
                            ->label('Justificación Detallada')
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        JobOfferChangeRequest::create([
                            'job_offer_id' => $this->record->id,
                            'requester_id' => Auth::id(),
                            'status' => 'pending',
                            'reason' => $data['reason'],
                            'justification' => $data['justification'],
                        ]);

                        Notification::make()
                            ->title('Solicitud enviada')
                            ->body('Su jefatura debe aprobar el cambio antes de poder editar.')
                            ->success()
                            ->send();
                        
                        $this->redirect($this->getResource()::getUrl('index'));
                    }),
                Action::make('cancel')
                    ->label(__('filament-panels::resources/pages/edit-record.form.actions.cancel.label'))
                    ->url($this->getResource()::getUrl('index'))
                    ->color('gray'),
            ];
        }

        return parent::getFormActions();
    }
}

