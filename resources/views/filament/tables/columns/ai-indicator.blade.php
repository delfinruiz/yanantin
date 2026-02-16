<div class="flex items-center justify-center w-full">
    @if($getRecord()->aiAppreciation)
        @svg('heroicon-o-cpu-chip', 'w-6 h-6 text-primary-500')
    @else
        <span class="text-sm text-gray-500 dark:text-gray-400 text-center">Sin apreciación</span>
    @endif
</div>
