<footer class="bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-800 mt-auto py-6">
    <div class="w-full mx-auto max-w-screen-xl px-4 flex flex-col items-center justify-center gap-4 text-center">
        <span class="text-sm text-gray-500 dark:text-gray-400">
            © {{ date('Y') }} <span class="font-semibold text-primary-600 dark:text-primary-400">{{ config('app.name', 'Finanzas Personales') }}</span>. {{ __('Todos los derechos reservados.') }}
        </span>
        <div class="hidden">
             <!-- Optional: Add links here if needed -->
        </div>
    </div>
</footer>