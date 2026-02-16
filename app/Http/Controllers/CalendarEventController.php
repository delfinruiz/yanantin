<?php

namespace App\Http\Controllers;

use App\Models\Calendar;
use App\Models\Event;
use App\Models\EmailAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use App\Services\CalDav\CalDavService;

use Illuminate\Support\Facades\Log;

class CalendarEventController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $calendarId = $request->integer('calendar_id');
        $start = $request->input('start');
        $end = $request->input('end');

        $query = Event::query()
            ->with('calendar')
            ->whereBetween('starts_at', [$start, $end]);

        if ($calendarId) {
            $query->where('calendar_id', $calendarId);
        } else {
            $authorizedCalendarIds = Calendar::query()
                ->where(function ($q) use ($user) {
                    $q->where('is_public', true)
                        ->orWhere('user_id', $user->id)
                        ->orWhere('manager_user_id', $user->id);
                })
                ->pluck('id');
            
            $query->where(function ($q) use ($authorizedCalendarIds, $user) {
                $q->whereIn('calendar_id', $authorizedCalendarIds)
                  ->orWhereHas('sharedWith', function ($q2) use ($user) {
                      $q2->where('user_id', $user->id);
                  });
            });
        }

        return $query->get()->map(function (Event $event) use ($user) {
            $end = $event->ends_at;
            if ($event->all_day && $end) {
                // FullCalendar expects exclusive end date for all-day events
                $end = $end->copy()->addDay();
            }

            // Determine if editable based on Policy logic (simplified here or use policy)
            // Policy: Update allowed if Own Calendar OR Manager Calendar. Shared users = Read Only.
            $isEditable = false;
            if ($event->calendar->user_id === $user->id || $event->calendar->manager_user_id === $user->id) {
                 $isEditable = true;
            }

            return [
                'id' => $event->id,
                'title' => $event->title,
                'start' => $event->starts_at,
                'end' => $end,
                'allDay' => $event->all_day,
                'color' => $event->color,
                'editable' => $isEditable, // FullCalendar property
                'startEditable' => $isEditable,
                'durationEditable' => $isEditable,
            ];
        });
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'calendar_id' => ['required', 'exists:calendars,id'],
            'title' => ['required', 'string'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date'],
            'all_day' => ['sometimes', 'boolean'],
            'color' => ['nullable', 'string'],
        ]);

        $calendar = Calendar::find($data['calendar_id']);

        // Check permissions: Only Owner or Manager can create events
        if ($calendar->user_id !== Auth::id() && $calendar->manager_user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (isset($data['all_day']) && $data['all_day'] && !empty($data['ends_at'])) {
            // FullCalendar sends exclusive end date, so we subtract one day to store inclusive
            $data['ends_at'] = Carbon::parse($data['ends_at'])->subDay();
        }

        $data['created_by'] = Auth::id();

        $event = Event::create($data);

        // Sync to CalDAV if personal calendar
        if ($calendar->is_personal && $calendar->user_id === Auth::id()) {
            Log::info("CalDAV Sync: Intentando crear evento personal", ['event_id' => $event->id, 'calendar_id' => $calendar->id]);
            try {
                $emailAccount = \App\Models\EmailAccount::where('user_id', Auth::id())->first();
                if ($emailAccount) {
                    Log::info("CalDAV Sync: Cuenta de email encontrada", ['email' => $emailAccount->email]);
                    $calDavService = app(CalDavService::class);
                    $result = $calDavService->createEvent($emailAccount, $calendar, $event);
                    $event->caldav_uid = $result['uid'];
                    $event->caldav_etag = $result['etag'];
                    $event->caldav_last_sync_at = now();
                    $event->save();
                    Log::info("CalDAV Sync: Evento creado exitosamente", ['uid' => $result['uid']]);
                } else {
                    Log::warning("CalDAV Sync: No se encontró cuenta de email para el usuario " . Auth::id());
                }
            } catch (\Exception $e) {
                // Log error but don't fail the request
                Log::error('CalDAV Create Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            }
        } else {
             Log::info("CalDAV Sync: Omitido (no es personal o no es propietario)", [
                'is_personal' => $calendar->is_personal,
                'calendar_user_id' => $calendar->user_id,
                'auth_id' => Auth::id()
            ]);
        }

        // Auto-share with the calendar manager if it's a public calendar managed by someone else
        if ($calendar && $calendar->manager_user_id && $calendar->manager_user_id !== Auth::id()) {
            $event->sharedWith()->attach($calendar->manager_user_id);

            // Notify manager
            $manager = \App\Models\User::find($calendar->manager_user_id);
            if ($manager) {
                Notification::make()
                    ->title('Evento creado en tu calendario gestionado')
                    ->body("El usuario " . Auth::user()->name . " ha creado '{$event->title}' en '{$calendar->name}'.")
                    ->info()
                    ->sendToDatabase($manager);
            }
        }

        // Public calendar notify all (if created by manager)
        if ($calendar && $calendar->is_public && $calendar->manager_user_id === Auth::id()) {
            $users = \App\Models\User::where('id', '!=', Auth::id())->get();
            foreach ($users as $user) {
                Notification::make()
                    ->title('Nuevo evento público')
                    ->body("El encargado {$calendar->manager->name} ha añadido '{$event->title}' al calendario '{$calendar->name}'.")
                    ->info()
                    ->sendToDatabase($user);
            }
        }

        return response()->json(['id' => $event->id], 201);
    }

    public function update(Request $request, Event $event)
    {
        $this->authorize('update', $event);
        $data = $request->only(['title', 'starts_at', 'ends_at', 'all_day', 'color']);

        if ($request->boolean('all_day') && !empty($data['ends_at'])) {
             // FullCalendar sends exclusive end date, so we subtract one day to store inclusive
             $data['ends_at'] = Carbon::parse($data['ends_at'])->subDay();
        }

        $event->update($data);

        // Sync to CalDAV if personal calendar
        $calendar = $event->calendar;
        if ($calendar && $calendar->is_personal && $calendar->user_id === Auth::id()) {
            Log::info("CalDAV Sync: Intentando actualizar evento personal", ['event_id' => $event->id]);
            try {
                $emailAccount = EmailAccount::where('user_id', Auth::id())->first();
                if ($emailAccount) {
                    $calDavService = app(CalDavService::class);
                    $etag = $calDavService->updateEvent($emailAccount, $calendar, $event);
                    if ($etag) {
                        $event->caldav_etag = $etag;
                        $event->caldav_last_sync_at = now();
                        $event->save();
                        Log::info("CalDAV Sync: Evento actualizado exitosamente", ['etag' => $etag]);
                    }
                }
            } catch (\Exception $e) {
                Log::error('CalDAV Update Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            }
        }
        
        return response()->json(['ok' => true]);
    }

    public function destroy(Event $event)
    {
        $this->authorize('delete', $event);
        $calendar = $event->calendar;

        // Sync to CalDAV if personal calendar
        if ($calendar->is_personal && $calendar->user_id === Auth::id()) {
            Log::info("CalDAV Sync: Intentando eliminar evento personal", ['event_id' => $event->id]);
            try {
                $emailAccount = \App\Models\EmailAccount::where('user_id', Auth::id())->first();
                if ($emailAccount) {
                    $calDavService = app(CalDavService::class);
                    $calDavService->deleteEvent($emailAccount, $event);
                    Log::info("CalDAV Sync: Evento eliminado exitosamente (CalDAV)");
                }
            } catch (\Exception $e) {
                Log::error('CalDAV Delete Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            }
        }

        $event->delete();
        return response()->json(['ok' => true]);
    }
}
