<div class="w-full px-0 py-6">
    <div class="bg-white dark:bg-zinc-800 shadow-lg rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-indigo-600 text-white p-6">
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center space-x-3 mb-2">
                        <a href="{{ route('pdf-templates.index') }}" wire:navigate
                           class="text-indigo-100 hover:text-white flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                            Back to Templates
                        </a>
                    </div>
                    <h1 class="text-2xl font-bold">{{ $template->name }}</h1>
                    <p class="text-indigo-100 mt-2">
                        {{ $template->field_count }} form fields •
                        {{ $template->mapped_field_count }} mapped ({{ $template->mapping_completion }}%)
                    </p>
                </div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        @if (session()->has('success'))
            <div class="p-4 bg-green-50 dark:bg-green-900/20 border-l-4 border-green-400">
                <p class="text-sm text-green-700 dark:text-green-300">{{ session('success') }}</p>
            </div>
        @endif
        @if (session()->has('error'))
            <div class="p-4 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-400">
                <p class="text-sm text-red-700 dark:text-red-300">{{ session('error') }}</p>
            </div>
        @endif

        <!-- Filters -->
        <div class="p-4 bg-gray-50 dark:bg-zinc-700 border-b dark:border-zinc-600">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <input type="text" wire:model.live="search" placeholder="Search fields..."
                           class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                </div>
                <div>
                    <select wire:model.live="filterMappingType"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                        <option value="">All Mapping Types</option>
                        <option value="unmapped">Unmapped</option>
                        <option value="model_field">Database Field</option>
                        <option value="custom_value">Custom Value</option>
                        <option value="computed">Computed</option>
                    </select>
                </div>
                <div>
                    <select wire:model.live="filterFieldType"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                        <option value="">All Field Types</option>
                        <option value="text">Text</option>
                        <option value="checkbox">Checkbox</option>
                        <option value="radio">Radio Button</option>
                        <option value="dropdown">Dropdown</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Fields Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                <thead class="bg-gray-100 dark:bg-zinc-900">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">PDF Field</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Mapping</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-700">
                    @forelse($fields as $field)
                        <tr class="hover:bg-gray-50 dark:hover:bg-zinc-700">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $field->field_name }}</div>
                                @if($field->field_label && $field->field_label !== $field->field_name)
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $field->field_label }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="text-lg">{{ $field->field_type_icon }}</span>
                                <span class="text-xs text-gray-600 dark:text-gray-400 ml-1">{{ $field->field_type }}</span>
                            </td>
                            <td class="px-4 py-3">
                                @if($field->isMapped())
                                    <div>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                            {{ $field->mapping_type_label }}
                                        </span>
                                        @if($field->full_mapping_path)
                                            <div class="text-xs text-gray-600 dark:text-gray-400 mt-1 font-mono">
                                                {{ $field->full_mapping_path }}
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300">
                                        Not Mapped
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm">
                                <div class="flex items-center space-x-2">
                                    <button wire:click="showEditField({{ $field->id }})"
                                            class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                        {{ $field->isMapped() ? 'Edit' : 'Map' }}
                                    </button>
                                    @if($field->isMapped())
                                        <button wire:click="unmapField({{ $field->id }})"
                                                class="text-red-600 dark:text-red-400 hover:underline">
                                            Unmap
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                                No fields found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($fields->hasPages())
            <div class="bg-white dark:bg-zinc-800 px-4 py-3 border-t border-gray-200 dark:border-zinc-700">
                {{ $fields->links() }}
            </div>
        @endif
    </div>

    <!-- Edit Field Mapping Modal -->
    @if($showEditModal)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50" wire:click.self="$set('showEditModal', false)">
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                @php
                    $editingField = $template->fieldMappings->firstWhere('id', $editingFieldId);
                @endphp

                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                        Map Field: {{ $editingField->field_name }}
                    </h3>
                    <button wire:click="$set('showEditModal', false)"
                            class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="mb-4 p-3 bg-gray-50 dark:bg-zinc-700 rounded">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <span class="text-lg">{{ $editingField->field_type_icon }}</span>
                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                {{ ucfirst($editingField->field_type) }} field
                            </span>
                        </div>
                        @if($suggestedMapping)
                            @php
                                $suggested = collect($availableFields)->firstWhere('id', $suggestedMapping);
                            @endphp
                            @if($suggested)
                                <button type="button" wire:click="$set('selectedFieldMapping', '{{ $suggestedMapping }}')"
                                        class="text-xs px-2 py-1 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 rounded hover:bg-green-200">
                                    💡 Suggestion: {{ $suggested['display'] }}
                                </button>
                            @endif
                        @endif
                    </div>
                </div>

                <div class="space-y-4">
                    <!-- Simplified Single Dropdown -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Map to Project Data
                        </label>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                            Search and select which project data should fill this PDF field
                        </p>

                        <livewire:searchable-select
                            :items="$availableFields"
                            :selected="$selectedFieldMapping"
                            name="field-mapping"
                            placeholder="Search participant, property, project fields..."
                            display-field="display"
                            secondary-field="category"
                            empty-message="No fields available"
                            :key="'field-mapping-select-' . $editingFieldId"
                        />

                        <div class="mt-3 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded">
                            <p class="text-xs text-blue-800 dark:text-blue-300">
                                <strong>Examples:</strong><br>
                                • "Participant: First Name" - Gets participant's first name<br>
                                • "Property: Street Address" - Gets property address<br>
                                • "Household: Household Code" - Gets household code via participant<br>
                                • "Project: Job Number" - Gets the project/job number
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Modal Actions -->
                <div class="mt-6 flex justify-end space-x-3 border-t dark:border-zinc-700 pt-6">
                    <button wire:click="$set('showEditModal', false)"
                            class="px-4 py-2 bg-gray-200 dark:bg-zinc-600 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-300">
                        Cancel
                    </button>
                    <button wire:click="saveFieldMapping"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                        Save Mapping
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
