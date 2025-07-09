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
                    <a href="{{ route('contract-report-due-dates.management') }}" 
                       class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        Report Due Dates
                    </a>
                    <button wire:click="showCreateContract" 
                            class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                        Create Contract
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

                        <div>
                            <label for="date_of_award" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date of Award</label>
                            <input type="date" wire:model="date_of_award" id="date_of_award" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500">
                            @error('date_of_award') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

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
                        <textarea wire:model="purpose" id="purpose" rows="3" 
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
                        @error('purpose') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                        <textarea wire:model="notes" id="notes" rows="3" 
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
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
                        <button type="button" wire:click="$set('showCreateForm', false); $set('showEditForm', false)" 
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
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Date of Award</label>
                                    <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedContract->date_of_award?->format('M d, Y') ?? 'N/A' }}</p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Start Date</label>
                                    <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedContract->start_date?->format('M d, Y') ?? 'N/A' }}</p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">End Date</label>
                                    <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedContract->end_date?->format('M d, Y') ?? 'Open' }}</p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Program</label>
                                    <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedContract->program->name ?? 'N/A' }}</p>
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
                        @if($selectedContract->reportDueDates && $selectedContract->reportDueDates->count() > 0)
                            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-zinc-600">
                                <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Report Due Dates</h4>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-600">
                                        <thead class="bg-gray-50 dark:bg-zinc-700">
                                            <tr>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Report Type</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Due Date</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-600">
                                            @foreach($selectedContract->reportDueDates as $dueDate)
                                                <tr>
                                                    <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">{{ $dueDate->report_type }}</td>
                                                    <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">{{ $dueDate->due_date?->format('M d, Y') }}</td>
                                                    <td class="px-4 py-2 text-sm">
                                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                                            @if($dueDate->status === 'completed') bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200
                                                            @elseif($dueDate->status === 'overdue') bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200
                                                            @else bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 @endif">
                                                            {{ ucfirst($dueDate->status) }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
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

        <!-- Contracts Table -->
        @if(!$showEditForm && !$showCreateForm)
            <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-600">
                <thead class="bg-gray-50 dark:bg-zinc-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Contract Number</th>
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
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $contract->contract_number ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $contract->program->name ?? 'N/A' }}</td>
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
    </div>
</div>