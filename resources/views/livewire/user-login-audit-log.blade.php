<div class="max-w-7xl mx-auto p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">User Login Audit Log</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Monitor user sign-on activity and active sessions</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 dark:bg-green-900">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Logins Today</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $todayLogins }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-red-100 dark:bg-red-900">
                    <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Failed Attempts Today</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $todayFailed }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Active Sessions</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $activeUsers }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-indigo-100 dark:bg-indigo-900">
                    <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Unique Users Today</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $uniqueUsersToday }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Sessions Section -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Currently Active Sessions</h2>

        @if($activeSessions->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                    <thead class="bg-gray-50 dark:bg-zinc-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">User</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">IP Address</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Browser / Platform</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Last Activity</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-700">
                        @foreach($activeSessions as $session)
                            <tr>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="h-8 w-8 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center">
                                            <span class="text-sm font-medium text-indigo-600 dark:text-indigo-400">{{ strtoupper(substr($session->name, 0, 1)) }}</span>
                                        </div>
                                        <div class="ml-3">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $session->name }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $session->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                    {{ $session->ip_address }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                    {{ $session->browser }} / {{ $session->platform }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                    <span title="{{ $session->last_activity_at->format('M d, Y g:i:s A') }}">
                                        {{ $session->last_activity_at->diffForHumans() }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-gray-500 dark:text-gray-400 text-sm">No active sessions found.</p>
        @endif
    </div>

    <!-- Audit Log Section -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Login History</h2>

        <!-- Filters -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search</label>
                <input type="text"
                       wire:model.live.debounce.300ms="search"
                       placeholder="Email, name, or IP..."
                       class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Action</label>
                <select wire:model.live="actionFilter"
                        class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    <option value="">All Actions</option>
                    <option value="login_success">Login Success</option>
                    <option value="login_failed">Login Failed</option>
                    <option value="logout">Logout</option>
                    <option value="password_reset_requested">Password Reset Requested</option>
                    <option value="password_reset_completed">Password Reset Completed</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">User</label>
                <select wire:model.live="userFilter"
                        class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    <option value="">All Users</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">From Date</label>
                <input type="date"
                       wire:model.live="dateFrom"
                       class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">To Date</label>
                <input type="date"
                       wire:model.live="dateTo"
                       class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 text-sm">
            </div>
        </div>

        @if($search || $actionFilter || $userFilter || $dateFrom || $dateTo)
            <div class="mb-4">
                <button wire:click="clearFilters" class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300">
                    Clear all filters
                </button>
            </div>
        @endif

        <!-- Audit Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                <thead class="bg-gray-50 dark:bg-zinc-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date/Time</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">User</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Action</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">IP Address</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Browser / Platform</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Details</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-700">
                    @forelse($audits as $audit)
                        <tr>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                <div>{{ $audit->created_at->format('M d, Y') }}</div>
                                <div class="text-xs text-gray-400">{{ $audit->created_at->format('g:i:s A') }}</div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if($audit->user)
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $audit->user->name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $audit->email }}</div>
                                @else
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $audit->email }}</div>
                                    <div class="text-xs text-gray-400">(Unknown user)</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @php
                                    $colorClasses = match($audit->action_color) {
                                        'green' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                        'red' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                        'yellow' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                        'blue' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                        'indigo' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200',
                                        default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
                                    };
                                @endphp
                                <span class="px-2 py-1 text-xs font-medium rounded-full {{ $colorClasses }}">
                                    {{ $audit->action_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                {{ $audit->ip_address }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                @if($audit->browser || $audit->platform)
                                    {{ $audit->browser ?? 'Unknown' }} / {{ $audit->platform ?? 'Unknown' }}
                                    @if($audit->device_type)
                                        <div class="text-xs text-gray-400">{{ $audit->device_type }}</div>
                                    @endif
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                @if($audit->failure_reason)
                                    <span class="text-red-600 dark:text-red-400">
                                        {{ ucfirst(str_replace('_', ' ', $audit->failure_reason)) }}
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                No login audit records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-4">
            {{ $audits->links() }}
        </div>
    </div>
</div>
