<div class="space-y-4">
    <x-filament::section heading="{{ __('surveys.catalog.manage_heading') }}">
    <div class="grid grid-cols-2 gap-3 items-end">
        <div class="col-span-2">
            <x-filament::input.wrapper>
                <x-filament::input
                    type="text"
                    wire:model="survey_name"
                    placeholder="{{ __('surveys.catalog.fields.survey_name') }}"
                />
            </x-filament::input.wrapper>
        </div>
        <div class="col-span-2">
            <x-filament::input.wrapper>
                <x-filament::input
                    type="text"
                    wire:model="item"
                    placeholder="{{ __('surveys.catalog.fields.dimension') }}"
                />
            </x-filament::input.wrapper>
        </div>
        <div class="col-span-1">
            <x-filament::input.wrapper>
                <x-filament::input
                    type="number"
                    step="0.01"
                    wire:model="kpi_target"
                    placeholder="{{ __('surveys.catalog.fields.target') }}"
                />
            </x-filament::input.wrapper>
        </div>
        <div class="col-span-1">
            <x-filament::input.wrapper>
                <x-filament::input
                    type="number"
                    step="0.01"
                    wire:model="weight"
                    placeholder="{{ __('surveys.catalog.fields.weight') }}"
                />
            </x-filament::input.wrapper>
        </div>
        <div class="col-span-4 flex gap-2">
            @if($editId)
                <x-filament::button type="button" wire:click="saveEdit">{{ __('surveys.catalog.buttons.save') }}</x-filament::button>
                <x-filament::button type="button" color="gray" wire:click="cancelEdit">{{ __('surveys.catalog.buttons.cancel') }}</x-filament::button>
            @else
                <x-filament::button type="button" wire:click="create">{{ __('surveys.catalog.buttons.create') }}</x-filament::button>
            @endif
        </div>
    </div>
    </x-filament::section>
    <x-filament::section heading="{{ __('surveys.catalog.modal_heading') }}">
    <div class="w-full overflow-x-hidden">
    <table class="w-full text-sm table-fixed">
        <colgroup>
            <col style="width:30%">
            <col style="width:30%">
            <col style="width:20%">
            <col style="width:20%">
        </colgroup>
        <thead>
            <tr class="text-left text-black dark:text-gray-100 border-b border-[#E9E9EA] dark:border-gray-700">
                <th class="py-2 px-2">{{ __('surveys.catalog.table.survey') }}</th>
                <th class="py-2 px-2">{{ __('surveys.catalog.table.dimension') }}</th>
                <th class="py-2 px-2">{{ __('surveys.catalog.table.target') }}</th>
                <th class="py-2 px-2">{{ __('surveys.catalog.table.weight') }}</th>
                <th class="py-2 px-2 text-center">{{ __('surveys.catalog.table.actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dimensions as $d)
                <tr class="border-t border-[#E9E9EA] dark:border-gray-700">
                    <td class="py-2 px-2">{{ $d->survey_name }}</td>
                    <td class="py-2 px-2">{{ $d->item }}</td>
                    <td class="py-2 px-2">{{ number_format($d->kpi_target, 2) }}</td>
                    <td class="py-2 px-2">{{ $d->weight !== null ? number_format($d->weight, 2) : '-' }}</td>
                    <td class="py-2 px-2">
                        <div class="flex items-center justify-center gap-2">
                            <x-filament::icon-button icon="heroicon-m-pencil-square" color="primary" label="{{ __('surveys.catalog.buttons.edit') }}" tooltip="{{ __('surveys.catalog.buttons.edit') }}" type="button" wire:click="startEdit({{ $d->id }})" />
                            <x-filament::icon-button icon="heroicon-m-trash" color="danger" label="{{ __('surveys.catalog.buttons.delete') }}" tooltip="{{ __('surveys.catalog.buttons.delete') }}" type="button" wire:click="delete({{ $d->id }})" />
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    </div>
    <div class="flex items-center justify-end mt-6 gap-3 border-t border-[#E9E9EA] dark:border-gray-700 pt-4">
        <x-filament::icon-button icon="heroicon-m-chevron-left" color="gray" label="{{ __('surveys.catalog.pagination.previous') }}" tooltip="{{ __('surveys.catalog.pagination.previous') }}" type="button" wire:click="prevPage" :disabled="$page <= 1" />
        <span class="text-xs text-gray-400 px-2">{{ __('surveys.catalog.pagination.info', ['page' => $page, 'max' => $maxPage]) }}</span>
        <x-filament::icon-button icon="heroicon-m-chevron-right" color="gray" label="{{ __('surveys.catalog.pagination.next') }}" tooltip="{{ __('surveys.catalog.pagination.next') }}" type="button" wire:click="nextPage" :disabled="$page >= $maxPage" />
    </div>
    </x-filament::section>
</div>
