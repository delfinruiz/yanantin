<?php

namespace App\Filament\Resources\Tasks\Pages;

use App\Filament\Resources\Tasks\TaskResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Mail;
use App\Models\User;

class CreateTask extends CreateRecord
{
    protected static string $resource = TaskResource::class;

    protected function afterCreate(): void
    {
        /** @var \App\Models\Task $task */
        $task = $this->record;

        // Notify Assignee if exists and is not the creator
        if ($task->assigned_to && $task->assigned_to !== $task->created_by) {
            $assignee = $task->assignee;

            if ($assignee) {
                // 1. Database Notification
                Notification::make()
                    ->title(__('New Task Assigned'))
                    ->body(__("You have been assigned a new task: :title", ['title' => $task->title]))
                    ->success()
                    ->actions([
                        Action::make('view')
                            ->button()
                            ->url(TaskResource::getUrl('view_task', ['record' => $task->id]), shouldOpenInNewTab: true),
                    ])
                    ->sendToDatabase($assignee);

                // 2. Email Notification (Basic)
                // In a real app, use a Mailable class. Here we use raw for simplicity/speed.
                try {
                    Mail::raw(__("You have been assigned a new task: :title.\n\nDescription: :desc\n\nView here: :url", [
                        'title' => $task->title,
                        'desc' => strip_tags($task->description), // Strip HTML from RichEditor
                        'url' => TaskResource::getUrl('view_task', ['record' => $task->id]),
                    ]), function ($message) use ($assignee, $task) {
                        $message->to($assignee->email)
                            ->subject(__('New Task Assigned: ') . $task->title);
                    });
                } catch (\Exception $e) {
                    // Log error or ignore if mail fails to avoid blocking the user
                    // Log::error("Failed to send task email: " . $e->getMessage());
                }
            }
        }
    }
}
