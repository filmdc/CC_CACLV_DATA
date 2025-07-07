<div class="max-w-7xl mx-auto p-6">
    <div class="bg-white dark:bg-zinc-800 shadow-lg rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-purple-600 text-white p-6">
            <h1 class="text-2xl font-bold">Program Management</h1>
            <p class="text-purple-100 mt-2">Manage program configurations and settings</p>
        </div>

        <!-- Search and Actions -->
        <div class="p-4 bg-gray-50 dark:bg-zinc-700 border-b border-gray-200 dark:border-zinc-600">
            <div class="flex items-center justify-between">
                <div class="flex-1 max-w-md">
                    <input type="text" wire:model.live="search" placeholder="Search programs..." 
                           class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 bg-white dark:bg-zinc-800 text-gray-900 dark:text-white">
                </div>
                <button wire:click="showCreateProgram" 
                        class="ml-4 px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500">
                    Create Program
                </button>
            </div>
        </div>

        <!-- Success/Error Messages -->
        @if (session()->has('success'))
            <div class="p-4 bg-green-50 dark:bg-green-900/20 border-l-4 border-green-400">
                <div class="flex">
                    <div class="ml-3">
                        <p class="text-sm text-green-700 dark:text-green-300">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="p-4 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-400">
                <div class="flex">
                    <div class="ml-3">
                        <p class="text-sm text-red-700 dark:text-red-300">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Create/Edit Form -->
        @if($showCreateForm || $showEditForm)
            <div class="p-6 bg-purple-50 dark:bg-purple-900/20 border-b border-gray-200 dark:border-zinc-600">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">{{ $showCreateForm ? 'Create New Program' : 'Edit Program' }}</h3>

                <form wire:submit="{{ $showCreateForm ? 'createProgram' : 'updateProgram' }}" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Program Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" wire:model="name" id="name" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 bg-white dark:bg-zinc-800 text-gray-900 dark:text-white">
                            @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="program_manager_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Program Manager</label>
                            <select wire:model="program_manager_id" id="program_manager_id" 
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 bg-white dark:bg-zinc-800 text-gray-900 dark:text-white">
                                <option value="">Select Manager</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                            @error('program_manager_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start Date</label>
                            <input type="date" wire:model="start_date" id="start_date" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 bg-white dark:bg-zinc-800 text-gray-900 dark:text-white">
                            @error('start_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">End Date</label>
                            <input type="date" wire:model="end_date" id="end_date" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 bg-white dark:bg-zinc-800 text-gray-900 dark:text-white">
                            @error('end_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="md:col-span-2 space-y-3">
                            <div class="flex items-center">
                                <input type="checkbox" wire:model="active" id="active" class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-800">
                                <label for="active" class="ml-2 block text-sm text-gray-900 dark:text-white">Program is active</label>
                            </div>
                            <div class="flex items-center">
                                <input type="checkbox" wire:model="housing_module_enabled" id="housing_module_enabled" class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-800">
                                <label for="housing_module_enabled" class="ml-2 block text-sm text-gray-900 dark:text-white">Enable housing module</label>
                            </div>
                            <div class="flex items-center">
                                <input type="checkbox" wire:model="contract_management_only" id="contract_management_only" class="h-4 w-4 text-orange-600 focus:ring-orange-500 border-gray-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-800">
                                <label for="contract_management_only" class="ml-2 block text-sm text-gray-900 dark:text-white">
                                    <span class="text-orange-600 dark:text-orange-400 font-medium">Contract management only</span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">Internal program for contract management - hidden from end users</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                        <textarea wire:model="description" id="description" rows="3" 
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"></textarea>
                        @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                        <textarea wire:model="notes" id="notes" rows="3" 
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"></textarea>
                        @error('notes') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" wire:click="$set('showCreateForm', false); $set('showEditForm', false)" 
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-zinc-700 border border-gray-300 dark:border-zinc-600 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-600 focus:outline-none focus:ring-2 focus:ring-gray-500">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 text-sm font-medium text-white bg-purple-600 border border-transparent rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500">
                            {{ $showCreateForm ? 'Create Program' : 'Update Program' }}
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <!-- Programs Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-600">
                <thead class="bg-gray-50 dark:bg-zinc-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Program Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Manager</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Services</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Contracts</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Duration</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-600">
                    @forelse($programs as $program)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    <div class="flex items-center space-x-2">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $program->name }}</span>
                                        @if($program->contract_management_only)
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-orange-100 dark:bg-orange-900/20 text-orange-800 dark:text-orange-300" title="Contract Management Only - Hidden from end users">
                                                Contract Only
                                            </span>
                                        @endif
                                        @if($program->housing_module_enabled)
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 dark:bg-blue-900/20 text-blue-800 dark:text-blue-300" title="Housing Module Enabled">
                                                Housing
                                            </span>
                                        @endif
                                    </div>
                                    @if($program->description)
                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ Str::limit($program->description, 50) }}</div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $program->programManager->name ?? 'Unassigned' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($program->active)
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">Active</span>
                                @else
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200">Inactive</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $program->services_count ?? $program->services->count() }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $program->contracts_count ?? $program->contracts->count() }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    @if($program->start_date)
                                        <div>Start: {{ $program->start_date->format('M j, Y') }}</div>
                                    @endif
                                    @if($program->end_date)
                                        <div>End: {{ $program->end_date->format('M j, Y') }}</div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <button wire:click="showEditProgram({{ $program->id }})" 
                                            class="text-purple-600 dark:text-purple-400 hover:text-purple-900 dark:hover:text-purple-300">Edit</button>
                                    <button wire:click="toggleProgramStatus({{ $program->id }})"
                                            class="text-{{ $program->active ? 'yellow' : 'green' }}-600 dark:text-{{ $program->active ? 'yellow' : 'green' }}-400 hover:text-{{ $program->active ? 'yellow' : 'green' }}-900 dark:hover:text-{{ $program->active ? 'yellow' : 'green' }}-300">
                                        {{ $program->active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                    <button wire:click="deleteProgram({{ $program->id }})" 
                                            onclick="return confirm('Are you sure you want to delete this program?')"
                                            class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">Delete</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                No programs found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($programs->hasPages())
            <div class="px-6 py-3 border-t border-gray-200 dark:border-zinc-600 bg-gray-50 dark:bg-zinc-700">
                {{ $programs->links() }}
            </div>
        @endif
    </div>
</div>