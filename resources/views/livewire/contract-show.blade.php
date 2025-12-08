<div class="w-full px-0 py-6">
    <div class="bg-white dark:bg-zinc-800 shadow-lg rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">{{ $contract->title }}</h1>
                    <p class="text-blue-100 mt-2">Contract Details</p>
                </div>
                <div class="flex space-x-3">
                    <a href="{{ route('contract.export-pdf', $contract->id) }}"
                       target="_blank"
                       class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg font-medium transition-colors flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 2v6h6" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 13H8m8 4H8m2-8h2" />
                        </svg>
                        <span>Export PDF</span>
                    </a>
                    @if ($this->can('contracts.update'))
                        <button wire:click="editContract" 
                                class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                            Edit Contract
                        </button>
                    @endif
                    <a href="{{ route('contract.management') }}" 
                       class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                        Back to List
                    </a>
                </div>
            </div>
        </div>

        <div class="p-6">
            <!-- Basic Information -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-zinc-700 pb-2">
                    Basic Information
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Contract Title</label>
                        <p class="text-gray-900 dark:text-white">{{ $contract->title }}</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Contract Number</label>
                        <p class="text-gray-900 dark:text-white">{{ $contract->contract_number ?: 'Not specified' }}</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Status</label>
                        <p>
                            @if($contract->status === 'Archived')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100">
                                    Archived
                                </span>
                            @elseif($contract->status === 'Active')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                    Active
                                </span>
                            @elseif($contract->status === 'Expired')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100">
                                    Expired
                                </span>
                            @elseif($contract->status === 'Upcoming')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100">
                                    Upcoming
                                </span>
                            @elseif($contract->status === 'Draft')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                                    Draft
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100">
                                    {{ $contract->status }}
                                </span>
                            @endif
                        </p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Grant Code</label>
                        <p class="text-gray-900 dark:text-white">{{ $contract->grant_code ?: 'Not specified' }}</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">CFDA Number</label>
                        <p class="text-gray-900 dark:text-white">{{ $contract->cfda_number ?: 'Not specified' }}</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Contract Amount</label>
                        <p class="text-gray-900 dark:text-white">
                            @if($contract->amount)
                                ${{ number_format($contract->amount, 2) }}
                            @else
                                Not specified
                            @endif
                        </p>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Purpose</label>
                        <div class="text-gray-900 dark:text-white rich-text-content">
                            {!! $contract->purpose ?: '<p>No purpose specified</p>' !!}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Program and Funder Information -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-zinc-700 pb-2">
                    Program and Funder
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">
                            Program{{ $contract->getAllPrograms()->count() > 1 ? 's' : '' }}
                        </label>
                        <div class="text-gray-900 dark:text-white">
                            @php $allPrograms = $contract->getAllPrograms(); @endphp
                            @if($allPrograms->count() > 0)
                                @foreach($allPrograms as $program)
                                    <div class="mb-1">
                                        <a href="{{ route('program.show', $program->id) }}" 
                                           class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                            {{ $program->name }}
                                        </a>
                                        @if($program->contract_management_only)
                                            <span class="text-xs text-gray-500 dark:text-gray-400">(Contract Only)</span>
                                        @endif
                                    </div>
                                @endforeach
                            @else
                                <p>Not assigned</p>
                            @endif
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Funder</label>
                        <p class="text-gray-900 dark:text-white">
                            @if($contract->funder)
                                <a href="{{ route('funder.show', $contract->funder->id) }}" 
                                   class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                    {{ $contract->funder->name }}
                                </a>
                            @else
                                Not assigned
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            <!-- Important Dates -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-zinc-700 pb-2">
                    Important Dates
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    {{-- Date of Award field hidden as requested --}}
                    {{--
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Date of Award</label>
                        <p class="text-gray-900 dark:text-white">
                            {{ $contract->date_of_award ? \Carbon\Carbon::parse($contract->date_of_award)->format('M j, Y') : 'N/A' }}
                        </p>
                    </div>
                    --}}
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Start Date</label>
                        <p class="text-gray-900 dark:text-white">
                            {{ $contract->start_date ? \Carbon\Carbon::parse($contract->start_date)->format('M j, Y') : 'N/A' }}
                        </p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">End Date</label>
                        <p class="text-gray-900 dark:text-white">
                            {{ $contract->end_date ? \Carbon\Carbon::parse($contract->end_date)->format('M j, Y') : 'N/A' }}
                        </p>
                    </div>
                    
                    @if($contract->archived)
                        <div>
                            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Date Archived</label>
                            <p class="text-gray-900 dark:text-white">
                                {{ $contract->date_archived ? \Carbon\Carbon::parse($contract->date_archived)->format('M j, Y') : 'N/A' }}
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Contract Documents -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-zinc-700 pb-2">
                    Contract Documents ({{ $contract->documents->count() }})
                </h2>
                
                @if($contract->documents->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($contract->documents as $document)
                            <div class="border border-gray-200 dark:border-zinc-700 rounded-lg p-4 hover:shadow-lg transition-shadow duration-200">
                                <div class="flex items-start space-x-3">
                                    <!-- File Icon -->
                                    <div class="flex-shrink-0">
                                        @php
                                            $extension = strtolower(pathinfo($document->file_name, PATHINFO_EXTENSION));
                                            $iconClass = match($extension) {
                                                'pdf' => 'text-red-500',
                                                'doc', 'docx' => 'text-blue-500',
                                                'xls', 'xlsx' => 'text-green-500',
                                                'png', 'jpg', 'jpeg' => 'text-purple-500',
                                                default => 'text-gray-500'
                                            };
                                        @endphp
                                        
                                        @if(in_array($extension, ['png', 'jpg', 'jpeg']))
                                            <svg class="w-10 h-10 {{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                        @else
                                            <svg class="w-10 h-10 {{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                        @endif
                                    </div>
                                    
                                    <!-- File Details -->
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-sm font-medium text-gray-900 dark:text-white truncate" title="{{ $document->name }}">
                                            {{ $document->name }}
                                        </h3>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            {{ strtoupper($extension) }} • {{ $document->human_readable_size }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            Uploaded {{ $document->created_at->format('M j, Y') }}
                                        </p>
                                        
                                        <!-- Actions -->
                                        <div class="mt-3 flex items-center space-x-3">
                                            <a href="{{ route('contract.document.download', ['contractId' => $contract->id, 'mediaId' => $document->id]) }}" 
                                               class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 font-medium inline-flex items-center">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                </svg>
                                                Download
                                            </a>
                                            
                                            @if(in_array($extension, ['pdf', 'png', 'jpg', 'jpeg']))
                                                <a href="{{ route('contract.document.preview', ['contractId' => $contract->id, 'mediaId' => $document->id]) }}" 
                                                   target="_blank"
                                                   class="text-xs text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-300 font-medium inline-flex items-center">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                    </svg>
                                                    Preview
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 dark:text-gray-400">No documents attached to this contract.</p>
                @endif
            </div>

            <!-- Report Due Dates -->
            <div class="mb-8">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white border-b border-gray-200 dark:border-zinc-700 pb-2">
                        Report Due Dates ({{ $contract->reportDueDates->count() }})
                    </h2>
                    @if($this->can('due_dates.create'))
                        <button wire:click="showCreateDueDate" 
                                class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                            <svg class="w-5 h-5 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Add Due Date
                        </button>
                    @endif
                </div>
                
                @if($contract->reportDueDates->count() > 0)
                    <div class="space-y-4">
                        @foreach($contract->reportDueDates as $dueDate)
                            @php
                                $isOverdue = $dueDate->due_date < now() && $dueDate->status !== 'completed';
                                $isDueSoon = $dueDate->due_date <= now()->addDays(7) && $dueDate->status !== 'completed' && !$isOverdue;
                                $templates = $dueDate->getMedia('templates');
                                $submissions = $dueDate->contractReports;
                            @endphp

                            <!-- Report Due Date Card -->
                            <div x-data="{ expanded: false }"
                                 class="border rounded-lg {{ $isOverdue ? 'border-red-300 dark:border-red-700 bg-red-50 dark:bg-red-900/10' : ($isDueSoon ? 'border-yellow-300 dark:border-yellow-700 bg-yellow-50 dark:bg-yellow-900/10' : 'border-gray-200 dark:border-zinc-700 bg-white dark:bg-zinc-800') }} transition-all duration-200">

                                <!-- Card Header -->
                                <div class="p-4">
                                    <div class="flex items-start justify-between">
                                        <!-- Left Side: Type, Date, Status -->
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-3">
                                                <!-- Calendar Icon -->
                                                <div class="flex-shrink-0">
                                                    <div class="w-12 h-12 rounded-lg {{ $isOverdue ? 'bg-red-100 dark:bg-red-900/30' : ($isDueSoon ? 'bg-yellow-100 dark:bg-yellow-900/30' : 'bg-blue-100 dark:bg-blue-900/30') }} flex items-center justify-center">
                                                        <svg class="w-6 h-6 {{ $isOverdue ? 'text-red-600 dark:text-red-400' : ($isDueSoon ? 'text-yellow-600 dark:text-yellow-400' : 'text-blue-600 dark:text-blue-400') }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                        </svg>
                                                    </div>
                                                </div>

                                                <!-- Title and Details -->
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center space-x-2">
                                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                                            {{ ucfirst($dueDate->report_type ?: 'Other') }} Report
                                                        </h3>

                                                        <!-- Status Badge -->
                                                        @if($dueDate->status === 'completed')
                                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                                </svg>
                                                                Completed
                                                            </span>
                                                        @elseif($isOverdue)
                                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100">
                                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                                                </svg>
                                                                Overdue
                                                            </span>
                                                        @elseif($isDueSoon)
                                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100">
                                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                                                </svg>
                                                                Due Soon
                                                            </span>
                                                        @else
                                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100">
                                                                {{ ucfirst($dueDate->status) }}
                                                            </span>
                                                        @endif
                                                    </div>

                                                    <div class="mt-1 flex items-center space-x-4 text-sm text-gray-600 dark:text-gray-400">
                                                        <span class="flex items-center">
                                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                            </svg>
                                                            Due: {{ $dueDate->due_date ? \Carbon\Carbon::parse($dueDate->due_date)->format('M j, Y') : 'N/A' }}
                                                        </span>

                                                        @if($dueDate->emails->count() > 0)
                                                            <span class="flex items-center">
                                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                                                </svg>
                                                                {{ $dueDate->emails->count() }} assigned
                                                            </span>
                                                        @endif

                                                        <span class="flex items-center">
                                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                            </svg>
                                                            {{ $submissions->count() }} submission{{ $submissions->count() !== 1 ? 's' : '' }}
                                                        </span>
                                                    </div>

                                                    @if($dueDate->description)
                                                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                                                            {!! strip_tags($dueDate->description) !!}
                                                        </p>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Right Side: Actions -->
                                        <div class="flex items-center space-x-2 ml-4">
                                            @if($this->can('contract_reports.create') && $dueDate->status !== 'completed')
                                                <button wire:click="showUploadReport({{ $dueDate->id }})"
                                                        class="p-2 text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors"
                                                        title="Upload Report">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                                    </svg>
                                                </button>
                                            @endif

                                            @if($this->can('due_dates.edit'))
                                                <button wire:click="showEditDueDate({{ $dueDate->id }})"
                                                        class="p-2 text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-zinc-700 rounded-lg transition-colors"
                                                        title="Edit">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                    </svg>
                                                </button>
                                            @endif

                                            @if($this->can('due_dates.delete'))
                                                <button wire:click="deleteDueDate({{ $dueDate->id }})"
                                                        wire:confirm="Are you sure you want to delete this due date?"
                                                        class="p-2 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                                                        title="Delete">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            @endif

                                            <!-- Expand/Collapse Button -->
                                            <button @click="expanded = !expanded"
                                                    class="p-2 text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-zinc-700 rounded-lg transition-all"
                                                    title="View Documents">
                                                <svg class="w-5 h-5 transition-transform duration-200"
                                                     :class="{ 'rotate-180': expanded }"
                                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Expandable Document Section -->
                                <div x-show="expanded"
                                     x-collapse
                                     class="border-t border-gray-200 dark:border-zinc-700">

                                    <div class="p-4 space-y-4">
                                        <!-- Templates/Requirements Section -->
                                        <div>
                                            <div class="flex items-center justify-between mb-2">
                                                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center">
                                                    <svg class="w-4 h-4 mr-2 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                    </svg>
                                                    Templates & Requirements
                                                    <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">({{ $templates->count() }} file{{ $templates->count() !== 1 ? 's' : '' }})</span>
                                                </h4>
                                            </div>

                                            @if($templates->count() > 0)
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                                    @foreach($templates as $media)
                                                        <div class="flex items-center justify-between p-3 bg-purple-50 dark:bg-purple-900/10 border border-purple-200 dark:border-purple-800 rounded-lg">
                                                            <div class="flex items-center space-x-2 min-w-0 flex-1">
                                                                <svg class="w-5 h-5 flex-shrink-0 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                                                </svg>
                                                                <span class="text-sm text-gray-900 dark:text-white truncate" title="{{ $media->name }}">{{ $media->name }}</span>
                                                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $media->human_readable_size }}</span>
                                                            </div>
                                                            <a href="{{ $media->getUrl() }}" target="_blank"
                                                               class="ml-2 text-purple-600 hover:text-purple-800 dark:text-purple-400 dark:hover:text-purple-300">
                                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                                </svg>
                                                            </a>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <p class="text-sm text-gray-500 dark:text-gray-400 italic">No templates uploaded yet</p>
                                            @endif
                                        </div>

                                        <!-- Submissions Section -->
                                        <div>
                                            <div class="flex items-center justify-between mb-2">
                                                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center">
                                                    <svg class="w-4 h-4 mr-2 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    Submitted Reports
                                                    <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">({{ $submissions->count() }} report{{ $submissions->count() !== 1 ? 's' : '' }})</span>
                                                </h4>
                                            </div>

                                            @if($submissions->count() > 0)
                                                <div class="space-y-2">
                                                    @foreach($submissions as $report)
                                                        @php $reportMedia = $report->getMedia('submissions'); @endphp
                                                        <div class="p-3 bg-green-50 dark:bg-green-900/10 border border-green-200 dark:border-green-800 rounded-lg">
                                                            <div class="flex items-start justify-between">
                                                                <div class="flex-1 min-w-0">
                                                                    <div class="flex items-center space-x-2">
                                                                        <svg class="w-5 h-5 flex-shrink-0 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                                        </svg>
                                                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $report->title }}</span>
                                                                        @if($report->submitted_date)
                                                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                                                ({{ $report->submitted_date->format('M j, Y') }})
                                                                            </span>
                                                                        @endif
                                                                    </div>

                                                                    @if($reportMedia->count() > 0)
                                                                        <div class="mt-2 space-y-1">
                                                                            @foreach($reportMedia as $media)
                                                                                <div class="flex items-center space-x-2 text-xs">
                                                                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                                                                                    </svg>
                                                                                    <span class="text-gray-600 dark:text-gray-400">{{ $media->name }}</span>
                                                                                    <span class="text-gray-500 dark:text-gray-500">{{ $media->human_readable_size }}</span>
                                                                                    <a href="{{ $media->getUrl() }}" target="_blank"
                                                                                       class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300">
                                                                                        Download
                                                                                    </a>
                                                                                </div>
                                                                            @endforeach
                                                                        </div>
                                                                    @endif
                                                                </div>

                                                                @if($this->can('contract_reports.delete'))
                                                                    <button wire:click="deleteReport({{ $report->id }})"
                                                                            wire:confirm="Are you sure you want to delete this report?"
                                                                            class="ml-2 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                                        </svg>
                                                                    </button>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <p class="text-sm text-gray-500 dark:text-gray-400 italic">No reports submitted yet</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 dark:text-gray-400">No report due dates configured for this contract.</p>
                @endif
            </div>

            <!-- General Reports (Reports Without Due Dates) -->
            <div class="mb-8">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white border-b border-gray-200 dark:border-zinc-700 pb-2">
                        General Reports ({{ $contract->orphanReports->count() }})
                    </h2>
                    @if($this->can('contract_reports.create_orphan'))
                        <button wire:click="showCreateOrphanReport"
                                class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500">
                            <svg class="w-5 h-5 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Create General Report
                        </button>
                    @endif
                </div>

                @if($contract->orphanReports->count() > 0)
                    <div class="space-y-4">
                        @foreach($contract->orphanReports as $orphanReport)
                            <div class="border border-gray-200 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-800 p-4 hover:shadow-md transition-shadow">
                                <div class="flex items-start justify-between">
                                    <!-- Report Info -->
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-2 mb-2">
                                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                                {{ $orphanReport->title }}
                                            </h3>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-100">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                </svg>
                                                General Report
                                            </span>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                                {{ ucfirst($orphanReport->status) }}
                                            </span>
                                        </div>

                                        @if($orphanReport->description)
                                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                                {{ strip_tags($orphanReport->description) }}
                                            </p>
                                        @endif

                                        <div class="flex items-center space-x-4 text-sm text-gray-500 dark:text-gray-400">
                                            @if($orphanReport->submitted_date)
                                                <span class="flex items-center">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                    </svg>
                                                    Submitted: {{ $orphanReport->submitted_date->format('M j, Y') }}
                                                </span>
                                            @endif

                                            @if($orphanReport->submissions->count() > 0)
                                                <span class="flex items-center">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                                    </svg>
                                                    {{ $orphanReport->submissions->count() }} document(s)
                                                </span>
                                            @endif
                                        </div>

                                        <!-- Documents -->
                                        @if($orphanReport->submissions->count() > 0)
                                            <div class="mt-3 space-y-2">
                                                @foreach($orphanReport->submissions as $document)
                                                    <div class="flex items-center space-x-2 text-sm">
                                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                        </svg>
                                                        <a href="{{ route('contract.report.document.download', ['contractId' => $contract->id, 'reportId' => $orphanReport->id, 'mediaId' => $document->id]) }}"
                                                           class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                                            {{ $document->name }}
                                                        </a>
                                                        <span class="text-gray-400">{{ $document->human_readable_size }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex flex-col space-y-2 ml-4">
                                        @if($this->can('contract_reports.associate'))
                                            <button wire:click="showAssociateOrphanReport({{ $orphanReport->id }})"
                                                    class="px-3 py-1.5 text-xs font-medium text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-900/30 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 rounded-md transition-colors flex items-center space-x-1"
                                                    title="Associate with Due Date">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                                </svg>
                                                <span>Link to Due Date</span>
                                            </button>
                                        @endif

                                        @if($this->can('contract_reports.delete'))
                                            <button wire:click="deleteOrphanReport({{ $orphanReport->id }})"
                                                    onclick="return confirm('Are you sure you want to delete this general report? This action cannot be undone.')"
                                                    class="px-3 py-1.5 text-xs font-medium text-red-700 dark:text-red-300 bg-red-50 dark:bg-red-900/30 hover:bg-red-100 dark:hover:bg-red-900/50 rounded-md transition-colors flex items-center space-x-1"
                                                    title="Delete Report">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                                <span>Delete</span>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 dark:text-gray-400">No general reports for this contract. General reports are submissions that aren't linked to a specific due date.</p>
                @endif
            </div>

            <!-- Notes -->
            @if($contract->notes)
                <div class="mb-8">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-zinc-700 pb-2">
                        Notes
                    </h2>
                    <div class="text-gray-900 dark:text-white rich-text-content">
                        {!! $contract->notes !!}
                    </div>
                </div>
            @endif

            <!-- Audit Information -->
            <div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-zinc-700 pb-2">
                    Audit Information
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Created By</label>
                        <p class="text-gray-900 dark:text-white">
                            {{ $contract->createdBy ? $contract->createdBy->name : 'System' }}
                        </p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Created At</label>
                        <p class="text-gray-900 dark:text-white">
                            {{ $contract->created_at ? $contract->created_at->format('M j, Y g:i A') : 'N/A' }}
                        </p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Last Updated By</label>
                        <p class="text-gray-900 dark:text-white">
                            {{ $contract->updatedBy ? $contract->updatedBy->name : 'System' }}
                        </p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Last Updated At</label>
                        <p class="text-gray-900 dark:text-white">
                            {{ $contract->updated_at ? $contract->updated_at->format('M j, Y g:i A') : 'N/A' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Due Date Form Modal -->
    @if($showDueDateForm)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50 p-4">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="bg-blue-600 text-white p-6 rounded-t-lg">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-bold">
                            {{ $selectedDueDate ? 'Edit Report Due Date' : 'Create Report Due Date' }}
                        </h3>
                        <button wire:click="closeDueDateForm" class="text-blue-200 hover:text-white transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <form wire:submit.prevent="saveDueDate" class="p-6">
                    <div class="space-y-4">
                        <div>
                            <label for="due_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Due Date *</label>
                            <input type="date" id="due_date" wire:model="due_date" 
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                                   required>
                            @error('due_date') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                        
                        <div>
                            <label for="report_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Report Type *</label>
                            <select id="report_type" wire:model="report_type" 
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                                    required>
                                @foreach($reportTypes as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('report_type') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                        
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                            <x-wysiwyg-editor model="description" :rows="3" placeholder="Describe this due date or report requirement..." id="contract_description" />
                            @error('description') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Reminder Intervals (days before due date)</label>
                            <div class="space-y-2">
                                @foreach([60 => '60 days', 30 => '30 days', 15 => '15 days', 7 => '7 days', 3 => '3 days', 1 => '1 day', 0 => 'On due date'] as $days => $label)
                                    <label class="flex items-center">
                                        <input type="checkbox" value="{{ $days }}" 
                                               wire:model="reminder_intervals"
                                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Assign Users to Receive Reminders</label>
                            <div class="max-h-48 overflow-y-auto border border-gray-300 dark:border-zinc-600 rounded-md p-2">
                                @foreach($users as $user)
                                    <label class="flex items-center py-1">
                                        <input type="checkbox" value="{{ $user->id }}" 
                                               wire:model="assigned_users"
                                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                            {{ $user->name }} ({{ $user->email }})
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" wire:model="active" 
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Active</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" wire:click="closeDueDateForm" 
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-zinc-700 border border-gray-300 dark:border-zinc-600 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-600">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700">
                            {{ $selectedDueDate ? 'Update' : 'Create' }} Due Date
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
    
    <!-- Report Upload Modal -->
    @if($showReportForm && $selectedDueDateForReport)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50 p-4">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="bg-green-600 text-white p-6 rounded-t-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-bold">Upload Report</h3>
                            <p class="text-green-100 mt-1">
                                Due: {{ $selectedDueDateForReport->due_date->format('M j, Y') }}
                                @if($selectedDueDateForReport->description)
                                    - {{ strip_tags($selectedDueDateForReport->description) }}
                                @endif
                            </p>
                        </div>
                        <button wire:click="closeReportForm" class="text-green-200 hover:text-white transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <form wire:submit.prevent="uploadReport" class="p-6">
                    <div class="space-y-4">
                        <div>
                            <label for="report_title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Report Title *</label>
                            <input type="text" id="report_title" wire:model="report_title" 
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500" 
                                   required>
                            @error('report_title') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                        
                        <div>
                            <label for="report_description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                            <x-wysiwyg-editor model="report_description" :rows="3" placeholder="Describe the report requirements and expectations..." id="contract_report_description" />
                            @error('report_description') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                        
                        <div>
                            <label for="report_documents" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Documents *</label>
                            <input type="file" id="report_documents" wire:model="report_documents" multiple
                                   class="mt-1 block w-full text-sm text-gray-900 dark:text-gray-300 border border-gray-300 dark:border-zinc-600 rounded-md cursor-pointer bg-gray-50 dark:bg-zinc-700 focus:outline-none"
                                   accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg" required>
                            @error('report_documents.*') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            
                            @if($report_documents)
                                <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                    Selected files:
                                    <ul class="mt-1">
                                        @foreach($report_documents as $document)
                                            <li>{{ $document->getClientOriginalName() }} ({{ number_format($document->getSize() / 1024 / 1024, 2) }} MB)</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                        
                        <div>
                            <label for="report_notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Notes</label>
                            <x-wysiwyg-editor model="report_notes" :rows="3" placeholder="Additional notes about this report..." id="contract_report_notes" />
                            @error('report_notes') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    
                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" wire:click="closeReportForm" 
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-zinc-700 border border-gray-300 dark:border-zinc-600 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-600">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700">
                            Upload Report
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
    
    <!-- Assigned Users Modal -->
    @if($showAssignedUsersModal && $selectedDueDateForUsers)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50" wire:click.self="closeAssignedUsersModal">
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-2xl w-full mx-4 max-h-[80vh] overflow-y-auto">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Assigned Users - {{ ucfirst($selectedDueDateForUsers->report_type ?: 'Other') }} Report
                    </h3>
                    <button wire:click="closeAssignedUsersModal"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <div class="mb-4 p-3 bg-gray-50 dark:bg-zinc-700 rounded">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Due Date: <span class="font-medium text-gray-900 dark:text-white">{{ $selectedDueDateForUsers->due_date->format('M j, Y') }}</span>
                    </p>
                    @if($selectedDueDateForUsers->description)
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            {{ strip_tags($selectedDueDateForUsers->description) }}
                        </p>
                    @endif
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

    <!-- Create Orphan Report Modal -->
    @if($showOrphanReportForm)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75 flex items-center justify-center z-50 p-4">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                            Create General Report
                        </h3>
                        <button wire:click="closeOrphanReportForm"
                                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        General reports are not tied to a specific due date. They can be associated with a due date later if needed.
                    </p>

                    <form wire:submit.prevent="saveOrphanReport">
                        <div class="space-y-4">
                            <!-- Title -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Report Title <span class="text-red-500">*</span>
                                </label>
                                <input type="text"
                                       wire:model="orphan_report_title"
                                       class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-blue-500 focus:ring-blue-500"
                                       placeholder="Enter report title"
                                       required>
                                @error('orphan_report_title')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Description -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Description
                                </label>
                                <textarea wire:model="orphan_report_description"
                                          rows="4"
                                          class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-blue-500 focus:ring-blue-500"
                                          placeholder="Enter report description"></textarea>
                                @error('orphan_report_description')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Documents -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Upload Documents
                                </label>
                                <input type="file"
                                       wire:model="orphan_report_documents"
                                       multiple
                                       class="w-full text-sm text-gray-500 dark:text-gray-400
                                              file:mr-4 file:py-2 file:px-4
                                              file:rounded-md file:border-0
                                              file:text-sm file:font-semibold
                                              file:bg-blue-50 file:text-blue-700
                                              hover:file:bg-blue-100
                                              dark:file:bg-blue-900 dark:file:text-blue-200
                                              dark:hover:file:bg-blue-800">
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    Upload PDF, Word, Excel, or image files (max 10MB each)
                                </p>
                                @error('orphan_report_documents.*')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Notes -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Notes
                                </label>
                                <textarea wire:model="orphan_report_notes"
                                          rows="3"
                                          class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-blue-500 focus:ring-blue-500"
                                          placeholder="Additional notes about this report"></textarea>
                                @error('orphan_report_notes')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3 mt-6 pt-4 border-t border-gray-200 dark:border-zinc-600">
                            <button type="button"
                                    wire:click="closeOrphanReportForm"
                                    class="px-4 py-2 bg-gray-200 dark:bg-zinc-600 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-500">
                                Cancel
                            </button>
                            <button type="submit"
                                    class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500">
                                Create Report
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Associate Orphan Report Modal -->
    @if($showAssociateModal)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75 flex items-center justify-center z-50 p-4">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-md w-full">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                            Link Report to Due Date
                        </h3>
                        <button wire:click="closeAssociateModal"
                                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Select a due date to associate this general report with. The report will be moved to that due date's submissions.
                    </p>

                    <form wire:submit.prevent="associateOrphanWithDueDate">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Select Due Date <span class="text-red-500">*</span>
                            </label>
                            <select wire:model="targetDueDateId"
                                    class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                                    required>
                                <option value="">-- Select a Due Date --</option>
                                @foreach($contract->reportDueDates as $dueDate)
                                    <option value="{{ $dueDate->id }}">
                                        {{ $dueDate->due_date->format('M j, Y') }} - {{ ucfirst($dueDate->report_type) }} Report
                                    </option>
                                @endforeach
                            </select>
                            @error('targetDueDateId')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex justify-end space-x-3">
                            <button type="button"
                                    wire:click="closeAssociateModal"
                                    class="px-4 py-2 bg-gray-200 dark:bg-zinc-600 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-500">
                                Cancel
                            </button>
                            <button type="submit"
                                    class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                Associate Report
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
