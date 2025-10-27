<div class="w-full px-0 py-6">
    <div class="bg-white dark:bg-zinc-800 shadow-lg rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-purple-600 text-white p-6">
            <h1 class="text-2xl font-bold">Reference Data Set Management</h1>
            <p class="text-purple-100 mt-2">Manage versioned reference data sets for participant records</p>
        </div>

        <!-- Search and Actions -->
        <div class="p-4 bg-gray-50 dark:bg-zinc-700 border-b border-gray-200 dark:border-zinc-600">
            <div class="flex items-center justify-between">
                <div class="flex-1 max-w-md">
                    <input type="text" wire:model.live="search" placeholder="Search data sets..." 
                           class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                <button wire:click="showCreateDataSet" 
                        class="ml-4 px-4 py-2 text-sm font-medium text-white bg-purple-600 border border-transparent rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500">
                    Create New Data Set
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

        <!-- Create Form -->
        @if($showCreateForm)
            <div class="p-6 bg-blue-50 dark:bg-blue-900/20 border-b border-gray-200 dark:border-zinc-600">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Create New Reference Data Set</h3>

                <form wire:submit="createDataSet" class="space-y-4">
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
                            <label for="version" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Version <span class="text-red-500">*</span>
                            </label>
                            <input type="text" wire:model="version" id="version" placeholder="e.g., 1.0.0"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                            @error('version') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                        <x-wysiwyg-editor model="description" :rows="3" placeholder="Enter reference data set description..." id="description" />
                        @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" wire:click="resetForm" 
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-zinc-700 border border-gray-300 dark:border-zinc-600 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-600 focus:outline-none focus:ring-2 focus:ring-gray-500">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 text-sm font-medium text-white bg-purple-600 border border-transparent rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500">
                            Create Data Set
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <!-- Duplicate Form -->
        @if($showDuplicateForm && $selectedDataSet)
            <div class="p-6 bg-green-50 dark:bg-green-900/20 border-b border-gray-200 dark:border-zinc-600">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Duplicate Data Set: {{ $selectedDataSet->name }}</h3>

                <form wire:submit="duplicateDataSet" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="dup_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                New Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" wire:model="name" id="dup_name" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500">
                            @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="dup_version" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                New Version <span class="text-red-500">*</span>
                            </label>
                            <input type="text" wire:model="version" id="dup_version" placeholder="e.g., 1.1.0"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500">
                            @error('version') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" wire:click="resetForm" 
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-zinc-700 border border-gray-300 dark:border-zinc-600 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-600 focus:outline-none focus:ring-2 focus:ring-gray-500">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                            Duplicate Data Set
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <!-- Edit Form -->
        @if($showEditForm && $selectedDataSet)
            <div class="p-6 bg-orange-50 dark:bg-orange-900/20 border-b border-gray-200 dark:border-zinc-600">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Edit Data Set: {{ $selectedDataSet->name }}</h3>

                <form wire:submit="updateDataSet" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="edit_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" wire:model="name" id="edit_name" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-orange-500">
                            @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="edit_version" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Version <span class="text-red-500">*</span>
                            </label>
                            <input type="text" wire:model="version" id="edit_version" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-orange-500">
                            @error('version') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="edit_description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                        <x-wysiwyg-editor model="description" :rows="3" placeholder="Enter reference data set description..." id="edit_description" />
                        @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" wire:click="resetForm" 
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-zinc-700 border border-gray-300 dark:border-zinc-600 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-600 focus:outline-none focus:ring-2 focus:ring-gray-500">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 text-sm font-medium text-white bg-orange-600 border border-transparent rounded-md hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500">
                            Update Data Set
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <!-- Data Sets Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-600">
                <thead class="bg-gray-50 dark:bg-zinc-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name & Version</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Data</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Created</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-600">
                    @forelse($dataSets as $dataSet)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $dataSet->name }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">Version {{ $dataSet->version }}</div>
                                    @if($dataSet->description)
                                        <div class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ Str::limit($dataSet->description, 60) }}</div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex flex-col space-y-1">
                                    @if($dataSet->is_active)
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">Active</span>
                                    @elseif($dataSet->is_draft)
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200">Draft</span>
                                    @else
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">Published</span>
                                    @endif
                                    
                                    @if($dataSet->is_import_default)
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-200">Import Default</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                <div>{{ $dataSet->getCategoriesCount() }} categories</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $dataSet->getDataCount() }} items</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">{{ $dataSet->created_at->format('M j, Y') }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    by {{ $dataSet->createdBy->name ?? 'System' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2 flex-wrap">
                                    @if($dataSet->is_draft)
                                        <button wire:click="showEditDataSet({{ $dataSet->id }})" 
                                                class="text-orange-600 dark:text-orange-400 hover:text-orange-900 dark:hover:text-orange-300">Edit Info</button>
                                        <button wire:click="publishDataSet({{ $dataSet->id }})" 
                                                class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300">Publish</button>
                                    @else
                                        <button wire:click="createEditableVersion({{ $dataSet->id }})" 
                                                onclick="return confirm('This will create a new editable draft version of this data set. Continue?')"
                                                class="text-orange-600 dark:text-orange-400 hover:text-orange-900 dark:hover:text-orange-300">Edit Data</button>
                                    @endif
                                    
                                    @if(!$dataSet->is_draft && !$dataSet->is_active)
                                        <button wire:click="activateDataSet({{ $dataSet->id }})" 
                                                onclick="return confirm('This will deactivate the current active data set. Are you sure?')"
                                                class="text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300">Activate</button>
                                    @endif
                                    
                                    @if(!$dataSet->is_draft && !$dataSet->is_import_default)
                                        <button wire:click="setAsImportDefault({{ $dataSet->id }})" 
                                                onclick="return confirm('This will change the default reference data set used for imports. Are you sure?')"
                                                class="text-orange-600 dark:text-orange-400 hover:text-orange-900 dark:hover:text-orange-300">Set Import Default</button>
                                    @endif
                                    
                                    <button wire:click="showDuplicateDataSet({{ $dataSet->id }})" 
                                            class="text-purple-600 dark:text-purple-400 hover:text-purple-900 dark:hover:text-purple-300">Duplicate</button>
                                    
                                    @if(!$dataSet->is_active && $dataSet->participants()->count() === 0)
                                        <button wire:click="deleteDataSet({{ $dataSet->id }})" 
                                                onclick="return confirm('Are you sure you want to delete this data set?')"
                                                class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">Delete</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                No reference data sets found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($dataSets->hasPages())
            <div class="px-6 py-3 border-t border-gray-200 dark:border-zinc-600">
                {{ $dataSets->links() }}
            </div>
        @endif
    </div>
</div>