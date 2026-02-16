<div
    wire:poll.15s="updateBadge"
    x-data="{
        badgeContent: @entangle('badgeContent'),
        shouldShow: @entangle('shouldShow'),
        url: '{{ $webmailUrl }}',
        init() {
            this.updateDom();
            this.$watch('badgeContent', () => this.updateDom());
            this.$watch('shouldShow', () => this.updateDom());
            document.addEventListener('livewire:navigated', () => this.updateDom());
            document.addEventListener('visibilitychange', () => this.updateDom());
        },
        updateDom() {
            const items = Array.from(document.querySelectorAll('.fi-sidebar a'))
            const link = items.find(el => (el.textContent || '').trim().includes('Webmail'))
            if (!link) return
            let badge = link.querySelector('.fi-sidebar-item-badge') 
                || link.querySelector('.fi-badge') 
                || link.querySelector('#fp-webmail-badge')
            if (this.shouldShow && this.badgeContent) {
                if (!badge) {
                    const newBadge = document.createElement('span')
                    newBadge.className = 'fi-sidebar-item-badge flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 min-w-[theme(spacing.6)] py-1 fi-color-danger bg-danger-50 text-danger-600 ring-danger-600/10 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/30'
                    newBadge.style.color = 'rgb(220 38 38)'
                    newBadge.style.backgroundColor = 'rgb(254 242 242)'
                    newBadge.id = 'fp-webmail-badge'
                    link.appendChild(newBadge)
                    badge = newBadge
                } else {
                    badge.style.display = ''
                }
                if (badge.innerText !== this.badgeContent) {
                    badge.innerText = this.badgeContent
                }
            } else {
                if (badge) {
                    badge.style.display = 'none'
                }
            }
            if (window.Livewire && typeof window.Livewire.dispatch === 'function') {
                window.Livewire.dispatch('refresh-sidebar')
            }
        }
    }"
    style="display:none;"
></div>
