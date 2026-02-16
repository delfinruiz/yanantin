<?php

namespace App\Filament\Resources\Meetings;

use App\Filament\Resources\Meetings\Pages\CreateMeeting;
use App\Filament\Resources\Meetings\Pages\EditMeeting;
use App\Filament\Resources\Meetings\Pages\ListMeetings;
use App\Filament\Resources\Meetings\Schemas\MeetingForm;
use App\Filament\Resources\Meetings\Tables\MeetingsTable;
use App\Models\Meeting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

use App\Filament\Resources\Meetings\RelationManagers\ParticipantsRelationManager;
use App\Filament\Resources\Meetings\RelationManagers\TasksRelationManager;

class MeetingResource extends Resource
{
    protected static ?string $model = Meeting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;



    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.my_apps');
    }

    public static function getNavigationSort(): ?int
    {
        return 3;
    }

    public static function getModelLabel(): string
    {
        return __('meetings.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('meetings.plural_model_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.labels.my_meetings');
    }

    public static function getNavigationBadge(): ?string
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        if (!$user) return null;

        $todayCount = static::getModel()::query()
            ->whereDate('start_time', now())
            ->whereNotIn('status', ['finished', 'canceled'])
            ->where('type', '!=', 1)
            ->whereRaw('DATE_ADD(start_time, INTERVAL duration MINUTE) >= NOW()')
            ->where(function ($query) use ($user) {
                $query->where('host_id', $user->id)
                      ->orWhereHas('participants', function ($q) use ($user) {
                          $q->where('user_id', $user->id);
                      });
            })
            ->count();

        $instantCount = static::getModel()::query()
            ->where('type', 1) // Instantánea
            ->where('status', 'active')
            ->whereHas('participants', function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->where('status', 'invited')
                  ->whereNull('joined_at');
            })
            ->count();

        $count = $todayCount + $instantCount;
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function form(Schema $schema): Schema
    {
        return MeetingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MeetingsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ParticipantsRelationManager::class,
            TasksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMeetings::route('/'),
            'create' => CreateMeeting::route('/create'),
            'edit' => EditMeeting::route('/{record}/edit'),
        ];
    }
}
