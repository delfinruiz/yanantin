<x-filament-panels::page subheading="Este es tu panel como candidato. Aquí verás tus datos y las ofertas disponibles.">
    <div x-data="{ activeTab: @entangle('activeTab') }">
        <x-filament::tabs label="Content tabs" class="mb-6">
            <x-filament::tabs.item
                :active="$activeTab === 'offers'"
                wire:click="$set('activeTab', 'offers')"
                icon="heroicon-o-briefcase"
            >
                Ofertas Laborales
            </x-filament::tabs.item>

            <x-filament::tabs.item
                :active="$activeTab === 'curriculum'"
                wire:click="$set('activeTab', 'curriculum')"
                icon="heroicon-o-user-circle"
            >
                Mi Curriculum
            </x-filament::tabs.item>

            <x-filament::tabs.item
                :active="$activeTab === 'applications'"
                wire:click="$set('activeTab', 'applications')"
                icon="heroicon-o-document-text"
            >
                Mis Postulaciones
            </x-filament::tabs.item>
        </x-filament::tabs>

        {{-- Contenido normal --}}
        <div class="mt-4">
            @if ($activeTab === 'offers')
                <div wire:key="offers-tab">
                    {{ $this->table }}
                </div>
            @elseif ($activeTab === 'curriculum')
                <div wire:key="curriculum-tab">
                    @include('filament.pages.public-dashboard-tabs.curriculum')
                </div>
            @elseif ($activeTab === 'applications')
                <div wire:key="applications-tab">
                    @livewire('my-applications-table')
                </div>
            @endif
        </div>
    </div>
    
    <x-filament-actions::modals />
</x-filament-panels::page>
