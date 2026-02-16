<?php

namespace App\Filament\Pages;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Support\Icons\Heroicon;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Actions\Action;
use App\Filament\Resources\Calendars\CalendarResource;
use App\Filament\Resources\Events\EventResource;
use App\Models\Event;
use App\Models\Calendar;
use App\Models\EmailAccount;
use Filament\Forms;
use Filament\Forms\ComponentContainer;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\Width;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use App\Services\CalDav\CalDavService;
use Illuminate\Support\Facades\Log;
use App\Jobs\CalDavSyncJob;

class Calendarios extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.calendarios';

    protected static BackedEnum|string|null $navigationIcon = Heroicon::OutlinedCalendar;

    public static function getNavigationLabel(): string
    {
        return __('navigation.labels.my_calendars');
    }

    public static function getNavigationSort(): ?int
    {
        return 5;
    }

    public function getTitle(): string
    {
        return __('calendars.title');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.my_apps');
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function mount(): void
    {
        $user = Auth::user();
        if (!$user) {
            return;
        }

        $hasEmailAccount = EmailAccount::where('user_id', $user->id)->exists();
        if (!$hasEmailAccount) {
            return;
        }

        CalDavSyncJob::dispatch($user->id);
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    public function pollCalendar(): void
    {
        $this->dispatch('refresh-calendar');
    }

    public function createEventAction(): Action
    {
        return Action::make('createEvent')
            ->label(__('calendars.create_event'))
            ->model(Event::class)
            ->schema(EventResource::getFormSchema(false, true))
            ->mountUsing(function (Schema $form, array $arguments) {
                $endsAt = $arguments['ends_at'] ?? null;
                if (($arguments['all_day'] ?? false) && $endsAt) {
                    $endsAt = \Carbon\Carbon::parse($endsAt)->subDay()->toDateTimeString();
                }

                $form->fill([
                    'starts_at' => $arguments['starts_at'] ?? null,
                    'ends_at' => $endsAt,
                    'all_day' => $arguments['all_day'] ?? false,
                    'calendar_id' => $arguments['calendar_id'] ?? null,
                ]);
            })
            ->action(function (array $data, array $arguments) {
                if (!isset($data['calendar_id']) && isset($arguments['calendar_id'])) {
                    $data['calendar_id'] = $arguments['calendar_id'];
                }
                
                $sharedWith = $data['sharedWith'] ?? [];
                unset($data['sharedWith']);
                
                $event = Event::create($data);
                
                if (!empty($sharedWith)) {
                    $event->sharedWith()->sync($sharedWith);
                }

                $calendar = $event->calendar;
                $user = Auth::user();
                if ($calendar && $calendar->is_personal && $calendar->user_id === $user?->id) {
                    try {
                        $emailAccount = EmailAccount::where('user_id', $user->id)->first();
                        if ($emailAccount) {
                            $service = app(CalDavService::class);
                            $result = $service->createEvent($emailAccount, $calendar, $event);
                            $event->caldav_uid = $result['uid'] ?? $event->caldav_uid;
                            $event->caldav_etag = $result['etag'] ?? null;
                            $event->caldav_last_sync_at = now();
                            $event->save();
                        } else {
                            Log::warning('CalDAV Sync (Filament create): emailAccount no encontrado', [
                                'user_id' => $user->id,
                            ]);
                        }
                    } catch (\Throwable $e) {
                        Log::error('CalDAV Create Error (Filament create): ' . $e->getMessage(), [
                            'event_id' => $event->id,
                        ]);
                    }
                }
                    
                $recipients = \App\Models\User::where('id', '!=', Auth::id())->get();
                Notification::make()
                    ->title(__('calendars.notification.new_event_title'))
                    ->body(__('calendars.notification.new_event_body', ['title' => $event->title]))
                    ->success()
                    ->sendToDatabase($recipients);

                $this->dispatch('refresh-calendar');
                Notification::make()
                    ->title(__('calendars.notification.created_ok'))
                    ->success()
                    ->send();
            });
    }

    public function viewEventAction(): Action
    {
        return Action::make('viewEvent')
            ->label(__('calendars.view_event'))
            ->modalWidth('5xl')
            ->modalSubmitAction(false)
            ->modalCancelAction(fn (Action $action) => $action->label(__('Close')))
            ->schema(function (array $arguments) {
                $event = Event::with('calendar')->find($arguments['id'] ?? null);
                $isPublic = $event?->calendar?->is_public ?? false;

                return [
                    Section::make()
                        ->columns(2)
                        ->schema([
                            Forms\Components\TextInput::make('calendar_name')
                                ->label(__('calendars.field.calendar'))
                                ->disabled(),
                            Forms\Components\ColorPicker::make('color')
                                ->label(__('calendars.field.color'))
                                ->disabled(),
                            Forms\Components\TextInput::make('title')
                                ->label(__('calendars.field.title'))
                                ->disabled()
                                ->columnSpanFull(),
                            Forms\Components\DateTimePicker::make('starts_at')
                                ->label(__('calendars.field.start'))
                                ->disabled(),
                            Forms\Components\DateTimePicker::make('ends_at')
                                ->label(__('calendars.field.end'))
                                ->disabled(),
                            Forms\Components\Toggle::make('all_day')
                                ->label(__('calendars.field.all_day'))
                                ->disabled()
                                ->columnSpanFull(),
                            Forms\Components\Textarea::make('shared_with_names')
                                ->label(__('calendars.field.shared_with'))
                                ->disabled()
                                ->columnSpanFull()
                                ->rows(2)
                                ->hidden($isPublic),
                            Forms\Components\Textarea::make('description')
                                ->label(__('calendars.field.description'))
                                ->disabled()
                                ->columnSpanFull(),
                            Forms\Components\FileUpload::make('attachments')
                                ->label('Adjuntos')
                                ->disk('public')
                                ->directory('event-attachments')
                                ->visibility('public')
                                ->multiple()
                                ->downloadable()
                                ->disabled()
                                ->columnSpanFull(),
                        ])
                ];
            })
            ->mountUsing(function (Schema $form, array $arguments) {
                $event = Event::with(['calendar', 'sharedWith'])->find($arguments['id'] ?? null);
                if ($event) {
                    $data = $event->attributesToArray();
                    $data['calendar_name'] = $event->calendar->name ?? 'N/A';
                    $data['shared_with_names'] = $event->sharedWith->pluck('name')->join(', ');
                    $form->fill($data);
                }
            });
    }

    public function editEventAction(): Action
    {
        return Action::make('editEvent')
            ->label(__('calendars.edit_event'))
            ->model(Event::class)
            ->schema(function (array $arguments) {
                $event = Event::find($arguments['id'] ?? null);
                $user = Auth::user();
                $canEdit = $event && ($event->calendar->user_id === $user->id || $event->calendar->manager_user_id === $user->id);
                
                $schema = EventResource::getFormSchema(true, true);
                
                if (!$canEdit) {
                    foreach ($schema as $component) {
                        if ($component instanceof \Filament\Schemas\Components\Section) {
                            foreach ($component->getChildComponents() as $child) {
                                if ($child instanceof \Filament\Schemas\Components\Grid) {
                                    foreach ($child->getChildComponents() as $gridChild) {
                                        $gridChild->disabled(true);
                                    }
                                } else {
                                    $child->disabled(true);
                                }
                            }
                        } elseif ($component instanceof \Filament\Schemas\Components\Grid) {
                             foreach ($component->getChildComponents() as $child) {
                                 $child->disabled(true);
                             }
                        } else {
                            $component->disabled(true);
                        }
                    }
                }
                return $schema;
            })
            ->mountUsing(function (Schema $form, array $arguments) {
                $event = Event::find($arguments['id'] ?? null);
                if ($event) {
                    $data = $event->attributesToArray();
                    $data['sharedWith'] = $event->sharedWith->pluck('id')->toArray();
                    $form->fill($data);
                }
            })
            ->action(function (array $data, array $arguments) {
                 $event = Event::find($arguments['id']);
                 $user = Auth::user();
                 if ($event->calendar->user_id === $user->id || $event->calendar->manager_user_id === $user->id) {
                     $sharedWith = $data['sharedWith'] ?? [];
                     unset($data['sharedWith']);

                     $event->update($data);
                     $event->sharedWith()->sync($sharedWith);

                     $calendar = $event->calendar;
                     if ($calendar && $calendar->is_personal && $calendar->user_id === $user->id) {
                         try {
                             $emailAccount = EmailAccount::where('user_id', $user->id)->first();
                             if ($emailAccount) {
                                 $service = app(CalDavService::class);
                                 $etag = $service->updateEvent($emailAccount, $calendar, $event);
                                 if ($etag) {
                                     $event->caldav_etag = $etag;
                                     $event->caldav_last_sync_at = now();
                                     $event->save();
                                 }
                             } else {
                                 Log::warning('CalDAV Sync (Filament update): emailAccount no encontrado', [
                                     'user_id' => $user->id,
                                 ]);
                             }
                         } catch (\Throwable $e) {
                             Log::error('CalDAV Update Error (Filament update): ' . $e->getMessage(), [
                                 'event_id' => $event->id,
                             ]);
                         }
                     }

                     $recipients = \App\Models\User::where('id', '!=', Auth::id())->get();
                     Notification::make()
                        ->title(__('calendars.notification.updated_title'))
                        ->body(__('calendars.notification.updated_body', ['title' => $event->title]))
                        ->info()
                        ->sendToDatabase($recipients);

                     $this->dispatch('refresh-calendar');
                     Notification::make()
                        ->title(__('calendars.notification.updated_ok'))
                        ->success()
                        ->send();
                 }
            })
            ->modalSubmitAction(function (array $arguments) {
                 $event = Event::find($arguments['id'] ?? null);
                 $user = Auth::user();
                 $canEdit = $event && ($event->calendar->user_id === $user->id || $event->calendar->manager_user_id === $user->id);
                 return $canEdit ? Action::make('save')->label(__('Save'))->submit('editEvent') : false;
            });
    }
}
