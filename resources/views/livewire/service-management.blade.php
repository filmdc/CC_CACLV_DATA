<div class="max-w-7xl mx-auto p-6">
    <div class="bg-white shadow-lg rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-teal-600 text-white p-6">
            <h1 class="text-2xl font-bold">Service Management</h1>
            <p class="text-teal-100 mt-2">Manage program services and their configurations</p>
        </div>

        <!-- Search and Actions -->
        <div class="p-4 bg-gray-50 border-b">
            <div class="flex items-center justify-between">
                <div class="flex-1 max-w-md">
                    <input type="text" wire:model.live="search" placeholder="Search services or programs..." 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500">
                </div>
                <button wire:click="showCreateService" 
                        class="ml-4 px-4 py-2 bg-teal-600 text-white rounded-md hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500">
                    Create New Service
                </button>
            </div>
        </div>

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
            <div class="p-6 bg-blue-50 border-b">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Create New Service</h3>
                
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
            <div class="p-6 bg-yellow-50 border-b">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Edit Service: {{ $selectedService->name }}</h3>
                
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
        <div class="p-6">
            <div class="bg-white shadow overflow-hidden sm:rounded-md">
                <ul class="divide-y divide-gray-200">
                    @forelse ($services as $service)
                        <li class="px-6 py-4">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3">
                                        <h3 class="text-lg font-medium text-gray-900">{{ $service->name }}</h3>
                                        <span class="px-2 py-1 text-xs rounded-full {{ $service->active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $service->active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600 mt-1">{{ $service->description }}</p>
                                    <div class="mt-2 flex items-center text-sm text-gray-500 space-x-4">
                                        <span>Program: {{ $service->program->name }}</span>
                                        <span>Start: {{ $service->start_date->format('M j, Y') }}</span>
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
    </div>
</div>