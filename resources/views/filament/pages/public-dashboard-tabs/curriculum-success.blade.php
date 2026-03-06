<div class="flex flex-col items-center justify-center space-y-4 p-8 text-center">
    <div class="rounded-full bg-success-100 p-3 text-success-600 dark:bg-success-900/30">
        <svg class="h-12 w-12" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
    </div>
    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">¡Curriculum Guardado!</h2>
    <p class="text-gray-500 dark:text-gray-400 max-w-md">
        Tu información ha sido actualizada correctamente. Ahora puedes postular a las ofertas laborales con tu perfil completo.
    </p>
    <div class="flex gap-4 mt-4">
        <x-filament::button 
            wire:click="openReviewModal"
            color="primary"
            tag="button"
        >
            Revisar mis datos
        </x-filament::button>
    </div>
</div>
