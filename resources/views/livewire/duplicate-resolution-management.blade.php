<div class="w-full px-0 py-6">
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl">
        <!-- Header -->
        <div class="p-6 border-b border-gray-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Duplicate Resolution</h1>
                    <p class="text-gray-600 dark:text-gray-300 mt-1">
                        Review and resolve potential participant duplicates
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    @if ($rescanSummary)
                        <div class="text-xs text-gray-500 dark:text-gray-300">
                            Last rescan processed {{ $rescanSummary['evaluated'] }} participants; {{ $rescanSummary['created'] }} duplicates logged.
                        </div>
                    @endif
                    <button wire:click="rescanDuplicates"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-60 cursor-progress"
                            wire:target="rescanDuplicates"
                            class="inline-flex items-center gap-2 rounded-md bg-purple-600 px-3 py-2 text-xs font-semibold text-white shadow hover:bg-purple-700">
                        <svg wire:loading wire:target="rescanDuplicates" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        Rescan All Participants
                    </button>
                </div>

                <!-- Summary Stats -->
                <div class="flex space-x-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-yellow-600">{{ $pendingCount }}</div>
                        <div class="text-sm text-gray-500">Pending</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-600">{{ $totalCount }}</div>
                        <div class="text-sm text-gray-500">Total</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="p-6 border-b border-gray-200 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-900">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Status Filter -->
                <div>
                    <label for="statusFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                    <select wire:model.live="statusFilter" id="statusFilter" 
                            class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                        <option value="">All Statuses</option>
                        @foreach($this->getStatusTypes() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Source Filter -->
                <div>
                    <label for="sourceFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Source</label>
                    <select wire:model.live="sourceFilter" id="sourceFilter" 
                            class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                        <option value="">All Sources</option>
                        @foreach($this->getSourceTypes() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Confidence Filter -->
                <div>
                    <label for="confidenceFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Confidence</label>
                    <select wire:model.live="confidenceFilter" id="confidenceFilter" 
                            class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                        <option value="">All Levels</option>
                        @foreach($this->getConfidenceFilters() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Search -->
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search</label>
                    <input type="text" wire:model.live.debounce.500ms="search" id="search" 
                           placeholder="Search by name..."
                           class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                </div>
            </div>
            
            <!-- Bulk Actions -->
            @if($statusFilter === 'pending' && $duplicates->count() > 0)
                <div class="mt-4 flex space-x-2">
                    <button wire:click="bulkApprove('approved_merge')" 
                            wire:confirm="Are you sure you want to bulk approve merges for filtered duplicates?"
                            class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                        Bulk Approve Merges
                    </button>
                    <button wire:click="bulkApprove('approved_separate')" 
                            wire:confirm="Are you sure you want to bulk approve as separate for filtered duplicates?"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        Bulk Approve Separate
                    </button>
                </div>
            @endif
        </div>

        <!-- Duplicates List -->
        <div class="p-6">
            @if($duplicates->count() > 0)
                <div class="space-y-4">
                    @foreach($duplicates as $duplicate)
                        <div class="border border-gray-200 dark:border-zinc-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-zinc-800 transition-colors">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-4">
                                        <!-- Confidence Badge -->
                                        <span class="px-2 py-1 text-xs rounded-full {{ $duplicate->getStatusBadgeClass() }}">
                                            {{ ucfirst(str_replace('_', ' ', $duplicate->status)) }}
                                        </span>
                                        
                                        <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">
                                            {{ $duplicate->getConfidenceLevel() }} ({{ number_format($duplicate->confidence_score, 1) }}%)
                                        </span>
                                        
                                        <span class="text-sm text-gray-500">
                                            {{ $this->getSourceTypes()[$duplicate->source_type] ?? $duplicate->source_type }}
                                        </span>
                                    </div>
                                    
                                    <div class="mt-2 grid grid-cols-2 gap-6">
                                        <!-- Proposed Data -->
                                        <div>
                                            <h4 class="font-medium text-gray-900 dark:text-white">Proposed New Participant</h4>
                                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                                {{ $duplicate->getProposedName() }}
                                            </p>
                                            @if(isset($duplicate->proposed_data['date_of_birth']))
                                                <p class="text-sm text-gray-500">
                                                    DOB: {{ $duplicate->proposed_data['date_of_birth'] }}
                                                </p>
                                            @endif
                                        </div>
                                        
                                        <!-- Existing Participant -->
                                        <div>
                                            <h4 class="font-medium text-gray-900 dark:text-white">Existing Participant</h4>
                                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                                {{ $duplicate->matchedParticipant->full_name }}
                                            </p>
                                            <p class="text-sm text-gray-500">
                                                DOB: {{ $duplicate->matchedParticipant->date_of_birth?->format('Y-m-d') ?? 'Not set' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Actions -->
                                <div class="flex space-x-2">
                                    <button wire:click="showDetail({{ $duplicate->id }})" 
                                            class="px-3 py-2 text-sm bg-blue-100 text-blue-700 rounded hover:bg-blue-200">
                                        Review
                                    </button>
                                    
                                    @if($duplicate->status === 'pending')
                                        <button wire:click="approveMerge({{ $duplicate->id }})" 
                                                wire:confirm="Are you sure you want to approve merging these participants?"
                                                class="px-3 py-2 text-sm bg-green-100 text-green-700 rounded hover:bg-green-200">
                                            Merge
                                        </button>
                                        <button wire:click="approveSeparate({{ $duplicate->id }})" 
                                                wire:confirm="Are you sure you want to approve these as separate participants?"
                                                class="px-3 py-2 text-sm bg-yellow-100 text-yellow-700 rounded hover:bg-yellow-200">
                                            Separate
                                        </button>
                                        <button wire:click="reject({{ $duplicate->id }})" 
                                                wire:confirm="Are you sure you want to reject this duplicate?"
                                                class="px-3 py-2 text-sm bg-red-100 text-red-700 rounded hover:bg-red-200">
                                            Reject
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                <!-- Pagination -->
                <div class="mt-6">
                    {{ $duplicates->links() }}
                </div>
            @else
                <div class="text-center py-8">
                    <div class="text-gray-500 dark:text-gray-400">
                        @if($statusFilter === 'pending')
                            No pending duplicates found.
                        @else
                            No duplicates found matching your filters.
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Detail Modal -->
    @if($showDetailModal && $selectedDuplicate)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                        Duplicate Review Details
                    </h3>
                    <button wire:click="closeDetailModal" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <div class="space-y-6">
                    <!-- Confidence and Status -->
                    <div class="flex space-x-4">
                        <span class="px-3 py-1 rounded-full {{ $selectedDuplicate->getStatusBadgeClass() }}">
                            {{ ucfirst(str_replace('_', ' ', $selectedDuplicate->status)) }}
                        </span>
                        <span class="px-3 py-1 rounded-full bg-blue-100 text-blue-800">
                            {{ $selectedDuplicate->getConfidenceLevel() }} - {{ number_format($selectedDuplicate->confidence_score, 1) }}%
                        </span>
                    </div>
                    
                    <!-- Detailed Comparison -->
                    @php
                        $proposed = $selectedDuplicate->proposed_data ?? [];
                        $existing = $selectedDuplicate->matchedParticipant;
                        $existingHousehold = $existing?->household;
                        $existingAddress = $existingHousehold?->currentAddress;

                        $fields = [
                            'First Name' => [data_get($proposed, 'first_name'), $existing?->first_name],
                            'Middle Name' => [data_get($proposed, 'middle_name'), $existing?->middle_name],
                            'Last Name' => [data_get($proposed, 'last_name'), $existing?->last_name],
                            'Date of Birth' => [data_get($proposed, 'date_of_birth'), optional($existing?->date_of_birth)->format('Y-m-d')],
                            'Gender' => [data_get($proposed, 'gender'), $existing?->gender],
                            'Ethnicity' => [data_get($proposed, 'ethnicity'), $existing?->ethnicity],
                            'Race' => [implode(', ', (array) data_get($proposed, 'race')), implode(', ', (array) ($existing?->race ? (is_array($existing->race) ? $existing->race : json_decode($existing->race, true)) : []))],
                            'Email' => [data_get($proposed, 'email'), $existing?->email],
                            'Phone' => [data_get($proposed, 'telephone'), $existing?->telephone],
                            'Address' => [data_get($proposed, 'street_address'), $existingAddress?->address],
                            'City' => [data_get($proposed, 'city'), $existingAddress?->city],
                            'State' => [data_get($proposed, 'state'), $existingAddress?->state],
                            'Postal Code' => [data_get($proposed, 'zip_code'), $existingAddress?->zip_code],
                            'Household Code' => [data_get($proposed, 'household_code'), $existingHousehold?->household_code],
                            'Notes' => [data_get($proposed, 'notes'), $existing?->notes],
                        ];
                    @endphp

                    <div class="rounded-lg border border-gray-200 dark:border-zinc-700">
                        <div class="max-h-[55vh] overflow-y-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700 text-sm">
                                <thead class="sticky top-0 bg-gray-50 dark:bg-zinc-900">
                                    <tr>
                                        <th class="px-4 py-2 text-left font-semibold text-gray-600 dark:text-gray-300">Field</th>
                                        <th class="px-4 py-2 text-left font-semibold text-gray-600 dark:text-gray-300">Proposed</th>
                                        <th class="px-4 py-2 text-left font-semibold text-gray-600 dark:text-gray-300">Existing</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-zinc-800">
                                    @foreach ($fields as $label => [$proposedValue, $existingValue])
                                        @php
                                            $formatValue = function ($value) {
                                                if (is_array($value)) {
                                                    return implode(', ', array_filter($value));
                                                }

                                                if (is_string($value) && strlen($value) > 160) {
                                                    return nl2br(e(\Illuminate\Support\Str::limit($value, 160)));
                                                }

                                                return $value;
                                            };
                                        @endphp
                                        <tr>
                                            <td class="px-4 py-2 font-medium text-gray-700 dark:text-gray-200">{{ $label }}</td>
                                            <td class="px-4 py-2 text-gray-600 dark:text-gray-300">
                                                {!! $proposedValue ? $formatValue($proposedValue) : '—' !!}
                                            </td>
                                            <td class="px-4 py-2 text-gray-600 dark:text-gray-300">
                                                {!! $existingValue ? $formatValue($existingValue) : '—' !!}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Review Notes -->
                    @if($selectedDuplicate->status === 'pending')
                        <div>
                            <label for="reviewNotes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Review Notes
                            </label>
                            <textarea wire:model="reviewNotes" id="reviewNotes" rows="3" 
                                      class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white"
                                      placeholder="Add any notes about your decision..."></textarea>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="flex justify-end space-x-3">
                            <button wire:click="closeDetailModal" 
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                                Cancel
                            </button>
                            <button wire:click="reject({{ $selectedDuplicate->id }})" 
                                    class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700">
                                Reject
                            </button>
                            <button wire:click="approveSeparate({{ $selectedDuplicate->id }})" 
                                    class="px-4 py-2 text-sm font-medium text-white bg-yellow-600 rounded-md hover:bg-yellow-700">
                                Approve as Separate
                            </button>
                            <button wire:click="approveMerge({{ $selectedDuplicate->id }})" 
                                    class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700">
                                Approve Merge
                            </button>
                        </div>
                    @else
                        <!-- Show existing review information -->
                        <div class="bg-gray-50 dark:bg-zinc-900 p-4 rounded-lg">
                            <h5 class="font-medium text-gray-900 dark:text-white mb-2">Review Information</h5>
                            <div class="text-sm space-y-1">
                                <div><span class="font-medium">Reviewed by:</span> {{ $selectedDuplicate->reviewer->name ?? 'System' }}</div>
                                <div><span class="font-medium">Reviewed at:</span> {{ $selectedDuplicate->reviewed_at?->format('M j, Y g:i A') }}</div>
                                @if($selectedDuplicate->review_notes)
                                    <div><span class="font-medium">Notes:</span> {{ $selectedDuplicate->review_notes }}</div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- Success/Error Messages -->
    @if (session()->has('message'))
        <div class="fixed top-4 right-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md z-50">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="fixed top-4 right-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md z-50">
            {{ session('error') }}
        </div>
    @endif
</div>
