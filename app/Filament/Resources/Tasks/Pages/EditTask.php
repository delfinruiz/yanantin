<?php

namespace App\Filament\Resources\Tasks\Pages;

use App\Filament\Resources\Tasks\TaskResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Models\Task;

class EditTask extends EditRecord
{
    protected static string $resource = TaskResource::class;

    protected function afterSave(): void
    {
        /** @var \App\Models\Task $task */
        $task = $this->record;
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        // 1. Check if assigned_to was changed (Re-assignment)
        if ($task->wasChanged('assigned_to')) {
            $newAssignee = $task->assignee;
            // Notify new assignee if they are not the one doing the action (rare, but possible)
            if ($newAssignee && $newAssignee->id !== $currentUser->id) {
                $this->sendTaskNotification($newAssignee, $task, 'assigned');
            }
        } else {
            // 2. Regular Update (Status, Description, etc.)
            // Notify Assignee if they are not the one doing the update
            if ($task->assigned_to && $task->assigned_to !== $currentUser->id) {
                $this->sendTaskNotification($task->assignee, $task, 'updated');
            }
        }

        // 3. Always notify Creator if they are not the one doing the update
        // (This covers the "Bidirectional" requirement: Assignee updates -> Creator gets notified)
        if ($task->created_by && $task->created_by !== $currentUser->id) {
            // If creator exists (user might be deleted, check relation)
            if ($task->creator) {
                $this->sendTaskNotification($task->creator, $task, 'updated');
            }
        }
    }

    protected function sendTaskNotification(User $recipient, Task $task, string $type): void
    {
        $title = $type === 'assigned' 
            ? __('New Task Assigned') 
            : __('Task Updated');
            
        $body = $type === 'assigned'
            ? __("You have been assigned a new task: :title", ['title' => $task->title])
            : __("The task ':title' has been updated.", ['title' => $task->title]);

        // 1. Database Notification
        Notification::make()
            ->title($title)
            ->body($body)
            ->success() // or info()
            ->actions([
                Action::make('view')
                    ->button()
                    ->url(TaskResource::getUrl('view_task', ['record' => $task->id]), shouldOpenInNewTab: true),
            ])
            ->sendToDatabase($recipient);

        // 2. Email Notification
        try {
            Mail::raw($body . "\n\n" . __("View here: ") . TaskResource::getUrl('view_task', ['record' => $task->id]), 
            function ($message) use ($recipient, $title) {
                $message->to($recipient->email)
                    ->subject($title);
            });
        } catch (\Exception $e) {
            // Log error
        }
    }
}
