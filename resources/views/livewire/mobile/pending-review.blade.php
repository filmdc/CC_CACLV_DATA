<div class="p-4 lg:py-8 page-enter">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-lg lg:text-2xl font-semibold text-white">Review Queue</h1>
        <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full bg-amber-500/20 text-amber-400">{{ $pendingCount }} pending</span>
    </div>

    {{-- Flash Messages --}}
    @if (session()->has('message'))
        <div class="mb-4 p-3 rounded-lg bg-green-500/20 border border-green-500/30 text-sm text-green-400">
            {{ session('message') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="mb-4 p-3 rounded-lg bg-red-500/20 border border-red-500/30 text-sm text-red-400">
            {{ session('error') }}
        </div>
    @endif

    {{-- Search & Filters --}}
    <div class="lg:flex lg:items-center lg:gap-4 mb-4">
        {{-- Search --}}
        <div class="mb-3 lg:mb-0 lg:flex-1 lg:max-w-md">
            <input
                wire:model.live.debounce.300ms="search"
                type="search"
                placeholder="Search by name, submitter, or program..."
                class="w-full h-10 px-3 text-sm rounded-lg bg-zinc-900 border border-zinc-800 text-white placeholder-zinc-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/30 focus:outline-none"
            >
        </div>

        {{-- Filter Chips --}}
        <div class="flex gap-2 mb-3 lg:mb-0 overflow-x-auto pb-1 lg:overflow-visible lg:pb-0">
            @foreach(['all' => 'All', 'documents' => 'Documents', 'tasks' => 'Tasks', 'submissions' => 'Submissions'] as $value => $label)
                <button
                    wire:click="$set('filterType', '{{ $value }}')"
                    class="flex-shrink-0 px-3 py-1.5 text-xs font-medium rounded-full transition-colors
                        {{ $filterType === $value ? 'bg-indigo-600 text-white' : 'bg-zinc-800 text-zinc-400 hover:bg-zinc-700' }}"
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- Status Filter --}}
        <div class="flex gap-2 overflow-x-auto pb-1 lg:overflow-visible lg:pb-0">
            @foreach(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'all' => 'All Status'] as $value => $label)
                <button
                    wire:click="$set('filterStatus', '{{ $value }}')"
                    class="flex-shrink-0 px-3 py-1.5 text-xs font-medium rounded-full transition-colors
                        {{ $filterStatus === $value ? 'bg-zinc-600 text-white' : 'bg-zinc-800 text-zinc-400 hover:bg-zinc-700' }}"
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- Approval Cards --}}
    @if($approvals->isEmpty())
        <div class="bg-zinc-900 rounded-xl border border-zinc-800 p-8 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-zinc-600 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-sm text-zinc-500">No items to review</p>
        </div>
    @else
        <div class="space-y-3 lg:grid lg:grid-cols-2 lg:gap-4 lg:space-y-0">
            @foreach($approvals as $approval)
            <div class="approval-card bg-zinc-900 rounded-xl border border-zinc-800 overflow-hidden">
                {{-- Card Header --}}
                <a href="{{ route('mobile.review.detail', $approval->id) }}" wire:navigate class="block p-4">
                    <div class="flex items-start justify-between gap-3 mb-2">
                        <div class="flex-1 min-w-0">
                            {{-- Type badge --}}
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium mb-1.5
                                {{ $approval->approvable_type === 'App\Models\ApprovalSubmission' ? 'bg-purple-500/20 text-purple-400' : '' }}
                                {{ str_contains($approval->approvable_type, 'Document') ? 'bg-blue-500/20 text-blue-400' : '' }}
                                {{ $approval->approvable_type === 'App\Models\WorkPlanGoal' ? 'bg-teal-500/20 text-teal-400' : '' }}
                            ">
                                {{ $approval->getTypeLabel() }}
                            </span>

                            {{-- Item name --}}
                            <h3 class="text-sm font-medium text-white truncate">{{ $approval->item_name }}</h3>

                            @if($approval->item_description)
                                <p class="text-xs text-zinc-500 line-clamp-2 mt-0.5">{{ Str::limit(strip_tags($approval->item_description), 100) }}</p>
                            @endif
                        </div>

                        {{-- Status badge --}}
                        <span class="flex-shrink-0 px-2 py-0.5 rounded text-[10px] font-semibold
                            {{ $approval->status === 'pending' ? 'status-pending' : '' }}
                            {{ $approval->status === 'approved' ? 'status-approved' : '' }}
                            {{ $approval->status === 'rejected' ? 'status-rejected' : '' }}
                        ">
                            {{ ucfirst($approval->status) }}
                        </span>
                    </div>

                    {{-- Meta info --}}
                    <div class="flex items-center gap-3 text-xs text-zinc-500">
                        <span>{{ $approval->submittedBy?->name ?? 'Unknown' }}</span>
                        <span>&middot;</span>
                        <span>{{ $approval->created_at->diffForHumans() }}</span>
                        @if($approval->program)
                            <span>&middot;</span>
                            <span class="truncate">{{ $approval->program->name }}</span>
                        @endif
                    </div>
                </a>

                {{-- Action Buttons (only for pending) --}}
                @if($approval->status === 'pending')
                <div class="flex border-t border-zinc-800">
                    <button
                        wire:click="approve({{ $approval->id }})"
                        wire:confirm="Are you sure you want to approve this item?"
                        class="flex-1 flex items-center justify-center gap-1.5 py-3 text-sm font-medium text-green-400 hover:bg-green-500/10 active:bg-green-500/20 transition-colors"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                        Approve
                    </button>
                    <div class="w-px bg-zinc-800"></div>
                    <button
                        wire:click="openRejectModal({{ $approval->id }})"
                        class="flex-1 flex items-center justify-center gap-1.5 py-3 text-sm font-medium text-red-400 hover:bg-red-500/10 active:bg-red-500/20 transition-colors"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Reject
                    </button>
                </div>
                @endif
            </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-4">
            {{ $approvals->links() }}
        </div>
    @endif

    {{-- Reject Modal --}}
    @if($showRejectModal)
    <div class="fixed inset-0 z-[60] flex items-end sm:items-center justify-center" x-data x-init="$el.querySelector('textarea')?.focus()">
        {{-- Backdrop --}}
        <div class="absolute inset-0 bg-black/60" wire:click="$set('showRejectModal', false)"></div>

        {{-- Modal content --}}
        <div class="relative w-full sm:max-w-md bg-zinc-900 rounded-t-2xl sm:rounded-2xl border border-zinc-800 p-6 safe-bottom">
            <h3 class="text-lg font-semibold text-white mb-1">Reject Item</h3>
            <p class="text-sm text-zinc-400 mb-4">Please explain why this item is being rejected.</p>

            <textarea
                wire:model="rejectionReason"
                rows="4"
                class="w-full px-3 py-2 text-base rounded-lg bg-zinc-800 border border-zinc-700 text-white placeholder-zinc-500 focus:border-red-500 focus:ring-1 focus:ring-red-500/30 focus:outline-none resize-none"
                placeholder="Reason for rejection (minimum 10 characters)..."
            ></textarea>
            @error('rejectionReason')
                <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
            @enderror

            <div class="flex gap-3 mt-4">
                <button
                    wire:click="$set('showRejectModal', false)"
                    class="flex-1 py-3 text-sm font-medium text-zinc-400 bg-zinc-800 rounded-lg hover:bg-zinc-700 transition-colors"
                >
                    Cancel
                </button>
                <button
                    wire:click="reject"
                    class="flex-1 py-3 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-500 active:bg-red-700 transition-colors"
                    wire:loading.attr="disabled"
                >
                    Confirm Rejection
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
