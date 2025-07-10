<div class="max-w-7xl mx-auto p-6">
    <div class="bg-white dark:bg-zinc-800 shadow-lg rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-purple-600 text-white p-6">
            <h1 class="text-2xl font-bold">Funder Management</h1>
            <p class="text-purple-100 mt-2">Manage funding organizations and contact information</p>
        </div>

        <!-- Search and Actions -->
        @if(!$showEditForm && !$showCreateForm && !$showViewModal)
        <div class="p-4 bg-gray-50 dark:bg-zinc-700 border-b border-gray-200 dark:border-zinc-600">
            <div class="flex items-center justify-between">
                <div class="flex-1 max-w-md">
                    <input type="text" wire:model.live="search" placeholder="Search funders..." 
                           class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                @if($this->can('funders.create'))
                    <button wire:click="showCreateFunder" 
                            class="ml-4 px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        Create Funder
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
            <div class="p-6 border-b border-gray-200 dark:border-zinc-600">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{ $showCreateForm ? 'Create New Funder' : 'Edit Funder' }}</h3>
                    <button wire:click="$set('showCreateForm', false); $set('showEditForm', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form wire:submit="{{ $showCreateForm ? 'createFunder' : 'updateFunder' }}" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" wire:model="name" id="name" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                            @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Code</label>
                            <input type="text" wire:model="code" id="code" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                            @error('code') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="contact_person" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Contact Person</label>
                            <input type="text" wire:model="contact_person" id="contact_person" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                            @error('contact_person') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="contact_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Contact Email</label>
                            <input type="email" wire:model="contact_email" id="contact_email" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                            @error('contact_email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="contact_phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Contact Phone</label>
                            <input type="text" wire:model="contact_phone" id="contact_phone" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                            @error('contact_phone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="website" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Website</label>
                            <input type="url" wire:model="website" id="website" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                            @error('website') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="address" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Address</label>
                        <textarea wire:model="address" id="address" rows="3" 
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500"></textarea>
                        @error('address') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                        <textarea wire:model="description" id="description" rows="3" 
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500"></textarea>
                        @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" wire:model="active" 
                                   class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Active</span>
                        </label>
                        @error('active') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" wire:click="$set('showCreateForm', false); $set('showEditForm', false)" 
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-zinc-700 border border-gray-300 dark:border-zinc-600 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-600 focus:outline-none focus:ring-2 focus:ring-gray-500">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 text-sm font-medium text-white bg-purple-600 border border-transparent rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500">
                            {{ $showCreateForm ? 'Create Funder' : 'Update Funder' }}
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <!-- Funders Table -->
        @if(!$showEditForm && !$showCreateForm && !$showViewModal)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-600">
                <thead class="bg-gray-50 dark:bg-zinc-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Code</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Contact</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Website</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-600">
                    @forelse($funders as $funder)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    <button wire:click="viewFunder({{ $funder->id }})" class="text-purple-600 dark:text-purple-400 hover:text-purple-900 dark:hover:text-purple-300 hover:underline">
                                        {{ $funder->name }}
                                    </button>
                                </div>
                                @if($funder->description)
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ Str::limit($funder->description, 50) }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $funder->code ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($funder->contact_person)
                                    <div class="text-sm text-gray-900 dark:text-white">{{ $funder->contact_person }}</div>
                                @endif
                                @if($funder->contact_email)
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $funder->contact_email }}</div>
                                @endif
                                @if($funder->contact_phone)
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $funder->contact_phone }}</div>
                                @endif
                                @if(!$funder->contact_person && !$funder->contact_email && !$funder->contact_phone)
                                    <div class="text-sm text-gray-500 dark:text-gray-400">No contact info</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                @if($funder->website)
                                    <a href="{{ $funder->website }}" target="_blank" class="text-purple-600 dark:text-purple-400 hover:text-purple-900 dark:hover:text-purple-300">
                                        Visit Site
                                    </a>
                                @else
                                    N/A
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $funder->active ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-300' }}">
                                    {{ $funder->active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    @if($this->can('funders.edit'))
                                        <button wire:click="showEditFunder({{ $funder->id }})" 
                                                class="text-purple-600 dark:text-purple-400 hover:text-purple-900 dark:hover:text-purple-300">Edit</button>
                                    @endif
                                    @if($this->can('funders.delete'))
                                        <button wire:click="deleteFunder({{ $funder->id }})" 
                                                onclick="return confirm('Are you sure you want to delete this funder?')"
                                                class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">Delete</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                No funders found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($funders->hasPages())
            <div class="px-6 py-3 border-t border-gray-200 dark:border-zinc-600">
                {{ $funders->links() }}
            </div>
        @endif
        </div>
        @endif

        <!-- View Modal -->
        @if($showViewModal && $selectedFunder)
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
                <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                    <!-- Modal Header -->
                    <div class="bg-purple-600 text-white p-6 rounded-t-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-xl font-bold">{{ $selectedFunder->name }}</h3>
                                @if($selectedFunder->code)
                                    <p class="text-purple-100 mt-1">Code: {{ $selectedFunder->code }}</p>
                                @endif
                            </div>
                            <button wire:click="$set('showViewModal', false)" class="text-purple-100 hover:text-white">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Modal Content -->
                    <div class="p-6">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Left Column - Basic Information -->
                            <div class="space-y-6">
                                <!-- Funder Information -->
                                <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-4">
                                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Funder Information</h4>
                                    <div class="space-y-2">
                                        <div>
                                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Name:</span>
                                            <span class="text-sm text-gray-900 dark:text-white ml-2">{{ $selectedFunder->name }}</span>
                                        </div>
                                        @if($selectedFunder->code)
                                            <div>
                                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Code:</span>
                                                <span class="text-sm text-gray-900 dark:text-white ml-2">{{ $selectedFunder->code }}</span>
                                            </div>
                                        @endif
                                        @if($selectedFunder->description)
                                            <div>
                                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Description:</span>
                                                <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $selectedFunder->description }}</p>
                                            </div>
                                        @endif
                                        <div>
                                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Status:</span>
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ml-2 {{ $selectedFunder->active ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-300' }}">
                                                {{ $selectedFunder->active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Contact Information -->
                                <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-4">
                                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Contact Information</h4>
                                    <div class="space-y-2">
                                        @if($selectedFunder->contact_person)
                                            <div>
                                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Contact Person:</span>
                                                <span class="text-sm text-gray-900 dark:text-white ml-2">{{ $selectedFunder->contact_person }}</span>
                                            </div>
                                        @endif
                                        @if($selectedFunder->contact_email)
                                            <div>
                                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Email:</span>
                                                <a href="mailto:{{ $selectedFunder->contact_email }}" class="text-sm text-purple-600 dark:text-purple-400 hover:text-purple-900 dark:hover:text-purple-300 ml-2">{{ $selectedFunder->contact_email }}</a>
                                            </div>
                                        @endif
                                        @if($selectedFunder->contact_phone)
                                            <div>
                                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Phone:</span>
                                                <span class="text-sm text-gray-900 dark:text-white ml-2">{{ $selectedFunder->contact_phone }}</span>
                                            </div>
                                        @endif
                                        @if($selectedFunder->website)
                                            <div>
                                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Website:</span>
                                                <a href="{{ $selectedFunder->website }}" target="_blank" class="text-sm text-purple-600 dark:text-purple-400 hover:text-purple-900 dark:hover:text-purple-300 ml-2">{{ $selectedFunder->website }}</a>
                                            </div>
                                        @endif
                                        @if(!$selectedFunder->contact_person && !$selectedFunder->contact_email && !$selectedFunder->contact_phone && !$selectedFunder->website)
                                            <div class="text-sm text-gray-500 dark:text-gray-400">No contact information available</div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Address Information -->
                                @if($selectedFunder->address)
                                    <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-4">
                                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Address</h4>
                                        <p class="text-sm text-gray-900 dark:text-white whitespace-pre-line">{{ $selectedFunder->address }}</p>
                                    </div>
                                @endif
                            </div>

                            <!-- Right Column - Contracts and Timestamps -->
                            <div class="space-y-6">
                                <!-- Associated Contracts -->
                                <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-4">
                                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Associated Contracts</h4>
                                    @if($selectedFunder->contracts && $selectedFunder->contracts->count() > 0)
                                        <div class="space-y-2">
                                            @foreach($selectedFunder->contracts as $contract)
                                                <div class="bg-white dark:bg-zinc-800 rounded-md p-3 border border-gray-200 dark:border-zinc-600">
                                                    <div class="flex items-center justify-between">
                                                        <div>
                                                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $contract->title }}</div>
                                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $contract->contract_number }}</div>
                                                        </div>
                                                        <div class="text-right">
                                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $contract->start_date ? $contract->start_date->format('M d, Y') : 'N/A' }} - {{ $contract->end_date ? $contract->end_date->format('M d, Y') : 'N/A' }}</div>
                                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $contract->archived ? 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-300' : 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300' }}">
                                                                {{ $contract->archived ? 'Archived' : 'Active' }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="text-sm text-gray-500 dark:text-gray-400">No contracts associated with this funder</div>
                                    @endif
                                </div>

                                <!-- Timestamps -->
                                <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-4">
                                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Record Information</h4>
                                    <div class="space-y-2">
                                        <div>
                                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Created:</span>
                                            <span class="text-sm text-gray-900 dark:text-white ml-2">{{ $selectedFunder->created_at->format('M d, Y g:i A') }}</span>
                                        </div>
                                        <div>
                                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Last Updated:</span>
                                            <span class="text-sm text-gray-900 dark:text-white ml-2">{{ $selectedFunder->updated_at->format('M d, Y g:i A') }}</span>
                                        </div>
                                        @if($selectedFunder->createdBy)
                                            <div>
                                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Created By:</span>
                                                <span class="text-sm text-gray-900 dark:text-white ml-2">{{ $selectedFunder->createdBy->name }}</span>
                                            </div>
                                        @endif
                                        @if($selectedFunder->updatedBy)
                                            <div>
                                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Updated By:</span>
                                                <span class="text-sm text-gray-900 dark:text-white ml-2">{{ $selectedFunder->updatedBy->name }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="bg-gray-50 dark:bg-zinc-700 px-6 py-4 rounded-b-lg">
                        <div class="flex justify-end space-x-3">
                            <button wire:click="$set('showViewModal', false)" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-zinc-800 border border-gray-300 dark:border-zinc-600 rounded-md hover:bg-gray-50 dark:hover:bg-zinc-700 focus:outline-none focus:ring-2 focus:ring-purple-500">
                                Close
                            </button>
                            @if($this->can('funders.edit'))
                                <button wire:click="showEditFunder({{ $selectedFunder->id }})" class="px-4 py-2 text-sm font-medium text-white bg-purple-600 border border-transparent rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500">
                                    Edit Funder
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>