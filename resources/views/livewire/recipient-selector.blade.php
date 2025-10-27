<div class="relative">
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
        Notification Recipients
    </label>

    <!-- Search Input -->
    <div class="relative">
        <input
            type="text"
            wire:model.live.debounce.300ms="searchTerm"
            placeholder="Search by name or email..."
            class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 pl-10"
            @click.away="showDropdown = false">

        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>

        <!-- Search Results Dropdown -->
        @if($showDropdown && !empty($searchResults))
            <div class="absolute z-10 mt-1 w-full bg-white dark:bg-zinc-800 shadow-lg rounded-md border border-gray-200 dark:border-zinc-600 max-h-60 overflow-auto">
                @foreach($searchResults as $index => $result)
                    <button
                        type="button"
                        wire:click="selectRecipient({{ $index }})"
                        class="w-full text-left px-4 py-3 hover:bg-gray-50 dark:hover:bg-zinc-700 border-b border-gray-100 dark:border-zinc-700 last:border-b-0">
                        <div class="flex items-center">
                            <!-- Icon based on type -->
                            @if($result['type'] === 'user')
                                <div class="flex-shrink-0 h-8 w-8 bg-indigo-100 dark:bg-indigo-900 rounded-full flex items-center justify-center">
                                    <svg class="h-5 w-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                            @else
                                <div class="flex-shrink-0 h-8 w-8 bg-purple-100 dark:bg-purple-900 rounded-full flex items-center justify-center">
                                    <svg class="h-5 w-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                </div>
                            @endif

                            <div class="ml-3">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $result['name'] }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $result['email'] }}</div>
                            </div>
                        </div>
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Selected Recipients (Chips) -->
    @if(!empty($selectedRecipientData))
        <div class="mt-3 flex flex-wrap gap-2">
            @foreach($selectedRecipientData as $recipient)
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm {{ $recipient['type'] === 'user' ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200' : 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' }}">
                    <!-- Icon -->
                    @if($recipient['type'] === 'user')
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    @else
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    @endif

                    <span class="mr-1.5">{{ $recipient['name'] }}</span>
                    <span class="text-xs opacity-75">({{ $recipient['email'] }})</span>

                    <!-- Remove button -->
                    <button
                        type="button"
                        wire:click="removeRecipient({{ $recipient['id'] }})"
                        class="ml-2 inline-flex items-center p-0.5 rounded-full hover:bg-indigo-200 dark:hover:bg-indigo-800 focus:outline-none">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </span>
            @endforeach
        </div>
    @endif

    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
        Search for staff members or distribution lists to receive notifications for this report due date
    </p>
</div>
