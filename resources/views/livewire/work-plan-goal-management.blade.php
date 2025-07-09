<div class="max-w-7xl mx-auto p-6">
    <div class="bg-white dark:bg-zinc-800 shadow-lg rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-orange-600 text-white p-6">
            <h1 class="text-2xl font-bold">Work Plan Goal Management</h1>
            <p class="text-orange-100 mt-2">Track goals and measure progress against targets</p>
        </div>

        <!-- Search and Actions -->
        @if(!$showCreateForm && !$showEditForm)
        <div class="p-4 bg-gray-50 dark:bg-zinc-700 border-b border-gray-200 dark:border-zinc-600">
            <div class="flex items-center justify-between">
                <div class="flex-1 max-w-md">
                    <input wire:model.live="search" placeholder="Search goals..." class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-orange-500" />
                </div>
                <div class="flex space-x-2">
                    <button wire:click="syncAllGoalsWithServiceData" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500" title="Sync all goals with current service enrollment data">
                        Sync All Goals
                    </button>
                    <button wire:click="showCreateGoal" class="px-4 py-2 bg-orange-600 text-white rounded-md hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500">Create Goal</button>
                </div>
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
            <div class="p-6 bg-orange-50 dark:bg-orange-900/20 border-b border-gray-200 dark:border-zinc-600">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ $showCreateForm ? 'Create New Goal' : 'Edit Goal' }}</h3>
                    <button wire:click="$set('showCreateForm', false); $set('showEditForm', false)" class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <form wire:submit="{{ $showCreateForm ? 'createGoal' : 'updateGoal' }}" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Work Plan *</label>
                            <select wire:model="work_plan_id" class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-orange-500">
                                <option value="">Select Work Plan</option>
                                @foreach($workPlans as $workPlan)
                                    <option value="{{ $workPlan->id }}">{{ $workPlan->name }} ({{ $workPlan->year }})</option>
                                @endforeach
                            </select>
                            @error('work_plan_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Associated Service</label>
                            <select wire:model="service_id" class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-orange-500">
                                <option value="">Select Service (Optional)</option>
                                @foreach($services as $service)
                                    <option value="{{ $service->id }}">{{ $service->name }}</option>
                                @endforeach
                            </select>
                            @error('service_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Goal Name *</label>
                            <input wire:model="goal_name" class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-orange-500" />
                            @error('goal_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Target Number *</label>
                            <input type="number" min="0" wire:model="target_number" class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-orange-500" />
                            @error('target_number') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Actual Number</label>
                            <input type="number" min="0" wire:model="actual_number" class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-orange-500" />
                            @error('actual_number') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                        <textarea wire:model="description" rows="3" class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-orange-500"></textarea>
                        @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end gap-2 mt-6">
                        <button type="button" wire:click="$set('showCreateForm', false); $set('showEditForm', false)" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-zinc-700 border border-gray-300 dark:border-zinc-600 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-600 focus:outline-none focus:ring-2 focus:ring-gray-500">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-orange-600 border border-transparent rounded-md hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500">
                            {{ $showCreateForm ? 'Create Goal' : 'Update Goal' }}
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <!-- Goals Table -->
        @if(!$showCreateForm && !$showEditForm)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-600">
                <thead class="bg-gray-50 dark:bg-zinc-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Goal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Work Plan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Service</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Service Metrics</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Progress</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Target vs Actual</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-600">
                    @forelse($goals as $goal)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">
                                        <button wire:click="viewGoal({{ $goal->id }})" class="text-orange-600 dark:text-orange-400 hover:text-orange-800 dark:hover:text-orange-300 hover:underline">
                                            {{ $goal->goal_name }}
                                        </button>
                                    </div>
                                    @if($goal->description)
                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ Str::limit($goal->description, 50) }}</div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $goal->workPlan->name ?? 'N/A' }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $goal->workPlan->program->name ?? 'N/A' }} ({{ $goal->workPlan->year ?? 'N/A' }})
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                @if($goal->service)
                                    {{ $goal->service->name }}
                                @else
                                    <span class="text-blue-600 dark:text-blue-400 font-medium">All Program Services</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($goal->service)
                                    <div class="text-sm">
                                        <div class="font-medium text-gray-900 dark:text-white mb-1">{{ $goal->service->name }}</div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-blue-600 dark:text-blue-400">Enrolled:</span>
                                            <span class="font-medium">{{ $goal->service_enrollment_count }}</span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-green-600 dark:text-green-400">Completed:</span>
                                            <span class="font-medium">{{ $goal->service_completion_count }}</span>
                                        </div>
                                    </div>
                                @else
                                    <div class="text-sm">
                                        <div class="font-medium text-blue-600 dark:text-blue-400 mb-1">All Program Services</div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-blue-600 dark:text-blue-400">Total Enrolled:</span>
                                            <span class="font-medium">{{ $goal->getProgramWideServiceCount() }}</span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-green-600 dark:text-green-400">Total Completed:</span>
                                            <span class="font-medium">{{ $goal->getProgramWideCompletionCount() }}</span>
                                        </div>
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 bg-gray-200 dark:bg-zinc-600 rounded-full h-2">
                                        <div 
                                            class="bg-orange-600 h-2 rounded-full" 
                                            style="width: {{ min($goal->progress_percentage, 100) }}%"
                                        ></div>
                                    </div>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ number_format($goal->progress_percentage, 1) }}%</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ number_format($goal->actual_number) }} / {{ number_format($goal->target_number) }}</div>
                                    <div class="text-gray-500 dark:text-gray-400">
                                        @if($goal->progress_percentage >= 100)
                                            <span class="text-green-600 dark:text-green-400">Target Met!</span>
                                        @elseif($goal->progress_percentage >= 75)
                                            <span class="text-yellow-600 dark:text-yellow-400">Close to Target</span>
                                        @else
                                            <span class="text-red-600 dark:text-red-400">Needs Attention</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex gap-2 flex-wrap">
                                    <button wire:click="viewGoal({{ $goal->id }})" class="text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300">
                                        View
                                    </button>
                                    <button wire:click="syncGoalWithServiceData({{ $goal->id }})" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300" title="Sync with current service data">
                                        Sync
                                    </button>
                                    @if($goal->service)
                                        <button wire:click="makeGoalProgramWide({{ $goal->id }})" class="text-purple-600 dark:text-purple-400 hover:text-purple-900 dark:hover:text-purple-300" title="Track all services in program instead of just this one">
                                            Make Program-Wide
                                        </button>
                                    @endif
                                    <button wire:click="showEditGoal({{ $goal->id }})" class="text-orange-600 dark:text-orange-400 hover:text-orange-900 dark:hover:text-orange-300">
                                        Edit
                                    </button>
                                    <button 
                                        wire:click="deleteGoal({{ $goal->id }})"
                                        onclick="return confirm('Are you sure you want to delete this goal?')"
                                        class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                No goals found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($goals->hasPages())
            <div class="px-6 py-3 border-t border-gray-200 dark:border-zinc-600">
                {{ $goals->links() }}
            </div>
        @endif
        @endif

        <!-- View Modal -->
        @if($showViewModal && $selectedViewGoal)
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
                <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-5xl w-full m-4 max-h-[90vh] overflow-y-auto">
                    <!-- Modal Header -->
                    <div class="bg-orange-600 text-white p-6 rounded-t-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-xl font-semibold">{{ $selectedViewGoal->goal_name }}</h3>
                                <p class="text-orange-100 mt-1">{{ $selectedViewGoal->workPlan->name }} - {{ $selectedViewGoal->workPlan->program->name }}</p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="px-3 py-1 text-sm rounded-full {{ $selectedViewGoal->progress_percentage >= 100 ? 'bg-green-100 text-green-800' : ($selectedViewGoal->progress_percentage >= 75 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                    {{ number_format($selectedViewGoal->progress_percentage, 1) }}% Complete
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
                            <!-- Goal Information -->
                            <div class="space-y-6">
                                <div>
                                    <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-3">Goal Details</h4>
                                    <div class="bg-gray-50 dark:bg-zinc-700 p-4 rounded-lg space-y-3">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Goal Name</label>
                                            <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $selectedViewGoal->goal_name }}</p>
                                        </div>
                                        @if($selectedViewGoal->description)
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                                                <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $selectedViewGoal->description }}</p>
                                            </div>
                                        @endif
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Target Number</label>
                                                <p class="text-sm text-gray-900 dark:text-white mt-1">{{ number_format($selectedViewGoal->target_number) }}</p>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Actual Number</label>
                                                <p class="text-sm text-gray-900 dark:text-white mt-1">{{ number_format($selectedViewGoal->actual_number) }}</p>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Progress</label>
                                            <div class="mt-2">
                                                <div class="flex items-center justify-between mb-1">
                                                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ number_format($selectedViewGoal->progress_percentage, 1) }}%</span>
                                                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ number_format($selectedViewGoal->actual_number) }} / {{ number_format($selectedViewGoal->target_number) }}</span>
                                                </div>
                                                <div class="w-full bg-gray-200 dark:bg-zinc-600 rounded-full h-2">
                                                    <div class="bg-orange-600 h-2 rounded-full" style="width: {{ min($selectedViewGoal->progress_percentage, 100) }}%"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                                            <p class="text-sm mt-1 {{ $selectedViewGoal->progress_percentage >= 100 ? 'text-green-600 dark:text-green-400' : ($selectedViewGoal->progress_percentage >= 75 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400') }}">
                                                @if($selectedViewGoal->progress_percentage >= 100)
                                                    Target Met!
                                                @elseif($selectedViewGoal->progress_percentage >= 75)
                                                    Close to Target
                                                @else
                                                    Needs Attention
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Work Plan Information -->
                                <div>
                                    <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-3">Work Plan Information</h4>
                                    <div class="bg-gray-50 dark:bg-zinc-700 p-4 rounded-lg space-y-3">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Work Plan</label>
                                            <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $selectedViewGoal->workPlan->name }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Program</label>
                                            <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $selectedViewGoal->workPlan->program->name }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Year</label>
                                            <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $selectedViewGoal->workPlan->year }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Work Plan Status</label>
                                            <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $selectedViewGoal->workPlan->active ? 'Active' : 'Inactive' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Service Information -->
                            <div class="space-y-6">
                                @if($selectedViewGoal->service)
                                    <div>
                                        <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-3">Associated Service</h4>
                                        <div class="bg-gray-50 dark:bg-zinc-700 p-4 rounded-lg space-y-3">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Service Name</label>
                                                <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $selectedViewGoal->service->name }}</p>
                                            </div>
                                            @if($selectedViewGoal->service->description)
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Service Description</label>
                                                    <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $selectedViewGoal->service->description }}</p>
                                                </div>
                                            @endif
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Service Status</label>
                                                <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $selectedViewGoal->service->active ? 'Active' : 'Inactive' }}</p>
                                            </div>
                                            <div class="grid grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Current Enrollments</label>
                                                    <p class="text-sm text-blue-600 dark:text-blue-400 mt-1 font-medium">{{ $selectedViewGoal->service_enrollment_count }}</p>
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Completions</label>
                                                    <p class="text-sm text-green-600 dark:text-green-400 mt-1 font-medium">{{ $selectedViewGoal->service_completion_count }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Recent Enrollments -->
                                    @if($selectedViewGoal->service->serviceEnrollments && $selectedViewGoal->service->serviceEnrollments->count() > 0)
                                        <div>
                                            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-3">Recent Enrollments</h4>
                                            <div class="bg-gray-50 dark:bg-zinc-700 p-4 rounded-lg">
                                                <div class="space-y-3 max-h-60 overflow-y-auto">
                                                    @foreach($selectedViewGoal->service->serviceEnrollments->take(10) as $enrollment)
                                                        <div class="flex items-center justify-between border-b border-gray-200 dark:border-zinc-600 pb-2 last:border-b-0">
                                                            <div>
                                                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $enrollment->participant->full_name }}</p>
                                                                <p class="text-xs text-gray-500 dark:text-gray-400">Enrolled: {{ $enrollment->enrollment_date->format('M j, Y') }}</p>
                                                            </div>
                                                            <span class="px-2 py-1 text-xs rounded-full {{ $enrollment->status === 'enrolled' || $enrollment->status === 'in_progress' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : ($enrollment->status === 'completed' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200') }}">
                                                                {{ ucfirst($enrollment->status) }}
                                                            </span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @else
                                    <div>
                                        <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-3">Program-Wide Goal</h4>
                                        <div class="bg-gray-50 dark:bg-zinc-700 p-4 rounded-lg space-y-3">
                                            <div class="text-center py-4">
                                                <div class="text-purple-600 dark:text-purple-400 mb-2">
                                                    <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H3m5 0h6M7 7h10M7 11h10M7 15h10"></path>
                                                    </svg>
                                                </div>
                                                <p class="text-sm text-gray-900 dark:text-white font-medium">This goal tracks all services in the program</p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Not associated with a specific service</p>
                                            </div>
                                            <div class="grid grid-cols-2 gap-4">
                                                <div class="text-center">
                                                    <div class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ $selectedViewGoal->getProgramWideServiceCount() }}</div>
                                                    <div class="text-xs text-gray-500 dark:text-gray-400">Total Enrollments</div>
                                                </div>
                                                <div class="text-center">
                                                    <div class="text-lg font-bold text-green-600 dark:text-green-400">{{ $selectedViewGoal->getProgramWideCompletionCount() }}</div>
                                                    <div class="text-xs text-gray-500 dark:text-gray-400">Total Completions</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="flex justify-between items-center mt-6">
                            <div class="flex space-x-3">
                                <button wire:click="syncGoalWithServiceData({{ $selectedViewGoal->id }})" 
                                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    Sync with Service Data
                                </button>
                                @if($selectedViewGoal->service)
                                    <button wire:click="makeGoalProgramWide({{ $selectedViewGoal->id }})" 
                                            class="px-4 py-2 text-sm font-medium text-white bg-purple-600 border border-transparent rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500">
                                        Make Program-Wide
                                    </button>
                                @endif
                            </div>
                            <button wire:click="$set('showViewModal', false)" 
                                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-zinc-600 border border-gray-300 dark:border-zinc-500 rounded-md hover:bg-gray-50 dark:hover:bg-zinc-500 focus:outline-none focus:ring-2 focus:ring-orange-500">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>