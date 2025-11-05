<div class="w-full px-0 py-6">
    <div class="bg-white dark:bg-zinc-800 shadow-lg rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-indigo-600 text-white p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">PDF Form Templates</h1>
                    <p class="text-indigo-100 mt-2">Manage reusable PDF forms for auto-filling with participant and project data</p>
                </div>
                @if($this->can('pdf_templates.create'))
                    <button wire:click="showUploadTemplateForm"
                            class="px-4 py-2 bg-white text-indigo-600 rounded-md hover:bg-indigo-50 font-semibold">
                        + Upload New Template
                    </button>
                @endif
            </div>
        </div>

        <!-- PDFtk Status Banner -->
        @if(!$pdftkInstalled)
            <div class="p-4 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-400">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-red-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <div>
                        <p class="text-sm font-semibold text-red-700 dark:text-red-300">PDFtk Not Installed</p>
                        <p class="text-xs text-red-600 dark:text-red-400 mt-1">
                            PDF form automation requires PDFtk. Please install it with: <code class="bg-red-100 dark:bg-red-900 px-1 rounded">sudo apt-get install pdftk</code>
                        </p>
                    </div>
                </div>
            </div>
        @elseif($pdftkVersion)
            <div class="p-2 bg-green-50 dark:bg-green-900/20 border-l-4 border-green-400">
                <p class="text-xs text-green-700 dark:text-green-300">
                    PDFtk is installed and ready
                </p>
            </div>
        @endif

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

        <!-- Filters & Search -->
        @if(!$showUploadForm && !$showEditForm)
        <div class="p-4 bg-gray-50 dark:bg-zinc-700 border-b dark:border-zinc-600">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <input type="text" wire:model.live="search" placeholder="Search templates..."
                           class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <select wire:model.live="filterCategory"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                        <option value="">All Categories</option>
                        <option value="housing">Housing</option>
                        <option value="participant">Participant</option>
                        <option value="contract">Contract</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div>
                    <select wire:model.live="filterActive"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                        <option value="">All Templates</option>
                        <option value="active">Active Only</option>
                        <option value="inactive">Inactive Only</option>
                    </select>
                </div>
            </div>
        </div>
        @endif

        <!-- Templates Table -->
        @if(!$showUploadForm && !$showEditForm)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                <thead class="bg-gray-100 dark:bg-zinc-900">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Template</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Category</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Fields</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Usage</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-700">
                    @forelse($templates as $template)
                        <tr class="hover:bg-gray-50 dark:hover:bg-zinc-700">
                            <td class="px-4 py-3">
                                <div class="font-semibold text-gray-900 dark:text-white">{{ $template->name }}</div>
                                @if($template->description)
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ Str::limit($template->description, 60) }}</div>
                                @endif
                                <div class="text-xs text-gray-400 mt-1">{{ $template->original_filename }}</div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if($template->category)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @if($template->category === 'housing') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300
                                        @elseif($template->category === 'participant') bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300
                                        @elseif($template->category === 'contract') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300
                                        @else bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300
                                        @endif">
                                        {{ ucfirst($template->category) }}
                                    </span>
                                @else
                                    <span class="text-sm text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">
                                    {{ $template->field_count }} total
                                </div>
                                <div class="text-xs {{ $template->mapping_completion >= 100 ? 'text-green-600 dark:text-green-400' : ($template->mapping_completion > 0 ? 'text-yellow-600 dark:text-yellow-400' : 'text-gray-400') }}">
                                    {{ $template->mapped_field_count }} mapped ({{ $template->mapping_completion }}%)
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $template->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300' }}">
                                    {{ $template->is_active ? 'Active' : 'Inactive' }}
                                </span>
                                <div class="text-xs text-gray-400 mt-1">{{ $template->formatted_file_size }}</div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                {{ $template->usage_count }} times
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm">
                                <div class="flex items-center space-x-2">
                                    <a href="{{ route('pdf-templates.map-fields', $template->id) }}" wire:navigate
                                       class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                        Map Fields
                                    </a>
                                    @if($this->can('pdf_templates.edit'))
                                        <button wire:click="showEditTemplate({{ $template->id }})"
                                                class="text-gray-600 dark:text-gray-400 hover:text-gray-900">
                                            Edit
                                        </button>
                                        <button wire:click="rescanFields({{ $template->id }})"
                                                class="text-gray-600 dark:text-gray-400 hover:text-gray-900"
                                                title="Rescan form fields">
                                            🔄
                                        </button>
                                        <button wire:click="toggleActive({{ $template->id }})"
                                                class="text-gray-600 dark:text-gray-400 hover:text-gray-900"
                                                title="{{ $template->is_active ? 'Deactivate' : 'Activate' }}">
                                            {{ $template->is_active ? '⏸️' : '▶️' }}
                                        </button>
                                    @endif
                                    <button wire:click="downloadTemplate({{ $template->id }})"
                                            class="text-gray-600 dark:text-gray-400 hover:text-gray-900"
                                            title="Download template">
                                        ⬇️
                                    </button>
                                    @if($this->can('pdf_templates.delete'))
                                        <button wire:click="deleteTemplate({{ $template->id }})"
                                                wire:confirm="Delete this template and all its mappings?"
                                                class="text-red-600 dark:text-red-400 hover:text-red-800">
                                            Delete
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <p class="text-lg font-medium text-gray-500 dark:text-gray-400">No templates found</p>
                                    <p class="text-sm text-gray-400 mt-1">Upload your first PDF form template to get started</p>
                                    @if($this->can('pdf_templates.create') && $pdftkInstalled)
                                        <button wire:click="showUploadTemplateForm"
                                                class="mt-4 px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                                            Upload First Template
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($templates->hasPages())
            <div class="bg-white dark:bg-zinc-800 px-4 py-3 border-t border-gray-200 dark:border-zinc-700">
                {{ $templates->links() }}
            </div>
        @endif
        @endif

        <!-- Upload Form -->
        @if($showUploadForm)
            <div class="p-6">
                <div class="mb-6 flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Upload PDF Template</h2>
                    <button wire:click="$set('showUploadForm', false)"
                            class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="space-y-4 max-w-2xl">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Template Name *</label>
                        <input type="text" wire:model="name" placeholder="e.g., CSBG Intake Form"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                        @error('name') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                        <textarea wire:model="description" rows="3" placeholder="Brief description of this form's purpose..."
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md"></textarea>
                        @error('description') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category</label>
                        <select wire:model="category"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                            <option value="">Select Category</option>
                            <option value="housing">Housing</option>
                            <option value="participant">Participant</option>
                            <option value="contract">Contract</option>
                            <option value="other">Other</option>
                        </select>
                        @error('category') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">PDF Template File *</label>
                        <input type="file" wire:model="templateFile" accept="application/pdf"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                        @error('templateFile') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Must be a fillable PDF form with AcroForm fields. Maximum size: 50MB.
                        </p>

                        @if($templateFile)
                            <div class="mt-2 p-2 bg-indigo-50 dark:bg-indigo-900/20 rounded">
                                <p class="text-xs text-indigo-700 dark:text-indigo-300">
                                    Selected: {{ $templateFile->getClientOriginalName() }} ({{ round($templateFile->getSize() / 1024 / 1024, 2) }} MB)
                                </p>
                            </div>
                        @endif
                    </div>

                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-md p-3">
                        <p class="text-xs text-blue-800 dark:text-blue-300">
                            After upload, the system will automatically scan the PDF and extract all form fields. You'll then be able to map each field to data from your system.
                        </p>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="mt-6 flex justify-end space-x-3 border-t dark:border-zinc-700 pt-6">
                    <button wire:click="$set('showUploadForm', false)"
                            class="px-4 py-2 bg-gray-200 dark:bg-zinc-600 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-300">
                        Cancel
                    </button>
                    <button wire:click="uploadTemplate"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700"
                            wire:loading.attr="disabled"
                            wire:target="templateFile">
                        <span wire:loading.remove wire:target="uploadTemplate">Upload & Scan Fields</span>
                        <span wire:loading wire:target="uploadTemplate">Uploading...</span>
                    </button>
                </div>
            </div>
        @endif

        <!-- Edit Form -->
        @if($showEditForm)
            <div class="p-6">
                <div class="mb-6 flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Edit Template</h2>
                    <button wire:click="$set('showEditForm', false)"
                            class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="space-y-4 max-w-2xl">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Template Name *</label>
                        <input type="text" wire:model="name"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                        @error('name') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                        <textarea wire:model="description" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md"></textarea>
                        @error('description') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category</label>
                        <select wire:model="category"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                            <option value="">Select Category</option>
                            <option value="housing">Housing</option>
                            <option value="participant">Participant</option>
                            <option value="contract">Contract</option>
                            <option value="other">Other</option>
                        </select>
                        @error('category') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="mt-6 flex justify-end space-x-3 border-t dark:border-zinc-700 pt-6">
                    <button wire:click="$set('showEditForm', false)"
                            class="px-4 py-2 bg-gray-200 dark:bg-zinc-600 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-300">
                        Cancel
                    </button>
                    <button wire:click="updateTemplate"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                        Update Template
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>
