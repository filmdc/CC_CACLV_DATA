@if($activeTab === 'sync-history')
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Sync History</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">View past synchronization logs</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-gray-50 dark:bg-zinc-700 p-4 rounded-lg">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="filterStatus" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Status
                    </label>
                    <select wire:model.live="filterStatus"
                            id="filterStatus"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">All Statuses</option>
                        <option value="success">Success</option>
                        <option value="failed">Failed</option>
                        <option value="partial">Partial</option>
                        <option value="running">Running</option>
                    </select>
                </div>

                <div>
                    <label for="filterStartDate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Start Date
                    </label>
                    <input type="date"
                           wire:model.live="filterStartDate"
                           id="filterStartDate"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label for="filterEndDate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        End Date
                    </label>
                    <input type="date"
                           wire:model.live="filterEndDate"
                           id="filterEndDate"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div class="flex items-end">
                    <button wire:click="clearFilters"
                            class="w-full px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-zinc-600 border border-gray-300 dark:border-zinc-500 rounded-md hover:bg-gray-50 dark:hover:bg-zinc-500">
                        Clear Filters
                    </button>
                </div>
            </div>
        </div>

        <!-- Sync History Table -->
        @if($syncHistory->count() > 0)
            <div class="bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-700 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                    <thead class="bg-gray-50 dark:bg-zinc-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Started At</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Completed At</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Records</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Success</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Failed</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-700">
                        @foreach($syncHistory as $sync)
                            <tr class="hover:bg-gray-50 dark:hover:bg-zinc-700">
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ $sync->started_at->format('M d, Y H:i') }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ $sync->completed_at ? $sync->completed_at->format('M d, Y H:i') : '-' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center space-x-2">
                                        @if($sync->status === 'success')
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">
                                                Success
                                            </span>
                                        @elseif($sync->status === 'failed')
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200">
                                                Failed
                                            </span>
                                        @elseif($sync->status === 'partial')
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200">
                                                Partial
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">
                                                Running
                                            </span>
                                        @endif

                                        @if($sync->reset_at)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-200"
                                                  title="Reset {{ $sync->reset_count > 1 ? $sync->reset_count . ' times, last' : '' }} on {{ $sync->reset_at->format('M d, Y g:i A') }}{{ $sync->resetBy ? ' by ' . $sync->resetBy->name : '' }}">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                </svg>
                                                Reset{{ $sync->reset_count > 1 ? ' ×' . $sync->reset_count : '' }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ $sync->total_records }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-green-600 dark:text-green-400">
                                    {{ $sync->successful_count }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-red-600 dark:text-red-400">
                                    {{ $sync->failed_count }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm">
                                    <a href="{{ route('intacct.sync-history.details', $sync->id) }}"
                                       class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/20">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                        View Details
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-4">
                {{ $syncHistory->links() }}
            </div>
        @else
            <div class="text-center py-12 bg-gray-50 dark:bg-zinc-700 rounded-lg">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No sync history</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Sync history will appear here once synchronization runs.</p>
            </div>
        @endif
    </div>
@endif
