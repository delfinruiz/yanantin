<div
    wire:poll.30s="updateBadge"
    x-data="{
        badgeContent: @entangle('badgeContent'),
        shouldShow: @entangle('shouldShow'),
        url: '{{ \App\Filament\Resources\Events\EventResource::getUrl() }}',
        init() {
            this.updateDom();
            this.$watch('badgeContent', () => this.updateDom());
            this.$watch('shouldShow', () => this.updateDom());
            document.addEventListener('livewire:navigated', () => this.updateDom());
            document.addEventListener('visibilitychange', () => this.updateDom());
            if (window.Echo) {
                window.Echo.channel('events')
                    .listen('.EventChanged', (e) => {
                        if (window.Livewire && typeof window.Livewire.dispatch === 'function') {
                            window.Livewire.dispatch('refresh-sidebar');
                        }
                        this.$wire.updateBadge();
                    });
            }
        },
        updateDom() {
            // Buscar el enlace en el sidebar que coincida con la URL del recurso Eventos
            const link = document.querySelector(`a[href='${this.url}']`);
            if (!link) return;

            // Intentar encontrar el badge existente (Filament v3 suele ponerlo en un span con clases de badge)
            let badge = link.querySelector('.fi-sidebar-item-badge') || link.querySelector('.fi-badge');

            if (this.shouldShow) {
                if (!badge) {
                    badge = document.createElement('span');
                    // Clases estándar de badge en Filament v3
                    badge.className = 'fi-sidebar-item-badge flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 min-w-[theme(spacing.6)] py-1 fi-color-custom bg-custom-50 text-custom-600 ring-custom-600/10 dark:bg-custom-400/10 dark:text-custom-400 dark:ring-custom-400/30';
                    
                    // Configurar colores para 'danger' (Rojo) ya que EventResource usa 'danger'
                    badge.style.setProperty('--c-50', 'var(--danger-50)');
                    badge.style.setProperty('--c-400', 'var(--danger-400)');
                    badge.style.setProperty('--c-600', 'var(--danger-600)');

                    // Estilos de respaldo por si las variables CSS no están disponibles
                    badge.style.color = 'rgb(220 38 38)';
                    badge.style.backgroundColor = 'rgb(254 242 242)';
                    
                    link.appendChild(badge);
                } else {
                     badge.style.display = '';
                }
                
                badge.innerText = this.badgeContent;
            } else {
                if (badge) {
                    badge.remove(); // Eliminarlo si no hay eventos para coincidir con el comportamiento de Filament
                }
            }
        }
    }"
    style="display: none;"
>
</div>
