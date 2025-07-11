<div class="block p-6 bg-white dark:bg-zinc-700 border border-gray-200 dark:border-zinc-600 rounded-lg shadow relative">
    <div class="flex items-center mb-4">
        <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
        </svg>
        <h2 class="ml-3 text-xl font-semibold text-gray-900 dark:text-white">General Search</h2>
    </div>
    
    <div class="space-y-4">
        <div class="relative">
            <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Search Participants & Households
            </label>
            <div class="relative">
                <input type="text" 
                       id="search"
                       wire:model.live.debounce.300ms="search" 
                       class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white pr-10 focus:border-indigo-500 focus:ring-indigo-500"
                       placeholder="Enter name, number, or address...">
                @if($search)
                <button wire:click="clearSearch" 
                        class="absolute inset-y-0 right-0 pr-3 flex items-center">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
                @endif
            </div>
            
            @if($showResults && $search)
            <div class="absolute z-50 mt-1 w-full bg-white dark:bg-zinc-800 rounded-md shadow-lg border border-gray-200 dark:border-zinc-600 max-h-96 overflow-y-auto">
                <div class="p-4 space-y-4">
                    @if($participants->count() > 0)
                    <div>
                        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Participants</h3>
                        <div class="space-y-2">
                            @foreach($participants as $participant)
                            <a href="{{ route('participant.show', $participant->id) }}" 
                               wire:navigate
                               class="block p-3 bg-gray-50 dark:bg-zinc-700 rounded hover:bg-gray-100 dark:hover:bg-zinc-600 transition-colors">
                                <div class="font-medium text-gray-900 dark:text-white">
                                    {{ $participant->first_name }} {{ $participant->last_name }}
                                </div>
                                @if($participant->household)
                                <div class="text-sm text-gray-600 dark:text-gray-300">
                                    Household: {{ $participant->household->household_code }}
                                </div>
                                @endif
                            </a>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if($households->count() > 0)
                    <div>
                        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Households</h3>
                        <div class="space-y-2">
                            @foreach($households as $household)
                            <a href="{{ route('household.show', $household->id) }}" 
                               wire:navigate
                               class="block p-3 bg-gray-50 dark:bg-zinc-700 rounded hover:bg-gray-100 dark:hover:bg-zinc-600 transition-colors">
                                <div class="font-medium text-gray-900 dark:text-white">
                                    Household {{ $household->household_code }}
                                    <span class="text-sm text-gray-500 dark:text-gray-400">({{ $household->participants_count }} members)</span>
                                </div>
                                @if($household->street_address)
                                <div class="text-sm text-gray-600 dark:text-gray-300">
                                    {{ $household->street_address }}, {{ $household->city }}, {{ $household->state }} {{ $household->zip_code }}
                                </div>
                                @endif
                            </a>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if($participants->count() == 0 && $households->count() == 0)
                    <p class="text-gray-500 dark:text-gray-400 text-center py-4">
                        No results found for "{{ $search }}"
                    </p>
                    @endif
                </div>
            </div>
            @endif
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Search Type
            </label>
            <select wire:model.live="searchType" 
                    class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                <option value="all">All</option>
                @if($this->can('participants.view'))
                <option value="participants">Participants Only</option>
                @endif
                @if($this->can('households.view'))
                <option value="households">Households Only</option>
                @endif
            </select>
        </div>
    </div>
</div>