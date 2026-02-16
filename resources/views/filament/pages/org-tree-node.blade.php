@props(['node'])

<div class="flex flex-col items-center">
    
    <!-- Node Card -->
    <div 
        class="z-10 bg-white dark:bg-gray-800 p-2 rounded-lg shadow border-l-4 border-blue-500 dark:border-blue-400 border-y border-r border-gray-200 dark:border-gray-700 w-48 text-center relative group hover:shadow-lg transition-all"
        x-show="matchesSearch('{{ $node['name'] }}', '{{ $node['cargo'] }}')"
    >
         <div class="flex flex-col items-center gap-1">
             <img src="{{ $node['avatar'] }}" class="w-10 h-10 rounded-full object-cover border border-gray-100 dark:border-gray-600 bg-gray-100" alt="{{ $node['name'] }}">
             <div class="w-full overflow-hidden">
                 <p class="font-bold text-xs text-gray-900 dark:text-gray-100 leading-tight truncate" title="{{ $node['name'] }}">{{ $node['name'] }}</p>
                 <p class="text-[10px] text-blue-600 dark:text-blue-400 leading-tight truncate" title="{{ $node['cargo'] }}">{{ $node['cargo'] }}</p>
             </div>
         </div>
    </div>

    <!-- Children -->
    @if(count($node['children']) > 0)
        <!-- Line Down from Parent Node to Bus -->
        <div class="h-6 w-px bg-gray-300 dark:bg-gray-600"></div>
        
        <!-- Children Container -->
        <div class="flex justify-center items-start">
            @foreach($node['children'] as $child)
                <div class="flex flex-col items-center px-2"> <!-- Wrapper with padding for spacing -->
                    
                    <!-- Lines Area (Bus + Connector to Child) -->
                    <div class="w-full h-6 relative">
                        <!-- Vertical Line (Bus to Child) -->
                        <div class="absolute left-1/2 -translate-x-1/2 top-0 h-full w-px bg-gray-300 dark:bg-gray-600"></div>

                        <!-- Horizontal Bus Segments -->
                        @if($loop->count > 1)
                            @if($loop->first)
                                <!-- First: Center to Right -->
                                <div class="absolute right-0 top-0 w-1/2 h-px bg-gray-300 dark:bg-gray-600 border-t-0"></div>
                                <!-- Optional: Rounded corner logic requires more complex CSS, simplified for now -->
                            @elseif($loop->last)
                                <!-- Last: Left to Center -->
                                <div class="absolute left-0 top-0 w-1/2 h-px bg-gray-300 dark:bg-gray-600"></div>
                            @else
                                <!-- Middle: Full Width -->
                                <div class="absolute left-0 top-0 w-full h-px bg-gray-300 dark:bg-gray-600"></div>
                            @endif
                        @endif
                    </div>

                    <!-- Recursive Call -->
                    @include('filament.pages.org-tree-node', ['node' => $child])
                    
                </div>
            @endforeach
        </div>
    @endif
</div>
