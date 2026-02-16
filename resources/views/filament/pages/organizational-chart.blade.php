<x-filament-panels::page>
    <div 
        x-data="{ 
            zoom: 1,
            search: '',
            panning: false,
            startX: 0,
            startY: 0,
            scrollLeft: 0,
            scrollTop: 0,
            matchesSearch(name, cargo) {
                if (this.search === '') return true;
                const searchLower = this.search.toLowerCase();
                return name.toLowerCase().includes(searchLower) || 
                       cargo.toLowerCase().includes(searchLower);
            },
            exportToImage() {
                window.print();
            },
            init() {
                this.$nextTick(() => {
                    this.autoZoom();
                    // Centrar horizontalmente inicialmente
                    const container = this.$refs.container;
                    const content = this.$refs.content;
                    if (container && content) {
                        container.scrollLeft = (content.scrollWidth - container.clientWidth) / 2;
                    }
                });
            },
            autoZoom() {
                const container = this.$refs.container;
                const content = this.$refs.content;
                if (container && content) {
                    const containerW = container.clientWidth;
                    const containerH = container.clientHeight;
                    const contentW = content.scrollWidth;
                    const contentH = content.scrollHeight;
                    
                    if (contentW > 0 && contentH > 0) {
                        const scaleW = (containerW - 80) / contentW;
                        const scaleH = (containerH - 80) / contentH;
                        this.zoom = Math.min(1, Math.min(scaleW, scaleH));
                        
                        // Recalcular centro tras zoom (opcional, pero buena práctica)
                        // this.$nextTick(() => {
                        //    container.scrollLeft = (content.scrollWidth - container.clientWidth) / 2;
                        // });
                    }
                }
            },
            startPan(e) {
                this.panning = true;
                this.startX = e.pageX - this.$refs.container.offsetLeft;
                this.startY = e.pageY - this.$refs.container.offsetTop;
                this.scrollLeft = this.$refs.container.scrollLeft;
                this.scrollTop = this.$refs.container.scrollTop;
                this.$refs.container.style.cursor = 'grabbing';
            },
            endPan() {
                this.panning = false;
                this.$refs.container.style.cursor = 'grab';
            },
            pan(e) {
                if (!this.panning) return;
                e.preventDefault();
                const x = e.pageX - this.$refs.container.offsetLeft;
                const y = e.pageY - this.$refs.container.offsetTop;
                const walkX = (x - this.startX) * 1; // Ajustar velocidad
                const walkY = (y - this.startY) * 1;
                this.$refs.container.scrollLeft = this.scrollLeft - walkX;
                this.$refs.container.scrollTop = this.scrollTop - walkY;
            },
            handleWheel(e) {
                // Zoom with Ctrl + Wheel or just Wheel if preferred.
                // Using just Wheel for better accessibility/precision as requested.
                const delta = e.deltaY > 0 ? -0.05 : 0.05;
                const newZoom = Math.max(0.1, Math.min(2, this.zoom + delta));
                this.zoom = parseFloat(newZoom.toFixed(2));
            }
        }"
        x-init="init()"
        class="flex flex-col h-full"
    >
        <!-- Controls -->
        <div class="mb-6 flex flex-wrap gap-4 items-center justify-between bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-4 flex-1">
                <div class="relative w-full max-w-md">
                    <x-filament::input.wrapper>
                        <x-filament::input
                            type="search"
                            wire:model.live="search"
                            x-model="search"
                            placeholder="{{ __('organizational_chart.search_placeholder') }}"
                        />
                    </x-filament::input.wrapper>
                </div>
            </div>
            
            <div class="flex items-center gap-2">
                <x-filament::button
                    color="gray"
                    icon="heroicon-m-minus"
                    @click="zoom = Math.max(0.1, parseFloat((zoom - 0.05).toFixed(2)))"
                    size="sm"
                />
                <span class="text-sm font-medium w-12 text-center" x-text="Math.round(zoom * 100) + '%'"></span>
                <x-filament::button
                    color="gray"
                    icon="heroicon-m-plus"
                    @click="zoom = Math.min(2, parseFloat((zoom + 0.05).toFixed(2)))"
                    size="sm"
                />
                <x-filament::button
                    color="gray"
                    icon="heroicon-m-arrows-pointing-in"
                    @click="autoZoom()"
                    size="sm"
                    tooltip="{{ __('organizational_chart.fit_to_screen') }}"
                />
                <x-filament::button
                    color="primary"
                    icon="heroicon-m-arrow-down-tray"
                    @click="exportToImage()"
                    class="ml-2"
                >
                    {{ __('organizational_chart.export') }}
                </x-filament::button>
            </div>
        </div>

        <!-- Chart Container -->
        <div 
            class="flex-1 overflow-auto bg-gray-50 dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-8 relative cursor-grab active:cursor-grabbing" 
            id="org-chart-container"
            x-ref="container"
            @wheel.prevent="handleWheel($event)"
            @resize.window="autoZoom()"
            @mousedown="startPan($event)"
            @mouseleave="endPan()"
            @mouseup="endPan()"
            @mousemove="pan($event)"
        >
            <div 
                class="min-w-max mx-auto transition-transform origin-top-center"
                :style="`transform: scale(${zoom})`"
                x-ref="content"
            >
                <!-- Tree Structure -->
                <div class="flex flex-col items-center">
                    
                    <!-- CEO Level -->
                    @if($ceo)
                        <div class="flex flex-col items-center relative z-10">
                            <div class="w-64 p-4 bg-white dark:bg-gray-800 rounded-lg shadow-lg border-l-4 border-primary-600 dark:border-primary-500 hover:shadow-xl transition-all relative group">
                                <div class="flex items-center gap-4">
                                    <img src="{{ $ceo->getFilamentAvatarUrl() ?? 'https://ui-avatars.com/api/?name=' . urlencode($ceo->name) . '&color=7F9CF5&background=EBF4FF' }}" alt="{{ $ceo->name }}" class="w-12 h-12 rounded-full object-cover border-2 border-primary-100 dark:border-primary-900">
                                    <div>
                                        <h3 class="font-bold text-gray-900 dark:text-white text-lg leading-tight">{{ $ceo->name }}</h3>
                                        <p class="text-primary-600 dark:text-primary-400 text-sm font-medium">{{ $ceo->employeeProfile?->cargo?->name ?? __('organizational_chart.ceo_role_fallback') }}</p>
                                    </div>
                                </div>
                                <!-- Tooltip -->
                                <div class="absolute invisible group-hover:visible opacity-0 group-hover:opacity-100 transition-opacity bg-gray-900 text-white text-xs rounded py-1 px-2 -top-10 left-1/2 transform -translate-x-1/2 w-max max-w-xs z-50">
                                    {{ __('organizational_chart.ceo_tooltip') }}
                                    <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-900"></div>
                                </div>
                            </div>
                            <!-- Connector to Departments -->
                            <div class="h-8 w-[3px] bg-gray-400 dark:bg-gray-500"></div>
                        </div>
                    @endif

                    <!-- Departments Level -->
                    <div class="flex justify-center items-start pt-0 gap-6">
                        @foreach($departmentsTree as $dept)
                            <div class="flex flex-col items-center relative" x-show="matchesSearch('{{ $dept['name'] }}', '') || '{{ $search }}' === ''">
                                
                                <!-- Department Connector Area -->
                                <div class="w-[calc(100%+1.5rem)] h-8 relative left-0">
                                    @if($departmentsTree->count() > 1)
                                        @if($loop->first)
                                            <!-- First Item: Right Half + Vertical Down (Top-Left Rounded Corner) -->
                                            <div class="absolute right-0 top-0 w-[calc(50%+1.5px)] h-full border-t-[3px] border-l-[3px] border-gray-400 dark:border-gray-500 rounded-tl-xl"></div>
                                        @elseif($loop->last)
                                            <!-- Last Item: Left Half + Vertical Down (Top-Right Rounded Corner) -->
                                            <div class="absolute left-0 top-0 w-[calc(50%+1.5px)] h-full border-t-[3px] border-r-[3px] border-gray-400 dark:border-gray-500 rounded-tr-xl"></div>
                                        @else
                                            <!-- Middle Items: Full Top Line + Vertical Down -->
                                            <div class="absolute top-0 left-0 w-full h-[3px] bg-gray-400 dark:bg-gray-500"></div>
                                            <div class="absolute left-1/2 -translate-x-1/2 top-0 h-full w-[3px] bg-gray-400 dark:bg-gray-500"></div>
                                        @endif
                                    @else
                                        <!-- Single Item: Just Vertical Line -->
                                        <div class="absolute left-1/2 -translate-x-1/2 top-0 h-full w-[3px] bg-gray-400 dark:bg-gray-500"></div>
                                    @endif
                                </div>
                                
                                <!-- Department Node -->
                                <div class="mb-8 p-4 bg-gray-100 dark:bg-gray-800/50 rounded-xl border-2 border-gray-200 dark:border-gray-700 w-auto min-w-[300px]">
                                    <h4 class="text-center font-bold text-gray-700 dark:text-gray-200 text-lg mb-4 uppercase tracking-wide border-b border-gray-200 dark:border-gray-700 pb-2">
                                        {{ $dept['name'] }}
                                    </h4>

                                    <!-- Department Tree -->
                                    <div class="flex justify-center w-full overflow-x-auto pt-4 pb-2">
                                        @if($dept['tree']->count() > 0)
                                            <div class="flex justify-center gap-8 items-start">
                                                @foreach($dept['tree'] as $rootNode)
                                                    @include('filament.pages.org-tree-node', ['node' => $rootNode])
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="text-center py-4 text-gray-400 dark:text-gray-500 text-sm italic">
                                                {{ __('organizational_chart.no_leaders') }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                </div>
            </div>
        </div>
    </div>
    
    <style>
        @media print {
            body * {
                visibility: hidden;
            }
            #org-chart-container, #org-chart-container * {
                visibility: visible;
            }
            #org-chart-container {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                margin: 0;
                padding: 0;
                background: white !important;
                color: black !important;
            }
        }
    </style>
</x-filament-panels::page>
