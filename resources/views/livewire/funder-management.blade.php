<div class="max-w-7xl mx-auto p-6">
    <div class="bg-white dark:bg-zinc-800 shadow-lg rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-purple-600 text-white p-6">
            <h1 class="text-2xl font-bold">Funder Management</h1>
            <p class="text-purple-100 mt-2">Manage funding organizations and contact information</p>
        </div>

        <!-- Search and Actions -->
        <div class="p-4 bg-gray-50 dark:bg-zinc-700 border-b border-gray-200 dark:border-zinc-600">
            <div class="flex items-center justify-between">
                <div class="flex-1 max-w-md">
                    <input type="text" wire:model.live="search" placeholder="Search funders..." 
                           class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                <button wire:click="showCreateFunder" 
                        class="ml-4 px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500">
                    Create Funder
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
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">{{ $showCreateForm ? 'Create New Funder' : 'Edit Funder' }}</h3>

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
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $funder->name }}</div>
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
                                    <button wire:click="showEditFunder({{ $funder->id }})" 
                                            class="text-purple-600 dark:text-purple-400 hover:text-purple-900 dark:hover:text-purple-300">Edit</button>
                                    <button wire:click="deleteFunder({{ $funder->id }})" 
                                            onclick="return confirm('Are you sure you want to delete this funder?')"
                                            class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">Delete</button>
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
</div>