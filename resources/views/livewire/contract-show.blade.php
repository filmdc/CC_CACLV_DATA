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

            <!-- Report Due Dates -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-zinc-700 pb-2">
                    Report Due Dates ({{ $contract->reportDueDates->count() }})
                </h2>
                @if($contract->reportDueDates->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Report Type
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Report Name
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Due Date
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Reminder Sent
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Status
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                                @foreach($contract->reportDueDates as $dueDate)
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                            {{ ucfirst($dueDate->report_type) }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                            {{ $dueDate->report_name ?: 'N/A' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                            {{ $dueDate->due_date ? \Carbon\Carbon::parse($dueDate->due_date)->format('M j, Y') : 'N/A' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                            @if($dueDate->reminder_sent)
                                                Yes ({{ $dueDate->reminder_sent_at ? $dueDate->reminder_sent_at->format('M j, Y') : 'Date unknown' }})
                                            @else
                                                No
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            @if($dueDate->active)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                                    Active
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100">
                                                    Inactive
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
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
</div>