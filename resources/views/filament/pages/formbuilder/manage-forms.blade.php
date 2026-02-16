<x-filament::page>
    <div class="space-y-6">
        <div>
            {{ $this->form }}
            <x-filament::button wire:click="save" class="mt-4">{{ __('formbuilder.save_form') }}</x-filament::button>
        </div>
        <x-filament::section>
            <x-slot name="heading">
                {{ __('formbuilder.saved_forms') }}
            </x-slot>
            <div class="space-y-2">
                @if(count($forms) === 0)
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('formbuilder.no_forms') }}</div>
                @endif

                @foreach($forms as $form)
                    <div wire:key="form-{{ $form['id'] }}" class="flex items-center justify-between rounded-lg p-3 border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
                        <div>
                            <div class="font-medium flex items-center gap-2">
                                {{ $form['name'] }}
                                @if(($form['submission_count'] ?? 0) > 0)
                                    <x-filament::badge color="info" size="xs">
                                        {{ $form['submission_count'] }}
                                    </x-filament::badge>
                                @endif
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">ID: {{ $form['id'] }}</div>
                        </div>
                        <div class="flex gap-2">
                            <x-filament::button color="gray" icon="heroicon-o-pencil" wire:click="editForm('{{ $form['id'] }}')">{{ __('formbuilder.edit') }}</x-filament::button>
                            <x-filament::button tag="a" href="{{ \App\Filament\Pages\FormBuilder\FormSubmissions::getUrl(['formId' => $form['id']]) }}" color="gray" icon="heroicon-o-table-cells">{{ __('formbuilder.submissions') }}</x-filament::button>
                            <x-filament::button tag="a" href="{{ route('forms.show', $form['id']) }}" target="_blank" color="gray" icon="heroicon-o-eye">{{ __('formbuilder.view') }}</x-filament::button>
                            <x-filament::button color="gray" icon="heroicon-o-code-bracket" wire:click="openEmbed('{{ $form['id'] }}')">{{ __('formbuilder.embed') }}</x-filament::button>
                            {{ ($this->deleteFormAction)(['id' => $form['id']]) }}
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    </div>
    <x-filament-actions::modals />
</x-filament::page>
