<?php

namespace App\Filament\Resources\Tasks;

use App\Filament\Resources\Tasks\Pages\ListTasks;
use App\Filament\Resources\Tasks\Pages\ViewTask;
use App\Filament\Resources\Tasks\Schemas\TaskForm;
use App\Filament\Resources\Tasks\Schemas\TaskView;
use App\Filament\Resources\Tasks\Tables\TasksTable;
use App\Models\Task;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    public static function getEloquentQuery(): Builder
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (! $user) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }
        
        // If the user has no department, they effectively see nothing (or maybe their own tasks if we wanted to be lenient, 
        // but the requirement implies strict department boundaries).
        // Let's assume strict department filtering.
        
        $departmentIds = $user->departments()->pluck('departments.id')
            ->merge($user->supervisedDepartments()->pluck('departments.id'))
            ->unique();

        return parent::getEloquentQuery()->whereHas('creator', function ($query) use ($departmentIds) {
            $query->whereHas('departments', function ($subQuery) use ($departmentIds) {
                $subQuery->whereIn('departments.id', $departmentIds);
            });
        });
    }

    public static function canCreate(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        // Only users belonging to a department (or supervising one) can create tasks
        return $user->departments()->exists() || $user->supervisedDepartments()->exists();
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckCircle;

    protected static ?string $recordTitleAttribute = 'Task v2';

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.my_apps');
    }

    public static function getNavigationSort(): ?int
    {
        return 7;
    }

    //funcion personalizar titulo del menu
    public static function getNavigationLabel(): string
    {
        return __('navigation.labels.my_tasks');
    }

    //funcion personalizar titulo del modelo en singular
    public static function getModelLabel(): string
    {
        return __('Task_v2');
    }

    //funcion personalizar titulo del modelo en plural
    public static function getPluralModelLabel(): string
    {
        return __('Tasks_v2');
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getEloquentQuery()
            ->whereDate('due_date', now())
            ->where('status_id', '!=', 2)
            ->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getEloquentQuery()
            ->whereDate('due_date', now())
            ->where('status_id', '!=', 2)
            ->exists() ? 'danger' : null;
    }


    public static function form(Schema $schema): Schema
    {
        return TaskForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TasksTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTasks::route('/'),
            //'create' => CreateTask::route('/create'),
            //'edit' => EditTask::route('/{record}/edit'),
            //'view' => ViewTask::route('/{record}'),
            //agregar ruta para el boton de accion de la notifiacion para ver tarea
            'view_task' => ViewTask::route('/{record}'),
        ];
    }

    //funcion personalizar el view
    public static function infolist(Schema $schema): Schema
    {
            return TaskView::configure($schema);
    }

}
