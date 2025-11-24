@if($activeTab === 'field-mappings')
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Field Mappings</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Map DonorPerfect fields to Sage Intacct fields</p>
            </div>
            <button wire:click="showAddMapping"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                Add Mapping
            </button>
        </div>

        @if(count($availableDpFields) === 0)
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                <p class="text-sm text-yellow-700 dark:text-yellow-300">
                    No DonorPerfect fields available. Please configure DonorPerfect settings first.
                </p>
            </div>
        @endif

        <!-- Add/Edit Mapping Form -->
        @if($showAddMappingForm)
            <div class="bg-gray-50 dark:bg-zinc-700 p-4 rounded-lg border-2 border-indigo-200 dark:border-indigo-800">
                <h4 class="font-medium text-gray-900 dark:text-white mb-4">
                    {{ $editingMappingId ? 'Edit Field Mapping' : 'Add Field Mapping' }}
                </h4>
                <form wire:submit="saveFieldMapping" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- DonorPerfect Field -->
                        <div>
                            <label for="mapping_dp_field" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                DonorPerfect Field <span class="text-red-500">*</span>
                            </label>
                            <select wire:model="mapping_dp_field"
                                    id="mapping_dp_field"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">Select a field</option>
                                @foreach($availableDpFields as $field)
                                    <option value="{{ $field }}">{{ $field }}</option>
                                @endforeach
                            </select>
                            @error('mapping_dp_field') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Intacct Field -->
                        <div>
                            <label for="mapping_intacct_field" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Intacct Field <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   wire:model="mapping_intacct_field"
                                   id="mapping_intacct_field"
                                   placeholder="e.g., AMOUNT, CUSTOMERID"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            @error('mapping_intacct_field') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Default Value -->
                    <div>
                        <label for="mapping_transformation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Default Value (Optional)
                        </label>
                        <input type="text"
                               wire:model="mapping_transformation"
                               id="mapping_transformation"
                               placeholder="e.g., Default Department"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Value to use if the DonorPerfect field is empty
                        </p>
                    </div>

                    <!-- Is Required -->
                    <div class="flex items-center">
                        <input type="checkbox"
                               wire:model="mapping_is_required"
                               id="mapping_is_required"
                               class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-zinc-600 rounded">
                        <label for="mapping_is_required" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                            Mark as required field
                        </label>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end space-x-3">
                        <button type="button"
                                wire:click="cancelFieldMapping"
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-zinc-600 border border-gray-300 dark:border-zinc-500 rounded-md hover:bg-gray-50 dark:hover:bg-zinc-500">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700">
                            {{ $editingMappingId ? 'Update Mapping' : 'Add Mapping' }}
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <!-- Field Mappings Table -->
        @if($fieldMappings->count() > 0)
            <div class="bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-700 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                    <thead class="bg-gray-50 dark:bg-zinc-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Order</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">DonorPerfect Field</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Intacct Field</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Required</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Default Value</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-700">
                        @foreach($fieldMappings as $mapping)
                            <tr class="hover:bg-gray-50 dark:hover:bg-zinc-700">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center space-x-1">
                                        <button wire:click="moveFieldMapping({{ $mapping->id }}, 'up')"
                                                class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 disabled:opacity-50"
                                                {{ $loop->first ? 'disabled' : '' }}>
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                            </svg>
                                        </button>
                                        <button wire:click="moveFieldMapping({{ $mapping->id }}, 'down')"
                                                class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 disabled:opacity-50"
                                                {{ $loop->last ? 'disabled' : '' }}>
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        </button>
                                        <span class="text-sm text-gray-600 dark:text-gray-400 ml-2">{{ $mapping->order }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="text-sm font-mono text-gray-900 dark:text-white">{{ $mapping->donor_perfect_field }}</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="text-sm font-mono text-gray-900 dark:text-white">{{ $mapping->intacct_field }}</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if($mapping->is_required)
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200">
                                            Required
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-200">
                                            Optional
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ $mapping->default_value ?: '-' }}</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium">
                                    <button wire:click="editFieldMapping({{ $mapping->id }})"
                                            class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 mr-3">
                                        Edit
                                    </button>
                                    <button wire:click="deleteFieldMapping({{ $mapping->id }})"
                                            onclick="return confirm('Are you sure you want to delete this mapping?')"
                                            class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-12 bg-gray-50 dark:bg-zinc-700 rounded-lg">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No field mappings</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by adding a field mapping.</p>
            </div>
        @endif
    </div>
@endif
