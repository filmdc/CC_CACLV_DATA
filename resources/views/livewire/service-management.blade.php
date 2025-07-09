<div class="max-w-7xl mx-auto p-6">
    <div class="bg-white dark:bg-zinc-800 shadow-lg rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-teal-600 text-white p-6">
            <h1 class="text-2xl font-bold">Service Management</h1>
            <p class="text-teal-100 mt-2">Manage program services and their configurations</p>
        </div>

        <!-- Search and Actions -->
        @if(!$showEditForm && !$showCreateForm && !$showViewModal)
        <div class="p-4 bg-gray-50 dark:bg-zinc-700 border-b dark:border-zinc-600">
            <div class="flex items-center justify-between">
                <div class="flex-1 max-w-md">
                    <input type="text" wire:model.live="search" placeholder="Search services or programs..." 
                           class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500">
                </div>
                <button wire:click="showCreateService" 
                        class="ml-4 px-4 py-2 bg-teal-600 text-white rounded-md hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500">
                    Create New Service
                </button>
            </div>
        </div>
        @endif

        <!-- Success/Error Messages -->
        @if (session()->has('success'))
            <div class="p-4 bg-green-50 border-l-4 border-green-400">
                <div class="flex">
                    <div class="ml-3">
                        <p class="text-sm text-green-700">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="p-4 bg-red-50 border-l-4 border-red-400">
                <div class="flex">
                    <div class="ml-3">
                        <p class="text-sm text-red-700">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Create Service Form -->
        @if ($showCreateForm)
            <div class="p-6 border-b">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Create New Service</h3>
                    <button wire:click="$set('showCreateForm', false)" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <form wire:submit="createService" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                                Service Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" wire:model="name" id="name" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500">
                            @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="program_id" class="block text-sm font-medium text-gray-700 mb-1">
                                Program <span class="text-red-500">*</span>
                            </label>
                            <select wire:model="program_id" id="program_id" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500">
                                <option value="">Select program...</option>
                                @foreach($programs as $program)
                                    <option value="{{ $program->id }}">{{ $program->name }}</option>
                                @endforeach
                            </select>
                            @error('program_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">
                                Start Date <span class="text-red-500">*</span>
                            </label>
                            <input type="date" wire:model="start_date" id="start_date" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500">
                            @error('start_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                            <input type="date" wire:model="end_date" id="end_date" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500">
                            @error('end_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="md:col-span-2 flex items-center">
                            <input type="checkbox" wire:model="active" id="active" class="h-4 w-4 text-teal-600 focus:ring-teal-500 border-gray-300 rounded">
                            <label for="active" class="ml-2 block text-sm text-gray-900">Active Service</label>
                        </div>
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea wire:model="description" id="description" rows="3" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500"></textarea>
                    </div>

                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                        <textarea wire:model="notes" id="notes" rows="2" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500"></textarea>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button type="button" wire:click="$set('showCreateForm', false)" 
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 border border-gray-300 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 text-sm font-medium text-white bg-teal-600 border border-transparent rounded-md hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500">
                            Create Service
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <!-- Edit Service Form -->
        @if ($showEditForm && $selectedService)
            <div class="p-6 border-b">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Edit Service: {{ $selectedService->name }}</h3>
                    <button wire:click="$set('showEditForm', false)" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <form wire:submit="updateService" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="edit_name" class="block text-sm font-medium text-gray-700 mb-1">
                                Service Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" wire:model="name" id="edit_name" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500">
                            @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="edit_program_id" class="block text-sm font-medium text-gray-700 mb-1">
                                Program <span class="text-red-500">*</span>
                            </label>
                            <select wire:model="program_id" id="edit_program_id" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500">
                                <option value="">Select program...</option>
                                @foreach($programs as $program)
                                    <option value="{{ $program->id }}">{{ $program->name }}</option>
                                @endforeach
                            </select>
                            @error('program_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="edit_start_date" class="block text-sm font-medium text-gray-700 mb-1">
                                Start Date <span class="text-red-500">*</span>
                            </label>
                            <input type="date" wire:model="start_date" id="edit_start_date" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500">
                            @error('start_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="edit_end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                            <input type="date" wire:model="end_date" id="edit_end_date" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500">
                            @error('end_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="md:col-span-2 flex items-center">
                            <input type="checkbox" wire:model="active" id="edit_active" class="h-4 w-4 text-teal-600 focus:ring-teal-500 border-gray-300 rounded">
                            <label for="edit_active" class="ml-2 block text-sm text-gray-900">Active Service</label>
                        </div>
                    </div>

                    <div>
                        <label for="edit_description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea wire:model="description" id="edit_description" rows="3" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500"></textarea>
                    </div>

                    <div>
                        <label for="edit_notes" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                        <textarea wire:model="notes" id="edit_notes" rows="2" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500"></textarea>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button type="button" wire:click="$set('showEditForm', false)" 
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 border border-gray-300 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 text-sm font-medium text-white bg-yellow-600 border border-transparent rounded-md hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-yellow-500">
                            Update Service
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <!-- Services List -->
        @if(!$showEditForm && !$showCreateForm && !$showViewModal)
        <div class="p-6">
            <div class="bg-white shadow overflow-hidden sm:rounded-md">
                <ul class="divide-y divide-gray-200">
                    @forelse ($services as $service)
                        <li class="px-6 py-4">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3">
                                        <h3 class="text-lg font-medium text-gray-900">
                                            <button wire:click="viewService({{ $service->id }})" class="text-teal-600 hover:text-teal-800 hover:underline">
                                                {{ $service->name }}
                                            </button>
                                        </h3>
                                        <span class="px-2 py-1 text-xs rounded-full {{ $service->active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $service->active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600 mt-1">{{ $service->description }}</p>
                                    <div class="mt-2 flex items-center text-sm text-gray-500 space-x-4">
                                        <span>Program: {{ $service->program->name }}</span>
                                        <span>Start: {{ $service->start_date ? $service->start_date->format('M j, Y') : 'Not set' }}</span>
                                        @if($service->end_date)
                                            <span>End: {{ $service->end_date->format('M j, Y') }}</span>
                                        @endif
                                        <span>Goals: {{ $service->workPlanGoals->count() }}</span>
                                        <span class="font-medium {{ $service->getEnrolledParticipantsCount() > 0 ? 'text-blue-600' : 'text-gray-500' }}">
                                            Enrolled: {{ $service->getEnrolledParticipantsCount() }}
                                        </span>
                                        <span class="text-green-600">
                                            Completed: {{ $service->getCompletedEnrollmentsCount() }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <button wire:click="showEditService({{ $service->id }})" 
                                            class="px-3 py-1 text-sm bg-blue-100 text-blue-700 rounded hover:bg-blue-200">
                                        Edit
                                    </button>
                                    <button wire:click="toggleServiceStatus({{ $service->id }})" 
                                            class="px-3 py-1 text-sm rounded {{ $service->active ? 'bg-red-100 text-red-700 hover:bg-red-200' : 'bg-green-100 text-green-700 hover:bg-green-200' }}">
                                        {{ $service->active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                    <button wire:click="deleteService({{ $service->id }})" 
                                            wire:confirm="Are you sure you want to delete this service?"
                                            class="px-3 py-1 text-sm bg-red-100 text-red-700 rounded hover:bg-red-200">
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="px-6 py-4 text-center text-gray-500">
                            No services found.
                        </li>
                    @endforelse
                </ul>
            </div>

            <!-- Pagination -->
            <div class="mt-6">
                {{ $services->links() }}
            </div>
        </div>
        @endif

        <!-- View Service Modal -->
        @if ($showViewModal && $selectedViewService)
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
                <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-4xl w-full m-4 max-h-[90vh] overflow-y-auto">
                    <!-- Modal Header -->
                    <div class="bg-teal-600 text-white p-6 rounded-t-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-xl font-semibold">{{ $selectedViewService->name }}</h3>
                                <p class="text-teal-100 mt-1">{{ $selectedViewService->program->name }}</p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="px-3 py-1 text-sm rounded-full {{ $selectedViewService->active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $selectedViewService->active ? 'Active' : 'Inactive' }}
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
                            <!-- Service Information -->
                            <div class="space-y-6">
                                <div>
                                    <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-3">Service Information</h4>
                                    <div class="bg-gray-50 dark:bg-zinc-700 p-4 rounded-lg space-y-3">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Service Name</label>
                                            <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $selectedViewService->name }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                                            <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $selectedViewService->description ?: 'No description provided' }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Program</label>
                                            <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $selectedViewService->program->name }}</p>
                                        </div>
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Start Date</label>
                                                <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $selectedViewService->start_date ? $selectedViewService->start_date->format('M j, Y') : 'Not set' }}</p>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">End Date</label>
                                                <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $selectedViewService->end_date ? $selectedViewService->end_date->format('M j, Y') : 'No end date' }}</p>
                                            </div>
                                        </div>
                                        @if($selectedViewService->notes)
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Notes</label>
                                                <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $selectedViewService->notes }}</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Program Information -->
                                <div>
                                    <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-3">Program Information</h4>
                                    <div class="bg-gray-50 dark:bg-zinc-700 p-4 rounded-lg space-y-3">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Program Name</label>
                                            <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $selectedViewService->program->name }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Program Description</label>
                                            <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $selectedViewService->program->description ?: 'No description provided' }}</p>
                                        </div>
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Program Status</label>
                                                <p class="text-sm mt-1">
                                                    <span class="px-2 py-1 text-xs rounded-full {{ $selectedViewService->program->active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                        {{ $selectedViewService->program->active ? 'Active' : 'Inactive' }}
                                                    </span>
                                                </p>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Program Type</label>
                                                <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $selectedViewService->program->program_type ?? 'Not specified' }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Statistics and Enrollments -->
                            <div class="space-y-6">
                                <!-- Statistics -->
                                <div>
                                    <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-3">Service Statistics</h4>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="bg-blue-50 p-4 rounded-lg">
                                            <div class="text-2xl font-bold text-blue-600">{{ $selectedViewService->getEnrolledParticipantsCount() }}</div>
                                            <div class="text-sm text-blue-700">Currently Enrolled</div>
                                        </div>
                                        <div class="bg-green-50 p-4 rounded-lg">
                                            <div class="text-2xl font-bold text-green-600">{{ $selectedViewService->getCompletedEnrollmentsCount() }}</div>
                                            <div class="text-sm text-green-700">Completed</div>
                                        </div>
                                        <div class="bg-purple-50 p-4 rounded-lg">
                                            <div class="text-2xl font-bold text-purple-600">{{ $selectedViewService->serviceEnrollments->count() }}</div>
                                            <div class="text-sm text-purple-700">Total Enrollments</div>
                                        </div>
                                        <div class="bg-orange-50 p-4 rounded-lg">
                                            <div class="text-2xl font-bold text-orange-600">{{ $selectedViewService->workPlanGoals->count() }}</div>
                                            <div class="text-sm text-orange-700">Work Plan Goals</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Recent Enrollments -->
                                <div>
                                    <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-3">Recent Enrollments</h4>
                                    <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg">
                                        @if($selectedViewService->serviceEnrollments->count() > 0)
                                            <div class="max-h-64 overflow-y-auto">
                                                @foreach($selectedViewService->serviceEnrollments->take(10) as $enrollment)
                                                    <div class="p-3 border-b border-gray-200 dark:border-zinc-600 last:border-b-0">
                                                        <div class="flex items-center justify-between">
                                                            <div>
                                                                <p class="font-medium text-sm text-gray-900 dark:text-white">{{ $enrollment->participant->first_name }} {{ $enrollment->participant->last_name }}</p>
                                                                <p class="text-xs text-gray-600 dark:text-gray-300">Enrolled: {{ $enrollment->enrollment_date->format('M j, Y') }}</p>
                                                            </div>
                                                            <div class="text-right">
                                                                <span class="px-2 py-1 text-xs rounded-full
                                                                    @if($enrollment->status === 'completed') bg-green-100 text-green-800
                                                                    @elseif($enrollment->status === 'in_progress') bg-blue-100 text-blue-800
                                                                    @elseif($enrollment->status === 'enrolled') bg-yellow-100 text-yellow-800
                                                                    @else bg-gray-100 text-gray-800
                                                                    @endif">
                                                                    {{ ucfirst($enrollment->status) }}
                                                                </span>
                                                                @if($enrollment->completion_date)
                                                                    <p class="text-xs text-gray-600 dark:text-gray-300 mt-1">Completed: {{ $enrollment->completion_date->format('M j, Y') }}</p>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        @if($enrollment->hours_received || $enrollment->sessions_attended)
                                                            <div class="mt-2 flex items-center space-x-4 text-xs text-gray-600 dark:text-gray-300">
                                                                @if($enrollment->hours_received)
                                                                    <span>Hours: {{ $enrollment->hours_received }}</span>
                                                                @endif
                                                                @if($enrollment->sessions_attended)
                                                                    <span>Sessions: {{ $enrollment->sessions_attended }}</span>
                                                                @endif
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="p-4 text-center text-gray-500 dark:text-gray-400">
                                                <p>No enrollments found for this service.</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="bg-gray-50 dark:bg-zinc-700 px-6 py-4 rounded-b-lg">
                        <div class="flex justify-between items-center">
                            <div class="text-sm text-gray-600 dark:text-gray-300">
                                Created: {{ $selectedViewService->created_at->format('M j, Y g:i A') }}
                                @if($selectedViewService->updated_at != $selectedViewService->created_at)
                                    • Updated: {{ $selectedViewService->updated_at->format('M j, Y g:i A') }}
                                @endif
                            </div>
                            <div class="flex space-x-3">
                                <button wire:click="showEditService({{ $selectedViewService->id }})" 
                                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    Edit Service
                                </button>
                                <button wire:click="$set('showViewModal', false)" 
                                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-zinc-600 border border-gray-300 dark:border-zinc-500 rounded-md hover:bg-gray-50 dark:hover:bg-zinc-500 focus:outline-none focus:ring-2 focus:ring-teal-500">
                                    Close
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>