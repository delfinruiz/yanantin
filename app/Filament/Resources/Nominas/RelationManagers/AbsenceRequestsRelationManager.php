<?php

namespace App\Filament\Resources\Nominas\RelationManagers;

use App\Filament\Resources\AbsenceRequests\Schemas\AbsenceRequestForm;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use App\Models\VacationLedger;

class AbsenceRequestsRelationManager extends RelationManager
{
    protected static string $relationship = 'absenceRequests';

    protected static ?string $title = null;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('nominas.absence_requests.title');
    }

    public function form(Schema $schema): Schema
    {
        return AbsenceRequestForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('id')
                    ->label(__('nominas.absence_requests.column_id'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('type.name')
                    ->label(__('nominas.absence_requests.column_type'))
                    ->badge()
                    ->color(fn ($record) => $record->type->color ?? 'gray'),
                TextColumn::make('start_date')
                    ->label(__('nominas.absence_requests.column_start_date'))
                    ->date('d-m-Y')
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label(__('nominas.absence_requests.column_end_date'))
                    ->date('d-m-Y')
                    ->sortable(),
                TextColumn::make('days_requested')
                    ->label(__('nominas.absence_requests.column_days'))
                    ->numeric(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved_supervisor' => 'info',
                        'approved_hr' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => __('nominas.absence_requests.status.pending'),
                        'approved_supervisor' => __('nominas.absence_requests.status.approved_supervisor'),
                        'approved_hr' => __('nominas.absence_requests.status.approved_hr'),
                        'rejected' => __('nominas.absence_requests.status.rejected'),
                        default => $state,
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('nominas.absence_requests.filter_status'))
                    ->options([
                        'pending' => __('nominas.absence_requests.status.pending'),
                        'approved_supervisor' => __('nominas.absence_requests.status.approved_supervisor'),
                        'approved_hr' => __('nominas.absence_requests.status.approved_hr'),
                        'rejected' => __('nominas.absence_requests.status.rejected'),
                    ]),
                SelectFilter::make('absence_type_id')
                    ->relationship('type', 'name')
                    ->label(__('nominas.absence_requests.filter_type')),
                Filter::make('start_date')
                    ->schema([
                        DatePicker::make('start_from')->label(__('nominas.absence_requests.filter_date_from')),
                        DatePicker::make('start_until')->label(__('nominas.absence_requests.filter_date_to')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['start_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('start_date', '>=', $date),
                            )
                            ->when(
                                $data['start_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('start_date', '<=', $date),
                            );
                    }),
            ])
            ->headerActions([
                // CreateAction removed as per user request (requests should be made in the Absence Requests module)
            ])
            ->recordActions([
                Action::make('approve_supervisor')
                    ->label(__('nominas.absence_requests.action_approve_sup'))
                    ->icon('heroicon-o-check')
                    ->color('info')
                    ->visible(function ($record) {
                        /** @var \App\Models\User|null $user */
                        $user = Auth::user();
                        $isSupervisor = $user?->hasRole(['Supervisores', 'Gerencia']) || ($user && $user->supervisedDepartments()->exists());
                        return $record->status === 'pending' && $isSupervisor;
                    })
                    ->schema([
                        Textarea::make('comment')->label(__('nominas.absence_requests.modal_comment'))->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => 'approved_supervisor',
                            'supervisor_id' => Auth::id(),
                            'supervisor_approved_at' => now(),
                            'supervisor_comment' => $data['comment'],
                        ]);
                        
                        Notification::make()
                            ->title(__('nominas.absence_requests.notification.approved_sup_title'))
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
                    ->label(__('nominas.absence_requests.action_approve_hr'))
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(function ($record) {
                        /** @var \App\Models\User|null $user */
                        $user = Auth::user();
                        $hasRole = $user?->hasRole(['aprobador_vacaciones', 'recursos humanos']) ?? false;
                        return $record->status === 'approved_supervisor' && $hasRole;
                    })
                    ->schema([
                        Textarea::make('comment')->label(__('nominas.absence_requests.modal_comment'))->required(),
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
                            ->title(__('nominas.absence_requests.notification.approved_hr_title'))
                            ->success()
                            ->send();

                        $this->dispatch('refreshVacationLedger');

                        // Notify Employee about final approval
                        try {
                            $service = new \App\Services\AbsenceService();
                            $service->notifyEmployee($record, 'approved_hr');
                            
                            // Notify Supervisor about final approval
                            $service->notifySupervisors($record, 'approved_hr');
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error('Error notifying parties of final approval: ' . $e->getMessage());
                        }
                    }),

                Action::make('reject')
                    ->label(__('nominas.absence_requests.action_reject'))
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(function ($record) {
                        /** @var \App\Models\User|null $user */
                        $user = Auth::user();
                        $isSupervisor = $user?->hasRole(['Supervisores', 'Gerencia']) || ($user && $user->supervisedDepartments()->exists());
                        $isHr = $user?->hasRole(['aprobador_vacaciones', 'recursos humanos']);
                        
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
                        Textarea::make('comment')->label(__('nominas.absence_requests.modal_reject_reason'))->required(),
                    ])
                    ->action(function ($record, array $data) {
                        /** @var \App\Models\User|null $user */
                        $user = Auth::user();
                        $isHr = $user?->hasRole(['aprobador_vacaciones', 'recursos humanos']);
                        
                        $record->update([
                            'status' => 'rejected',
                            'supervisor_comment' => (!$isHr) ? $data['comment'] : $record->supervisor_comment,
                            'hr_comment' => ($isHr) ? $data['comment'] : $record->hr_comment,
                        ]);

                        // Notify Actor
                        Notification::make()
                            ->title(__('nominas.absence_requests.notification.rejected_title'))
                            ->warning()
                            ->send();

                        // Notify Employee and Supervisor
                        try {
                            $service = new \App\Services\AbsenceService();
                            $service->notifyEmployee($record, 'rejected');
                            
                            // Notify Supervisor if rejected by HR
                            if ($isHr) {
                                $service->notifySupervisors($record, 'rejected_hr');
                            }
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error('Error notifying parties of rejection: ' . $e->getMessage());
                        }
                    }),

                Action::make('cancel')
                    ->label(__('nominas.absence_requests.action_cancel'))
                    ->icon('heroicon-o-archive-box-x-mark')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading(__('nominas.absence_requests.modal_cancel_heading'))
                    ->modalDescription(__('nominas.absence_requests.modal_cancel_desc'))
                    ->modalSubmitActionLabel(__('nominas.absence_requests.modal_cancel_confirm'))
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
                            ->title(__('nominas.absence_requests.notification.cancelled_title'))
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
                    ->label(__('nominas.absence_requests.action_edit'))
                    ->visible(fn ($record) => $record->status === 'pending' && $record->employee_profile_id === Auth::user()?->employeeProfile?->id),
                
                DeleteAction::make()
                    ->label(__('nominas.absence_requests.action_delete'))
                    ->visible(fn ($record) => $record->status === 'pending' && $record->employee_profile_id === Auth::user()?->employeeProfile?->id),
            ]);
    }
}
