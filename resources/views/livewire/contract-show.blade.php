<div class="max-w-7xl mx-auto p-6">
    <div class="bg-white dark:bg-zinc-800 shadow-lg rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">{{ $contract->title }}</h1>
                    <p class="text-blue-100 mt-2">Contract Details</p>
                </div>
                <div class="flex space-x-3">
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
                        <p class="text-gray-900 dark:text-white whitespace-pre-line">{{ $contract->purpose ?: 'No purpose specified' }}</p>
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
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Program</label>
                        <p class="text-gray-900 dark:text-white">
                            @if($contract->program)
                                <a href="{{ route('program.show', $contract->program->id) }}" 
                                   class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                    {{ $contract->program->name }}
                                </a>
                            @else
                                Not assigned
                            @endif
                        </p>
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
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Date of Award</label>
                        <p class="text-gray-900 dark:text-white">
                            {{ $contract->date_of_award ? \Carbon\Carbon::parse($contract->date_of_award)->format('M j, Y') : 'N/A' }}
                        </p>
                    </div>
                    
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
                                            <button wire:click="downloadDocument({{ $document->id }})"
                                                    class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 font-medium">
                                                <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                </svg>
                                                Download
                                            </button>
                                            
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
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Type
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Due Date
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Assigned To
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Reports
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                                @foreach($contract->reportDueDates as $dueDate)
                                    <tr class="{{ $dueDate->due_date < now() && $dueDate->status !== 'completed' ? 'bg-red-50 dark:bg-red-900/20' : '' }}">
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                            <span class="font-medium">{{ ucfirst($dueDate->report_type ?: 'Other') }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                            {{ $dueDate->due_date ? \Carbon\Carbon::parse($dueDate->due_date)->format('M j, Y') : 'N/A' }}
                                            @if($dueDate->due_date < now() && $dueDate->status !== 'completed')
                                                <span class="ml-2 text-xs text-red-600 dark:text-red-400">(Overdue)</span>
                                            @elseif($dueDate->due_date <= now()->addDays(7) && $dueDate->status !== 'completed')
                                                <span class="ml-2 text-xs text-yellow-600 dark:text-yellow-400">(Due Soon)</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            @if($dueDate->status === 'completed')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                                    Completed
                                                </span>
                                            @elseif($dueDate->status === 'pending')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100">
                                                    Pending
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100">
                                                    {{ ucfirst($dueDate->status) }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                            @if($dueDate->emails->count() > 0)
                                                <button wire:click="showAssignedUsers({{ $dueDate->id }})"
                                                        class="flex -space-x-2 group cursor-pointer hover:opacity-80 transition-opacity">
                                                    @foreach($dueDate->emails->take(3) as $email)
                                                        <div class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gray-300 dark:bg-zinc-600 text-xs font-medium text-gray-700 dark:text-gray-200 ring-2 ring-white dark:ring-zinc-800 group-hover:ring-blue-500" 
                                                             title="{{ $email->name ?: $email->email }}">
                                                            {{ strtoupper(substr($email->name ?: $email->email, 0, 2)) }}
                                                        </div>
                                                    @endforeach
                                                    @if($dueDate->emails->count() > 3)
                                                        <div class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gray-400 dark:bg-zinc-500 text-xs font-medium text-white ring-2 ring-white dark:ring-zinc-800 group-hover:ring-blue-500">
                                                            +{{ $dueDate->emails->count() - 3 }}
                                                        </div>
                                                    @endif
                                                </button>
                                            @else
                                                <span class="text-gray-500 dark:text-gray-400">None</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                            {{ $dueDate->contractReports->count() }} uploaded
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            <div class="flex items-center space-x-2">
                                                @if($this->can('contract_reports.create') && $dueDate->status !== 'completed')
                                                    <button wire:click="showUploadReport({{ $dueDate->id }})" 
                                                            class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300" 
                                                            title="Upload Report">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                                        </svg>
                                                    </button>
                                                @endif
                                                @if($this->can('due_dates.edit'))
                                                    <button wire:click="showEditDueDate({{ $dueDate->id }})" 
                                                            class="text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-300" 
                                                            title="Edit">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                        </svg>
                                                    </button>
                                                @endif
                                                @if($this->can('due_dates.delete'))
                                                    <button wire:click="deleteDueDate({{ $dueDate->id }})" 
                                                            wire:confirm="Are you sure you want to delete this due date?"
                                                            class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300" 
                                                            title="Delete">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                        </svg>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    
                                    @if($dueDate->contractReports->count() > 0)
                                        <tr>
                                            <td colspan="6" class="px-4 py-2 bg-gray-50 dark:bg-zinc-900">
                                                <div class="text-sm">
                                                    <strong class="text-gray-700 dark:text-gray-300">Uploaded Reports:</strong>
                                                    <ul class="mt-1 space-y-1">
                                                        @foreach($dueDate->contractReports as $report)
                                                            <li class="flex items-center justify-between">
                                                                <div>
                                                                    <span class="text-gray-900 dark:text-white">{{ $report->title }}</span>
                                                                    <span class="text-gray-500 dark:text-gray-400 ml-2">
                                                                        ({{ $report->submitted_date ? $report->submitted_date->format('M j, Y') : 'N/A' }})
                                                                    </span>
                                                                </div>
                                                                <div class="flex items-center space-x-2">
                                                                    @foreach($report->getMedia('documents') as $media)
                                                                        <a href="#" wire:click.prevent="downloadReport({{ $report->id }}, {{ $media->id }})" 
                                                                           class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-xs">
                                                                            Download {{ $loop->count > 1 ? '#' . $loop->iteration : '' }}
                                                                        </a>
                                                                    @endforeach
                                                                    @if($this->can('contract_reports.delete'))
                                                                        <button wire:click="deleteReport({{ $report->id }})" 
                                                                                wire:confirm="Are you sure you want to delete this report?"
                                                                                class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 text-xs">
                                                                            Delete
                                                                        </button>
                                                                    @endif
                                                                </div>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-gray-500 dark:text-gray-400">No report due dates configured for this contract.</p>
                @endif
            </div>

            <!-- Notes -->
            @if($contract->notes)
                <div class="mb-8">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-zinc-700 pb-2">
                        Notes
                    </h2>
                    <p class="text-gray-900 dark:text-white whitespace-pre-line">{{ $contract->notes }}</p>
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
                            <textarea id="description" wire:model="description" rows="3"
                                      class="mt-1 block w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
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
                            <textarea id="report_description" wire:model="report_description" rows="3"
                                      class="mt-1 block w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500"></textarea>
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
                            <textarea id="report_notes" wire:model="report_notes" rows="3"
                                      class="mt-1 block w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500"></textarea>
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
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
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
</div>