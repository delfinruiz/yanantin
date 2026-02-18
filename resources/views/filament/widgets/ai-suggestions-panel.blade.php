<x-filament::section>
    <x-slot name="heading">Acciones Sugeridas por IA</x-slot>
    <div class="space-y-3">
        @if ($date)
            <div class="text-xs text-gray-400 dark:text-gray-500">Actualizado: {{ $date }}</div>
        @endif
        <div class="prose prose-sm dark:prose-invert max-w-none leading-relaxed">
            <span class="font-semibold">Impacto en la productividad:</span> {{ $impact }}
        </div>
        <div class="space-y-2">
            <div class="text-sm font-semibold text-gray-300 dark:text-gray-200">Acciones sugeridas</div>
            <div class="prose prose-sm dark:prose-invert max-w-none leading-relaxed">
                {!! nl2br(e($actions)) !!}
            </div>
        </div>
    </div>
</x-filament::section>
