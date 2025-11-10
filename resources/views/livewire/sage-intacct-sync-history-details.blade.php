<div class="w-full px-0 py-6">
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl">
        <!-- Header -->
        <div class="bg-indigo-600 text-white p-6 rounded-t-lg">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Sync Run Details #{{ $syncHistory->id }}</h1>
                    <p class="text-indigo-100 mt-2">Started: {{ $syncHistory->started_at->format('M d, Y H:i:s') }}</p>
                </div>
                <a href="{{ route('intacct.configuration') }}?tab=sync-history"
                   class="px-4 py-2 bg-white text-indigo-600 rounded-md hover:bg-indigo-50 font-medium">
                    &larr; Back to Sync History
                </a>
            </div>
        </div>

        <div class="p-6 space-y-6">
            <!-- Sync Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <div class="text-sm font-medium text-blue-600 dark:text-blue-400">Total Records</div>
                    <div class="text-2xl font-bold text-blue-900 dark:text-blue-100">{{ $syncHistory->total_records }}</div>
                </div>

                <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                    <div class="text-sm font-medium text-green-600 dark:text-green-400">Successful</div>
                    <div class="text-2xl font-bold text-green-900 dark:text-green-100">{{ $syncHistory->successful_count }}</div>
                </div>

                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                    <div class="text-sm font-medium text-red-600 dark:text-red-400">Failed</div>
                    <div class="text-2xl font-bold text-red-900 dark:text-red-100">{{ $syncHistory->failed_count }}</div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-900/20 border border-gray-200 dark:border-gray-800 rounded-lg p-4">
                    <div class="text-sm font-medium text-gray-600 dark:text-gray-400">Status</div>
                    <div class="text-2xl font-bold">
                        @if($syncHistory->status === 'success')
                            <span class="text-green-600 dark:text-green-400">Success</span>
                        @elseif($syncHistory->status === 'failed')
                            <span class="text-red-600 dark:text-red-400">Failed</span>
                        @elseif($syncHistory->status === 'partial')
                            <span class="text-yellow-600 dark:text-yellow-400">Partial</span>
                        @else
                            <span class="text-blue-600 dark:text-blue-400">{{ ucfirst($syncHistory->status) }}</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Sync Metadata -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Basic Information -->
                <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Sync Information</h3>
                    <dl class="space-y-2">
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600 dark:text-gray-400">Date Range:</dt>
                            <dd class="text-sm font-medium text-gray-900 dark:text-white">
                                @if($syncHistory->sync_start_date && $syncHistory->sync_end_date)
                                    {{ $syncHistory->sync_start_date }} to {{ $syncHistory->sync_end_date }}
                                @else
                                    N/A
                                @endif
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600 dark:text-gray-400">Completed At:</dt>
                            <dd class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $syncHistory->completed_at ? $syncHistory->completed_at->format('M d, Y H:i:s') : 'In Progress' }}
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600 dark:text-gray-400">Duration:</dt>
                            <dd class="text-sm font-medium text-gray-900 dark:text-white">
                                @if($syncHistory->started_at && $syncHistory->completed_at)
                                    {{ $syncHistory->started_at->diffInSeconds($syncHistory->completed_at) }}s
                                @else
                                    N/A
                                @endif
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600 dark:text-gray-400">Triggered By:</dt>
                            <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $syncHistory->triggered_by ?? 'N/A' }}</dd>
                        </div>
                    </dl>

                    @if($syncHistory->error_message)
                        <div class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded">
                            <p class="text-sm text-red-800 dark:text-red-200">
                                <strong>Error:</strong> {{ $syncHistory->error_message }}
                            </p>
                        </div>
                    @endif
                </div>

                <!-- Configuration Snapshot -->
                @if($configurationSnapshot)
                <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Configuration Snapshot</h3>
                    <dl class="space-y-2">
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600 dark:text-gray-400">Company ID:</dt>
                            <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $configurationSnapshot['company_id'] ?? 'N/A' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600 dark:text-gray-400">Auth Method:</dt>
                            <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ strtoupper($configurationSnapshot['auth_method'] ?? 'N/A') }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600 dark:text-gray-400">Sandbox:</dt>
                            <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $configurationSnapshot['is_sandbox'] ? 'Yes' : 'No' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600 dark:text-gray-400">Code Mapping:</dt>
                            <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $configurationSnapshot['code_mapping_enabled'] ? 'Enabled' : 'Disabled' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600 dark:text-gray-400">Field Mappings:</dt>
                            <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $configurationSnapshot['field_mappings_count'] ?? 0 }}</dd>
                        </div>
                    </dl>
                </div>
                @endif

                <!-- Environment Information -->
                @if($environmentInfo)
                <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-4 md:col-span-2">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Environment Information</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <div>
                            <dt class="text-xs text-gray-600 dark:text-gray-400">PHP Version</dt>
                            <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $environmentInfo['php_version'] ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-600 dark:text-gray-400">Laravel Version</dt>
                            <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $environmentInfo['laravel_version'] ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-600 dark:text-gray-400">Environment</dt>
                            <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $environmentInfo['app_env'] ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-600 dark:text-gray-400">Server</dt>
                            <dd class="text-sm font-medium text-gray-900 dark:text-white truncate" title="{{ $environmentInfo['server_name'] ?? 'N/A' }}">
                                {{ $environmentInfo['server_name'] ?? 'N/A' }}
                            </dd>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <!-- Filters -->
            <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="statusFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Status
                        </label>
                        <select wire:model.live="statusFilter"
                                id="statusFilter"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="all">All Statuses</option>
                            <option value="success">Success</option>
                            <option value="failed">Failed</option>
                            <option value="pending">Pending</option>
                        </select>
                    </div>

                    <div>
                        <label for="searchGiftId" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Search Gift ID
                        </label>
                        <input type="text"
                               wire:model.live="searchGiftId"
                               id="searchGiftId"
                               placeholder="Enter Gift ID..."
                               class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div class="flex items-end">
                        <button wire:click="$set('statusFilter', 'all')"
                                class="w-full px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-zinc-600 border border-gray-300 dark:border-zinc-500 rounded-md hover:bg-gray-50 dark:hover:bg-zinc-500">
                            Clear Filters
                        </button>
                    </div>
                </div>
            </div>

            <!-- Sync Items Table -->
            @if($syncItems->count() > 0)
                <div class="bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-700 overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                        <thead class="bg-gray-50 dark:bg-zinc-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Gift ID</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Amount</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Donor</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Error</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-700">
                            @foreach($syncItems as $item)
                                <tr class="hover:bg-gray-50 dark:hover:bg-zinc-700">
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-mono text-gray-900 dark:text-white">
                                        {{ $item->gift_id }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        @if($item->status === 'success')
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">
                                                Success
                                            </span>
                                        @elseif($item->status === 'failed')
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200">
                                                Failed
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-200">
                                                Pending
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        @if($item->gift_data_decoded && isset($item->gift_data_decoded['amount']))
                                            ${{ number_format($item->gift_data_decoded['amount'], 2) }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        @if($item->gift_data_decoded)
                                            {{ ($item->gift_data_decoded['first_name'] ?? '') }} {{ ($item->gift_data_decoded['last_name'] ?? '') }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-red-600 dark:text-red-400">
                                        @if($item->has_error)
                                            <span class="truncate max-w-xs block" title="{{ $item->error_message }}">
                                                {{ Str::limit($item->error_message, 50) }}
                                            </span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm">
                                        <button wire:click="showItemDetails({{ $item->id }})"
                                                class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                            View Details
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-4">
                    {{ $syncItems->links() }}
                </div>
            @else
                <div class="text-center py-12 bg-gray-50 dark:bg-zinc-700 rounded-lg">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No sync items found</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Try adjusting your filters.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Item Details Modal -->
    @if($showItemDetails && $selectedItem)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50 p-4">
            <div class="bg-white dark:bg-zinc-800 rounded-lg max-w-6xl w-full max-h-[90vh] overflow-hidden flex flex-col">
                <!-- Modal Header -->
                <div class="bg-indigo-600 text-white p-4 rounded-t-lg flex justify-between items-center">
                    <h3 class="text-lg font-bold">Gift Details - {{ $selectedItem->gift_id }}</h3>
                    <button wire:click="closeItemDetails" class="text-white hover:text-gray-200">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Modal Content -->
                <div class="p-6 overflow-y-auto flex-1">
                    <div class="space-y-6">
                        <!-- Status Section -->
                        <div>
                            <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-2">Status Information</h4>
                            <dl class="grid grid-cols-2 gap-4">
                                <div>
                                    <dt class="text-sm text-gray-600 dark:text-gray-400">Status:</dt>
                                    <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ ucfirst($selectedItem->status) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-gray-600 dark:text-gray-400">Intacct Record ID:</dt>
                                    <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $selectedItem->intacct_record_id ?? 'N/A' }}</dd>
                                </div>
                                @if($selectedItem->synced_at)
                                <div>
                                    <dt class="text-sm text-gray-600 dark:text-gray-400">Synced At:</dt>
                                    <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $selectedItem->synced_at }}</dd>
                                </div>
                                @endif
                                @if($selectedItem->error_message)
                                <div class="col-span-2">
                                    <dt class="text-sm text-gray-600 dark:text-gray-400 mb-1">Error Message:</dt>
                                    <dd class="text-sm font-medium text-red-600 dark:text-red-400 p-3 bg-red-50 dark:bg-red-900/20 rounded">
                                        {{ $selectedItem->error_message }}
                                    </dd>
                                </div>
                                @endif
                            </dl>
                        </div>

                        <!-- Gift Data Section -->
                        @if($selectedItem->gift_data)
                        <div>
                            <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-2">Original Gift Data</h4>
                            <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-4 overflow-x-auto">
                                <pre class="text-xs text-gray-900 dark:text-white">{{ json_encode($selectedItem->gift_data, JSON_PRETTY_PRINT) }}</pre>
                            </div>
                        </div>
                        @endif

                        <!-- Transformed Data Section -->
                        @if($selectedItem->transformed_data)
                        <div>
                            <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-2">Transformed Data (Sent to Intacct)</h4>
                            <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-4 overflow-x-auto">
                                <pre class="text-xs text-gray-900 dark:text-white">{{ json_encode($selectedItem->transformed_data, JSON_PRETTY_PRINT) }}</pre>
                            </div>
                        </div>
                        @endif

                        <!-- API Request Section -->
                        @if($selectedItem->api_request)
                        <div>
                            <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-2">API Request</h4>
                            <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-4 overflow-x-auto">
                                <pre class="text-xs text-gray-900 dark:text-white">{{ json_encode($selectedItem->api_request, JSON_PRETTY_PRINT) }}</pre>
                            </div>
                        </div>
                        @endif

                        <!-- API Response Section -->
                        @if($selectedItem->api_response)
                        <div>
                            <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-2">API Response</h4>
                            <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-4 overflow-x-auto">
                                <pre class="text-xs text-gray-900 dark:text-white">{{ json_encode($selectedItem->api_response, JSON_PRETTY_PRINT) }}</pre>
                            </div>
                        </div>
                        @endif

                        <!-- Validation Errors Section -->
                        @if($selectedItem->validation_errors)
                        <div>
                            <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-2">Validation Errors</h4>
                            <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4 overflow-x-auto">
                                <pre class="text-xs text-red-900 dark:text-red-100">{{ json_encode($selectedItem->validation_errors, JSON_PRETTY_PRINT) }}</pre>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="bg-gray-50 dark:bg-zinc-700 p-4 rounded-b-lg flex justify-end">
                    <button wire:click="closeItemDetails"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
