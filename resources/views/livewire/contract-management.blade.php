<div class="max-w-7xl mx-auto p-6">
    <div class="bg-white dark:bg-zinc-800 shadow-lg rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-green-600 text-white p-6">
            <h1 class="text-2xl font-bold">Contract Management</h1>
            <p class="text-green-100 mt-2">Manage funding contracts and agreements</p>
        </div>

        <!-- Search and Actions -->
        @if(!$showEditForm && !$showCreateForm)
            <div class="p-4 bg-gray-50 dark:bg-zinc-700 border-b border-gray-200 dark:border-zinc-600">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-col sm:flex-row gap-4 flex-1">
                    <div class="flex-1 max-w-md">
                        <input type="text" wire:model.live="search" placeholder="Search contracts..." 
                               class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                    <div class="sm:w-32">
                        <select wire:model.live="statusFilter" 
                                class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500">
                            <option value="all">All Status</option>
                            <option value="active">Active</option>
                            <option value="upcoming">Upcoming</option>
                            <option value="expired">Expired</option>
                        </select>
                    </div>
                    <button wire:click="toggleArchivedView" 
                            class="px-4 py-2 text-sm {{ $showArchivedContracts ? 'bg-gray-600' : 'bg-gray-400' }} text-white rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500">
                        {{ $showArchivedContracts ? 'Show Active' : 'Show Archived' }}
                    </button>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('funder.management') }}" 
                       class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        Manage Funders
                    </a>
                    <!-- <a href="{{ route('contract-report-due-dates.management') }}" 
                       class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        Report Due Dates
                    </a> -->
                    <button wire:click="showCreateContract" 
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-50 cursor-not-allowed"
                            class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 transition-opacity">
                        <span wire:loading.remove wire:target="showCreateContract">Create Contract</span>
                        <span wire:loading wire:target="showCreateContract">Loading...</span>
                    </button>
                </div>
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
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{ $showCreateForm ? 'Create New Contract' : 'Edit Contract' }}</h3>
                    <button wire:click="resetContractForm" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form wire:submit="{{ $showCreateForm ? 'createContract' : 'updateContract' }}" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Title <span class="text-red-500">*</span>
                            </label>
                            <input type="text" wire:model="title" id="title" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500">
                            @error('title') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="contract_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Contract Number</label>
                            <input type="text" wire:model="contract_number" id="contract_number" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500">
                            @error('contract_number') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="cfda_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">CFDA Number</label>
                            <input type="text" wire:model="cfda_number" id="cfda_number" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500">
                            @error('cfda_number') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        {{-- Date of Award field hidden as requested --}}
                        {{--
                        <div>
                            <label for="date_of_award" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date of Award</label>
                            <input type="date" wire:model="date_of_award" id="date_of_award" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500">
                            @error('date_of_award') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        --}}

                        <div>
                            <label for="grant_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Grant Code</label>
                            <input type="text" wire:model="grant_code" id="grant_code" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500">
                            @error('grant_code') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="program_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Program <span class="text-red-500">*</span>
                            </label>
                            <select wire:model="program_id" id="program_id" 
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500">
                                <option value="">Select Program</option>
                                @foreach($programs as $program)
                                    <option value="{{ $program->id }}">
                                        {{ $program->name }}
                                        @if($program->contract_management_only)
                                            (Contract Only)
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            @if($showEditForm && $selectedContract && $selectedContract->getAllPrograms()->count() > 1)
                                <p class="text-sm text-blue-600 dark:text-blue-400 mt-1">
                                    <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                    </svg>
                                    This contract has multiple programs ({{ $selectedContract->getAllPrograms()->count() }} total). Editing will set the primary program.
                                </p>
                            @endif
                            @error('program_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="funder_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Funder <span class="text-red-500">*</span>
                            </label>
                            <select wire:model="funder_id" id="funder_id" 
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500">
                                <option value="">Select Funder</option>
                                @foreach($funders as $funder)
                                    <option value="{{ $funder->id }}">{{ $funder->display_name }}</option>
                                @endforeach
                            </select>
                            @error('funder_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Amount <span class="text-red-500">*</span>
                            </label>
                            <input type="number" step="0.01" wire:model="amount" id="amount" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500">
                            @error('amount') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Start Date <span class="text-red-500">*</span>
                            </label>
                            <input type="date" wire:model="start_date" id="start_date" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500">
                            @error('start_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">End Date</label>
                            <input type="date" wire:model="end_date" id="end_date" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500">
                            @error('end_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                    </div>

                    <div>
                        <label for="purpose" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Purpose</label>
                        <x-wysiwyg-editor model="purpose" :rows="3" placeholder="Describe the purpose of this contract..." id="purpose" />
                        @error('purpose') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                        <x-wysiwyg-editor model="notes" :rows="3" placeholder="Additional notes or special instructions..." id="notes" />
                        @error('notes') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- Document Upload -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Documents</label>
                        <input type="file" wire:model="documents" multiple 
                               class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500">
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Upload contract documents (PDF, DOC, DOCX - Max 10MB each)</p>
                        @error('documents.*') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        
                        @if($showEditForm && $selectedContract && $selectedContract->getMedia('documents')->count() > 0)
                            <div class="mt-3">
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Existing Documents:</p>
                                <div class="space-y-2">
                                    @foreach($selectedContract->getMedia('documents') as $document)
                                        <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-zinc-700 rounded">
                                            <div class="flex items-center">
                                                <svg class="w-4 h-4 text-gray-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path>
                                                </svg>
                                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ $document->name }}</span>
                                            </div>
                                            <div class="flex space-x-2">
                                                <a href="{{ $document->getAuthenticatedUrl() }}" target="_blank" 
                                                   class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-sm">View</a>
                                                <a href="{{ $document->getDownloadUrl() }}" 
                                                   class="text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-300 text-sm">Download</a>
                                                <button type="button" wire:click="removeDocument({{ $document->id }})" 
                                                        onclick="return confirm('Are you sure you want to remove this document?')"
                                                        class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 text-sm">Remove</button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" wire:click="cancelForm" 
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-zinc-700 border border-gray-300 dark:border-zinc-600 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-600 focus:outline-none focus:ring-2 focus:ring-gray-500">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                            {{ $showCreateForm ? 'Create Contract' : 'Update Contract' }}
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <!-- View Contract Modal -->
        @if($showViewModal && $selectedContract)
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
                <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                    <!-- Modal Header -->
                    <div class="bg-green-600 text-white p-6 rounded-t-lg">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xl font-bold">Contract Details</h3>
                            <button wire:click="closeViewModal" class="text-green-200 hover:text-white transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Modal Content -->
                    <div class="p-6">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Basic Information -->
                            <div class="space-y-4">
                                <h4 class="text-lg font-semibold text-gray-900 dark:text-white border-b border-gray-200 dark:border-zinc-600 pb-2">Contract Information</h4>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Title</label>
                                    <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedContract->title }}</p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Contract Number</label>
                                    <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedContract->contract_number ?? 'N/A' }}</p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">CFDA Number</label>
                                    <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedContract->cfda_number ?? 'N/A' }}</p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Grant Code</label>
                                    <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedContract->grant_code ?? 'N/A' }}</p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                                    <p class="mt-1">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                            @if($selectedContract->status === 'Active') bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200
                                            @elseif($selectedContract->status === 'Upcoming') bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200
                                            @elseif($selectedContract->status === 'Expired') bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200
                                            @else bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-200 @endif">
                                            {{ $selectedContract->status }}
                                        </span>
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Financial & Dates -->
                            <div class="space-y-4">
                                <h4 class="text-lg font-semibold text-gray-900 dark:text-white border-b border-gray-200 dark:border-zinc-600 pb-2">Financial & Timeline</h4>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Amount</label>
                                    <p class="mt-1 text-sm text-gray-900 dark:text-white font-semibold">${{ number_format($selectedContract->amount, 2) }}</p>
                                </div>
                                
                                {{-- Date of Award field hidden as requested --}}
                                {{--
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Date of Award</label>
                                    <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedContract->date_of_award?->format('M d, Y') ?? 'N/A' }}</p>
                                </div>
                                --}}
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Start Date</label>
                                    <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedContract->start_date?->format('M d, Y') ?? 'N/A' }}</p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">End Date</label>
                                    <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedContract->end_date?->format('M d, Y') ?? 'Open' }}</p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Program{{ $selectedContract->getAllPrograms()->count() > 1 ? 's' : '' }}</label>
                                    <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedContract->getDisplayProgramNames() }}</p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Funder</label>
                                    <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedContract->funder->name ?? 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Purpose & Notes -->
                        @if($selectedContract->purpose || $selectedContract->notes)
                            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-zinc-600">
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                    @if($selectedContract->purpose)
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Purpose</label>
                                            <p class="text-sm text-gray-900 dark:text-white">{{ $selectedContract->purpose }}</p>
                                        </div>
                                    @endif
                                    
                                    @if($selectedContract->notes)
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Notes</label>
                                            <p class="text-sm text-gray-900 dark:text-white">{{ $selectedContract->notes }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                        
                        <!-- Documents -->
                        @if($selectedContract->documents && $selectedContract->documents->count() > 0)
                            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-zinc-600">
                                <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Documents</h4>
                                <div class="space-y-3">
                                    @foreach($selectedContract->documents as $document)
                                        <div class="flex items-start p-4 bg-gray-50 dark:bg-zinc-700 rounded-lg border border-gray-200 dark:border-zinc-600">
                                            <div class="flex-shrink-0">
                                                <svg class="w-8 h-8 {{ $document->getFileTypeIcon() }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                </svg>
                                            </div>
                                            <div class="ml-3 flex-1 min-w-0">
                                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                                                    <div class="flex-1 min-w-0">
                                                        <p class="text-sm font-medium text-gray-900 dark:text-white break-words">{{ $document->name }}</p>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                            {{ $document->getFileTypeLabel() }} • {{ $document->human_readable_size }}
                                                        </p>
                                                    </div>
                                                    <div class="flex space-x-3 mt-2 sm:mt-0 sm:ml-4 flex-shrink-0">
                                                        <a href="{{ $document->getAuthenticatedUrl() }}" target="_blank" 
                                                           class="inline-flex items-center px-3 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-800 hover:bg-blue-200 dark:bg-blue-900/30 dark:text-blue-400 dark:hover:bg-blue-900/50 transition-colors">
                                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                            </svg>
                                                            View
                                                        </a>
                                                        <a href="{{ $document->getDownloadUrl() }}" 
                                                           class="inline-flex items-center px-3 py-1 rounded-md text-xs font-medium bg-green-100 text-green-800 hover:bg-green-200 dark:bg-green-900/30 dark:text-green-400 dark:hover:bg-green-900/50 transition-colors">
                                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                            </svg>
                                                            Download
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        
                        <!-- Report Due Dates -->
                        <div class="mt-6 pt-6 border-t border-gray-200 dark:border-zinc-600">
                            <div class="flex justify-between items-center mb-4">
                                <h4 class="text-lg font-semibold text-gray-900 dark:text-white">Report Due Dates</h4>
                                @if($this->can('due_dates.create'))
                                    <button wire:click="showCreateDueDate" 
                                            class="inline-flex items-center px-3 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-800 hover:bg-blue-200 dark:bg-blue-900/30 dark:text-blue-400 dark:hover:bg-blue-900/50 transition-colors">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                        </svg>
                                        Add Due Date
                                    </button>
                                @endif
                            </div>
                            
                            @if($selectedContract->reportDueDates && $selectedContract->reportDueDates->count() > 0)
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-600">
                                        <thead class="bg-gray-50 dark:bg-zinc-700">
                                            <tr>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Report Name</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Due Date</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Reports Uploaded</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Assigned Staff</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-600">
                                            @foreach($selectedContract->reportDueDates as $dueDate)
                                                <tr>
                                                    <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">
                                                        <button wire:click="viewDueDate({{ $dueDate->id }})" 
                                                                class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 hover:underline">
                                                            {{ strip_tags($dueDate->report_name ?? $dueDate->report_type) }}
                                                        </button>
                                                        @if($dueDate->description)
                                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ Str::limit(strip_tags($dueDate->description), 50) }}</p>
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">
                                                        {{ $dueDate->due_date?->format('M d, Y') }}
                                                        @if($dueDate->is_overdue)
                                                            <span class="text-red-600 dark:text-red-400 text-xs block">
                                                                ({{ abs($dueDate->days_until_due) }} days overdue)
                                                            </span>
                                                        @elseif($dueDate->days_until_due > 0)
                                                            <span class="text-yellow-600 dark:text-yellow-400 text-xs block">
                                                                ({{ $dueDate->days_until_due }} days remaining)
                                                            </span>
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-2 text-sm">
                                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                                            @if($dueDate->status === 'completed') bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200
                                                            @elseif($dueDate->is_overdue) bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200
                                                            @else bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 @endif">
                                                            {{ ucfirst($dueDate->status) }}
                                                        </span>
                                                    </td>
                                                    <td class="px-4 py-2 text-sm">
                                                        @if($dueDate->contractReports && $dueDate->contractReports->count() > 0)
                                                            <div class="space-y-1">
                                                                <div class="text-gray-900 dark:text-white">
                                                                    {{ $dueDate->contractReports->count() }} report(s)
                                                                </div>
                                                                @foreach($dueDate->contractReports->take(2) as $report)
                                                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                                                        <div class="font-medium">{{ Str::limit($report->title, 30) }}</div>
                                                                        <div>
                                                                            By {{ $report->creator->name ?? 'Unknown' }} 
                                                                            on {{ $report->created_at->format('M d, Y g:i A') }}
                                                                        </div>
                                                                        @if($report->documents->count() > 0)
                                                                            <div class="text-blue-600 dark:text-blue-400">
                                                                                {{ $report->documents->count() }} file(s) attached
                                                                            </div>
                                                                        @endif
                                                                    </div>
                                                                @endforeach
                                                                @if($dueDate->contractReports->count() > 2)
                                                                    <div class="text-xs text-gray-500 dark:text-gray-400 italic">
                                                                        +{{ $dueDate->contractReports->count() - 2 }} more
                                                                    </div>
                                                                @endif
                                                                <button wire:click="viewDueDateReports({{ $dueDate->id }})" 
                                                                        class="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-medium">
                                                                    View All Reports
                                                                </button>
                                                            </div>
                                                        @else
                                                            <span class="text-gray-500 dark:text-gray-400 text-xs">No reports uploaded</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">
                                                        @if($dueDate->emails && $dueDate->emails->count() > 0)
                                                            <button wire:click="showAssignedUsers({{ $dueDate->id }})"
                                                                    class="flex -space-x-2 group cursor-pointer hover:opacity-80 transition-opacity">
                                                                @foreach($dueDate->emails->take(3) as $email)
                                                                    <div class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gray-300 dark:bg-zinc-600 text-xs font-medium text-gray-700 dark:text-gray-200 ring-2 ring-white dark:ring-zinc-800 group-hover:ring-blue-500" 
                                                                         title="{{ $email->name ?: $email->email }}">
                                                                        {{ strtoupper(substr($email->name ?: $email->email, 0, 2)) }}
                                                                    </div>
                                                                @endforeach
                                                                @if($dueDate->emails->count() > 3)
                                                                    <div class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gray-400 dark:bg-zinc-500 text-xs font-medium text-white ring-2 ring-white dark:ring-zinc-800 group-hover:ring-blue-500"
                                                                         title="{{ $dueDate->emails->skip(3)->pluck('name')->filter()->implode(', ') ?: $dueDate->emails->skip(3)->pluck('email')->implode(', ') }}">
                                                                        +{{ $dueDate->emails->count() - 3 }}
                                                                    </div>
                                                                @endif
                                                            </button>
                                                        @else
                                                            <span class="text-gray-500 dark:text-gray-400 text-xs">No staff assigned</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-2 text-sm">
                                                        <div class="flex items-center space-x-2">
                                                            @if($this->can('contract_reports.create'))
                                                                <button wire:click="showCreateReport({{ $dueDate->id }})" 
                                                                        class="text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-300 text-xs font-medium">
                                                                    Upload Report
                                                                </button>
                                                            @endif
                                                            @if($this->can('due_dates.edit'))
                                                                <button wire:click="showEditDueDate({{ $dueDate->id }})" 
                                                                        class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-xs font-medium">
                                                                    Edit
                                                                </button>
                                                            @endif
                                                            @if($this->can('due_dates.edit'))
                                                                <button wire:click="toggleDueDateStatus({{ $dueDate->id }})" 
                                                                        class="text-purple-600 dark:text-purple-400 hover:text-purple-800 dark:hover:text-purple-300 text-xs font-medium">
                                                                    {{ $dueDate->status === 'completed' ? 'Reopen' : 'Complete' }}
                                                                </button>
                                                            @endif
                                                            @if($this->can('due_dates.delete'))
                                                                <button wire:click="deleteDueDate({{ $dueDate->id }})" 
                                                                        onclick="return confirm('Are you sure you want to delete this due date?')"
                                                                        class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 text-xs font-medium">
                                                                    Delete
                                                                </button>
                                                            @endif
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-8">
                                    <svg class="w-12 h-12 mx-auto text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <p class="text-gray-500 dark:text-gray-400 mt-2">No report due dates found for this contract.</p>
                                    @if($this->can('due_dates.create'))
                                        <button wire:click="showCreateDueDate" 
                                                class="mt-4 inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                                            Add First Due Date
                                        </button>
                                    @endif
                                </div>
                            @endif
                        </div>
                        
                        <!-- Contract Reports -->
                        @if($selectedContract->contractReports && $selectedContract->contractReports->count() > 0)
                            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-zinc-600">
                                <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Contract Reports</h4>
                                <div class="space-y-3">
                                    @foreach($selectedContract->contractReports as $report)
                                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-zinc-700 rounded-lg">
                                            <div class="flex-1 min-w-0">
                                                <button wire:click="viewReport({{ $report->id }})" 
                                                        class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 hover:underline font-medium">
                                                    {{ $report->title }}
                                                </button>
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                    {{ $report->description ? Str::limit(strip_tags($report->description), 100) : 'No description' }}
                                                </p>
                                                <div class="flex items-center space-x-4 mt-2">
                                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                                        @if($report->status === 'approved') bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200
                                                        @elseif($report->status === 'submitted') bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200
                                                        @elseif($report->status === 'rejected') bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200
                                                        @else bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-200 @endif">
                                                        {{ ucfirst(str_replace('_', ' ', $report->status)) }}
                                                    </span>
                                                    @if($report->submitted_date)
                                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                                            Submitted: {{ $report->submitted_date->format('M d, Y') }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="flex items-center space-x-2 ml-4">
                                                @if($this->can('reports.edit') && $report->isEditable())
                                                    <button wire:click="showEditReport({{ $report->id }})" 
                                                            class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-xs font-medium">
                                                        Edit
                                                    </button>
                                                @endif
                                                @if($this->can('reports.edit') && $report->canBeSubmitted())
                                                    <button wire:click="submitReport({{ $report->id }})" 
                                                            class="text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-300 text-xs font-medium">
                                                        Submit
                                                    </button>
                                                @endif
                                                @if($this->can('reports.delete'))
                                                    <button wire:click="deleteReport({{ $report->id }})" 
                                                            onclick="return confirm('Are you sure you want to delete this report?')"
                                                            class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 text-xs font-medium">
                                                        Delete
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                    
                    <!-- Modal Footer -->
                    <div class="bg-gray-50 dark:bg-zinc-700 px-6 py-4 rounded-b-lg">
                        <div class="flex justify-end space-x-2">
                            <button wire:click="closeViewModal" 
                                    class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-300 dark:hover:bg-gray-500 transition">
                                Close
                            </button>
                            @if($this->can('contracts.edit'))
                                <button wire:click="showEditContract({{ $selectedContract->id }})" 
                                        class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition">
                                    Edit Contract
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Due Date Form Modal -->
        @if($showDueDateForm)
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
                <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                            {{ $selectedDueDate ? 'Edit Report Due Date' : 'Create Report Due Date' }}
                        </h3>
                        <button wire:click="closeDueDateForm" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <form wire:submit="saveDueDate" class="space-y-4">
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
                            <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                            <x-wysiwyg-editor model="description" :rows="3" placeholder="Describe this due date or report requirement..." id="report_description" />
                            @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Reminder Intervals</label>
                            <div class="grid grid-cols-2 gap-2">
                                @foreach($reminderIntervalOptions as $days => $label)
                                    <label class="flex items-center">
                                        <input type="checkbox" wire:model="reminder_intervals" value="{{ $days }}" 
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-zinc-600 rounded">
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('reminder_intervals') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Assigned Staff</label>
                            <div class="space-y-2 max-h-32 overflow-y-auto">
                                @foreach($users as $user)
                                    <label class="flex items-center">
                                        <input type="checkbox" wire:model="assigned_users" value="{{ $user->id }}" 
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-zinc-600 rounded">
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $user->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('assigned_users') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" wire:model="active" id="active" 
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-zinc-600 rounded">
                            <label for="active" class="ml-2 text-sm text-gray-700 dark:text-gray-300">Active</label>
                        </div>

                        <div class="flex justify-end space-x-2 pt-4">
                            <button type="button" wire:click="closeDueDateForm" 
                                    class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-300 dark:hover:bg-gray-500 transition">
                                Cancel
                            </button>
                            <button type="submit" 
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                                {{ $selectedDueDate ? 'Update' : 'Create' }} Due Date
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        <!-- Report Form Modal -->
        @if($showReportForm)
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
                <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                            {{ $selectedReport ? 'Edit Report' : 'Create Report' }}
                        </h3>
                        <button wire:click="closeReportForm" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <form wire:submit="saveReport" class="space-y-4">
                        @if($selectedDueDate)
                            <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-md">
                                <p class="text-sm text-blue-700 dark:text-blue-300">
                                    <span class="font-medium">Due Date:</span> {{ strip_tags($selectedDueDate->report_name) }} - {{ $selectedDueDate->due_date?->format('M d, Y') }}
                                </p>
                            </div>
                        @endif

                        <div>
                            <label for="report_title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Report Title <span class="text-red-500">*</span>
                            </label>
                            <input type="text" wire:model="report_title" id="report_title" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('report_title') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="report_description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Description
                            </label>
                            <x-wysiwyg-editor model="report_description" :rows="4" placeholder="Describe the report requirements and expectations..." id="report_description_edit" />
                            @error('report_description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="report_notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                            <x-wysiwyg-editor model="report_notes" :rows="3" placeholder="Additional notes about this report..." id="report_notes_edit" />
                            @error('report_notes') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="report_documents" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Report Files <span class="text-red-500">*</span>
                            </label>
                            <input type="file" wire:model="report_documents" id="report_documents" multiple
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Maximum file size: 100MB per file. You can upload multiple files.</p>
                            @error('report_documents') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            @error('report_documents.*') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            
                            <div wire:loading wire:target="report_documents" class="mt-2 text-sm text-blue-600 dark:text-blue-400">
                                Uploading files...
                            </div>
                        </div>

                        <div class="flex justify-end space-x-2 pt-4">
                            <button type="button" wire:click="closeReportForm" 
                                    class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-300 dark:hover:bg-gray-500 transition">
                                Cancel
                            </button>
                            <button type="submit" 
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                                Upload Report
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        <!-- Report View Modal -->
        @if($showReportView && $selectedReport)
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
                <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{ $selectedReport->title }}</h3>
                        <button wire:click="closeReportView" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                    @if($selectedReport->status === 'approved') bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200
                                    @elseif($selectedReport->status === 'submitted') bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200
                                    @elseif($selectedReport->status === 'rejected') bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200
                                    @else bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-200 @endif">
                                    {{ ucfirst(str_replace('_', ' ', $selectedReport->status)) }}
                                </span>
                            </div>
                            
                            @if($selectedReport->submitted_date)
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Submitted Date</label>
                                    <p class="text-sm text-gray-900 dark:text-white">{{ $selectedReport->submitted_date->format('M d, Y') }}</p>
                                </div>
                            @endif
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                            <p class="text-sm text-gray-900 dark:text-white">{{ strip_tags($selectedReport->description) }}</p>
                        </div>

                        @if($selectedReport->notes)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Notes</label>
                                <p class="text-sm text-gray-900 dark:text-white">{{ $selectedReport->notes }}</p>
                            </div>
                        @endif

                        @if($selectedReport->documents && $selectedReport->documents->count() > 0)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Documents</label>
                                <div class="space-y-2">
                                    @foreach($selectedReport->documents as $document)
                                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-zinc-700 rounded-lg">
                                            <div class="flex items-center">
                                                <svg class="w-5 h-5 {{ $document->getFileTypeIcon() }} mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                </svg>
                                                <div>
                                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $document->name }}</p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $document->getFileTypeLabel() }} • {{ $document->human_readable_size }}</p>
                                                </div>
                                            </div>
                                            <div class="flex space-x-2">
                                                <a href="{{ $document->getAuthenticatedUrl() }}" target="_blank" 
                                                   class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-sm font-medium">
                                                    View
                                                </a>
                                                <a href="{{ $document->getDownloadUrl() }}" 
                                                   class="text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-300 text-sm font-medium">
                                                    Download
                                                </a>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="flex justify-end space-x-2 pt-6">
                        <button wire:click="closeReportView" 
                                class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-300 dark:hover:bg-gray-500 transition">
                            Close
                        </button>
                        @if($this->can('reports.edit') && $selectedReport->isEditable())
                            <button wire:click="showEditReport({{ $selectedReport->id }})" 
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                                Edit Report
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <!-- Tabs -->
        @if(!$showEditForm && !$showCreateForm)
            <div class="border-b border-gray-200 dark:border-zinc-600">
                <nav class="-mb-px flex space-x-8 px-6">
                    <button wire:click="setActiveTab('contracts')"
                            class="py-2 px-1 border-b-2 font-medium text-sm transition-colors
                                   {{ $activeTab === 'contracts' 
                                      ? 'border-green-500 text-green-600 dark:text-green-400' 
                                      : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                        Contracts
                    </button>
                    
                    @if($this->can('due_dates.view'))
                        <button wire:click="setActiveTab('upcoming-deadlines')"
                                class="py-2 px-1 border-b-2 font-medium text-sm transition-colors
                                       {{ $activeTab === 'upcoming-deadlines' 
                                          ? 'border-green-500 text-green-600 dark:text-green-400' 
                                          : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                            Upcoming Deadlines
                        </button>
                    @endif
                </nav>
            </div>
        @endif

        <!-- Tab Content -->
        @if(!$showEditForm && !$showCreateForm)
            @if($activeTab === 'contracts')
                <!-- Contracts Table -->
                <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-600">
                <thead class="bg-gray-50 dark:bg-zinc-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Title</th>
                        <!--th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Contract Number</th-->
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Program</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Funder</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Period</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-600">
                    @forelse($contracts as $contract)
                        <tr class="{{ $contract->archived ? 'bg-gray-100 dark:bg-zinc-900' : '' }}">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium">
                                    <button wire:click="viewContract({{ $contract->id }})" 
                                            class="text-gray-900 dark:text-white hover:text-green-600 dark:hover:text-green-400 hover:underline cursor-pointer transition-colors duration-200">
                                        {{ $contract->title }}
                                    </button>
                                </div>
                                @if($contract->grant_code)
                                    <div class="text-sm text-gray-500 dark:text-gray-400">Grant: {{ $contract->grant_code }}</div>
                                @endif
                                @if($contract->reportDueDates->where('status', '!=', 'completed')->count() > 0)
                                    <div class="text-xs text-orange-600 dark:text-orange-400">{{ $contract->reportDueDates->where('status', '!=', 'completed')->count() }} pending reports</div>
                                @endif
                            </td>
                            <!-- <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $contract->contract_number ?? 'N/A' }}</td> -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $contract->getDisplayProgramNamesWithCount() }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $contract->funder->name ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${{ number_format($contract->amount, 2) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                <div>{{ $contract->start_date?->format('M j, Y') ?? 'N/A' }}</div>
                                <div>{{ $contract->end_date?->format('M j, Y') ?? 'Open' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                    {{ $contract->status === 'Active' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300' : 
                                       ($contract->status === 'Expired' ? 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-300' : 
                                        ($contract->status === 'Upcoming' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300' : 
                                         ($contract->status === 'Archived' ? 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-300' : 
                                          'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300'))) }}">
                                    {{ $contract->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <button wire:click="showEditContract({{ $contract->id }})" 
                                            class="text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300">Edit</button>
                                    @if($contract->archived)
                                        <button wire:click="unarchiveContract({{ $contract->id }})" 
                                                class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300">Unarchive</button>
                                    @else
                                        <button wire:click="archiveContract({{ $contract->id }})" 
                                                onclick="return confirm('Are you sure you want to archive this contract?')"
                                                class="text-yellow-600 dark:text-yellow-400 hover:text-yellow-900 dark:hover:text-yellow-300">Archive</button>
                                    @endif
                                    <button wire:click="deleteContract({{ $contract->id }})" 
                                            onclick="return confirm('Are you sure you want to delete this contract?')"
                                            class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">Delete</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                {{ $showArchivedContracts ? 'No archived contracts found.' : 'No contracts found.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>

                <!-- Pagination -->
                @if($contracts->hasPages())
                    <div class="px-6 py-3 border-t border-gray-200 dark:border-zinc-600">
                        {{ $contracts->links() }}
                    </div>
                @endif
            @endif
            
            @if($activeTab === 'upcoming-deadlines' && $this->can('due_dates.view'))
                <!-- Upcoming Deadlines Table -->
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Upcoming Report Deadlines</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-600">
                            <thead class="bg-gray-50 dark:bg-zinc-700">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Due Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Report Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Contract</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Contract #</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Program</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Funder</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Days</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Assigned</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-600">
                                @forelse($upcomingDeadlines as $deadline)
                                    @php
                                        $daysUntilDue = now()->startOfDay()->diffInDays($deadline->due_date, false);
                                        $isOverdue = $daysUntilDue < 0;
                                        $isDueSoon = $daysUntilDue >= 0 && $daysUntilDue <= 7;
                                    @endphp
                                    <tr class="{{ $isOverdue ? 'bg-red-50 dark:bg-red-900/20' : ($isDueSoon ? 'bg-yellow-50 dark:bg-yellow-900/20' : '') }}">
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="text-sm font-semibold {{ $isOverdue ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">
                                                {{ $deadline->due_date->format('M d, Y') }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ strip_tags($deadline->report_name) }}
                                            </div>
                                            @if($deadline->description)
                                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                    {{ Str::limit(strip_tags($deadline->description), 50) }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="text-sm text-gray-900 dark:text-white">
                                                {{ $deadline->contract->title }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ $deadline->contract->contract_number }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ $deadline->contract->getDisplayProgramNamesWithCount() }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ $deadline->contract->funder->name ?? 'N/A' }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            @if($isOverdue)
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                                    {{ abs($daysUntilDue) }} days overdue
                                                </span>
                                            @elseif($isDueSoon)
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                                    {{ $daysUntilDue }} days
                                                </span>
                                            @else
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                    {{ $daysUntilDue }} days
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                                @if($deadline->status === 'completed') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                                @elseif($deadline->status === 'in_progress') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                                @else bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200 @endif">
                                                {{ ucfirst($deadline->status) }}
                                            </span>
                                            @if($deadline->contractReports->count() > 0)
                                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                    {{ $deadline->contractReports->count() }} report(s)
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            @if($deadline->emails && $deadline->emails->count() > 0)
                                                <button wire:click="showAssignedUsers({{ $deadline->id }})"
                                                        class="flex -space-x-2 group cursor-pointer hover:opacity-80 transition-opacity">
                                                    @foreach($deadline->emails->take(3) as $email)
                                                        <div class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gray-300 dark:bg-zinc-600 text-xs font-medium text-gray-700 dark:text-gray-200 ring-2 ring-white dark:ring-zinc-800 group-hover:ring-blue-500" 
                                                             title="{{ $email->name ?: $email->email }}">
                                                            {{ strtoupper(substr($email->name ?: $email->email, 0, 2)) }}
                                                        </div>
                                                    @endforeach
                                                    @if($deadline->emails->count() > 3)
                                                        <div class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gray-400 dark:bg-zinc-500 text-xs font-medium text-white ring-2 ring-white dark:ring-zinc-800 group-hover:ring-blue-500"
                                                             title="{{ $deadline->emails->skip(3)->pluck('name')->filter()->implode(', ') ?: $deadline->emails->skip(3)->pluck('email')->implode(', ') }}">
                                                            +{{ $deadline->emails->count() - 3 }}
                                                        </div>
                                                    @endif
                                                </button>
                                            @else
                                                <span class="text-gray-500 dark:text-gray-400 text-xs">None</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">
                                            <div class="flex items-center space-x-2">
                                                <button wire:click="viewContract({{ $deadline->contract_id }})" 
                                                        class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300"
                                                        title="View Contract">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                    </svg>
                                                </button>
                                                
                                                @if($deadline->contractReports->count() > 0)
                                                    <button wire:click="viewDueDateReports({{ $deadline->id }})" 
                                                            class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                                            title="View Reports">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                        </svg>
                                                    </button>
                                                @endif
                                                
                                                @if($deadline->status !== 'completed')
                                                    <button wire:click="selectedContract = {{ $deadline->contract_id }}; selectedDueDate = {{ $deadline->id }}; showCreateReport({{ $deadline->id }})" 
                                                            class="text-purple-600 hover:text-purple-900 dark:text-purple-400 dark:hover:text-purple-300"
                                                            title="Upload Report">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                                        </svg>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                            No upcoming deadlines found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        @endif
    </div>
    
    <!-- Due Date Reports Modal -->
    @if($showDueDateReportsModal && $selectedDueDate)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl p-6 max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                            Reports for: {{ strip_tags($selectedDueDate->report_name) }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Due Date: {{ $selectedDueDate->due_date->format('M d, Y') }} | 
                            Contract: {{ $selectedDueDate->contract->title }}
                        </p>
                    </div>
                    <button wire:click="closeDueDateReportsModal" 
                            class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <div class="space-y-4">
                    @if($selectedDueDate->contractReports->count() > 0)
                        @foreach($selectedDueDate->contractReports as $report)
                            <div class="border border-gray-200 dark:border-zinc-600 rounded-lg p-4">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <h4 class="font-medium text-gray-900 dark:text-white">{{ $report->title }}</h4>
                                        @if($report->description)
                                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">{{ strip_tags($report->description) }}</p>
                                        @endif
                                        
                                        <div class="mt-2 space-y-1">
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                <span class="font-medium">Uploaded by:</span> {{ $report->creator->name ?? 'Unknown' }}
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                <span class="font-medium">Uploaded on:</span> {{ $report->created_at->format('M d, Y g:i A') }}
                                            </p>
                                            @if($report->submitted_date)
                                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                                    <span class="font-medium">Submitted date:</span> {{ $report->submitted_date->format('M d, Y') }}
                                                </p>
                                            @endif
                                        </div>
                                        
                                        <!-- Report Files -->
                                        @if($report->documents->count() > 0)
                                            <div class="mt-3">
                                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Attached Files:</p>
                                                <div class="space-y-2">
                                                    @foreach($report->documents as $document)
                                                        <div class="flex items-center justify-between bg-gray-50 dark:bg-zinc-700 rounded p-2">
                                                            <div class="flex items-center space-x-2">
                                                                <svg class="h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                                                </svg>
                                                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ $document->name }}</span>
                                                                <span class="text-xs text-gray-500 dark:text-gray-400">({{ $document->human_readable_size }})</span>
                                                            </div>
                                                            <button wire:click="downloadReport({{ $report->id }}, {{ $document->id }})"
                                                                    class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
                                                                Download
                                                            </button>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="ml-4">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                            {{ $report->status === 'approved' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300' : 
                                               ($report->status === 'rejected' ? 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-300' : 
                                               ($report->status === 'submitted' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300' :
                                                'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-300')) }}">
                                            {{ ucfirst($report->status) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <p class="text-center text-gray-500 dark:text-gray-400 py-4">No reports have been uploaded for this due date.</p>
                    @endif
                    
                    <div class="flex justify-end pt-4 border-t border-gray-200 dark:border-zinc-600">
                        <button wire:click="closeDueDateReportsModal"
                                class="px-4 py-2 bg-gray-200 dark:bg-zinc-600 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-500">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
    
    <!-- Assigned Users Modal -->
    @if($showAssignedUsersModal && $selectedDueDateForUsers)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-2xl w-full mx-4 max-h-[80vh] overflow-y-auto">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Assigned Users for {{ strip_tags($selectedDueDateForUsers->report_name) }}
                    </h3>
                    <button wire:click="closeAssignedUsersModal"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <div class="space-y-3">
                    @if($selectedDueDateForUsers->emails->count() > 0)
                        @foreach($selectedDueDateForUsers->emails as $email)
                            <div class="flex items-center space-x-3 p-3 bg-gray-50 dark:bg-zinc-700 rounded-lg">
                                <div class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-gray-300 dark:bg-zinc-600 text-sm font-medium text-gray-700 dark:text-gray-200">
                                    {{ strtoupper(substr($email->name ?: $email->email, 0, 2)) }}
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $email->name ?: 'No Name' }}
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $email->email }}
                                    </p>
                                </div>
                                @if($email->pivot->reminder_intervals)
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        Reminders: {{ implode(', ', json_decode($email->pivot->reminder_intervals, true) ?? []) }} days
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    @else
                        <p class="text-center text-gray-500 dark:text-gray-400 py-4">No users are assigned to this due date.</p>
                    @endif
                </div>
                
                <div class="flex justify-end pt-4 border-t border-gray-200 dark:border-zinc-600 mt-4">
                    <button wire:click="closeAssignedUsersModal"
                            class="px-4 py-2 bg-gray-200 dark:bg-zinc-600 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-500">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>