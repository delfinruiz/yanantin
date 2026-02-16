<?php

namespace App\Filament\Resources\AbsenceRequests\Tables;

use App\Models\VacationLedger;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class AbsenceRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                /** @var \App\Models\User $user */
                $user = Auth::user();
                return $query->accessibleBy($user);
            })
            ->columns([
                TextColumn::make('employee.user.name')
                    ->label('Empleado')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('employee.cargo.name')
                    ->label('Cargo')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('employee.user.departments.name')
                    ->label('Departamento')
                    ->badge()
                    ->separator(',')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('type.name')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn ($record) => $record->type->color ?? 'gray'),
                TextColumn::make('start_date')
                    ->label('Desde')
                    ->date('d-m-Y')
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label('Hasta')
                    ->date('d-m-Y')
                    ->sortable(),
                TextColumn::make('days_requested')
                    ->label('Días')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved_supervisor' => 'info',
                        'approved_hr' => 'success',
                        'rejected' => 'danger',
                        'cancelled' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pendiente',
                        'approved_supervisor' => 'Aprobado Sup.',
                        'approved_hr' => 'Aprobado Final',
                        'rejected' => 'Rechazado',
                        'cancelled' => 'Anulada',
                        default => $state,
                    })
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Solicitado el')
                    ->dateTime('d-m-Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('approve_supervisor')
                    ->label('Aprobar (Sup)')
                    ->icon('heroicon-o-check')
                    ->color('info')
                    ->visible(function ($record) {
                        /** @var \App\Models\User|null $user */
                        $user = Auth::user();
                        $isSupervisor = $user && $user->supervisedDepartments()->exists();
                        return $record->status === 'pending' && $isSupervisor;
                    })
                    ->schema([
                        Textarea::make('comment')->label('Comentario')->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => 'approved_supervisor',
                            'supervisor_id' => Auth::id(),
                            'supervisor_approved_at' => now(),
                            'supervisor_comment' => $data['comment'],
                        ]);
                        
                        Notification::make()
                            ->title('Solicitud aprobada por supervisor')
                            ->success()
                            ->send();

                        // Notify Employee
                        try {
                            $service = new \App\Services\AbsenceService();
                            $service->notifyEmployee($record, 'approved_supervisor');
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error('Error notifying employee: ' . $e->getMessage());
                        }

                        // Notify HR
                        try {
                            $service = new \App\Services\AbsenceService();
                            $service->notifyHR($record, 'approved_supervisor');
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error('Error notifying HR: ' . $e->getMessage());
                        }
                    }),

                Action::make('approve_hr')
                    ->label('Aprobar (RRHH)')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(function ($record) {
                        /** @var \App\Models\User|null $user */
                        $user = Auth::user();
                        $hasRole = $user?->hasRole(['aprobador_vacaciones']) ?? false;
                        return $record->status === 'approved_supervisor' && $hasRole;
                    })
                    ->schema([
                        Textarea::make('comment')->label('Comentario')->required(),
                    ])
                    ->action(function ($record, array $data) {
                        // Deduct balance if vacation
                        if ($record->type->is_vacation) {
                            VacationLedger::create([
                                'employee_profile_id' => $record->employee_profile_id,
                                'days' => -($record->days_requested),
                                'type' => 'usage',
                                'description' => 'Solicitud aprobada #' . $record->id,
                                'reference_id' => $record->id,
                            ]);
                        }

                        $record->update([
                            'status' => 'approved_hr',
                            'hr_user_id' => Auth::id(),
                            'hr_approved_at' => now(),
                            'hr_comment' => $data['comment'],
                        ]);

                        Notification::make()
                            ->title('Solicitud aprobada y procesada')
                            ->success()
                            ->send();

                        // Notify Employee about final approval
                        try {
                            $service = new \App\Services\AbsenceService();
                            $service->notifyEmployee($record, 'approved_hr');
                            
                            // NEW: Notify Supervisor about final approval
                            $service->notifySupervisors($record, 'approved_hr');
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error('Error notifying parties of final approval: ' . $e->getMessage());
                        }
                    }),

                Action::make('reject')
                    ->label('Rechazar')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(function ($record) {
                        /** @var \App\Models\User|null $user */
                        $user = Auth::user();
                        $isSupervisor = $user && $user->supervisedDepartments()->exists();
                        $isHr = $user?->hasRole(['aprobador_vacaciones']);
                        
                        // Supervisor can ONLY reject if pending
                        if ($isSupervisor && !$isHr) {
                            return $record->status === 'pending';
                        }
                        
                        // HR can reject if pending or approved_supervisor
                        if ($isHr) {
                            return in_array($record->status, ['pending', 'approved_supervisor']);
                        }
                        
                        return false;
                    })
                    ->schema([
                        Textarea::make('comment')->label('Motivo de Rechazo')->required(),
                    ])
                    ->action(function ($record, array $data) {
                        /** @var \App\Models\User|null $user */
                        $user = Auth::user();
                        $isHr = $user?->hasRole(['aprobador_vacaciones']);
                        
                        $record->update([
                            'status' => 'rejected',
                            'supervisor_comment' => (!$isHr) ? $data['comment'] : $record->supervisor_comment,
                            'hr_comment' => ($isHr) ? $data['comment'] : $record->hr_comment,
                        ]);

                        // Notify Actor
                        Notification::make()
                            ->title('Solicitud rechazada')
                            ->warning()
                            ->send();

                        // Notify Employee and Supervisor
                        try {
                            $service = new \App\Services\AbsenceService();
                            $service->notifyEmployee($record, 'rejected');
                            
                            // NEW: Notify Supervisor if rejected by HR
                            if ($isHr) {
                                $service->notifySupervisors($record, 'rejected_hr');
                            }
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error('Error notifying parties of rejection: ' . $e->getMessage());
                        }
                    }),

                Action::make('cancel')
                    ->label('Anular Solicitud')
                    ->icon('heroicon-o-archive-box-x-mark')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('¿Anular solicitud?')
                    ->modalDescription('Si anulas esta solicitud, no podrá ser reactivada. Deberás crear una nueva si es necesario.')
                    ->modalSubmitActionLabel('Sí, anular')
                    ->visible(function ($record) {
                        /** @var \App\Models\User|null $user */
                        $user = Auth::user();
                        $isOwner = $record->employee_profile_id === $user?->employeeProfile?->id;
                        $isSupervisor = $user?->hasRole(['Supervisores', 'Gerencia']) || ($user && $user->supervisedDepartments()->exists());
                        
                        // Visible if Approved by Supervisor (but not yet HR approved) AND (Owner or Supervisor)
                        return $record->status === 'approved_supervisor' && ($isOwner || $isSupervisor);
                    })
                    ->action(function ($record) {
                        $record->update(['status' => 'cancelled']);
                        
                        Notification::make()
                            ->title('Solicitud anulada')
                            ->success()
                            ->send();

                        try {
                            $service = new \App\Services\AbsenceService();
                            
                            // If Owner cancelled, notify Supervisor
                            if ($record->employee_profile_id === Auth::user()?->employeeProfile?->id) {
                                $service->notifySupervisors($record, 'cancelled');
                            } 
                            // If Supervisor cancelled, notify Employee
                            else {
                                $service->notifyEmployee($record, 'cancelled');
                            }
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error('Error sending cancellation notifications: ' . $e->getMessage());
                        }
                    }),

                EditAction::make()
                    ->label('Editar')
                    ->visible(fn ($record) => $record->status === 'pending' && $record->employee_profile_id === Auth::user()?->employeeProfile?->id)
                    ->after(function ($record) {
                        try {
                            $service = new \App\Services\AbsenceService();
                            $service->notifySupervisors($record, 'updated');
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error('Error sending update notifications: ' . $e->getMessage());
                        }
                    }),
                
                DeleteAction::make()
                    ->label('Borrar')
                    ->visible(fn ($record) => $record->status === 'pending' && $record->employee_profile_id === Auth::user()?->employeeProfile?->id),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Eliminar seleccionados'),
                ]),
            ]);
    }
}
