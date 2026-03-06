<?php

namespace App\Filament\Resources\JobOfferChangeRequests\Tables;

use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Table;

use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use App\Models\JobOfferChangeRequest;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;

class JobOfferChangeRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('jobOffer.title')
                    ->label('Oferta')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('requester.name')
                    ->label('Solicitante')
                    ->searchable(),
                TextColumn::make('reason')
                    ->label('Motivo')
                    ->limit(50),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'completed' => 'primary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pendiente',
                        'approved' => 'Aprobada',
                        'rejected' => 'Rechazada',
                        'completed' => 'Completada',
                        default => $state,
                    }),
                TextColumn::make('requested_at')
                    ->label('Fecha Solicitud')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make()->label('Ver Detalle'),
                Action::make('approve')
                    ->label('Aprobar')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(function (JobOfferChangeRequest $record) {
                        if ($record->status !== 'pending') {
                            return false;
                        }

                        /** @var \App\Models\User $user */
                        $user = Auth::user();

                        // El solicitante no puede aprobar su propia solicitud
                        if ($record->requester_id === $user->id) {
                            return false;
                        }

                        // Verificar si es jefe directo
                        $requesterProfile = $record->requester->employeeProfile;
                        $isDirectBoss = $requesterProfile && $requesterProfile->reports_to === $user->id;

                        // Permitir a Super Admin o RRHH como respaldo
                        $isAdmin = $user->hasRole(['super_admin', 'recursos_humanos']);

                        return $isDirectBoss || $isAdmin;
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Aprobar Solicitud de Edición')
                    ->modalDescription('¿Está seguro de autorizar la modificación de esta oferta? El solicitante podrá editarla y se recalcularán los puntajes al guardar.')
                    ->action(function (JobOfferChangeRequest $record) {
                        $record->update([
                            'status' => 'approved',
                            'approver_id' => Auth::id(),
                            'approved_at' => now(),
                        ]);
                        
                        Notification::make()
                            ->title('Solicitud Aprobada')
                            ->body('El reclutador ha sido notificado y puede proceder con la edición.')
                            ->success()
                            ->send();
                    }),
                Action::make('reject')
                    ->label('Rechazar')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(function (JobOfferChangeRequest $record) {
                        if ($record->status !== 'pending') {
                            return false;
                        }

                        /** @var \App\Models\User $user */
                        $user = Auth::user();

                        // El solicitante no puede rechazar su propia solicitud (aunque no tenga sentido, es seguro)
                        if ($record->requester_id === $user->id) {
                            return false;
                        }

                        // Verificar si es jefe directo
                        $requesterProfile = $record->requester->employeeProfile;
                        $isDirectBoss = $requesterProfile && $requesterProfile->reports_to === $user->id;

                        // Permitir a Super Admin o RRHH como respaldo
                        $isAdmin = $user->hasRole(['super_admin', 'recursos_humanos']);

                        return $isDirectBoss || $isAdmin;
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Rechazar Solicitud')
                    ->schema([
                        \Filament\Forms\Components\Textarea::make('rejection_reason')
                            ->label('Motivo del Rechazo')
                            ->required()
                    ])
                    ->action(function (JobOfferChangeRequest $record, array $data) {
                        $record->update([
                            'status' => 'rejected',
                            'approver_id' => Auth::id(),
                            'approved_at' => now(),
                            // Podríamos guardar el motivo de rechazo en algún campo extra si fuera necesario, 
                            // por ahora asumimos que se comunica externamente o se agrega un campo 'rejection_note'
                        ]);

                        Notification::make()
                            ->title('Solicitud Rechazada')
                            ->danger()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                // Eliminamos DeleteBulkAction para que nadie pueda borrar solicitudes
            ])
            ->defaultSort('created_at', 'desc');
    }
}
