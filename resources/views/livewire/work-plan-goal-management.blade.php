<div class="max-w-7xl mx-auto p-6">
    <div class="bg-white dark:bg-zinc-800 shadow-lg rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-orange-600 text-white p-6">
            <h1 class="text-2xl font-bold">Work Plan Goal Management</h1>
            <p class="text-orange-100 mt-2">Track goals and measure progress against targets</p>
        </div>

        <!-- Search and Actions -->
        <div class="p-4 bg-gray-50 dark:bg-zinc-700 border-b border-gray-200 dark:border-zinc-600">
            <div class="flex items-center justify-between">
                <div class="flex-1 max-w-md">
                    <input wire:model.live="search" placeholder="Search goals..." class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-orange-500" />
                </div>
                <button wire:click="showCreateGoal" class="ml-4 px-4 py-2 bg-orange-600 text-white rounded-md hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500">Create Goal</button>
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
            <div class="p-6 bg-orange-50 dark:bg-orange-900/20 border-b border-gray-200 dark:border-zinc-600">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">{{ $showCreateForm ? 'Create New Goal' : 'Edit Goal' }}</h3>
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
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-600">
                <thead class="bg-gray-50 dark:bg-zinc-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Goal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Work Plan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Service</th>
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
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $goal->goal_name }}</div>
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
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $goal->service->name ?? 'Not Associated' }}</td>
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
                                <div class="flex gap-2">
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
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
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
    </div>
</div>