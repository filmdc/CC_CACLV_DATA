<div class="w-full px-0 py-6">
    <div class="bg-white dark:bg-zinc-800 shadow-lg rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-teal-600 text-white p-6">
            <h1 class="text-2xl font-bold">Work Plan Management</h1>
            <p class="text-teal-100 mt-2">Manage annual work plans and track progress</p>
        </div>

        <!-- Search and Actions -->
        @if(!$showCreateForm && !$showEditForm)
        <div class="p-4 bg-gray-50 dark:bg-zinc-700 border-b border-gray-200 dark:border-zinc-600">
            <div class="flex items-center justify-between">
                <div class="flex-1 max-w-md">
                    <input wire:model.live="search" placeholder="Search work plans..." class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500" />
                </div>
                <button wire:click="showCreateWorkPlan" class="ml-4 px-4 py-2 bg-teal-600 text-white rounded-md hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500">Create Work Plan</button>
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
            <div class="p-6 bg-teal-50 dark:bg-teal-900/20 border-b border-gray-200 dark:border-zinc-600">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ $showCreateForm ? 'Create New Work Plan' : 'Edit Work Plan' }}</h3>
                    <button wire:click="cancelForm" class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <form wire:submit="{{ $showCreateForm ? 'createWorkPlan' : 'updateWorkPlan' }}" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Work Plan Name *</label>
                            <input wire:model="name" class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500" />
                            @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Program *</label>
                            <select wire:model="program_id" class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                                <option value="">Select Program</option>
                                @foreach($editablePrograms as $program)
                                    <option value="{{ $program->id }}">{{ $program->name }}</option>
                                @endforeach
                            </select>
                            @error('program_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Year *</label>
                            <input type="number" min="2020" max="2030" wire:model="year" class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500" />
                            @error('year') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" wire:model="active" class="mr-2 rounded border-gray-300">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Work plan is active</span>
                            </label>
                            @error('active') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                        <x-wysiwyg-editor model="description" :rows="3" placeholder="Describe the work plan objectives and goals..." id="workplan_description" />
                        @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end gap-2 mt-6">
                        <button type="button" wire:click="cancelForm" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-zinc-700 border border-gray-300 dark:border-zinc-600 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-600 focus:outline-none focus:ring-2 focus:ring-gray-500">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-teal-600 border border-transparent rounded-md hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500">
                            {{ $showCreateForm ? 'Create Work Plan' : 'Update Work Plan' }}
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <!-- Work Plans Table -->
        @if(!$showCreateForm && !$showEditForm)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-600">
                <thead class="bg-gray-50 dark:bg-zinc-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Work Plan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Program</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Year</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Goals</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-600">
                    @forelse($workPlans as $workPlan)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">
                                        <button wire:click="viewWorkPlan({{ $workPlan->id }})" class="text-teal-600 dark:text-teal-400 hover:text-teal-800 dark:hover:text-teal-300 hover:underline">
                                            {{ $workPlan->name }}
                                        </button>
                                    </div>
                                    @if($workPlan->description)
                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ Str::limit($workPlan->description, 50) }}</div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $workPlan->program->name ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $workPlan->year }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm">
                                    {{ $workPlan->goals->count() }} {{ Str::plural('goal', $workPlan->goals->count()) }}
                                    @if($workPlan->goals->count() > 0)
                                        <div class="text-gray-500 dark:text-gray-400">
                                            Avg Progress: {{ number_format($workPlan->goals->avg('progress_percentage'), 1) }}%
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($workPlan->active)
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">Active</span>
                                @else
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200">Inactive</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex gap-2">
                                    <button wire:click="viewWorkPlan({{ $workPlan->id }})" class="text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300">
                                        View
                                    </button>
                                    @if(in_array($workPlan->program_id, $editableProgramIds))
                                        <button wire:click="showEditWorkPlan({{ $workPlan->id }})" class="text-teal-600 dark:text-teal-400 hover:text-teal-900 dark:hover:text-teal-300">
                                            Edit
                                        </button>
                                        <button
                                            wire:click="toggleWorkPlanStatus({{ $workPlan->id }})"
                                            class="{{ $workPlan->active ? 'text-yellow-600 dark:text-yellow-400 hover:text-yellow-900 dark:hover:text-yellow-300' : 'text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300' }}"
                                        >
                                            {{ $workPlan->active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                        <button
                                            wire:click="deleteWorkPlan({{ $workPlan->id }})"
                                            onclick="return confirm('Are you sure you want to delete this work plan?')"
                                            class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"
                                        >
                                            Delete
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                No work plans found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($workPlans->hasPages())
            <div class="px-6 py-3 border-t border-gray-200 dark:border-zinc-600">
                {{ $workPlans->links() }}
            </div>
        @endif
        @endif

        <!-- View Modal -->
        @if($showViewModal && $selectedViewWorkPlan)
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50" wire:click.self="$set('showViewModal', false)">
                <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-5xl w-full m-4 max-h-[90vh] overflow-y-auto">
                    <!-- Modal Header -->
                    <div class="bg-teal-600 text-white p-6 rounded-t-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-xl font-semibold">{{ $selectedViewWorkPlan->name }}</h3>
                                <p class="text-teal-100 mt-1">{{ $selectedViewWorkPlan->program->name }} - {{ $selectedViewWorkPlan->year }}</p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="px-3 py-1 text-sm rounded-full {{ $selectedViewWorkPlan->active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $selectedViewWorkPlan->active ? 'Active' : 'Inactive' }}
                                </span>
                                <button wire:click="$set('showViewModal', false)" class="text-white hover:text-gray-300">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Content -->
                    <div class="p-6">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Work Plan Information -->
                            <div class="space-y-6">
                                <div>
                                    <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-3">Work Plan Information</h4>
                                    <div class="bg-gray-50 dark:bg-zinc-700 p-4 rounded-lg space-y-3">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                                            <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $selectedViewWorkPlan->name }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Program</label>
                                            <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $selectedViewWorkPlan->program->name }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Year</label>
                                            <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $selectedViewWorkPlan->year }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                                            <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $selectedViewWorkPlan->active ? 'Active' : 'Inactive' }}</p>
                                        </div>
                                        @if($selectedViewWorkPlan->description)
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                                                <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $selectedViewWorkPlan->description }}</p>
                                            </div>
                                        @endif
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Created</label>
                                            <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $selectedViewWorkPlan->created_at->format('M j, Y g:i A') }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Last Updated</label>
                                            <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $selectedViewWorkPlan->updated_at->format('M j, Y g:i A') }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Program Information -->
                                <div>
                                    <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-3">Program Information</h4>
                                    <div class="bg-gray-50 dark:bg-zinc-700 p-4 rounded-lg space-y-3">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Program Name</label>
                                            <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $selectedViewWorkPlan->program->name }}</p>
                                        </div>
                                        @if($selectedViewWorkPlan->program->description)
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Program Description</label>
                                                <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $selectedViewWorkPlan->program->description }}</p>
                                            </div>
                                        @endif
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Program Status</label>
                                            <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $selectedViewWorkPlan->program->active ? 'Active' : 'Inactive' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Goals Information -->
                            <div class="space-y-6">
                                <div>
                                    <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-3">Goals ({{ $selectedViewWorkPlan->goals->count() }})</h4>
                                    <div class="bg-gray-50 dark:bg-zinc-700 p-4 rounded-lg">
                                        @if($selectedViewWorkPlan->goals->count() > 0)
                                            <div class="space-y-4">
                                                @foreach($selectedViewWorkPlan->goals as $goal)
                                                    <div class="border-b border-gray-200 dark:border-zinc-600 pb-4 last:border-b-0">
                                                        <div class="flex items-start justify-between">
                                                            <div class="flex-1">
                                                                <h5 class="font-medium text-gray-900 dark:text-white">{{ $goal->goal_name }}</h5>
                                                                @if($goal->description)
                                                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $goal->description }}</p>
                                                                @endif
                                                                <div class="mt-2 flex items-center space-x-4 text-sm">
                                                                    <span class="text-gray-600 dark:text-gray-400">Target: {{ number_format($goal->target_number) }}</span>
                                                                    <span class="text-gray-600 dark:text-gray-400">Actual: {{ number_format($goal->actual_number) }}</span>
                                                                    <span class="font-medium {{ $goal->progress_percentage >= 100 ? 'text-green-600 dark:text-green-400' : ($goal->progress_percentage >= 75 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400') }}">
                                                                        {{ number_format($goal->progress_percentage, 1) }}%
                                                                    </span>
                                                                </div>
                                                                @if($goal->service)
                                                                    <p class="text-sm text-blue-600 dark:text-blue-400 mt-1">Service: {{ $goal->service->name }}</p>
                                                                @else
                                                                    <p class="text-sm text-purple-600 dark:text-purple-400 mt-1">Program-wide goal</p>
                                                                @endif
                                                            </div>
                                                            <div class="ml-4">
                                                                <div class="flex items-center">
                                                                    <div class="w-16 bg-gray-200 dark:bg-zinc-600 rounded-full h-2">
                                                                        <div class="bg-teal-600 h-2 rounded-full" style="width: {{ min($goal->progress_percentage, 100) }}%"></div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="text-center py-8">
                                                <p class="text-gray-500 dark:text-gray-400">No goals defined for this work plan yet.</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Summary Statistics -->
                                @if($selectedViewWorkPlan->goals->count() > 0)
                                    <div>
                                        <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-3">Summary Statistics</h4>
                                        <div class="bg-gray-50 dark:bg-zinc-700 p-4 rounded-lg space-y-3">
                                            <div class="grid grid-cols-2 gap-4">
                                                <div class="text-center">
                                                    <div class="text-2xl font-bold text-teal-600 dark:text-teal-400">{{ $selectedViewWorkPlan->goals->count() }}</div>
                                                    <div class="text-sm text-gray-600 dark:text-gray-400">Total Goals</div>
                                                </div>
                                                <div class="text-center">
                                                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $selectedViewWorkPlan->goals->where('progress_percentage', '>=', 100)->count() }}</div>
                                                    <div class="text-sm text-gray-600 dark:text-gray-400">Completed</div>
                                                </div>
                                            </div>
                                            <div class="grid grid-cols-2 gap-4">
                                                <div class="text-center">
                                                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($selectedViewWorkPlan->goals->avg('progress_percentage'), 1) }}%</div>
                                                    <div class="text-sm text-gray-600 dark:text-gray-400">Average Progress</div>
                                                </div>
                                                <div class="text-center">
                                                    <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($selectedViewWorkPlan->goals->sum('target_number')) }}</div>
                                                    <div class="text-sm text-gray-600 dark:text-gray-400">Total Target</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="flex justify-end mt-6">
                            @if($selectedViewWorkPlan->goals->count() > 0 && in_array($selectedViewWorkPlan->program_id, $editableProgramIds))
                                <button wire:click="syncWorkPlanGoals({{ $selectedViewWorkPlan->id }})"
                                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 mr-3">
                                    Sync Goals
                                </button>
                            @endif
                            <button wire:click="$set('showViewModal', false)"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-zinc-600 border border-gray-300 dark:border-zinc-500 rounded-md hover:bg-gray-50 dark:hover:bg-zinc-500 focus:outline-none focus:ring-2 focus:ring-teal-500">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>