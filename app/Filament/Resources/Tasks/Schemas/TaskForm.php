<?php

namespace App\Filament\Resources\Tasks\Schemas;

use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Mokhosh\FilamentRating\Components\Rating;


use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;

use Filament\Forms\Components\DatePicker;

class TaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label(__('Title'))
                    ->maxLength(55)
                    ->required()
                    ->disabled(
                        fn(?Task $record): bool =>
                        $record && $record->created_by !== Auth::id()
                    ),
                Select::make('priority')
                    ->label(__('Priority'))
                    ->options([
                        'High' => __('priority.high'),
                        'Medium' => __('priority.medium'),
                        'Low' => __('priority.low'),
                    ])
                    ->default('Medium')
                    ->required()
                    ->disabled(
                        fn(?Task $record): bool =>
                        $record && $record->created_by !== Auth::id()
                    ),
                DatePicker::make('due_date')
                    ->label(__('Due Date'))
                    ->native(false)
                    ->required()
                    ->disabled(
                        fn(?Task $record): bool =>
                        $record && $record->created_by !== Auth::id()
                    ),
                Select::make('assigned_to')
                    ->label(__('Assigned_to'))
                    ->relationship('assignee', 'name', modifyQueryUsing: function (Builder $query) {
                        /** @var \App\Models\User $user */
                        $user = Auth::user();
                        // Get current user's department IDs (including supervised ones)
                        $departmentIds = $user->departments()->pluck('departments.id')
                            ->merge($user->supervisedDepartments()->pluck('departments.id'))
                            ->unique()
                            ->toArray();
                        
                        // Filter users who belong to any of these departments
                        return $query->whereHas('departments', function ($q) use ($departmentIds) {
                            $q->whereIn('departments.id', $departmentIds);
                        });
                    })
                    ->required()
                    ->disabled(
                        fn(?Task $record): bool =>
                        $record && $record->created_by !== Auth::id()
                    ),
                Select::make('permissions_id')
                    ->label(__('Permissions'))
                    //listar permisos disponibles en la tabla permissions_tasks
                    ->relationship('permission', 'title')
                    ->getOptionLabelFromRecordUsing(fn($record) => match (strtolower($record->title)) {
                        'view' => __('View'),
                        'edit' => __('Edit'),
                        default => $record->title,
                    })
                    ->disabled(
                        fn(?Task $record): bool =>
                        $record && $record->created_by !== Auth::id()
                    )
                    ->default(fn(?Task $record) => $record->permission?->id ?? 1)
                    ->required(),
                Hidden::make('status_id')
                    ->default(1)
                    ->visibleOn('create'),
                Select::make('status_id')
                    ->label(__('Status'))
                    ->relationship('status', 'title')
                    ->options([
                        '1' => __('Pending'),
                        '3' => __('In_Progress'),
                        '2' => __('Completed'),
                    ])
                    ->default(fn(?Task $record) => $record->status->id ?? 1)
                    ->visibleOn('edit')
                    ->required()
                    ->live() // <- importantísimo para reaccionar
                    ->afterStateUpdated(function ($state, callable $set, callable $get, ?Task $record) {
                        // Si se cambia a un estado distinto de Completed (2),
                        // restaurar el rating original del registro
                        if ((int) $state !== 2 && $record) {
                            $set('rating', $record->rating ?? 0);
                        }
                    }),
                RichEditor::make('description')
                    ->label(__('Description'))
                    ->toolbarButtons([
                        ['bold', 'italic', 'underline', 'strike', 'subscript', 'superscript', 'link'],
                        ['h2', 'h3', 'alignStart', 'alignCenter', 'alignEnd'],
                        ['bulletList', 'orderedList'], ['table'], ['undo', 'redo'],
                    ])
                    ->required()
                    ->disabled(
                        fn(?Task $record): bool =>
                        $record && $record->created_by !== Auth::id()
                    )
                    ->columnSpanFull(),
                RichEditor::make('observation')
                    ->label(__('Observation'))
                    ->toolbarButtons([
                        ['bold', 'italic', 'underline', 'strike', 'subscript', 'superscript', 'link'],
                        ['h2', 'h3', 'alignStart', 'alignCenter', 'alignEnd'],
                        ['bulletList', 'orderedList'], ['table'], ['undo', 'redo'],
                    ])
                    ->columnSpanFull()
                    ->visibleOn('edit'),
                Rating::make('rating')
                    ->label(__('Rating'))
                    ->allowZero()
                    ->visibleOn('edit')
                    //desactivar si no es completed (2) o si no es el creador
                    ->disabled(fn(Get $get): bool => (int) $get('created_by') !== Auth::user()->id),
                FileUpload::make('attachment')
                    ->label(__('Attachment'))
                    ->directory('tasks-attachments')
                    ->preserveFilenames()
                    ->downloadable()
                    ->openable()
                    ->columnSpanFull()
                    ->disabled(
                        fn(?Task $record): bool =>
                        $record && $record->created_by !== Auth::id()
                    ),
                Hidden::make('created_by')
                    ->default(Auth::user()->id),
            ]);
    }
}
