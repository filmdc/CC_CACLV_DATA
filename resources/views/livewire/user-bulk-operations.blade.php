<div class="w-full px-0 py-6">
    <div class="bg-white dark:bg-zinc-800 shadow-lg rounded-lg overflow-hidden">
        {{-- Header --}}
        <div class="bg-purple-600 text-white p-6">
            <div class="flex items-center gap-3">
                <a href="{{ route('users.index') }}" wire:navigate class="text-purple-100 hover:text-white text-sm">← Users</a>
            </div>
            <h1 class="text-2xl font-bold mt-1">Bulk User Operations</h1>
            <p class="text-purple-100 mt-1 text-sm">Select users below, then choose an action. Bulk activate/deactivate is useful for hiding many inactive accounts at once.</p>
        </div>

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
                    @foreach([['all', 'All'], ['active', 'Active'], ['inactive', 'Inactive']] as [$value, $label])
                        <button type="button" wire:click="$set('statusFilter', '{{ $value }}')"
                                class="px-4 py-2 text-sm font-medium border border-gray-300 dark:border-zinc-600 transition
                                       {{ $loop->first ? 'rounded-l-md' : '' }} {{ $loop->last ? 'rounded-r-md' : '-ml-px' }}
                                       {{ $statusFilter === $value
                                           ? 'bg-purple-600 text-white border-purple-600 dark:border-purple-600'
                                           : 'bg-white dark:bg-zinc-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-zinc-700' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Action bar --}}
        <div class="p-4 border-b border-gray-200 dark:border-zinc-700 flex flex-wrap items-center justify-between gap-3 sticky top-0 bg-white dark:bg-zinc-800 z-10">
            <div class="text-sm text-gray-700 dark:text-gray-300">
                <strong>{{ $selectionCount }}</strong> user{{ $selectionCount === 1 ? '' : 's' }} selected
                @if($selectionCount > 0)
                    <button wire:click="clearSelection" class="ml-2 text-xs text-gray-500 dark:text-gray-400 hover:underline">clear</button>
                @endif
            </div>
            @if($this->can('users.edit'))
                <div class="flex flex-wrap gap-2">
                    <button wire:click="openAssignModal" {{ $selectionCount === 0 ? 'disabled' : '' }}
                            class="px-3 py-1.5 text-sm bg-purple-600 text-white rounded-md hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        Assign Roles &amp; Programs
                    </button>
                    <button wire:click="openActivateModal" {{ $selectionCount === 0 ? 'disabled' : '' }}
                            class="px-3 py-1.5 text-sm bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        Activate
                    </button>
                    <button wire:click="openDeactivateModal" {{ $selectionCount === 0 ? 'disabled' : '' }}
                            class="px-3 py-1.5 text-sm bg-orange-600 text-white rounded-md hover:bg-orange-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        Deactivate
                    </button>
                    @if($this->can('users.delete'))
                        <button wire:click="openDeleteModal" {{ $selectionCount === 0 ? 'disabled' : '' }}
                                class="px-3 py-1.5 text-sm bg-red-600 text-white rounded-md hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed">
                            Delete
                        </button>
                    @endif
                </div>
            @endif
        </div>

        {{-- Users Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                <thead class="bg-gray-50 dark:bg-zinc-700">
                    <tr>
                        <th class="px-4 py-3 w-10"></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Roles</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-700">
                    @forelse($users as $user)
                        <tr class="{{ in_array($user->id, $selectedUsers) ? 'bg-purple-50 dark:bg-purple-900/20' : ($user->active ? '' : 'bg-gray-50 dark:bg-zinc-700/30') }}">
                            <td class="px-4 py-3">
                                <input type="checkbox" wire:model.live="selectedUsers" value="{{ $user->id }}"
                                       class="rounded border-gray-300 dark:border-zinc-600 text-purple-600 focus:ring-purple-500">
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-sm">
                                <span class="font-medium text-gray-900 dark:text-white">{{ $user->name }}</span>
                                @if($user->id === auth()->id())
                                    <span class="ml-2 inline-flex px-2 py-0.5 text-xs font-semibold rounded-full bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">You</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $user->email }}</td>
                            <td class="px-6 py-3 whitespace-nowrap">
                                @if($user->active)
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">Active</span>
                                @else
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200">Inactive</span>
                                @endif
                            </td>
                            <td class="px-6 py-3">
                                <div class="flex flex-wrap gap-1">
                                    @forelse($user->roles as $role)
                                        <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200">{{ $role->display_name }}</span>
                                    @empty
                                        <span class="text-xs text-gray-400">—</span>
                                    @endforelse
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">No users found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="px-6 py-4 bg-gray-50 dark:bg-zinc-700 border-t border-gray-200 dark:border-zinc-600">
                {{ $users->links() }}
            </div>
        @endif
    </div>

    {{-- Assign modal --}}
    @if($showAssignModal)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50" wire:click.self="$set('showAssignModal', false)">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                <div class="bg-purple-600 text-white p-4 rounded-t-lg flex justify-between items-center">
                    <h3 class="font-bold">Assign Roles &amp; Programs to {{ $selectionCount }} user(s)</h3>
                    <button wire:click="$set('showAssignModal', false)" class="text-purple-200 hover:text-white">✕</button>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Action</label>
                        <div class="flex gap-4">
                            <label class="inline-flex items-center">
                                <input type="radio" wire:model="assignAction" value="add" class="text-purple-600 focus:ring-purple-500">
                                <span class="ml-2 text-sm">Add to existing</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" wire:model="assignAction" value="replace" class="text-purple-600 focus:ring-purple-500">
                                <span class="ml-2 text-sm">Replace existing</span>
                            </label>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Roles</label>
                            <div class="space-y-2 max-h-48 overflow-y-auto border border-gray-300 dark:border-zinc-600 rounded-md p-3 bg-white dark:bg-zinc-800">
                                @foreach($roles as $role)
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" wire:model="bulkRoles" value="{{ $role->id }}"
                                               class="rounded border-gray-300 dark:border-zinc-600 text-purple-600 focus:ring-purple-500">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $role->display_name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Programs</label>
                            <div class="space-y-2 max-h-48 overflow-y-auto border border-gray-300 dark:border-zinc-600 rounded-md p-3 bg-white dark:bg-zinc-800">
                                @foreach($programs as $program)
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" wire:model="bulkPrograms" value="{{ $program->id }}"
                                               class="rounded border-gray-300 dark:border-zinc-600 text-purple-600 focus:ring-purple-500">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $program->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-zinc-700 px-6 py-4 rounded-b-lg flex justify-end gap-2">
                    <button wire:click="$set('showAssignModal', false)" class="px-4 py-2 bg-gray-200 dark:bg-zinc-600 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-300">Cancel</button>
                    <button wire:click="applyAssignment" class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700">Apply</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Deactivate confirm --}}
    @if($showDeactivateModal)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50" wire:click.self="$set('showDeactivateModal', false)">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-md w-full mx-4">
                <div class="bg-orange-600 text-white p-4 rounded-t-lg flex justify-between items-center">
                    <h3 class="font-bold">Deactivate {{ $selectionCount }} user(s)?</h3>
                    <button wire:click="$set('showDeactivateModal', false)" class="text-orange-200 hover:text-white">✕</button>
                </div>
                <div class="p-6 text-sm text-gray-700 dark:text-gray-300 space-y-2">
                    <p>Inactive users cannot log in. They remain in the system and can be reactivated later.</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Your own account is automatically excluded.</p>
                </div>
                <div class="bg-gray-50 dark:bg-zinc-700 px-6 py-4 rounded-b-lg flex justify-end gap-2">
                    <button wire:click="$set('showDeactivateModal', false)" class="px-4 py-2 bg-gray-200 dark:bg-zinc-600 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-300">Cancel</button>
                    <button wire:click="applyDeactivate" class="px-4 py-2 bg-orange-600 text-white rounded-md hover:bg-orange-700">Deactivate</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Activate confirm --}}
    @if($showActivateModal)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50" wire:click.self="$set('showActivateModal', false)">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-md w-full mx-4">
                <div class="bg-green-600 text-white p-4 rounded-t-lg flex justify-between items-center">
                    <h3 class="font-bold">Activate {{ $selectionCount }} user(s)?</h3>
                    <button wire:click="$set('showActivateModal', false)" class="text-green-200 hover:text-white">✕</button>
                </div>
                <div class="p-6 text-sm text-gray-700 dark:text-gray-300">
                    <p>These users will be able to log in again.</p>
                </div>
                <div class="bg-gray-50 dark:bg-zinc-700 px-6 py-4 rounded-b-lg flex justify-end gap-2">
                    <button wire:click="$set('showActivateModal', false)" class="px-4 py-2 bg-gray-200 dark:bg-zinc-600 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-300">Cancel</button>
                    <button wire:click="applyActivate" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">Activate</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Delete confirm --}}
    @if($showDeleteModal)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50" wire:click.self="$set('showDeleteModal', false)">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-md w-full mx-4">
                <div class="bg-red-600 text-white p-4 rounded-t-lg flex justify-between items-center">
                    <h3 class="font-bold">Delete {{ $selectionCount }} user(s)?</h3>
                    <button wire:click="$set('showDeleteModal', false)" class="text-red-200 hover:text-white">✕</button>
                </div>
                <div class="p-6 text-sm text-gray-700 dark:text-gray-300 space-y-2">
                    <p>This is permanent. Their data and sessions will be removed.</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Your own account is automatically excluded. Consider deactivating instead if you may need to restore access.</p>
                </div>
                <div class="bg-gray-50 dark:bg-zinc-700 px-6 py-4 rounded-b-lg flex justify-end gap-2">
                    <button wire:click="$set('showDeleteModal', false)" class="px-4 py-2 bg-gray-200 dark:bg-zinc-600 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-300">Cancel</button>
                    <button wire:click="applyDelete" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">Delete</button>
                </div>
            </div>
        </div>
    @endif
</div>
