<x-filament-widgets::widget>
    <x-filament::section class="h-full min-h-[152px] flex flex-col justify-between">
        @php
        $count = $birthdays->count();
        @endphp

        <div class="flex items-center justify-between mb-2">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-pink-600/80 text-xl">
                    🎂
                </div>
                <div>
                    <div class="text-sm font-semibold text-slate-900 dark:text-slate-50">
                        Cumpleaños de hoy
                    </div>
                </div>
            </div>
            <span class="text-[11px] px-2 py-0.5 rounded-full bg-pink-100 text-pink-700 dark:bg-pink-500/20 dark:text-pink-200">
                {{ $count }} {{ \Illuminate\Support\Str::plural('persona', $count) }}
            </span>
        </div>

        @if ($count === 0)
            <p class="text-sm text-slate-600 dark:text-slate-400">
                Hoy no hay cumpleaños en la organización.
            </p>
        @else
            <ul class="space-y-2">
                @foreach ($birthdays as $item)
                    <li class="flex items-start justify-between text-sm">
                        <div class="flex items-start gap-2">
                            <span class="mt-2 h-1.5 w-1.5 rounded-full bg-pink-400"></span>
                            <div>
                                <div class="font-medium text-slate-900 dark:text-slate-50">
                                    {{ $item['name'] }}

                                    <span class="inline-flex items-center gap-1 rounded-full bg-slate-200 px-2 py-0.5 text-[11px] text-slate-800 dark:bg-slate-700/70 dark:text-slate-100">
                                        <x-heroicon-o-building-office class="h-3 w-3" />
                                        <span>{{ $item['department'] }}</span>
                                    </span>

                                </div>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
