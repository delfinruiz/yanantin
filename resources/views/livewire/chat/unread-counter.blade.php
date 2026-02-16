<div
    wire:poll.5s="updateCount"
    x-data="{
        count: @entangle('count'),
        url: '{{ \App\Filament\Pages\Chats::getUrl() }}',
        prev: null,
        init() {
            this.updateBadge();
            this.$watch('count', (value, old) => {
                this.updateBadge();
                if (value !== old) {
                    window.Livewire?.dispatch('refresh-sidebar');
                }
            });
            document.addEventListener('livewire:navigated', () => this.updateBadge());
            document.addEventListener('visibilitychange', () => this.updateBadge());
            if (window.Echo) {
                window.Echo.channel('chat')
                    .listen('.ChatMessageCreated', (e) => {
                        this.$wire.updateCount();
                        window.Livewire?.dispatch('refresh-sidebar');
                    });
            }
        },
        updateBadge() {
            const link = document.querySelector(`a[href='${this.url}']`);
            if (!link) return;

            // Try to find existing badge (Filament v3 usually puts it in a span with badge classes)
            // We look for a span that is the last child or has specific badge classes
            let badge = link.querySelector('.fi-sidebar-item-badge') || link.querySelector('.fi-badge');

            if (this.count > 0) {
                if (!badge) {
                    badge = document.createElement('span');
                    // Filament v3 badge classes
                    badge.className = 'fi-sidebar-item-badge flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 min-w-[theme(spacing.6)] py-1 fi-color-custom bg-custom-50 text-custom-600 ring-custom-600/10 dark:bg-custom-400/10 dark:text-custom-400 dark:ring-custom-400/30';
                    
                    // Manually set colors to 'danger' (red)
                    badge.style.setProperty('--c-50', 'var(--danger-50)');
                    badge.style.setProperty('--c-400', 'var(--danger-400)');
                    badge.style.setProperty('--c-600', 'var(--danger-600)');
                    
                    // Fallback styles
                    badge.style.color = 'rgb(220 38 38)';
                    badge.style.backgroundColor = 'rgb(254 242 242)';
                    
                    badge.title = 'Mensajes no leídos';
                    link.appendChild(badge);
                } else {
                     // If badge exists, ensure it's visible
                     badge.style.display = '';
                }
                
                badge.innerText = this.count > 99 ? '99+' : this.count;
            } else {
                if (badge) {
                    badge.remove(); // Remove it completely to match Filament behavior (null = no badge)
                }
            }
        }
    }"
    style="display: none;"
>
</div>
