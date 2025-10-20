<div class="max-w-7xl mx-auto p-6">
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl">
        <!-- Header -->
        <div class="p-6 border-b border-gray-200 dark:border-zinc-700">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Check Image Audit Log</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                        Comprehensive audit trail of all check image operations
                    </p>
                </div>
            </div>

            <!-- Action Statistics -->
            @if(!empty($actionStats))
            <div class="mt-4 grid grid-cols-5 gap-4">
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3 text-center">
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                        {{ $actionStats['accessed'] ?? 0 }}
                    </div>
                    <div class="text-xs text-blue-800 dark:text-blue-300">Accessed</div>
                </div>
                <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-3 text-center">
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                        {{ $actionStats['cached'] ?? 0 }}
                    </div>
                    <div class="text-xs text-green-800 dark:text-green-300">Cached</div>
                </div>
                <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-3 text-center">
                    <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                        {{ $actionStats['decrypted'] ?? 0 }}
                    </div>
                    <div class="text-xs text-yellow-800 dark:text-yellow-300">Decrypted</div>
                </div>
                <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-3 text-center">
                    <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                        {{ $actionStats['downloaded'] ?? 0 }}
                    </div>
                    <div class="text-xs text-purple-800 dark:text-purple-300">Downloaded</div>
                </div>
                <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-3 text-center">
                    <div class="text-2xl font-bold text-red-600 dark:text-red-400">
                        {{ $actionStats['deleted'] ?? 0 }}
                    </div>
                    <div class="text-xs text-red-800 dark:text-red-300">Deleted</div>
                </div>
            </div>
            @endif
        </div>

        <!-- Filters -->
        <div class="p-6 border-b border-gray-200 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-900">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <!-- Search -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Search
                    </label>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Filename, Gift ID, IP..."
                        class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                </div>

                <!-- Action Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Action
                    </label>
                    <select
                        wire:model.live="actionFilter"
                        class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        <option value="">All Actions</option>
                        <option value="accessed">Accessed</option>
                        <option value="cached">Cached</option>
                        <option value="decrypted">Decrypted</option>
                        <option value="downloaded">Downloaded</option>
                        <option value="deleted">Deleted</option>
                    </select>
                </div>

                <!-- User Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        User
                    </label>
                    <select
                        wire:model.live="userFilter"
                        class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        <option value="">All Users</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Date From -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        From Date
                    </label>
                    <input
                        type="date"
                        wire:model.live="dateFrom"
                        class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                </div>

                <!-- Date To -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        To Date
                    </label>
                    <input
                        type="date"
                        wire:model.live="dateTo"
                        class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                </div>
            </div>

            <!-- Clear Filters Button -->
            <div class="mt-4 flex justify-end">
                <button
                    wire:click="clearFilters"
                    class="px-4 py-2 bg-gray-200 dark:bg-zinc-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-600 transition text-sm">
                    Clear Filters
                </button>
            </div>
        </div>

        <!-- Audit Log Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                <thead class="bg-gray-50 dark:bg-zinc-900">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Timestamp
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            User
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Action
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Filename
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Gift ID
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            IP Address
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Details
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-700">
                    @forelse($logs as $log)
                        <tr class="hover:bg-gray-50 dark:hover:bg-zinc-700/50 transition">
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                {{ $log->created_at->format('Y-m-d H:i:s') }}
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $log->created_at->diffForHumans() }}
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                @if($log->user)
                                    <div>{{ $log->user->name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $log->user->email }}</div>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500 italic">System</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm">
                                <span class="px-2 py-1 rounded-full text-xs font-medium
                                    @if($log->action === 'accessed') bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300
                                    @elseif($log->action === 'cached') bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300
                                    @elseif($log->action === 'decrypted') bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300
                                    @elseif($log->action === 'downloaded') bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300
                                    @elseif($log->action === 'deleted') bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300
                                    @else bg-gray-100 dark:bg-gray-900/30 text-gray-800 dark:text-gray-300
                                    @endif">
                                    {{ ucfirst($log->action) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white font-mono">
                                <div class="max-w-xs truncate" title="{{ $log->filename }}">
                                    {{ $log->filename }}
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                {{ $log->gift_id ?? '-' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white font-mono">
                                {{ $log->ip_address ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                @if($log->metadata && !empty($log->metadata))
                                    <details class="cursor-pointer">
                                        <summary class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                            View metadata
                                        </summary>
                                        <pre class="mt-2 text-xs bg-gray-100 dark:bg-zinc-900 p-2 rounded overflow-auto max-w-md">{{ json_encode($log->metadata, JSON_PRETTY_PRINT) }}</pre>
                                    </details>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                No audit log entries found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="p-6 border-t border-gray-200 dark:border-zinc-700">
            {{ $logs->links() }}
        </div>
    </div>
</div>
