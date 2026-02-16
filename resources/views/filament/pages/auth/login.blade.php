<div x-data="{ 
    isProcessing: false,
    init() {
        Livewire.hook('commit', ({ component, succeed, fail }) => {
            // Usamos el ID inyectado desde Blade para mayor seguridad
            if (component.id !== '{{ $this->getId() }}') return;

            succeed(({ effects }) => {
                // Si hay redirección, mantenemos isProcessing = true (botón deshabilitado).
                // Si NO hay redirección (error de validación, etc.), liberamos el botón.
                if (!effects.redirect) {
                    this.isProcessing = false;
                }
            });

            fail(() => {
                this.isProcessing = false;
            });
        })
    }
}">
    <form wire:submit="authenticate" x-on:submit="isProcessing = true" class="grid gap-y-8">
        {{ $this->form }}

        <x-filament::button type="submit" class="w-full vd rj ek rc rg gh lk ml il _l gi hi" x-bind:disabled="isProcessing" x-bind:class="{ 'opacity-50 pointer-events-none': isProcessing }">
            <span x-show="!isProcessing">
                {{ __('Iniciar sesión') }}
            </span>
            <span x-show="isProcessing" x-cloak class="flex items-center justify-center">
                <x-filament::loading-indicator class="h-5 w-5 text-white" />
            </span>
        </x-filament::button>
    </form>
</div>
