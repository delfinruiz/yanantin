<?php

namespace App\Filament\Resources\Tasks\Pages;

use App\Filament\Resources\Tasks\TaskResource;
use App\Livewire\TareasPendientesWidget;
use App\Models\Task;
use App\Models\User; // ⭐️ Importar el modelo User
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Auth;

class ListTasks extends ListRecords
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                // ⭐️ Usamos el hook ->after() para ejecutar código después de la creación
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
                            ->title(__('Task Assigned'))
                            ->body(__('Task_Assigned_body', [
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
                            ->success()
                            ->sendToDatabase($recipient);
                    }
                }),
        ];
    }

    //llamar al componente de liveware TareasPendientesWidget en la vista 

    protected function getHeaderWidgets(): array
    {
        return [
            TareasPendientesWidget::class,
        ];
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }
}
