<div class="w-full px-0 py-6">
    <div class="bg-white dark:bg-zinc-800 shadow-lg rounded-lg overflow-hidden">
        {{-- Header --}}
        <div class="bg-purple-600 text-white p-6 flex items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold">Users</h1>
                <p class="text-purple-100 mt-1 text-sm">Browse, search, and manage accounts. Use the buttons on the right to add a new user or run bulk operations.</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-2 shrink-0">
                @if($this->can('users.edit'))
                    <a href="{{ route('users.create') }}" wire:navigate
                       class="inline-flex items-center px-4 py-2 bg-white text-purple-700 rounded-md hover:bg-purple-50 transition font-medium">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        New User
                    </a>
                    <a href="{{ route('users.bulk') }}" wire:navigate
                       class="inline-flex items-center px-4 py-2 bg-purple-700 text-white rounded-md hover:bg-purple-800 transition">
                        Bulk Operations
                    </a>
                @endif
            </div>
        </div>

        {{-- Flash messages --}}
        @if (session()->has('success'))
            <div class="p-4 bg-green-50 dark:bg-green-900/20 border-l-4 border-green-400">
                <p class="text-sm text-green-700 dark:text-green-300">{{ session('success') }}</p>
            </div>
        @endif
        @if (session()->has('error'))
            <div class="p-4 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-400">
                <p class="text-sm text-red-700 dark:text-red-300">{{ session('error') }}</p>
            </div>
        @endif

        {{-- Filters --}}
        <div class="p-4 bg-gray-50 dark:bg-zinc-700 border-b border-gray-200 dark:border-zinc-600">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <div class="flex-1 max-w-md">
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by name or email..."
                           class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                <div class="inline-flex rounded-md shadow-sm" role="group">
                    @foreach([
                        ['active', 'Active', $counts['active']],
                        ['inactive', 'Inactive', $counts['inactive']],
                        ['all', 'All', $counts['all']],
                    ] as [$value, $label, $count])
                        <button type="button" wire:click="setStatusFilter('{{ $value }}')"
                                class="px-4 py-2 text-sm font-medium border border-gray-300 dark:border-zinc-600 transition
                                       {{ $loop->first ? 'rounded-l-md' : '' }} {{ $loop->last ? 'rounded-r-md' : '-ml-px' }}
                                       {{ $statusFilter === $value
                                           ? 'bg-purple-600 text-white border-purple-600 dark:border-purple-600'
                                           : 'bg-white dark:bg-zinc-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-zinc-700' }}">
                            {{ $label }}
                            <span class="ml-1 text-xs opacity-75">({{ $count }})</span>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Selection bar --}}
        @if($this->can('users.edit') && count($selectedUsers) > 0)
            <div class="p-3 bg-purple-50 dark:bg-purple-900/20 border-b border-purple-200 dark:border-purple-800 flex items-center justify-between">
                <div class="text-sm text-purple-800 dark:text-purple-200">
                    <strong>{{ count($selectedUsers) }}</strong> user{{ count($selectedUsers) === 1 ? '' : 's' }} selected
                </div>
                <div class="flex gap-2">
                    <button wire:click="clearSelection"
                            class="px-3 py-1.5 text-sm bg-white dark:bg-zinc-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-zinc-600 rounded-md hover:bg-gray-50">
                        Clear
                    </button>
                    <button wire:click="bulkActOnSelected"
                            class="px-3 py-1.5 text-sm bg-purple-600 text-white rounded-md hover:bg-purple-700">
                        Bulk Actions →
                    </button>
                </div>
            </div>
        @endif

        {{-- Users Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                <thead class="bg-gray-50 dark:bg-zinc-700">
                    <tr>
                        @if($this->can('users.edit'))
                            <th class="px-4 py-3 w-10"></th>
                        @endif
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Roles</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Programs</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-700">
                    @forelse($users as $user)
                        <tr class="{{ ! $user->active ? 'bg-gray-50 dark:bg-zinc-700/30' : '' }}">
                            @if($this->can('users.edit'))
                                <td class="px-4 py-4">
                                    <input type="checkbox" wire:model.live="selectedUsers" value="{{ $user->id }}"
                                           class="rounded border-gray-300 dark:border-zinc-600 text-purple-600 focus:ring-purple-500">
                                </td>
                            @endif
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium">
                                    @if($this->can('users.edit'))
                                        <a href="{{ route('users.edit', $user->id) }}" wire:navigate
                                           class="text-gray-900 dark:text-white hover:text-purple-600 dark:hover:text-purple-400 hover:underline">
                                            {{ $user->name }}
                                        </a>
                                    @else
                                        <span class="text-gray-900 dark:text-white">{{ $user->name }}</span>
                                    @endif
                                    @if($user->id === auth()->id())
                                        <span class="ml-2 inline-flex px-2 py-0.5 text-xs font-semibold rounded-full bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">You</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $user->email }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($user->active)
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">Active</span>
                                @else
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200">Inactive</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1 max-w-xs">
                                    @forelse($user->roles as $role)
                                        <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200">{{ $role->display_name }}</span>
                                    @empty
                                        <span class="text-xs text-gray-400">—</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1 max-w-xs">
                                    @forelse($user->programs as $program)
                                        <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">{{ $program->name }}</span>
                                    @empty
                                        <span class="text-xs text-gray-400">—</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-end gap-3">
                                    @if($this->can('users.edit'))
                                        <a href="{{ route('users.edit', $user->id) }}" wire:navigate
                                           class="text-purple-600 dark:text-purple-400 hover:underline">Edit</a>
                                        <a href="{{ route('users.create', ['duplicate_from' => $user->id]) }}" wire:navigate
                                           class="text-indigo-600 dark:text-indigo-400 hover:underline" title="Create a new user with the same roles &amp; programs">Duplicate</a>
                                        @if($user->id !== auth()->id())
                                            <button wire:click="toggleUserStatus({{ $user->id }})"
                                                    class="{{ $user->active ? 'text-orange-600 dark:text-orange-400' : 'text-green-600 dark:text-green-400' }} hover:underline">
                                                {{ $user->active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $this->can('users.edit') ? 7 : 6 }}" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                <p class="mb-2">No users match the current filter.</p>
                                @if($search !== '' || $statusFilter !== 'active')
                                    <button wire:click="$set('search', '')" class="text-purple-600 dark:text-purple-400 hover:underline text-sm mr-3">Clear search</button>
                                    <button wire:click="setStatusFilter('all')" class="text-purple-600 dark:text-purple-400 hover:underline text-sm">Show all users</button>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($users->hasPages())
            <div class="px-6 py-4 bg-gray-50 dark:bg-zinc-700 border-t border-gray-200 dark:border-zinc-600">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>
