<?php

namespace App\Filament\Resources\Meetings\RelationManagers;

use App\Filament\Resources\Tasks\Schemas\TaskForm;
use App\Models\Task;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Mokhosh\FilamentRating\Columns\RatingColumn;
use Filament\Schemas\Schema;

class TasksRelationManager extends RelationManager
{
    protected static string $relationship = 'tasks';

    protected static ?string $recordTitleAttribute = 'title';

    public static function getModelLabel(): string
    {
        return __('meetings.tasks.task');
    }

    public static function getPluralModelLabel(): string
    {
        return __('meetings.tasks.title');
    }

    public function form(Schema $form): Schema
    {
        return TaskForm::configure($form);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('title')
                    ->label(__('meetings.tasks.title'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('assignee.name')
                    ->label(__('meetings.tasks.assigned_to'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('priority')
                    ->label(__('meetings.tasks.priority'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'High' => 'danger',
                        'Medium' => 'warning',
                        'Low' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('due_date')
                    ->label(__('meetings.tasks.due_date'))
                    ->date('d-m-Y')
                    ->sortable(),
                TextColumn::make('status.title')
                    ->label(__('meetings.tasks.status'))
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Pending' => 'danger',
                        'Completed' => 'success',
                        'In Progress' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('meetings.tasks.create_task'))
                    ->mutateDataUsing(function (array $data): array {
                        $data['created_by'] = Auth::id();
                        $data['meeting_id'] = $this->getOwnerRecord()->id;
                        return $data;
                    })
                    ->after(function (Task $record): void {
                        // Send notification to assignee
                        $assignedToId = $record->assigned_to;
                        $authId = Auth::id();
                        
                        if ($assignedToId && $assignedToId !== $authId) {
                            $recipient = User::find($assignedToId);
                            if ($recipient) {
                                Notification::make()
                                    ->title(__('Task Assigned'))
                                    ->body("You have been assigned a task from meeting: " . $this->getOwnerRecord()->topic)
                                    ->info()
                                    ->sendToDatabase($recipient);
                            }
                        }
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                //
            ]);
    }
}
