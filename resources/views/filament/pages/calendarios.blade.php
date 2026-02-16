<x-filament-panels::page>
    @php
        $user = auth()->user();
        $query = \App\Models\Calendar::query()
            ->where(function ($q) use ($user) {
                $q->where('is_public', true)
                  ->orWhere('user_id', $user?->id)
                  ->orWhere('manager_user_id', $user?->id);
            })
            ->orderBy('is_personal', 'desc')
            ->orderBy('name');

        $calendars = $query->get(['id','name','is_personal','user_id','manager_user_id']);
        
        $calendarPermissions = $calendars->mapWithKeys(function ($cal) use ($user) {
            return [$cal->id => ($cal->user_id === $user?->id || $cal->manager_user_id === $user?->id)];
        });

        $initialCalendarId = $calendars->first()?->id;
    @endphp

    <div x-data="{
        calendarId: '{{ $initialCalendarId }}',
        permissions: {{ json_encode($calendarPermissions) }},
        isLoading: false
    }">
        <div class="flex items-center gap-3 mb-4">
            <h2 class="text-xl font-semibold">{{ __('calendars.title') }}</h2>
            <div x-show="permissions[calendarId]" style="display: none;" x-cloak>
                <x-filament::badge color="success">
                    {{ __('calendars_admin.badge.manager') }}
                </x-filament::badge>
            </div>
            <style>
                :root {
                    --fc-button-bg-color: #288cfa;
                    --fc-button-border-color: #288cfa;
                    --fc-button-hover-bg-color: #103766;
                    --fc-button-hover-border-color: #103766;
                    --fc-button-active-bg-color: #103766;
                    --fc-button-active-border-color: #103766;
                    --fc-event-bg-color: #288cfa;
                    --fc-event-border-color: #288cfa;
                    --fc-today-bg-color: rgba(40, 140, 250, 0.15);
                }
                .fc-event {
                    cursor: pointer;
                }
                [x-cloak] { display: none !important; }
            </style>
            <div class="ml-auto">
                <x-filament::input.wrapper>
                    <x-filament::input.select x-model="calendarId" class="block w-64">
                        @foreach ($calendars as $cal)
                            <option value="{{ $cal->id }}">
                                {{ $cal->is_personal ? __('calendars_admin.dropdown.personal') : '' }}{{ $cal->name }}
                            </option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>
        </div>

    <div wire:poll.15s="pollCalendar"></div>

    <div
        wire:ignore
        x-init="
            (async () => {
                const el = document.getElementById('fp-fullcalendar');
                
                // Cargar script global inyectando tag script para asegurar window.FullCalendar
                if (!window.FullCalendar) {
                    await new Promise((resolve, reject) => {
                        const script = document.createElement('script');
                        script.src = 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js';
                        script.onload = resolve;
                        script.onerror = reject;
                        document.head.appendChild(script);
                    });
                }
                
                const Calendar = window.FullCalendar.Calendar;
                
                const makeEventsUrl = (id) => {
                    const base = '{{ route('calendar.events.index') }}';
                    const cid = id || calendarId;
                    return cid ? `${base}?calendar_id=${cid}` : base;
                };
                const calendar = new Calendar(el, {
                    initialView: 'dayGridMonth',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay'
                    },
                    buttonText: {
                        today: '{{ __('calendars.button.today') }}',
                        month: '{{ __('calendars.button.month') }}',
                        week: '{{ __('calendars.button.week') }}',
                        day: '{{ __('calendars.button.day') }}',
                    },
                    locale: '{{ app()->getLocale() }}',
                    timeZone: '{{ config('app.timezone') }}',
                    editable: true,
                    selectable: true,
                    loading: (isLoadingView) => {
                        isLoading = isLoadingView;
                    },
                    events: makeEventsUrl(calendarId),
                    select: async (selectionInfo) => {
                        if (!permissions[calendarId]) {
                            return;
                        }

                        $wire.mountAction('createEvent', {
                            calendar_id: calendarId,
                            starts_at: selectionInfo.startStr,
                            ends_at: selectionInfo.endStr,
                            all_day: selectionInfo.allDay,
                        });
                    },
                    eventClick: (info) => {
                         if (calendarId && permissions[calendarId]) {
                             $wire.mountAction('editEvent', { id: info.event.id });
                         } else {
                             $wire.mountAction('viewEvent', { id: info.event.id });
                         }
                    },
                    eventDrop: async (info) => {
                        isLoading = true;
                        const toLocalISOString = (date) => {
                            const offset = date.getTimezoneOffset() * 60000;
                            return new Date(date.getTime() - offset).toISOString().slice(0, 19);
                        };

                        // Obtener duración original
                        const oldStart = info.oldEvent.start;
                        const oldEnd = info.oldEvent.end || oldStart; // Si es null, asumimos mismo día
                        const duration = oldEnd.getTime() - oldStart.getTime();

                        let endsAt = info.event.endStr;
                        if (!endsAt) {
                             // Si FullCalendar no envía endStr (común al mover eventos de un día a otro día sin cambiar duración)
                             // calculamos el nuevo fin sumando la duración original al nuevo inicio
                             const newStart = new Date(info.event.startStr); // startStr ya tiene el offset correcto
                             const newEnd = new Date(newStart.getTime() + duration);
                             endsAt = toLocalISOString(newEnd);
                        } else {
                             // Si hay endStr, asegurarnos de que no sea UTC si el backend espera local
                             // (Aunque normalmente startStr/endStr de FullCalendar ya traen offset si timeZone está configurado)
                             // Para consistencia, podemos re-generarlo o confiar en el string. 
                             // Si el usuario reporta problemas de 3h, es mejor forzar nuestro formato local.
                             endsAt = toLocalISOString(info.event.end);
                        }
                        
                        const payload = {
                            starts_at: toLocalISOString(info.event.start),
                            ends_at: endsAt,
                            all_day: info.event.allDay,
                        };

                        // Revertir cambio visual inmediato para evitar glitch de 'un día más'
                        // y confiar en la respuesta del servidor
                        info.revert();

                        try {
                            await fetch(`{{ url('/calendar/events') }}/${info.event.id}`, {
                                method: 'PUT',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                },
                                body: JSON.stringify(payload),
                            });
                            calendar.refetchEvents();
                        } catch (error) {
                            isLoading = false;
                            console.error(error);
                        }
                    },
                    eventResize: async (info) => {
                        isLoading = true;
                        const toLocalISOString = (date) => {
                            const offset = date.getTimezoneOffset() * 60000;
                            return new Date(date.getTime() - offset).toISOString().slice(0, 19);
                        };

                        const payload = {
                            starts_at: toLocalISOString(info.event.start),
                            ends_at: toLocalISOString(info.event.end),
                            all_day: info.event.allDay,
                        };

                        info.revert();

                        try {
                            await fetch(`{{ url('/calendar/events') }}/${info.event.id}`, {
                                method: 'PUT',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                },
                                body: JSON.stringify(payload),
                            });
                            calendar.refetchEvents();
                        } catch (error) {
                            isLoading = false;
                            console.error(error);
                        }
                    }
                });
                calendar.render();

                $wire.on('refresh-calendar', () => {
                    calendar.refetchEvents();
                });

                $watch('calendarId', (value) => {
                    calendar.removeAllEvents();
                    calendar.refetchEvents();
                    calendar.setOption('events', makeEventsUrl(value));
                });

                window.fpCalendar = calendar;
            })();
        "
        class="bg-white dark:bg-gray-900 rounded-xl p-2 relative"
    >
        <div x-show="isLoading" class="absolute inset-0 z-50 flex items-center justify-center bg-white/50 dark:bg-gray-900/50 rounded-xl" style="display: none;">
             <x-filament::loading-indicator class="w-10 h-10 text-primary-500" />
        </div>
        <div id="fp-fullcalendar"></div>
    </div>
    </div>
</x-filament-panels::page>
