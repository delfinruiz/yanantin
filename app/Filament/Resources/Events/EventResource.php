<?php

namespace App\Filament\Resources\Events;

use App\Filament\Resources\Events\EventResource\Pages\ListEvents;
use App\Models\Calendar;
use App\Models\Event;
use App\Models\EmailAccount;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use App\Models\User;
use App\Services\CalDav\CalDavService;
use Illuminate\Support\Facades\Log;

use Filament\Support\Icons\Heroicon;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    public static function getNavigationLabel(): string
    {
        return __('navigation.labels.my_events');
    }

    public static function getNavigationSort(): ?int
    {
        return 6;
    }

    public static function getModelLabel(): string
    {
        return __('events.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('events.plural_model_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.my_apps');
    }

    public static function getNavigationBadge(): ?string
    {
        $now = now();

        $query = static::getEloquentQuery()
            ->whereDate('starts_at', $now->toDateString())
            ->where(function (Builder $q) use ($now) {
                $q->where('all_day', true)
                    ->orWhereNull('ends_at')
                    ->orWhere('ends_at', '>=', $now);
            });

        $count = $query->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $now = now();

        $hasEvents = static::getEloquentQuery()
            ->whereDate('starts_at', $now->toDateString())
            ->where(function (Builder $q) use ($now) {
                $q->where('all_day', true)
                    ->orWhereNull('ends_at')
                    ->orWhere('ends_at', '>=', $now);
            })
            ->exists();

        return $hasEvents ? 'danger' : null;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if (!$user) {
            return $query->whereHas('calendar', function ($q2) {
                $q2->where('is_public', true);
            });
        }

        return $query->where(function ($q) use ($user) {
            $q->whereHas('calendar', function ($q2) use ($user) {
                $q2->where('is_public', true)
                   ->orWhere('user_id', $user->id)
                   ->orWhere('manager_user_id', $user->id);
            })
            ->orWhereHas('sharedWith', function ($q3) use ($user) {
                $q3->where('user_id', $user->id);
            });
        });
    }

    public static function getFormSchema(bool $includePublicCalendars = false, bool $hiddenCalendar = false): array
    {
        return [
            Section::make()
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                Forms\Components\Select::make('calendar_id')
                    ->label(__('events.field.calendar'))
                    ->hidden($hiddenCalendar)
                    ->live()
                    ->options(function (Forms\Components\Select $component) use ($includePublicCalendars) {
                        $user = Auth::user();
                        $options = collect();
                        
                        // Use getState to get current value without accessing container/record directly
                        // This avoids initialization errors in standalone actions
                        $calendarId = $component->getState();
                        if ($calendarId) {
                             $calendar = Calendar::find($calendarId);
                             if ($calendar) {
                                 $options[$calendar->id] = $calendar->name;
                             }
                        }

                        // Determine if we are in a View context
                        // We rely on the passed argument or if it's explicitly a ViewAction
                        $isView = $includePublicCalendars;

                        $query = Calendar::query()
                            ->where(function ($q) use ($user, $isView) {
                                if ($isView) {
                                    $q->where('is_public', true)
                                      ->orWhere('user_id', $user->id)
                                      ->orWhere('manager_user_id', $user->id);
                                } else {
                                    $q->where('user_id', $user->id)
                                      ->orWhere('manager_user_id', $user->id);
                                }
                            });
                        
                        // Merge query results into options
                        $query->pluck('name', 'id')->each(function ($name, $id) use ($options) {
                            $options[$id] = $name;
                        });

                        return $options;
                    })
                    ->required()
                    ->searchable(),
                Forms\Components\TextInput::make('title')
                    ->label(__('events.field.title'))
                    ->required()
                    ->columnSpanFull(),
                Grid::make(2)->schema([
                    Forms\Components\DateTimePicker::make('starts_at')
                        ->label(__('events.field.start'))
                        ->required(),
                    Forms\Components\DateTimePicker::make('ends_at')
                        ->label(__('events.field.end')),
                ])->columnSpanFull(),
                Forms\Components\Toggle::make('all_day')
                    ->label(__('calendars.field.all_day'))
                    ->columnSpanFull(),
                Forms\Components\ColorPicker::make('color')
                    ->label(__('calendars.field.color')),
                Forms\Components\Select::make('sharedWith')
                    ->label(__('calendars.field.shared_with'))
                    ->hidden(function (Get $get) {
                        $calendarId = $get('calendar_id');
                        if (!$calendarId) return false;
                        $calendar = Calendar::find($calendarId);
                        return $calendar && $calendar->is_public;
                    })
                    ->options(function () {
                         return User::where('id', '!=', Auth::id())->pluck('name', 'id');
                    })
                    ->getOptionLabelsUsing(function (array $values): array {
                        return User::whereIn('id', $values)->pluck('name', 'id')->toArray();
                    })
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->columnSpanFull()
                    ->afterStateHydrated(function (Forms\Components\Select $component) {
                        // If state is already populated (e.g. manually in Actions), do nothing
                        if (!empty($component->getState())) {
                            return;
                        }

                        try {
                            $record = $component->getRecord();
                            if ($record && $record->sharedWith()->exists()) {
                                $component->state($record->sharedWith()->pluck('users.id')->toArray());
                            }
                        } catch (\Throwable $e) {
                            // Ignore if record cannot be retrieved (e.g. in standalone actions)
                        }
                    }),
                Forms\Components\Textarea::make('description')
                    ->label(__('events.field.description'))
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\FileUpload::make('attachments')
                    ->label(__('events.field.attachments'))
                    ->disk('public')
                    ->directory('event-attachments')
                    ->visibility('public')
                    ->multiple()
                    ->downloadable()
                    ->columnSpanFull(),
            ]),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components(static::getFormSchema());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('5s')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label(__('events.field.title'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('calendar.name')
                    ->label(__('events.field.calendar'))
                    ->sortable(),
                Tables\Columns\IconColumn::make('calendar.is_public')
                    ->label(__('events.field.is_public'))
                    ->boolean(),
                Tables\Columns\IconColumn::make('calendar.is_personal')
                    ->label(__('events.field.is_personal'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('starts_at')
                    ->dateTime()
                    ->label(__('events.field.start'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('ends_at')
                    ->dateTime()
                    ->label(__('events.field.end'))
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('calendar_id')
                    ->label(__('events.field.calendar'))
                    ->options(function () {
                        $user = Auth::user();
                        return Calendar::query()
                            ->where('is_public', true)
                            ->orWhere('user_id', $user->id)
                            ->orWhere('manager_user_id', $user->id)
                            ->pluck('name', 'id');
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->modalWidth('5xl')
                    ->schema(fn (Event $record) => EventResource::getFormSchema(true)),
                EditAction::make()
                    ->schema(fn (Event $record) => EventResource::getFormSchema(true, true))
                    ->visible(fn (Event $record) => $record->calendar->user_id === Auth::id() || $record->calendar->manager_user_id === Auth::id())
                    ->after(function (Event $record) {
                        $user = Auth::user();
                        $calendar = $record->calendar;
                        
                        // Sincronizar participantes si se modificó
                        // Nota: La sincronización de participantes se maneja en el formulario a través de sharedWith
                        // Aquí solo nos aseguramos de que los cambios se guarden si no fue automático
                        
                        // Sincronización CalDAV
                        if ($calendar && $calendar->is_personal && $calendar->user_id === $user->id) {
                            try {
                                $emailAccount = EmailAccount::where('user_id', $user->id)->first();
                                if ($emailAccount) {
                                    $service = app(CalDavService::class);
                                    // Si el evento no tiene UID (se creó sin sync), intentamos crearlo
                                    if (!$record->caldav_uid) {
                                        $result = $service->createEvent($emailAccount, $calendar, $record);
                                        $record->caldav_uid = $result['uid'] ?? $record->caldav_uid;
                                        $record->caldav_etag = $result['etag'] ?? null;
                                    } else {
                                        // Si ya tiene UID, actualizamos
                                        $etag = $service->updateEvent($emailAccount, $calendar, $record);
                                        if ($etag) {
                                            $record->caldav_etag = $etag;
                                        }
                                    }
                                    $record->caldav_last_sync_at = now();
                                    $record->save();
                                }
                            } catch (\Throwable $e) {
                                Log::error('CalDAV Update Error (EventResource EditAction): ' . $e->getMessage(), [
                                    'event_id' => $record->id,
                                ]);
                            }
                        }
                    }),
                DeleteAction::make()
                    ->visible(fn (Event $record) => $record->calendar->user_id === Auth::id() || $record->calendar->manager_user_id === Auth::id())
                    ->before(function (Event $record) {
                        $user = Auth::user();
                        $calendar = $record->calendar;
                        if (!$calendar || !$calendar->is_personal || $calendar->user_id !== $user?->id) {
                            return;
                        }
                        if (!$record->caldav_uid) {
                            return;
                        }
                        try {
                            $emailAccount = EmailAccount::where('user_id', $user->id)->first();
                            if ($emailAccount) {
                                $service = app(CalDavService::class);
                                $service->deleteEvent($emailAccount, $record);
                            } else {
                                Log::warning('CalDAV Sync (Filament delete): emailAccount no encontrado', [
                                    'user_id' => $user->id,
                                ]);
                            }
                        } catch (\Throwable $e) {
                            Log::error('CalDAV Delete Error (Filament delete): ' . $e->getMessage(), [
                                'event_id' => $record->id,
                            ]);
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEvents::route('/'),
       //     'create' => Pages\CreateEvent::route('/create'),
       //     'view' => Pages\ViewEvent::route('/{record}'),
       //     'edit' => Pages\EditEvent::route('/{record}/edit'),
        ];
    }
}
