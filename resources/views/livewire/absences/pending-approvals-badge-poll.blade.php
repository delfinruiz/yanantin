<div
    wire:poll.10s="updateBadge"
    x-data="{
        badgeContent: @entangle('badgeContent'),
        shouldShow: @entangle('shouldShow'),
        url: '{{ \App\Filament\Resources\AbsenceRequests\AbsenceRequestResource::getUrl() }}',
        init() {
            this.updateDom();
            this.$watch('badgeContent', () => this.updateDom());
            this.$watch('shouldShow', () => this.updateDom());
            document.addEventListener('livewire:navigated', () => this.updateDom());
            document.addEventListener('visibilitychange', () => this.updateDom());
        },
        updateDom() {
            const path = new URL(this.url, window.location.origin).pathname
            let link = document.querySelector(`a[href='${this.url}']`) 
                || document.querySelector(`a[href$='${path}']`);
            if (!link) {
                const items = Array.from(document.querySelectorAll('.fi-sidebar a'));
                link = items.find(el => el.textContent?.trim() === '{{ \App\Filament\Resources\AbsenceRequests\AbsenceRequestResource::getNavigationLabel() }}');
            }
            if (!link) return
            let badge = link.querySelector('#fp-absences-badge') || link.querySelector('.fi-sidebar-item-badge') || link.querySelector('.fi-badge')
            if (this.shouldShow && this.badgeContent) {
                if (!badge) {
                    badge = document.createElement('span')
                    badge.className = 'fi-sidebar-item-badge flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 min-w-[theme(spacing.6)] py-1 fi-color-danger bg-danger-50 text-danger-600 ring-danger-600/10 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/30'
                    badge.style.color = 'rgb(220 38 38)'
                    badge.style.backgroundColor = 'rgb(254 242 242)'
                    badge.id = 'fp-absences-badge'
                    link.appendChild(badge)
                } else {
                    badge.style.display = ''
                }
                if (badge.innerText !== this.badgeContent) {
                    badge.innerText = this.badgeContent
                }
            } else {
                if (badge) badge.style.display = 'none'
            }
            if (window.Livewire && typeof window.Livewire.dispatch === 'function') {
                window.Livewire.dispatch('refresh-sidebar')
            }
        }
    }"
    style="display:none;"
></div>

