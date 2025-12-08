<div class="max-w-7xl mx-auto p-6">
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl">
        <!-- Header -->
        <div class="p-6 border-b border-gray-200 dark:border-zinc-700">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Legacy Contract Import</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Import contracts from the legacy database with filters and preview</p>
        </div>

        <!-- Flash Messages -->
        @if (session()->has('success'))
            <div class="m-6 rounded-md bg-green-50 dark:bg-green-900/20 p-4">
                <div class="flex">
                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <p class="ml-3 text-sm font-medium text-green-800 dark:text-green-200">
                        {{ session('success') }}
                    </p>
                </div>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="m-6 rounded-md bg-red-50 dark:bg-red-900/20 p-4">
                <div class="flex">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <p class="ml-3 text-sm font-medium text-red-800 dark:text-red-200">
                        {{ session('error') }}
                    </p>
                </div>
            </div>
        @endif

        <div class="p-6">
            <!-- Connection Test Section -->
            <div class="mb-6 p-4 bg-gray-50 dark:bg-zinc-900 rounded-lg border border-gray-200 dark:border-zinc-700">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Database Connection</h3>
                    <button wire:click="testConnection"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-sm font-medium transition-colors">
                        Test Connection
                    </button>
                </div>

                @if($connectionStatus !== null)
                    <div class="flex items-center space-x-2">
                        @if($connectionStatus)
                            <svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-green-700 dark:text-green-300 font-medium">Connected</span>
                            <span class="text-gray-600 dark:text-gray-400">- {{ number_format($contractsCount) }} contracts available</span>
                        @else
                            <svg class="h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-red-700 dark:text-red-300 font-medium">Connection Failed</span>
                            <span class="text-gray-600 dark:text-gray-400">- {{ $connectionMessage }}</span>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Filter Section -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Filter Contracts</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Legacy ID Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Legacy ID
                        </label>
                        <input type="text"
                               wire:model="legacyIdFilter"
                               placeholder="e.g., 123, 100-200, 50,51,52"
                               class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-green-500 focus:ring-green-500">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Single ID, range (100-200), or list (50,51,52)</p>
                    </div>

                    <!-- Start Date Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Start Date From
                        </label>
                        <input type="date"
                               wire:model="startDateFilter"
                               class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-green-500 focus:ring-green-500">
                    </div>

                    <!-- End Date Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            End Date To
                        </label>
                        <input type="date"
                               wire:model="endDateFilter"
                               class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-green-500 focus:ring-green-500">
                    </div>

                    <!-- Preview Limit -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Preview Limit
                        </label>
                        <select wire:model="previewLimit"
                                class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-green-500 focus:ring-green-500">
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="200">200</option>
                            <option value="500">500</option>
                        </select>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="mt-4 flex space-x-3">
                    <button wire:click="previewContracts"
                            class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md font-medium transition-colors">
                        Preview Contracts
                    </button>
                    <button wire:click="resetFilters"
                            class="px-6 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-md font-medium transition-colors">
                        Reset Filters
                    </button>
                </div>
            </div>

            <!-- Preview Results -->
            @if($showPreview && count($previewContracts) > 0)
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Preview Results ({{ count($previewContracts) }} contracts)
                        </h3>
                        <div class="flex items-center space-x-4">
                            <label class="flex items-center">
                                <input type="checkbox"
                                       wire:model.live="selectAll"
                                       wire:click="toggleSelectAll"
                                       class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Select All</span>
                            </label>
                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                {{ count($selectedContracts) }} selected
                            </span>
                            @if(count($selectedContracts) > 0)
                                <button wire:click="showImportConfirmation"
                                        class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md text-sm font-medium transition-colors">
                                    Import Selected
                                </button>
                            @endif
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-600">
                            <thead class="bg-gray-50 dark:bg-zinc-700">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Select</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Legacy ID</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Title</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Grant Code</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Program</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Funder</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Dates</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Amount</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Related Data</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-600">
                                @foreach($previewContracts as $contract)
                                    <tr class="{{ $contract->already_imported ? 'bg-blue-50 dark:bg-blue-900/10' : '' }}">
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <input type="checkbox"
                                                   wire:model.live="selectedContracts"
                                                   value="{{ $contract->legacy_id }}"
                                                   class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $contract->legacy_id }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white max-w-xs truncate" title="{{ $contract->title }}">
                                            {{ $contract->title }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ $contract->grant_code ?: '-' }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ $contract->program_name ?: '-' }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ $contract->funder_name ?: '-' }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            <div>{{ $contract->start_date ? \Carbon\Carbon::parse($contract->start_date)->format('m/d/Y') : '-' }}</div>
                                            <div class="text-xs">to {{ $contract->end_date ? \Carbon\Carbon::parse($contract->end_date)->format('m/d/Y') : 'Open' }}</div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white font-medium">
                                            ${{ $contract->amount_formatted }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            <div class="flex space-x-2">
                                                @if($contract->due_dates_count > 0)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300">
                                                        {{ $contract->due_dates_count }} due dates
                                                    </span>
                                                @endif
                                                @if($contract->media_count > 0)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-300">
                                                        {{ $contract->media_count }} files
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            @if($contract->already_imported)
                                                <div class="flex flex-col">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300">
                                                        Imported
                                                    </span>
                                                    @if($contract->last_synced)
                                                        <span class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                            {{ \Carbon\Carbon::parse($contract->last_synced)->diffForHumans() }}
                                                        </span>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-300">
                                                    Not Imported
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @elseif($showPreview && count($previewContracts) === 0)
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No contracts found</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Try adjusting your filter criteria</p>
                </div>
            @endif

            <!-- Import Results -->
            @if($importResults && ($lastImportErrors || $lastImportWarnings))
                <div class="mt-6 space-y-4">
                    @if($lastImportErrors)
                        <div class="rounded-md bg-red-50 dark:bg-red-900/20 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-red-800 dark:text-red-200">Errors ({{ count($lastImportErrors) }})</h3>
                                    <div class="mt-2 text-sm text-red-700 dark:text-red-300">
                                        <ul class="list-disc pl-5 space-y-1">
                                            @foreach($lastImportErrors as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($lastImportWarnings)
                        <div class="rounded-md bg-yellow-50 dark:bg-yellow-900/20 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">Warnings ({{ count($lastImportWarnings) }})</h3>
                                    <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                                        <ul class="list-disc pl-5 space-y-1">
                                            @foreach($lastImportWarnings as $warning)
                                                <li>{{ $warning }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <!-- Import Confirmation Modal -->
    @if($showImportModal)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-md w-full mx-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Confirm Import</h3>

                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    You are about to import {{ count($selectedContracts) }} contract(s) from the legacy database.
                </p>

                <div class="mb-4">
                    <label class="flex items-center">
                        <input type="checkbox"
                               wire:model="updateExisting"
                               class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                            Update existing contracts (if already imported)
                        </span>
                    </label>
                </div>

                <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-md mb-4">
                    <p class="text-sm text-blue-800 dark:text-blue-200">
                        <strong>What will be imported:</strong>
                    </p>
                    <ul class="mt-2 text-sm text-blue-700 dark:text-blue-300 list-disc pl-5 space-y-1">
                        <li>Contract details (preserving HTML formatting)</li>
                        <li>Report due dates with reminder intervals</li>
                        <li>Assigned users for due dates</li>
                        <li>Contract attachments/documents</li>
                    </ul>
                </div>

                @if($isImporting)
                    <div class="flex items-center justify-center py-4">
                        <svg class="animate-spin h-8 w-8 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="ml-3 text-gray-900 dark:text-white">Importing contracts...</span>
                    </div>
                @else
                    <div class="flex space-x-3">
                        <button wire:click="importContracts"
                                class="flex-1 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md font-medium transition-colors">
                            Import Now
                        </button>
                        <button wire:click="cancelImport"
                                class="flex-1 px-4 py-2 bg-gray-300 dark:bg-zinc-600 hover:bg-gray-400 dark:hover:bg-zinc-500 text-gray-700 dark:text-white rounded-md font-medium transition-colors">
                            Cancel
                        </button>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
