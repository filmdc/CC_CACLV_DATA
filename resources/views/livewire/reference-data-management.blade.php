<div class="w-full px-0 py-6">
    <div class="bg-white dark:bg-zinc-800 shadow-lg rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-teal-600 text-white p-6">
            <h1 class="text-2xl font-bold">Reference Data Management</h1>
            <p class="text-teal-100 mt-2">Manage dropdown options and reference values across the system</p>
            @if($selectedDataSet)
                <div class="mt-3 text-sm">
                    <span class="font-medium">Current Data Set:</span> {{ $selectedDataSet->name }} ({{ $selectedDataSet->version }})
                    @if($selectedDataSet->is_active)
                        <span class="ml-2 inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                    @elseif($selectedDataSet->is_draft)
                        <span class="ml-2 inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Draft</span>
                    @else
                        <span class="ml-2 inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Published</span>
                    @endif
                </div>
            @endif
        </div>

        <!-- Filters and Actions -->
        <div class="p-4 bg-gray-50 dark:bg-zinc-700 border-b border-gray-200 dark:border-zinc-600">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex flex-col sm:flex-row gap-4 flex-1">
                    <!-- Data Set Selector -->
                    <div class="min-w-0 flex-1 max-w-xs">
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Data Set</label>
                        <select wire:model.live="selectedDataSetId" class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                            <option value="">Select Data Set</option>
                            @foreach($dataSets as $dataSet)
                                <option value="{{ $dataSet->id }}">
                                    {{ $dataSet->name }} ({{ $dataSet->version }})
                                    @if($dataSet->is_active) - Active @elseif($dataSet->is_draft) - Draft @else - Published @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Category Filter -->
                    <div class="min-w-0 flex-1 max-w-xs">
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Category</label>
                        <select wire:model.live="selectedCategory" class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                            <option value="">All Categories</option>
                            @foreach($categories as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <!-- Search -->
                    <div class="min-w-0 flex-1 max-w-md">
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Search</label>
                        <input wire:model.live="search" placeholder="Search reference data..." class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500" />
                    </div>
                </div>
                
                <div class="flex gap-2">
                    @if($selectedDataSet && $selectedDataSet->is_draft)
                        @if($this->can('reference_data.create'))
                            <button wire:click="showCreateItem" class="px-4 py-2 bg-teal-600 text-white rounded-md hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 whitespace-nowrap">
                                Add Reference Data
                            </button>
                        @endif
                    @elseif($selectedDataSet && !$selectedDataSet->is_draft)
                        <button wire:click="createEditableVersionOfCurrentSet" 
                                onclick="return confirm('This will create a new editable draft version of this data set. Continue?')"
                                class="px-4 py-2 bg-orange-600 text-white rounded-md hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 whitespace-nowrap">
                            Create Editable Version
                        </button>
                    @else
                        <div class="text-xs text-gray-500 dark:text-gray-400 max-w-xs text-right">
                            Select a data set to view reference data
                        </div>
                    @endif
                </div>
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
            <div class="p-6 bg-teal-50 dark:bg-teal-900/20 border-b border-gray-200 dark:border-zinc-600">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                    {{ $showCreateForm ? 'Add New Reference Data' : 'Edit Reference Data' }}
                </h3>
                
                <form wire:submit="{{ $showCreateForm ? 'createItem' : 'updateItem' }}" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category *</label>
                            <select wire:model="category" class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                                <option value="">Select Category</option>
                                @foreach($categories as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('category') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Value *</label>
                            <input wire:model="value" class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500" />
                            @error('value') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Display Label *</label>
                            <input wire:model="label" class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500" />
                            @error('label') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sort Order *</label>
                            <input type="number" min="0" wire:model="sort_order" class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500" />
                            @error('sort_order') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" wire:model="active" class="h-4 w-4 text-teal-600 focus:ring-teal-500 border-gray-300 dark:border-zinc-600 rounded">
                            <label class="ml-2 block text-sm text-gray-900 dark:text-white">Active</label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                        <x-wysiwyg-editor model="description" :rows="2" placeholder="Enter description..." id="reference_data_description" />
                        @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end gap-2 mt-6">
                        <button type="button" wire:click="$set('showCreateForm', false); $set('showEditForm', false)" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-zinc-700 border border-gray-300 dark:border-zinc-600 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-600 focus:outline-none focus:ring-2 focus:ring-gray-500">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-teal-600 border border-transparent rounded-md hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500">
                            {{ $showCreateForm ? 'Create Item' : 'Update Item' }}
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <!-- Reference Data Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-600">
                <thead class="bg-gray-50 dark:bg-zinc-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Value</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Label</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Sort Order</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-600">
                    @forelse($items as $item)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $categories[$item->category] ?? $item->category }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $item->category }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">{{ $item->value }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">{{ $item->label }}</div>
                                @if($item->description)
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ Str::limit(strip_tags($item->description), 50) }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                {{ $item->sort_order }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $item->active ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-300' }}">
                                    {{ $item->active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex gap-2">
                                    @if($selectedDataSet && $selectedDataSet->is_draft)
                                        @if($this->can('reference_data.edit'))
                                            <button wire:click="showEditItem({{ $item->id }})" class="text-teal-600 dark:text-teal-400 hover:text-teal-900 dark:hover:text-teal-300">
                                                Edit
                                            </button>
                                            <button wire:click="toggleStatus({{ $item->id }})" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300">
                                                {{ $item->active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        @endif
                                        @if($this->can('reference_data.delete'))
                                            <button 
                                                wire:click="deleteItem({{ $item->id }})"
                                                onclick="return confirm('Are you sure you want to delete this reference data item?')"
                                                class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"
                                            >
                                                Delete
                                            </button>
                                        @endif
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500 text-xs">View Only</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                No reference data found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($items->hasPages())
            <div class="px-6 py-3 border-t border-gray-200 dark:border-zinc-600">
                {{ $items->links() }}
            </div>
        @endif
    </div>
</div>