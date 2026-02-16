<?php

namespace App\Filament\Resources\Tasks\Tables;

use App\Filament\Resources\Tasks\TaskResource;
use App\Models\Task;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Mokhosh\FilamentRating\Columns\RatingColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\ExportBulkAction;

class TasksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(function () {
                return Task::query()
                    ->where(function ($q) {
                        $q->where('created_by', Auth::id())->orWhere('assigned_to', Auth::id());
                    })->orderBy('created_at', 'desc');
            })
            ->poll('10s')
            ->deferLoading()
            ->striped()//muestre filas pintadas alternadamente
            ->columns([
                TextColumn::make('id')
                    ->label('Id')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('title')
                    ->label(__('Title'))
                    //Limitar el texto a 2 lineas
                    ->wrap()
                    ->lineClamp(2)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('creator.name')
                    ->label(__('Created_by'))
                    ->numeric()
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('assignee.name')
                    ->label(__('Assigned_to'))
                    ->numeric()
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                RatingColumn::make('rating')
                    ->label(__('Rating'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('priority')
                    ->label(__('Priority'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'High' => __('priority.high'),
                        'Medium' => __('priority.medium'),
                        'Low' => __('priority.low'),
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'High' => 'danger',
                        'Medium' => 'warning',
                        'Low' => 'success',
                        default => 'gray',
                    })
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('due_date')
                    ->label(__('Due Date'))
                    ->date('d-m-Y')
                    ->badge(fn ($record) => $record->due_date && \Carbon\Carbon::parse($record->due_date)->endOfDay()->isPast() && $record->status_id != 2)
                    ->color(fn ($record) => $record->due_date && \Carbon\Carbon::parse($record->due_date)->endOfDay()->isPast() && $record->status_id != 2 ? 'danger' : null)
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('permissions_id')
                    ->label(__('Permissions'))
                    ->formatStateUsing(function (?string $state): string {
                        return match ($state) {
                            '1' => __('View'),
                            '2' => __('Edit'),
                        };
                    })
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        '1' => 'warning',
                        '2' => 'danger',
                    })
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('status_id')
                    ->label(__('Status'))
                    ->formatStateUsing(function (?string $state): string {
                        return match ($state) {
                            '1' => __('Pending'),
                            '2' => __('Completed'),
                            '3' => __('In_Progress'),
                        };
                    })
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        '1' => 'danger',
                        '2' => 'success',
                        '3' => 'warning',
                    })
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('Created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable()
                    ->formatStateUsing(fn($state) => $state->format('d-m-Y')),

            ])
            ->reorderableColumns()
            ->filters([
                //filtro por anio
                        SelectFilter::make('year')
                            ->label(__('Year'))
                            ->default(date('Y'))
                            ->options([
                                date('Y') => date('Y'),
                                date('Y') - 1 => date('Y') - 1,
                            ])                            
                    ->query(function (Builder $query, array $data) {
                        if (! empty($data)) {
                            $query->whereYear('created_at', $data);
                        }

                        return $query;
                    })
                    ->default(date('Y')),

                //filtro por mes
                        SelectFilter::make('month')
                            ->label(__('Month'))
                            ->default(date('m'))
                            ->options([
                                '01' => __('January'),
                                '02' => __('February'),
                                '03' => __('March'),
                                '04' => __('April'),
                                '05' => __('May'),
                                '06' => __('June'),
                                '07' => __('July'),
                                '08' => __('August'),
                                '09' => __('September'),
                                '10' => __('October'),
                                '11' => __('November'),
                                '12' => __('December'),
                            ])
                    ->query(function (Builder $query, array $data) {

                        if (! empty($data)) {
                                $query->whereMonth('created_at', $data);
                        }
                                return $query;
                            })
                            ->default(date('m')),

                //filtro por estado tarea
                        SelectFilter::make('status')
                        ->relationship('status','title')
                        ->label(__('Status'))
                        ->getOptionLabelFromRecordUsing(fn ($record) => __($record->title)),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->label(__('Edit'))
                        ->visible(function (Task $record) {
                            // 1. El creador siempre puede editar
                            if ($record->created_by === Auth::id()) {
                                return true;
                            }
                            
                            // 2. Si está completada (status_id = 2), nadie más puede editar
                            if ($record->status_id === 2) {
                                return false;
                            }
                            
                            // 3. Si no es el creador y no está completada, depende del permiso de edición (permissions_id = 2)
                            return $record->permissions_id === 2;
                        })
                        ->after(function (Task $record): void {

                            $assignedToId = $record->assigned_to;
                            $authId = Auth::id();
                            $recipient = null;
                            // 1. Verificar la condición: ¿Existe asignado y NO es el usuario actual?
                            if ($assignedToId && $assignedToId !== $authId) {
                                // 2. Buscar el MODELO de usuario para el destinatario
                                $recipient = User::find($assignedToId);
                            }
                            // 3. Enviar la notificación si se encontró un destinatario válido
                            if ($recipient) {
                                Notification::make()
                                    ->title(__('Task Updated'))
                                    ->body(__('Task_Updated_body', [
                                        'title' => $record->title,
                                        'user' => Auth::user()->name ?? 'System',
                                        'id' => $record->id
                                    ]))
                                    ->actions([
                                        Action::make('view')
                                            ->button()
                                            ->markAsRead()
                                            ->url(TaskResource::getUrl('view_task', ['record' => $record->id])),
                                    ])
                                    ->info()
                                    ->sendToDatabase($recipient);
                            }
                        }),
                    //solo pueden eliminar las tareas suyas
                    DeleteAction::make()
                        ->label(__('Delete'))
                        ->visible(fn($record) => $record->created_by === Auth::id())
                        ->after(function (Task $record): void {

                            $assignedToId = $record->assigned_to;
                            $authId = Auth::id();
                            $recipient = null;
                            // 1. Verificar la condición: ¿Existe asignado y NO es el usuario actual?
                            if ($assignedToId && $assignedToId !== $authId) {
                                // 2. Buscar el MODELO de usuario para el destinatario
                                $recipient = User::find($assignedToId);
                            }
                            // 3. Enviar la notificación si se encontró un destinatario válido
                            if ($recipient) {
                                Notification::make()
                                    ->title(__('Task Deleted'))
                                    ->body(__('Task_Deleted_body', [
                                        'title' => $record->title,
                                        'user' => Auth::user()->name ?? 'System',
                                        'id' => $record->id
                                    ]))
                                    ->info()
                                    ->sendToDatabase($recipient);
                            }
                        }),
                    ViewAction::make()
                        ->modalWidth('4xl')
                        ->label(__('View')),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //exportar en excel
                    ExportBulkAction::make()
                        ->label(__('Export')),
                ]),
            ]);
    }
}
