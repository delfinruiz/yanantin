<x-filament-panels::page>
    <div class="space-y-6">
        @if (! $this->getTableSearch())
        {{-- Breadcrumbs --}}
        <div class="flex items-center space-x-2 text-sm bg-gray-100 p-2 rounded-lg dark:bg-gray-800">
            <button wire:click="goToRoot" class="hover:text-primary-600 transition">
                <x-heroicon-o-home class="w-5 h-5" />
            </button>

            @php
            $parts = $currentPath === '/' ? [] : array_filter(explode('/', trim($currentPath, '/')));
            $cumulative = '';
            @endphp

            @foreach ($parts as $part)
            @php $cumulative .= '/' . $part; @endphp
            <span class="text-gray-400">|</span>
            <button
                wire:click="navigateTo('{{ $cumulative }}')"
                class="hover:text-primary-600 font-medium">
                {{ $part === \App\Filament\Pages\FileManager::SHARED_PATH ? __('FileManager_Shared_Folder_Name') : $part }}
            </button>
            @endforeach
        </div>
        @endif

        {{ $this->table }}
    </div>

    <div
        x-data="{ fileId: null, code: '' }"
        x-on:open-ack-confirm.window="
            fileId = $event.detail.fileId;
            code = '';
            $dispatch('open-modal', { id: 'ack-confirm' });
        "
        x-cloak>
        <x-filament::modal id="ack-confirm" width="md" icon="heroicon-o-check-badge" :close-by-clicking-away="false" :close-by-escaping="false">
            <x-slot name="heading">
                {{ __('FileManager_Ack_Required') }}
            </x-slot>
            <div class="space-y-4">
                <p class="text-sm text-gray-600">
                    {{ __('FileManager_Confirm_Ack_Description') }}
                </p>
                <x-filament::input.wrapper>
                    <x-filament::input
                        x-model="code"
                        type="text"
                        maxlength="12"
                        autocomplete="one-time-code"
                        placeholder="{{ __('FileManager_Ack_Code') }}" />
                </x-filament::input.wrapper>
                <div class="flex justify-end gap-3 md:gap-4">
                    <x-filament::button color="gray" x-on:click="$dispatch('close-modal', { id: 'ack-confirm' })">
                        {{ __('filament-panels::resources/pages/edit-record.form.actions.cancel.label') }}
                    </x-filament::button>
                    <x-filament::button color="primary" x-on:click="$wire.confirmAck(code)">
                        {{ __('FileManager_Confirm_Ack') }}
                    </x-filament::button>
                </div>
            </div>
        </x-filament::modal>
    </div>

    {{-- Modal Preview --}}
    <div
        x-data="{
            file: {
                path: null,
                type: null,
                name: null,
            },
            textContent: '',
            loadingText: false,
            resetMedia() {
                
                // Pausa y resetea audio/video
                const media = document.querySelector('audio, video');
                if (media) {
                    media.pause();
                    media.currentTime = 0;
                }
                // Limpia iframe
                const iframe = document.querySelector('iframe');
                if (iframe) {
                    iframe.src = '';
                }

                // Limpiar texto
                this.textContent = '';
                this.loadingText = false;

                // Limpiar objeto file inmediatamente (opcional, pero seguro para evitar estados inconsistentes)
                // Se comenta para evitar parpadeos si se abre otro archivo inmediatamente
                // this.file = { path: null, type: null, name: null };
            },
            async loadTextContent() {
                if (this.file.type === 'txt' && this.file.path) {
                    this.loadingText = true;
                    try {
                        const response = await fetch(this.file.path);
                        if (!response.ok) throw new Error('Error cargando el archivo');
                        this.textContent = await response.text();
                    } catch (error) {
                        console.error('Error fetching text:', error);
                        this.textContent = 'No se pudo cargar el contenido del archivo.';
                    } finally {
                        this.loadingText = false;
                    }
                }
            }
        }"
        x-on:open-preview.window="
            file = $event.detail;
            $dispatch('open-modal', { id: 'preview-modal' });
            console.log('Archivo:', $event.detail);
            if (file.type === 'txt') {
                loadTextContent();
            }
        "
        x-on:close-modal.window="
            resetMedia();

        "
        x-cloak>
        <x-filament::modal
            id="preview-modal"
            width="4xl"
            icon="heroicon-o-eye"
            :close-by-clicking-away="false"
            :close-by-escaping="false">
            <x-slot name="heading">
                <span x-text="file.name || 'Vista previa'"></span>
            </x-slot>

            <div class="space-y-4">
                <!-- TXT -->
                <template x-if="file.type === 'txt'">
                    <div class="w-full h-96 border rounded-lg bg-gray-50 dark:bg-gray-900 p-4 overflow-auto font-mono text-sm">
                        <div x-show="loadingText" class="flex items-center justify-center h-full text-gray-400">
                            <x-filament::loading-indicator class="w-8 h-8" />
                        </div>
                        <pre x-show="!loadingText" x-text="textContent" class="whitespace-pre-wrap break-words"></pre>
                    </div>
                </template>

                <!-- Imagen -->
                <template x-if="['jpg','jpeg','png','gif','svg','webp'].includes(file.type)">
                    <img :src="file.path"
                        class="max-w-full max-h-[80vh] mx-auto rounded-lg shadow-lg object-contain"
                        alt="Vista previa">
                </template>

                <!-- Audio -->
                <template x-if="['mp3','wav','ogg','aac','m4a'].includes(file.type)">
                    <audio controls class="w-full rounded-lg shadow-md"
                        x-on:play.debounce.100ms="console.log('Audio reproduciendo')"
                        preload="metadata">
                        <source :src="file.path">
                        Tu navegador no soporta audio.
                    </audio>
                </template>

                <!-- Video -->
                <template x-if="['mp4','webm','ogg'].includes(file.type)">
                    <video controls class="w-full max-h-[80vh] rounded-lg shadow-lg"
                        x-on:play.debounce.100ms="console.log('Video reproduciendo')"
                        preload="metadata">
                        <source :src="file.path">
                        Tu navegador no soporta video.
                    </video>
                </template>

                <!-- Otros archivos -->
                <template x-if="!['txt','pdf','jpg','jpeg','png','gif','svg','webp','mp3','wav','ogg','mp4','webm','avi','mov','aac','m4a'].includes(file.type)">
                    <div class="text-center py-12">
                        <x-heroicon-o-document class="w-16 h-16 text-gray-400 mx-auto mb-4" />
                        <p class="text-gray-500 text-lg">Vista previa no disponible para este tipo de archivo</p>
                        <p class="text-sm text-gray-400 mt-1" x-text="file.type?.toUpperCase()"></p>
                    </div>
                </template>
            </div>
        </x-filament::modal>

    </div>

    <x-filament::modal id="share-info" width="md">
        <x-slot name="heading">
            Compartido con
        </x-slot>

        <div
            x-data="{ shares: [] }"
            x-on:open-share-info.window="
            shares = $event.detail.shares;
            $dispatch('open-modal', { id: 'share-info' });
        "
            class="space-y-3">
            <template x-if="shares.length === 0">
                <p class="text-sm text-gray-500 text-center py-6">
                    Este archivo no está compartido
                </p>
            </template>

            <template x-for="userItem in shares" :key="userItem.name">
                <div class="flex items-center justify-between bg-gray-100 dark:bg-gray-800 rounded-lg px-4 py-3">

                    <!-- Usuario -->
                    <div class="flex flex-col">
                        <span class="font-medium text-gray-900 dark:text-gray-100 mb-1"
                            x-text="userItem.name"></span>

                        <div class="grid grid-cols-[auto_1fr] gap-x-3 gap-y-2 items-center">
                            <span class="text-xs text-gray-500 text-right">Permiso</span>
                            <div class="flex">
                                <x-filament::badge
                                    x-show="userItem.permission === 'view'"
                                    color="info">
                                    Solo ver
                                </x-filament::badge>
                                <x-filament::badge
                                    x-show="userItem.permission === 'edit'"
                                    color="warning">
                                    Editar
                                </x-filament::badge>
                            </div>

                            <span class="text-xs text-gray-500 text-right">Toma de conocimiento</span>
                            <div class="flex">
                                <x-filament::badge
                                    x-show="userItem.ack_required && !userItem.ack_completed"
                                    color="danger">
                                    Pendiente
                                </x-filament::badge>
                                <x-filament::badge
                                    x-show="userItem.ack_required && userItem.ack_completed"
                                    color="success">
                                    Completada
                                </x-filament::badge>
                                <x-filament::badge
                                    x-show="!userItem.ack_required"
                                    color="gray">
                                    No requerida
                                </x-filament::badge>
                            </div>

                            <span class="text-xs text-gray-500 text-right" x-show="userItem.ack_required && userItem.ack_completed && userItem.ack_completed_at">
                                Completada el
                            </span>
                            <div class="flex" x-show="userItem.ack_required && userItem.ack_completed && userItem.ack_completed_at">
                                <x-filament::badge color="success">
                                    <span x-text="userItem.ack_completed_at"></span>
                                </x-filament::badge>
                            </div>
                        </div>
                    </div>

                    <!-- Permiso + acciones -->
                    <div class="flex items-center gap-3">
                        <!-- Eliminar permiso -->
                        <button
                            x-on:click="$wire.removeShare(userItem.id)"
                            class="text-danger-600 hover:text-danger-700 transition"
                            title="Eliminar acceso">
                            <x-heroicon-o-trash class="w-5 h-5" />
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </x-filament::modal>



    {{-- Abrir archivos Onlyoffice en una pestaña nueva --}}
    <script>
        window.addEventListener('open-onlyoffice', event => {
            const url = event.detail.url;
            window.open(url, '_blank');
        });
    </script>
</x-filament-panels::page>
