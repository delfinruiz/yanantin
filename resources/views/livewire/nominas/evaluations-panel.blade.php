<div class="flex flex-col gap-6">
    {{-- Header / Cycle Info --}}
    <div class="flex flex-col gap-2">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <x-heroicon-o-chart-bar class="w-5 h-5 text-primary-600 dark:text-primary-400"/>
                Evaluación de Desempeño
            </h3>
            @if($cycle)
                <span class="inline-flex items-center rounded-md bg-primary-50 px-2 py-1 text-xs font-medium text-primary-700 ring-1 ring-inset ring-primary-700/10 dark:bg-primary-400/10 dark:text-primary-400 dark:ring-primary-400/30">
                    {{ $cycle->status }}
                </span>
            @endif
        </div>

        @if($cycle)
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Ciclo Actual</h4>
                        <p class="mt-1 text-base font-semibold text-gray-900 dark:text-white">{{ $cycle->name }}</p>
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 flex flex-col sm:items-end">
                        <div class="flex items-center gap-1">
                            <x-heroicon-m-calendar class="w-4 h-4 text-gray-400"/>
                            <span>Inicio: {{ $cycle->starts_at?->format('d/m/Y') ?? 'N/A' }}</span>
                        </div>
                        <div class="flex items-center gap-1 mt-1">
                            <x-heroicon-m-calendar class="w-4 h-4 text-gray-400"/>
                            <span>Fin: {{ $cycle->ends_at?->format('d/m/Y') ?? 'N/A' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="rounded-xl bg-yellow-50 p-4 ring-1 ring-yellow-600/20 dark:bg-yellow-400/10 dark:ring-yellow-400/30">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <x-heroicon-m-exclamation-triangle class="h-5 w-5 text-yellow-400" />
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-500">No hay ciclos de evaluación activos</h3>
                        <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-400">
                            <p>La información de objetivos y desempeño aparecerá aquí una vez que comience un nuevo ciclo.</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Objectives Section --}}
    <div class="flex flex-col gap-3">
        <h4 class="text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2">
            <x-heroicon-o-list-bullet class="w-5 h-5 text-gray-500"/>
            Objetivos Asignados
        </h4>

        @if($objectives->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($objectives as $obj)
                    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 flex flex-col justify-between h-full transition hover:shadow-md">
                        <div>
                            <div class="flex items-start justify-between gap-2">
                                <h5 class="text-sm font-semibold text-gray-900 dark:text-white line-clamp-2" title="{{ $obj->title }}">
                                    {{ $obj->title }}
                                </h5>
                                <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20 whitespace-nowrap">
                                    {{ $obj->status }}
                                </span>
                            </div>
                            
                            @if($obj->description)
                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 line-clamp-2" title="{{ $obj->description }}">
                                    {{ $obj->description }}
                                </p>
                            @endif
                            
                            @if($obj->rejection_reason)
                                <div class="mt-2 rounded-md bg-red-50 p-2 text-xs text-red-700 ring-1 ring-inset ring-red-600/10 dark:bg-red-400/10 dark:text-red-400 dark:ring-red-400/20">
                                    <strong>Rechazo:</strong> {{ $obj->rejection_reason }}
                                </div>
                            @endif
                        </div>

                        <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between text-xs">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center gap-1 text-gray-500">
                                    <x-heroicon-m-tag class="w-3 h-3"/>
                                    {{ ucfirst($obj->type) }}
                                </span>
                            </div>
                            <div class="flex items-center gap-1 font-medium text-primary-600 dark:text-primary-400">
                                <x-heroicon-m-scale class="w-3 h-3"/>
                                <span>Peso: {{ $obj->weight }}%</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="rounded-xl border-2 border-dashed border-gray-200 p-6 text-center dark:border-gray-700">
                <x-heroicon-o-clipboard-document-list class="mx-auto h-8 w-8 text-gray-400" />
                <span class="mt-2 block text-sm font-semibold text-gray-900 dark:text-white">Sin objetivos</span>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">No hay objetivos asignados para el ciclo seleccionado.</p>
            </div>
        @endif
    </div>

    {{-- Results Section --}}
    <div class="flex flex-col gap-3">
        <h4 class="text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2">
            <x-heroicon-o-trophy class="w-5 h-5 text-yellow-500"/>
            Resultados
        </h4>

        @if($result)
            <div class="overflow-hidden rounded-xl bg-gradient-to-br from-white to-gray-50 shadow-sm ring-1 ring-gray-950/5 dark:from-gray-900 dark:to-gray-800 dark:ring-white/10">
                <div class="grid grid-cols-1 sm:grid-cols-3 divide-y sm:divide-y-0 sm:divide-x divide-gray-100 dark:divide-gray-800">
                    
                    <div class="p-6 text-center flex flex-col items-center justify-center">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Puntaje Final</dt>
                        <dd class="mt-2 text-4xl font-bold tracking-tight text-primary-600 dark:text-primary-400">
                            {{ $result->final_score }}%
                        </dd>
                    </div>

                    <div class="p-6 flex flex-col justify-center">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Clasificación</dt>
                        <dd class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <span class="relative flex h-3 w-3">
                              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                              <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                            </span>
                            {{ $result->range?->name ?? 'Sin Clasificar' }}
                        </dd>
                        @if($result->range)
                            <p class="mt-1 text-xs text-gray-500">
                                Rango: {{ $result->range->min_percentage }}% - {{ $result->range->max_percentage }}%
                            </p>
                        @endif
                    </div>

                    <div class="p-6 flex flex-col justify-center">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Bono Obtenido</dt>
                        <dd class="text-2xl font-semibold text-gray-900 dark:text-white">
                            {{ $result->bonus_amount ? '$' . number_format($result->bonus_amount, 0) : 'N/A' }}
                        </dd>
                        <p class="mt-1 text-xs text-gray-500">Calculado en base al cumplimiento</p>
                    </div>
                </div>
            </div>
        @else
             <div class="rounded-xl border border-gray-200 bg-gray-50 p-6 text-center dark:border-gray-800 dark:bg-gray-900/50">
                <p class="text-sm text-gray-500 dark:text-gray-400">Los resultados aún no han sido calculados para este ciclo.</p>
            </div>
        @endif
    </div>
</div>
