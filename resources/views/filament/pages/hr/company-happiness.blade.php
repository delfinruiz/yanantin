@php
    $suggest = \App\Models\HappinessSuggestion::whereDate('date', now())->latest()->first();
@endphp
<x-filament-panels::page>
    <style>
        .company-happiness-section{height:673px;display:flex;flex-direction:column;position:relative}
        .company-happiness-section .fi-empty-state{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);margin:0}
        .company-happiness-section .fi-empty-state-content{margin:auto}
    </style>
    <x-filament::modal id="suggestions-modal" width="lg">
        <x-slot name="heading">Sugerencias de la IA</x-slot>
        <div class="prose max-w-none whitespace-pre-line">
            {{ optional($suggest)->suggestion ?: 'Sin sugerencias generadas aún.' }}
        </div>
    </x-filament::modal>
    <div class="mt-6 flex flex-col lg:flex-row gap-6">
        <div class="lg:w-2/3">
            <x-filament::section class="company-happiness-section">
                <x-slot name="heading">Registros de Estados de Ánimo</x-slot>
                <div class="flex-1 min-h-0">
                    {{ $this->table }}
                </div>
            </x-filament::section>
        </div>
        <div class="lg:w-1/3">
            <div wire:key="side-widgets-{{ $this->getId() }}-reset-{{ $this->filtersTick }}">
                <x-filament-widgets::widgets
                    :widgets="$this->getSideWidgets()"
                    :columns="1"
                    :data="['pageFilters' => $this->currentPageFilters()]"
                />
            </div>
        </div>
    </div>
</x-filament-panels::page>

