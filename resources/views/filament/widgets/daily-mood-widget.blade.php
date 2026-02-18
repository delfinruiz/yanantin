@php
    $moods = [
        ['code' => 'sad', 'label' => 'Triste', 'class' => 'bg-[#ef4444]', 'emoji' => '😢'],
        ['code' => 'med_sad', 'label' => 'Med Triste', 'class' => 'bg-[#f59e0b]', 'emoji' => '🙁'],
        ['code' => 'neutral', 'label' => 'Neutral', 'class' => 'bg-[#facc15]', 'emoji' => '😐'],
        ['code' => 'med_happy', 'label' => 'Med Feliz', 'class' => 'bg-[#84cc16]', 'emoji' => '🙂'],
        ['code' => 'happy', 'label' => 'Feliz', 'class' => 'bg-[#22c55e]', 'emoji' => '😄'],
    ];
@endphp

<x-filament::section>
    @if($today?->message)
        <div>
            <div class="text-sm text-success-600 font-semibold mb-1">Mensaje del día</div>
            <div class="rounded-lg border border-success-300 bg-success-50 p-4 text-success-900">
                “{{ $today->message }}”
            </div>
        </div>
    @endif
</x-filament::section>
