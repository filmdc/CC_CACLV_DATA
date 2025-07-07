<div class="max-w-7xl mx-auto p-6">
    <div class="bg-white dark:bg-zinc-800 shadow-lg rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-blue-600 text-white p-6">
            <h1 class="text-2xl font-bold">Contract Report Due Dates</h1>
            <p class="text-blue-100 mt-2">Manage contract reporting requirements and reminders</p>
        </div>

        <!-- Filters and Actions -->
        <div class="p-4 bg-gray-50 dark:bg-zinc-700 border-b border-gray-200 dark:border-zinc-600">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-col sm:flex-row gap-4 flex-1">
                    <div class="flex-1 max-w-md">
                        <input type="text" wire:model.live="search" placeholder="Search reports or contracts..." 
                               class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="sm:w-48">
                        <select wire:model.live="contractFilter" 
                                class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Contracts</option>
                            @foreach($contracts as $contract)
                                <option value="{{ $contract->id }}">{{ $contract->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:w-32">
                        <select wire:model.live="statusFilter" 
                                class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="completed">Completed</option>
                            <option value="overdue">Overdue</option>
                        </select>
                    </div>
                </div>
                <button wire:click="showCreateDueDate" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    Add Report Due Date
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
            <div class="p-6 bg-blue-50 dark:bg-blue-900/20 border-b border-gray-200 dark:border-zinc-600">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                    {{ $showCreateForm ? 'Add New Report Due Date' : 'Edit Report Due Date' }}
                </h3>

                <form wire:submit="{{ $showCreateForm ? 'createDueDate' : 'updateDueDate' }}" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="contract_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Contract <span class="text-red-500">*</span>
                            </label>
                            <select wire:model="contract_id" id="contract_id" 
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Contract</option>
                                @foreach($contracts as $contract)
                                    <option value="{{ $contract->id }}">
                                        {{ $contract->title }} - {{ $contract->program->name ?? 'N/A' }}
                                        @if($contract->program && $contract->program->contract_management_only)
                                            (Contract Only)
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('contract_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="report_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Report Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" wire:model="report_name" id="report_name" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('report_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="due_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Due Date <span class="text-red-500">*</span>
                            </label>
                            <input type="date" wire:model="due_date" id="due_date" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('due_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Active
                            </label>
                            <div class="flex items-center">
                                <input type="checkbox" wire:model="active" id="active" 
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="active" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                    Enable this due date
                                </label>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                        <textarea wire:model="description" id="description" rows="3" 
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                        @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Reminder Intervals (days before due date)</label>
                        <div class="grid grid-cols-3 md:grid-cols-6 gap-2">
                            @foreach($reminderIntervalOptions as $days => $label)
                                <label class="flex items-center">
                                    <input type="checkbox" wire:model="reminder_intervals" value="{{ $days }}" 
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('reminder_intervals') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Assign Users for Reminders</label>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 max-h-40 overflow-y-auto border border-gray-300 dark:border-zinc-600 rounded-md p-3">
                            @foreach($users as $user)
                                <label class="flex items-center">
                                    <input type="checkbox" wire:model="assigned_users" value="{{ $user->id }}" 
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $user->name }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('assigned_users') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" wire:click="$set('showCreateForm', false); $set('showEditForm', false)" 
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-zinc-700 border border-gray-300 dark:border-zinc-600 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-600 focus:outline-none focus:ring-2 focus:ring-gray-500">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            {{ $showCreateForm ? 'Add Due Date' : 'Update Due Date' }}
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <!-- Due Dates Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-600">
                <thead class="bg-gray-50 dark:bg-zinc-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Report Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Contract</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Due Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Assigned Users</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-600">
                    @forelse($dueDates as $dueDate)
                        <tr class="{{ $dueDate->isOverdue() ? 'bg-red-50 dark:bg-red-900/10' : '' }}">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $dueDate->report_name }}</div>
                                @if($dueDate->description)
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ Str::limit($dueDate->description, 50) }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">{{ $dueDate->contract->title ?? 'N/A' }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $dueDate->contract->program->name ?? 'N/A' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">{{ $dueDate->due_date?->format('M j, Y') ?? 'N/A' }}</div>
                                @if($dueDate->due_date)
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $dueDate->due_date->diffForHumans() }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                    {{ $dueDate->status === 'completed' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300' : 
                                       ($dueDate->isOverdue() ? 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-300' : 
                                        'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300') }}">
                                    {{ $dueDate->isOverdue() && $dueDate->status !== 'completed' ? 'Overdue' : ucfirst($dueDate->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">
                                    {{ $dueDate->assignedUsers->count() }} user(s)
                                </div>
                                @if($dueDate->assignedUsers->count() > 0)
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $dueDate->assignedUsers->pluck('name')->take(2)->join(', ') }}
                                        @if($dueDate->assignedUsers->count() > 2)
                                            +{{ $dueDate->assignedUsers->count() - 2 }} more
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <button wire:click="showEditDueDate({{ $dueDate->id }})" 
                                            class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300">Edit</button>
                                    <button wire:click="toggleStatus({{ $dueDate->id }})" 
                                            class="text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300">
                                        {{ $dueDate->status === 'completed' ? 'Reopen' : 'Complete' }}
                                    </button>
                                    <button wire:click="deleteDueDate({{ $dueDate->id }})" 
                                            onclick="return confirm('Are you sure you want to delete this due date?')"
                                            class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">Delete</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                No report due dates found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($dueDates->hasPages())
            <div class="px-6 py-3 border-t border-gray-200 dark:border-zinc-600">
                {{ $dueDates->links() }}
            </div>
        @endif
    </div>
</div>