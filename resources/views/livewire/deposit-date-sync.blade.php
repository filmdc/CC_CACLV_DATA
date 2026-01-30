<div class="w-full px-0 py-6">
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl">
        <!-- Header -->
        <div class="bg-indigo-600 text-white p-6 rounded-t-lg">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Deposit Date Sync</h1>
                    <p class="text-indigo-100 mt-2">Sync deposit dates from Sage Intacct back to DonorPerfect</p>
                </div>
                <a href="{{ route('sage-intacct.configuration') }}"
                   class="px-4 py-2 bg-white text-indigo-600 rounded-md hover:bg-indigo-50 font-medium">
                    &larr; Back to Sage Configuration
                </a>
            </div>
        </div>

        <!-- Flash Messages -->
        @if (session()->has('message'))
            <div class="mx-6 mt-4 rounded-md bg-green-50 dark:bg-green-900/30 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('message') }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="mx-6 mt-4 rounded-md bg-red-50 dark:bg-red-900/30 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-red-800 dark:text-red-200">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
        @endif

        <div class="p-6 space-y-6">
            <!-- Configuration Status -->
            @if(!$isConfigured)
                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                    <div class="flex">
                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">Configuration Required</h3>
                            <p class="mt-1 text-sm text-yellow-700 dark:text-yellow-300">
                                Please ensure both Sage Intacct and DonorPerfect are properly configured before running the sync.
                            </p>
                            <ul class="mt-2 text-sm text-yellow-700 dark:text-yellow-300 list-disc list-inside">
                                @foreach($configurationStatus as $component => $status)
                                    <li class="{{ $status ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                        {{ str_replace('_', ' ', ucfirst($component)) }}: {{ $status ? 'Ready' : 'Not Ready' }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <div class="text-sm font-medium text-blue-600 dark:text-blue-400">Total Synced to Sage</div>
                    <div class="text-2xl font-bold text-blue-900 dark:text-blue-100">{{ $statistics['total_synced_to_sage'] ?? 0 }}</div>
                </div>

                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                    <div class="text-sm font-medium text-yellow-600 dark:text-yellow-400">Pending Check</div>
                    <div class="text-2xl font-bold text-yellow-900 dark:text-yellow-100">{{ $statistics['pending_deposit_check'] ?? 0 }}</div>
                </div>

                <div class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg p-4">
                    <div class="text-sm font-medium text-purple-600 dark:text-purple-400">Has Deposit Date</div>
                    <div class="text-2xl font-bold text-purple-900 dark:text-purple-100">{{ $statistics['has_deposit_date'] ?? 0 }}</div>
                </div>

                <div class="bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-lg p-4">
                    <div class="text-sm font-medium text-orange-600 dark:text-orange-400">Pending DP Update</div>
                    <div class="text-2xl font-bold text-orange-900 dark:text-orange-100">{{ $statistics['pending_dp_update'] ?? 0 }}</div>
                </div>

                <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                    <div class="text-sm font-medium text-green-600 dark:text-green-400">Synced to DP</div>
                    <div class="text-2xl font-bold text-green-900 dark:text-green-100">{{ $statistics['synced_to_dp'] ?? 0 }}</div>
                </div>

                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                    <div class="text-sm font-medium text-red-600 dark:text-red-400">With Errors</div>
                    <div class="text-2xl font-bold text-red-900 dark:text-red-100">{{ $statistics['with_errors'] ?? 0 }}</div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-wrap gap-4">
                <button
                    wire:click="runSync"
                    wire:loading.attr="disabled"
                    wire:target="runSync"
                    @if(!$isConfigured || $syncInProgress) disabled @endif
                    class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed font-medium flex items-center"
                >
                    <span wire:loading.remove wire:target="runSync">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </span>
                    <span wire:loading wire:target="runSync">
                        <svg class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </span>
                    Run Sync
                </button>

                <button
                    wire:click="runDrySync"
                    wire:loading.attr="disabled"
                    wire:target="runDrySync"
                    @if(!$isConfigured || $syncInProgress) disabled @endif
                    class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed font-medium flex items-center"
                >
                    <span wire:loading.remove wire:target="runDrySync">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                    </span>
                    <span wire:loading wire:target="runDrySync">
                        <svg class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </span>
                    Preview (Dry Run)
                </button>

                @if(($statistics['with_errors'] ?? 0) > 0)
                    <button
                        wire:click="retryFailed"
                        wire:loading.attr="disabled"
                        wire:target="retryFailed"
                        @if(!$isConfigured || $syncInProgress) disabled @endif
                        class="px-4 py-2 bg-orange-600 text-white rounded-md hover:bg-orange-700 disabled:opacity-50 disabled:cursor-not-allowed font-medium flex items-center"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Retry Failed ({{ $statistics['with_errors'] ?? 0 }})
                    </button>
                @endif

                <button
                    wire:click="loadStatistics"
                    class="px-4 py-2 bg-white dark:bg-zinc-700 text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-zinc-600 rounded-md hover:bg-gray-50 dark:hover:bg-zinc-600 font-medium flex items-center"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Refresh Stats
                </button>
            </div>

            <!-- Last Sync Result -->
            @if($lastSyncResult)
                <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Last Sync Result</h3>
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Total Pending:</span>
                            <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ $lastSyncResult['total_pending'] ?? 0 }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Queried from Sage:</span>
                            <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ $lastSyncResult['sage_queried'] ?? 0 }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Deposited Found:</span>
                            <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ $lastSyncResult['deposited_found'] ?? 0 }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Updated in DP:</span>
                            <span class="ml-2 font-medium text-green-600 dark:text-green-400">{{ $lastSyncResult['dp_updated'] ?? 0 }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Failed:</span>
                            <span class="ml-2 font-medium text-red-600 dark:text-red-400">{{ $lastSyncResult['dp_failed'] ?? 0 }}</span>
                        </div>
                    </div>
                    @if($lastSyncResult['dry_run'] ?? false)
                        <div class="mt-2 text-sm text-yellow-600 dark:text-yellow-400">
                            This was a dry run - no changes were made to DonorPerfect.
                        </div>
                    @endif
                </div>
            @endif

            <!-- Tabs -->
            <div class="border-b border-gray-200 dark:border-zinc-700">
                <nav class="-mb-px flex space-x-8">
                    <button
                        wire:click="$set('activeTab', 'overview')"
                        class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'overview' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}"
                    >
                        Overview
                    </button>
                    <button
                        wire:click="$set('activeTab', 'pending')"
                        class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'pending' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}"
                    >
                        Pending Check ({{ $statistics['pending_deposit_check'] ?? 0 }})
                    </button>
                    <button
                        wire:click="$set('activeTab', 'ready')"
                        class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'ready' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}"
                    >
                        Ready for DP ({{ $statistics['pending_dp_update'] ?? 0 }})
                    </button>
                    <button
                        wire:click="$set('activeTab', 'errors')"
                        class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'errors' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}"
                    >
                        Errors ({{ $statistics['with_errors'] ?? 0 }})
                    </button>
                    <button
                        wire:click="$set('activeTab', 'completed')"
                        class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'completed' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}"
                    >
                        Recently Completed
                    </button>
                </nav>
            </div>

            <!-- Tab Content -->
            <div class="mt-4">
                @if($activeTab === 'overview')
                    <!-- Overview Tab -->
                    <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">How It Works</h3>
                        <div class="space-y-4 text-sm text-gray-600 dark:text-gray-300">
                            <div class="flex items-start">
                                <span class="flex-shrink-0 w-6 h-6 bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-400 rounded-full flex items-center justify-center text-xs font-bold mr-3">1</span>
                                <p><strong>Check Sage:</strong> Query Sage Intacct for Other Receipts that have been deposited and have a deposit date.</p>
                            </div>
                            <div class="flex items-start">
                                <span class="flex-shrink-0 w-6 h-6 bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-400 rounded-full flex items-center justify-center text-xs font-bold mr-3">2</span>
                                <p><strong>Store Date:</strong> Save the deposit date from Sage in our tracking system.</p>
                            </div>
                            <div class="flex items-start">
                                <span class="flex-shrink-0 w-6 h-6 bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-400 rounded-full flex items-center justify-center text-xs font-bold mr-3">3</span>
                                <p><strong>Update DonorPerfect:</strong> Write the deposit date to the <code class="bg-gray-200 dark:bg-zinc-600 px-1 rounded">DEPOSIT_DATE</code> custom field on the gift record in DonorPerfect.</p>
                            </div>
                        </div>
                        <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                            <p class="text-sm text-blue-800 dark:text-blue-200">
                                <strong>Tip:</strong> Use the "Preview (Dry Run)" button to see which items would be updated without actually making changes to DonorPerfect.
                            </p>
                        </div>
                    </div>
                @elseif($activeTab === 'pending')
                    <!-- Pending Check Tab -->
                    <div>
                        <div class="mb-4">
                            <input
                                type="text"
                                wire:model.live.debounce.300ms="searchGiftId"
                                placeholder="Search by Gift ID..."
                                class="w-full md:w-64 rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                            >
                        </div>
                        @if($pendingItems->count() > 0)
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                                    <thead class="bg-gray-50 dark:bg-zinc-700">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Gift ID</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Intacct Record</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Synced to Sage</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-700">
                                        @foreach($pendingItems as $item)
                                            <tr>
                                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $item->gift_id }}</td>
                                                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $item->intacct_record_id }}</td>
                                                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $item->synced_at?->format('M d, Y H:i') }}</td>
                                                <td class="px-4 py-3 text-sm">
                                                    <button
                                                        wire:click="checkSingleItem({{ $item->id }})"
                                                        class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                                    >
                                                        Check Status
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-4">
                                {{ $pendingItems->links() }}
                            </div>
                        @else
                            <p class="text-gray-500 dark:text-gray-400 text-center py-8">No items pending deposit date check.</p>
                        @endif
                    </div>
                @elseif($activeTab === 'ready')
                    <!-- Ready for DP Tab -->
                    @if($itemsWithDepositDates->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                                <thead class="bg-gray-50 dark:bg-zinc-700">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Gift ID</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Intacct Record</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Deposit Date</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-700">
                                    @foreach($itemsWithDepositDates as $item)
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $item->gift_id }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $item->intacct_record_id }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white font-medium">{{ $item->sage_deposit_date?->format('M d, Y') }}</td>
                                            <td class="px-4 py-3 text-sm">
                                                <button
                                                    wire:click="syncSingleItem({{ $item->id }})"
                                                    class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300"
                                                >
                                                    Sync to DP
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4">
                            {{ $itemsWithDepositDates->links() }}
                        </div>
                    @else
                        <p class="text-gray-500 dark:text-gray-400 text-center py-8">No items ready for DonorPerfect update.</p>
                    @endif
                @elseif($activeTab === 'errors')
                    <!-- Errors Tab -->
                    @if($itemsWithErrors->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                                <thead class="bg-gray-50 dark:bg-zinc-700">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Gift ID</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Deposit Date</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Error</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-700">
                                    @foreach($itemsWithErrors as $item)
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $item->gift_id }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $item->sage_deposit_date?->format('M d, Y') ?? 'N/A' }}</td>
                                            <td class="px-4 py-3 text-sm text-red-600 dark:text-red-400 max-w-md truncate" title="{{ $item->deposit_sync_error }}">{{ $item->deposit_sync_error }}</td>
                                            <td class="px-4 py-3 text-sm space-x-2">
                                                <button
                                                    wire:click="syncSingleItem({{ $item->id }})"
                                                    class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                                >
                                                    Retry
                                                </button>
                                                <button
                                                    wire:click="clearError({{ $item->id }})"
                                                    class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300"
                                                >
                                                    Clear
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4">
                            {{ $itemsWithErrors->links() }}
                        </div>
                    @else
                        <p class="text-gray-500 dark:text-gray-400 text-center py-8">No errors to display.</p>
                    @endif
                @elseif($activeTab === 'completed')
                    <!-- Recently Completed Tab -->
                    @if($recentlySyncedItems->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                                <thead class="bg-gray-50 dark:bg-zinc-700">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Gift ID</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Intacct Record</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Deposit Date</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Synced to DP</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-700">
                                    @foreach($recentlySyncedItems as $item)
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $item->gift_id }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $item->intacct_record_id }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $item->sage_deposit_date?->format('M d, Y') }}</td>
                                            <td class="px-4 py-3 text-sm text-green-600 dark:text-green-400">{{ $item->deposit_date_synced_at?->format('M d, Y H:i') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-gray-500 dark:text-gray-400 text-center py-8">No recently completed syncs.</p>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>
