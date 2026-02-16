<x-filament-panels::page 
    x-data="{
        activeTab: 'resumen',

        translations: {
            resumen: {
                title: '{{ __('dashboard.resumen.title') }}',
                description: '{{ __('dashboard.resumen.description') }}'
            },
            gastos: {
                title: '{{ __('dashboard.gastos.title') }}',
                description: '{{ __('dashboard.gastos.description') }}'
            },
            ingresos: {
                title: '{{ __('dashboard.ingresos.title') }}',
                description: '{{ __('dashboard.ingresos.description') }}'
            },
            tipos: {
                title: '{{ __('dashboard.tipos.title') }}',
                description: '{{ __('dashboard.tipos.description') }}'
            },
            categorias: {
                title: '{{ __('dashboard.categorias.title') }}',
                description: '{{ __('dashboard.categorias.description') }}'
            },
        },

        get titleTab() {
            return this.translations[this.activeTab]?.title ?? '';
        },

        get descriptionTab() {
            return this.translations[this.activeTab]?.description ?? '';
        },

        updateTabFromHash() {
            switch (window.location.hash) {
                case '#expenses':
                    this.activeTab = 'gastos';
                    break;
                case '#incomes':
                    this.activeTab = 'ingresos';
                    break;
                case '#incomesTypes':
                    this.activeTab = 'tipos';
                    break;
                case '#expensesCategories':
                    this.activeTab = 'categorias';
                    break;
                default:
                    this.activeTab = 'resumen';
            }
        },

        clearHash() {
            history.replaceState(null, '', window.location.pathname);
        }
    }"

    x-init="
        updateTabFromHash();
        window.addEventListener('hashchange', () => updateTabFromHash());
    "
>
<x-filament::section>
    <x-slot name="heading">
        <span x-text="titleTab"></span>
    </x-slot>

    <x-slot name="description">
        <span x-text="descriptionTab"></span>
    </x-slot>

    <x-filament::tabs>
        <x-filament::tabs.item
            icon="heroicon-m-arrow-trending-up"
            alpine-active="activeTab === 'resumen'"
            x-on:click="activeTab = 'resumen'; $dispatch('refresh-resumen'); clearHash()"
        >
            {{ __('resumen') }}
        </x-filament::tabs.item>

        <x-filament::tabs.item
            icon="heroicon-m-hand-thumb-down"
            alpine-active="activeTab === 'gastos'"
            x-on:click="activeTab = 'gastos'; clearHash()"
        >
            {{ __('expense') }}
        </x-filament::tabs.item>

        <x-filament::tabs.item
            icon="heroicon-m-banknotes"
            alpine-active="activeTab === 'ingresos'"
            x-on:click="activeTab = 'ingresos'; clearHash()"
        >
            {{ __('income') }}
        </x-filament::tabs.item>
    </x-filament::tabs>

    <!-- contenido -->
    <div x-show="activeTab === 'resumen'">
        @livewire(\App\Filament\Pages\ResumenDashboard::class)
    </div>

    <div x-show="activeTab === 'gastos'">
        @livewire(\App\Filament\Resources\Expenses\Pages\ListExpenses::class)
    </div>

    <div x-show="activeTab === 'ingresos'">
        @livewire(\App\Filament\Resources\Incomes\Pages\ListIncomes::class)        
    </div>

    <div x-show="activeTab === 'tipos'">
        @livewire(\App\Filament\Resources\IncomeTypes\Pages\ListIncomeTypes::class)
    </div>

    <div x-show="activeTab === 'categorias'">
        @livewire(\App\Filament\Resources\ExpenseCategories\Pages\ListExpenseCategories::class)
    </div>

</x-filament::section>
</x-filament-panels::page>
