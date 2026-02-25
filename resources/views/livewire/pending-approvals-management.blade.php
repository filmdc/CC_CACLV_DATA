<div class="w-full px-0 py-6">
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl">
        <!-- Header -->
        <div class="bg-indigo-600 text-white p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Pending Approvals</h1>
                    <p class="text-indigo-100 mt-2">Review and approve submitted items</p>
                </div>
                <div class="bg-white dark:bg-zinc-700 text-indigo-600 dark:text-indigo-400 rounded-lg px-4 py-2">
                    <div class="text-3xl font-bold">{{ $pendingCount }}</div>
                    <div class="text-xs uppercase">Pending</div>
                </div>
            </div>
        </div>

        <!-- Filters and Search -->
        <div class="p-4 bg-gray-50 dark:bg-zinc-700 border-b border-gray-200 dark:border-zinc-600">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Search -->
                <div class="md:col-span-2">
                    <input type="text" wire:model.live="search"
                           placeholder="Search by submitter, program, or item name..."
                           class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <!-- Type Filter -->
                <div>
                    <select wire:model.live="filterType"
                            class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="all">All Types</option>
                        <option value="documents">Documents</option>
                        <option value="tasks">Tasks</option>
                        <option value="submissions">Submissions</option>
                    </select>
                </div>

                <!-- Status Filter -->
                <div>
                    <select wire:model.live="filterStatus"
                            class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="pending">Pending Only</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                        <option value="all">All Statuses</option>
                    </select>
                </div>
            </div>

            <!-- Additional Filters -->
            <div class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Program Filter -->
                <div>
                    <select wire:model.live="filterProgram"
                            class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Programs</option>
                        @foreach($programs as $program)
                            <option value="{{ $program->id }}">{{ $program->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Reset Filters -->
                <div class="flex items-center">
                    <button wire:click="resetFilters"
                            class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400">
                        Reset Filters
                    </button>
                </div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        @if (session()->has('message'))
            <div class="p-4 bg-green-50 dark:bg-green-900 border-l-4 border-green-400">
                <div class="flex">
                    <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="ml-3 text-sm text-green-700 dark:text-green-200">{{ session('message') }}</p>
                </div>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="p-4 bg-red-50 dark:bg-red-900 border-l-4 border-red-400">
                <div class="flex">
                    <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="ml-3 text-sm text-red-700 dark:text-red-200">{{ session('error') }}</p>
                </div>
            </div>
        @endif

        <!-- Bulk Action Bar -->
        @if(count($bulkSelected) > 0)
            <div class="p-4 bg-indigo-50 dark:bg-indigo-900 border-b border-indigo-200 dark:border-indigo-700">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-indigo-700 dark:text-indigo-200">
                        {{ count($bulkSelected) }} item(s) selected
                    </span>
                    @can('approvals.approve_reject')
                        <button wire:click="bulkApprove"
                                class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                            Approve All Selected
                        </button>
                    @endcan
                </div>
            </div>
        @endif

        <!-- Approvals Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                <thead class="bg-gray-50 dark:bg-zinc-700">
                    <tr>
                        @can('approvals.approve_reject')
                            <th class="px-6 py-3 text-left">
                                <input type="checkbox"
                                       wire:model.live="selectAll"
                                       class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            </th>
                        @endcan
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Item</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Submitted By</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Submitted Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Program</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-700">
                    @forelse($approvals as $approval)
                        <tr wire:loading.class="opacity-50">
                            @can('approvals.approve_reject')
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($approval->status === 'pending')
                                        <input type="checkbox"
                                               wire:model.live="bulkSelected"
                                               value="{{ $approval->id }}"
                                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    @endif
                                </td>
                            @endcan
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    @php
                                        $iconClass = 'h-5 w-5';
                                        $iconColor = 'text-gray-400';
                                        if (str_contains($approval->item_type, 'Document')) {
                                            $icon = 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z';
                                            $iconColor = 'text-blue-500';
                                        } else {
                                            $icon = 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4';
                                            $iconColor = 'text-green-500';
                                        }
                                    @endphp
                                    <svg class="{{ $iconClass }} {{ $iconColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}"></path>
                                    </svg>
                                    <span class="ml-2 text-sm text-gray-900 dark:text-white">
                                        {{ $approval->item_type }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $approval->item_name }}
                                </div>
                                @if($approval->item_description)
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ Str::limit($approval->item_description, 50) }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">
                                    {{ $approval->submittedBy->name ?? 'N/A' }}
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $approval->submittedBy->email ?? '' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">
                                    {{ $approval->submitted_at->format('M d, Y') }}
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $approval->submitted_at->format('g:i A') }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">
                                    {{ $approval->program->name ?? 'N/A' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                    @if($approval->status === 'approved') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                    @elseif($approval->status === 'rejected') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                    @else bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                    @endif">
                                    {{ ucfirst($approval->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                <div class="flex items-center justify-center space-x-2">
                                    @if($approval->status === 'pending')
                                        @can('approvals.approve_reject')
                                            <button wire:click="approve({{ $approval->id }})"
                                                    wire:loading.attr="disabled"
                                                    class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </button>
                                            <button wire:click="showRejectModal({{ $approval->id }})"
                                                    wire:loading.attr="disabled"
                                                    class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        @endcan
                                    @else
                                        <span class="text-gray-400 text-xs">
                                            {{ $approval->approver ? 'By ' . $approval->approver->name : '' }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center">
                                <div class="text-gray-500 dark:text-gray-400">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <h3 class="mt-2 text-sm font-medium">No approvals found</h3>
                                    <p class="mt-1 text-sm">Try adjusting your filters.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-200 dark:border-zinc-700">
            {{ $approvals->links() }}
        </div>
    </div>

    <!-- Reject Modal -->
    @if($showRejectModal && $selectedApproval)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50" wire:click.self="$set('showRejectModal', false)">
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-md w-full mx-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                    Reject Approval Request
                </h3>

                <div class="mb-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                        Item: <strong>{{ $selectedApproval->item_name }}</strong>
                    </p>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Please provide a reason for rejection:
                    </p>

                    <textarea wire:model="rejectionReason"
                              rows="4"
                              placeholder="Enter rejection reason (minimum 10 characters)..."
                              class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-red-500 focus:ring-red-500"></textarea>

                    @error('rejectionReason')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end space-x-3">
                    <button wire:click="$set('showRejectModal', false)"
                            type="button"
                            class="px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-zinc-700">
                        Cancel
                    </button>
                    <button wire:click="reject"
                            type="button"
                            wire:loading.attr="disabled"
                            class="px-4 py-2 bg-red-600 text-white rounded-md text-sm font-medium hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 disabled:opacity-50">
                        <span wire:loading.remove wire:target="reject">Confirm Rejection</span>
                        <span wire:loading wire:target="reject">Processing...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
