<x-filament-panels::page>
    <style>
        @media print {
            .fi-sidebar,
            .fi-topbar,
            .fi-breadcrumbs,
            .fi-global-search,
            .fi-tenant-menu,
            .fi-theme-toggle,
            .fi-header,
            .fi-page-header,
            .fi-notifications {
                display: none !important;
            }
            body { margin: 0 !important; }
            .print-area {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                padding: 0 16px;
            }
            .print-area .x-section,
            .print-area .fi-section {
                break-inside: avoid;
            }
        }
    </style>
    <div class="flex items-center justify-between print:hidden">
        <h2 class="text-xl font-semibold">{{ $record->title }}</h2>
        <x-filament::button onclick="window.print()">Imprimir / PDF</x-filament::button>
    </div>
    <div class="print-area">
        <p class="text-sm text-gray-600">{{ $record->description }}</p>
        <div class="mt-4">
            <p><strong>Participantes asignados:</strong> {{ $stats['participants'] }}</p>
            <p><strong>Promedio global:</strong> {{ $stats['global_avg'] ?? 'N/A' }}</p>
        </div>
    @if($record->is_public)
        <div class="mt-4">
            <p class="font-medium">Participantes que respondieron:</p>
            @if(!empty($stats['respondents']))
                <ul class="text-sm mt-2 list-disc list-inside">
                    @foreach($stats['respondents'] as $name)
                        <li>{{ $name }}</li>
                    @endforeach
                </ul>
            @else
                <p class="text-sm text-gray-600">Aún no hay respuestas.</p>
            @endif
        </div>
    @endif
    <div class="grid md:grid-cols-2 gap-4 mt-6">
        @foreach($stats['dimensions'] as $dim => $info)
            <x-filament::section>
                <h3 class="font-medium">{{ $dim }}</h3>
                <ul class="text-sm mt-2 space-y-1">
                    <li>Preguntas: {{ $info['questions_count'] }}</li>
                    <li>Respuestas: {{ $info['responses_count'] }}</li>
                    <li>Promedio: {{ $info['avg'] ?? 'N/A' }}</li>
                </ul>
            </x-filament::section>
        @endforeach
    </div>
    </div>
</x-filament-panels::page>
