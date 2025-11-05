<div class="relative" x-data="{ open: @entangle('isOpen') }" @click.away="open = false">
    <!-- Selected Item Display / Trigger Button -->
    <button
        type="button"
        @click="open = !open"
        class="w-full px-3 py-2 text-left border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500
               {{ $selectedItem ? 'border-gray-300 dark:border-zinc-600' : 'border-gray-300 dark:border-zinc-600' }}
               {{ $required && !$selected ? 'border-red-300' : '' }}
               bg-white dark:bg-zinc-700 text-gray-900 dark:text-white
               hover:border-gray-400 dark:hover:border-zinc-500">

        <div class="flex items-center justify-between">
            <div class="flex-1 min-w-0">
                @if($selectedItem)
                    <div class="truncate font-medium">{{ $selectedItem[$displayField] ?? 'Unknown' }}</div>
                    @if(!empty($selectedItem[$secondaryField]))
                        <div class="truncate text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            {{ $selectedItem[$secondaryField] }}
                        </div>
                    @endif
                @else
                    <span class="text-gray-400 dark:text-gray-500">{{ $placeholder }}</span>
                @endif
            </div>

            <div class="flex items-center ml-2 space-x-1">
                @if($selectedItem && !$required)
                    <button
                        type="button"
                        wire:click.stop="clearSelection"
                        class="p-1 hover:bg-gray-100 dark:hover:bg-zinc-600 rounded">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                @endif
                <svg class="w-5 h-5 text-gray-400" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </div>
        </div>
    </button>

    <!-- Dropdown Menu -->
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute z-50 w-full mt-1 bg-white dark:bg-zinc-700 border border-gray-300 dark:border-zinc-600 rounded-md shadow-lg"
        style="display: none;">

        <!-- Search Input -->
        <div class="p-2 border-b border-gray-200 dark:border-zinc-600">
            <input
                type="text"
                wire:model.live="search"
                placeholder="Type to search..."
                class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md
                       bg-white dark:bg-zinc-800 text-gray-900 dark:text-white
                       focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm"
                @click.stop>
        </div>

        <!-- Items List -->
        <div class="max-h-60 overflow-y-auto">
            @if(count($filteredItems) > 0)
                @foreach($filteredItems as $item)
                    <button
                        type="button"
                        wire:click="selectItem({{ $item['id'] }})"
                        class="w-full px-3 py-2 text-left hover:bg-indigo-50 dark:hover:bg-indigo-900/20
                               {{ $selected == $item['id'] ? 'bg-indigo-100 dark:bg-indigo-900/30' : '' }}
                               focus:outline-none focus:bg-indigo-50 dark:focus:bg-indigo-900/20
                               border-b border-gray-100 dark:border-zinc-600 last:border-b-0">
                        <div class="truncate font-medium text-gray-900 dark:text-white text-sm">
                            {{ $item[$displayField] ?? 'Unknown' }}
                        </div>
                        @if(!empty($item[$secondaryField]))
                            <div class="truncate text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                {{ $item[$secondaryField] }}
                            </div>
                        @endif
                    </button>
                @endforeach
            @else
                <div class="px-3 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                    {{ $emptyMessage }}
                </div>
            @endif
        </div>
    </div>
</div>
