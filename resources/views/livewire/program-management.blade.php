<div class="w-full px-0 py-6">
    <div class="bg-white dark:bg-zinc-800 shadow-lg rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-purple-600 text-white p-6">
            <h1 class="text-2xl font-bold">Program Management</h1>
            <p class="text-purple-100 mt-2">Manage program configurations and settings</p>
        </div>

        <!-- Search and Actions -->
        @if(!$showEditForm && !$showCreateForm)
            <div class="p-4 bg-gray-50 dark:bg-zinc-700 border-b border-gray-200 dark:border-zinc-600">
                <div class="flex items-center justify-between">
                    <div class="flex-1 max-w-md">
                        <input type="text" wire:model.live="search" placeholder="Search programs..." 
                               class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 bg-white dark:bg-zinc-800 text-gray-900 dark:text-white">
                    </div>
                    @if($this->can('programs.create'))
                        <button wire:click="showCreateProgram" 
                                class="ml-4 px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500">
                            Create Program
                        </button>
                    @endif
                </div>
            </div>
        @endif

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
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{ $showCreateForm ? 'Create New Program' : 'Edit Program' }}</h3>
                    <button wire:click="resetForm" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

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
                        <x-wysiwyg-editor model="description" :rows="3" placeholder="Describe this program's purpose and services..." id="program_description" />
                        @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                        <x-wysiwyg-editor model="notes" :rows="3" placeholder="Additional notes about this program..." id="program_notes" />
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
        @if(!$showEditForm && !$showCreateForm)
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
                                        <button wire:click="viewProgram({{ $program->id }})" 
                                                class="text-gray-900 dark:text-white hover:text-purple-600 dark:hover:text-purple-400 hover:underline cursor-pointer transition-colors duration-200">
                                            <span class="text-sm font-medium">{{ $program->name }}</span>
                                        </button>
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
                                    @if($this->can('programs.edit'))
                                        <button wire:click="showEditProgram({{ $program->id }})" 
                                                class="text-purple-600 dark:text-purple-400 hover:text-purple-900 dark:hover:text-purple-300">Edit</button>
                                        <button wire:click="toggleProgramStatus({{ $program->id }})"
                                                class="text-{{ $program->active ? 'yellow' : 'green' }}-600 dark:text-{{ $program->active ? 'yellow' : 'green' }}-400 hover:text-{{ $program->active ? 'yellow' : 'green' }}-900 dark:hover:text-{{ $program->active ? 'yellow' : 'green' }}-300">
                                            {{ $program->active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    @endif
                                    @if($this->can('programs.delete'))
                                        <button wire:click="deleteProgram({{ $program->id }})" 
                                                onclick="return confirm('Are you sure you want to delete this program?')"
                                                class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">Delete</button>
                                    @endif
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
        @endif
    </div>

    <!-- View Program Modal -->
    @if($showViewModal && $selectedProgram)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50" wire:click.self="$set('showViewModal', false)">
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">Program Details</h3>
                    <button wire:click="$set('showViewModal', false)" class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Program Information -->
                    <div class="bg-gray-50 dark:bg-zinc-700 p-4 rounded-lg">
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Program Information</h4>
                        <div class="space-y-3">
                            <div>
                                <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Program Name</label>
                                <p class="text-sm text-gray-900 dark:text-white">{{ $selectedProgram->name }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Description</label>
                                <p class="text-sm text-gray-900 dark:text-white">{{ $selectedProgram->description ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Program Manager</label>
                                <p class="text-sm text-gray-900 dark:text-white">{{ $selectedProgram->programManager->name ?? 'Unassigned' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Status</label>
                                <p class="text-sm text-gray-900 dark:text-white">
                                    @if($selectedProgram->active)
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">Active</span>
                                    @else
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200">Inactive</span>
                                    @endif
                                </p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Special Features</label>
                                <div class="flex flex-wrap gap-2 mt-1">
                                    @if($selectedProgram->contract_management_only)
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-orange-100 dark:bg-orange-900/20 text-orange-800 dark:text-orange-300">Contract Management Only</span>
                                    @endif
                                    @if($selectedProgram->housing_module_enabled)
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 dark:bg-blue-900/20 text-blue-800 dark:text-blue-300">Housing Module Enabled</span>
                                    @endif
                                    @if(!$selectedProgram->contract_management_only && !$selectedProgram->housing_module_enabled)
                                        <span class="text-sm text-gray-500 dark:text-gray-400">None</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Duration Information -->
                    <div class="bg-gray-50 dark:bg-zinc-700 p-4 rounded-lg">
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Duration Information</h4>
                        <div class="space-y-3">
                            <div>
                                <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Start Date</label>
                                <p class="text-sm text-gray-900 dark:text-white">
                                    {{ $selectedProgram->start_date ? $selectedProgram->start_date->format('F j, Y') : 'Not specified' }}
                                </p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600 dark:text-gray-400">End Date</label>
                                <p class="text-sm text-gray-900 dark:text-white">
                                    {{ $selectedProgram->end_date ? $selectedProgram->end_date->format('F j, Y') : 'Not specified' }}
                                </p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Created</label>
                                <p class="text-sm text-gray-900 dark:text-white">{{ $selectedProgram->created_at->format('F j, Y g:i A') }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Last Updated</label>
                                <p class="text-sm text-gray-900 dark:text-white">{{ $selectedProgram->updated_at->format('F j, Y g:i A') }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Services -->
                    <div class="bg-gray-50 dark:bg-zinc-700 p-4 rounded-lg">
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Services ({{ $selectedProgram->services->count() }})</h4>
                        @if($selectedProgram->services && $selectedProgram->services->count() > 0)
                            <div class="space-y-2">
                                @foreach($selectedProgram->services as $service)
                                    <div class="flex items-center justify-between p-2 bg-white dark:bg-zinc-800 rounded border">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $service->name }}</div>
                                            @if($service->description)
                                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ Str::limit($service->description, 50) }}</div>
                                            @endif
                                        </div>
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                            {{ $service->active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                                            {{ $service->active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400">No services configured</p>
                        @endif
                    </div>

                    <!-- Contracts -->
                    <div class="bg-gray-50 dark:bg-zinc-700 p-4 rounded-lg">
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Contracts ({{ $selectedProgram->contracts->count() }})</h4>
                        @if($selectedProgram->contracts && $selectedProgram->contracts->count() > 0)
                            <div class="space-y-2">
                                @foreach($selectedProgram->contracts as $contract)
                                    <div class="flex items-center justify-between p-2 bg-white dark:bg-zinc-800 rounded border">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $contract->title }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                Funder: {{ $contract->funder->name ?? 'N/A' }}
                                            </div>
                                        </div>
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                            {{ $contract->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' }}">
                                            {{ ucfirst($contract->status) }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400">No contracts</p>
                        @endif
                    </div>

                    <!-- Enrollment Statistics -->
                    <div class="bg-gray-50 dark:bg-zinc-700 p-4 rounded-lg md:col-span-2">
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Enrollment Statistics</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="bg-white dark:bg-zinc-800 p-3 rounded border">
                                <div class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Enrollments</div>
                                <div class="text-lg font-bold text-gray-900 dark:text-white">{{ $selectedProgram->programEnrollments->count() }}</div>
                            </div>
                            <div class="bg-white dark:bg-zinc-800 p-3 rounded border">
                                <div class="text-sm font-medium text-gray-600 dark:text-gray-400">Active Enrollments</div>
                                <div class="text-lg font-bold text-green-600 dark:text-green-400">{{ $selectedProgram->programEnrollments->where('status', 'active')->count() }}</div>
                            </div>
                            <div class="bg-white dark:bg-zinc-800 p-3 rounded border">
                                <div class="text-sm font-medium text-gray-600 dark:text-gray-400">Work Plans</div>
                                <div class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ $selectedProgram->workPlans->count() }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    @if($selectedProgram->notes)
                        <div class="bg-gray-50 dark:bg-zinc-700 p-4 rounded-lg md:col-span-2">
                            <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Notes</h4>
                            <p class="text-sm text-gray-900 dark:text-white whitespace-pre-wrap">{{ $selectedProgram->notes }}</p>
                        </div>
                    @endif
                </div>

                <div class="mt-6 flex justify-end">
                    <button wire:click="$set('showViewModal', false)" 
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-zinc-700 border border-gray-300 dark:border-zinc-600 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-600 focus:outline-none focus:ring-2 focus:ring-gray-500">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>