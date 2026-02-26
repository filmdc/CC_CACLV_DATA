<div class="p-4 lg:py-8 page-enter">
    {{-- Back Button --}}
    <a href="{{ route('mobile.review') }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-indigo-400 hover:text-indigo-300 mb-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
        </svg>
        Back to Review
    </a>

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

    {{-- Desktop: two-column layout for detail + sidebar --}}
    <div class="lg:grid lg:grid-cols-3 lg:gap-6">
        {{-- Main content column --}}
        <div class="lg:col-span-2">
            {{-- Main Card --}}
            <div class="bg-zinc-900 rounded-xl border border-zinc-800 overflow-hidden mb-4">
                {{-- Header with type and status --}}
                <div class="p-4 lg:p-6 border-b border-zinc-800">
                    <div class="flex items-center justify-between gap-3 mb-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium
                            {{ $approval->approvable_type === 'App\Models\ApprovalSubmission' ? 'bg-purple-500/20 text-purple-400' : '' }}
                            {{ str_contains($approval->approvable_type ?? '', 'Document') ? 'bg-blue-500/20 text-blue-400' : '' }}
                            {{ $approval->approvable_type === 'App\Models\WorkPlanGoal' ? 'bg-teal-500/20 text-teal-400' : '' }}
                        ">
                            {{ $approval->getTypeLabel() }}
                        </span>

                        <span class="px-2.5 py-0.5 rounded text-xs font-semibold
                            {{ $approval->status === 'pending' ? 'status-pending' : '' }}
                            {{ $approval->status === 'approved' ? 'status-approved' : '' }}
                            {{ $approval->status === 'rejected' ? 'status-rejected' : '' }}
                        ">
                            {{ ucfirst($approval->status) }}
                        </span>
                    </div>
                    <h1 class="text-lg lg:text-xl font-semibold text-white">{{ $approval->item_name }}</h1>
                </div>

                {{-- Details --}}
                <div class="p-4 lg:p-6 space-y-4 lg:space-y-5">
                    {{-- Description --}}
                    @if($approval->item_description)
                    <div>
                        <h3 class="text-xs font-medium text-zinc-500 uppercase tracking-wider mb-1">Description</h3>
                        <div class="text-sm text-zinc-300 rich-text-content">{!! $approval->item_description !!}</div>
                    </div>
                    @endif

                    {{-- Submitter Notes --}}
                    @if($approval->submitter_notes)
                    <div class="bg-blue-500/10 border border-blue-500/20 rounded-lg p-3">
                        <h3 class="text-xs font-medium text-blue-400 uppercase tracking-wider mb-1">Submitter Notes</h3>
                        <p class="text-sm text-zinc-300">{{ $approval->submitter_notes }}</p>
                    </div>
                    @endif

                    {{-- Submission Description --}}
                    @if($approval->approvable && $approval->approvable instanceof \App\Models\ApprovalSubmission && $approval->approvable->description)
                    <div>
                        <h3 class="text-xs font-medium text-zinc-500 uppercase tracking-wider mb-1">Submission Details</h3>
                        <div class="text-sm text-zinc-300">{!! nl2br(e($approval->approvable->description)) !!}</div>
                    </div>
                    @endif

                    {{-- Meta Grid --}}
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                        <div>
                            <span class="text-xs text-zinc-500">Submitted by</span>
                            <p class="text-sm text-white">{{ $approval->submittedBy?->name ?? 'Unknown' }}</p>
                        </div>
                        <div>
                            <span class="text-xs text-zinc-500">Date</span>
                            <p class="text-sm text-white">{{ $approval->created_at->format('M j, Y g:i A') }}</p>
                        </div>
                        @if($approval->program)
                        <div>
                            <span class="text-xs text-zinc-500">Program</span>
                            <p class="text-sm text-white">{{ $approval->program->name }}</p>
                        </div>
                        @endif
                        @if($approval->priority)
                        <div>
                            <span class="text-xs text-zinc-500">Priority</span>
                            <p class="text-sm text-white capitalize">{{ $approval->priority }}</p>
                        </div>
                        @endif
                    </div>

                    {{-- Approval Chain Progress --}}
                    @if($totalSteps > 1)
                    <div>
                        <h3 class="text-xs font-medium text-zinc-500 uppercase tracking-wider mb-2">Approval Progress</h3>
                        <div class="flex items-center gap-1">
                            @for($i = 1; $i <= $totalSteps; $i++)
                                <div class="flex-1 h-2 rounded-full
                                    {{ $i < $currentStep ? 'bg-green-500' : '' }}
                                    {{ $i === $currentStep ? 'bg-indigo-500' : '' }}
                                    {{ $i > $currentStep ? 'bg-zinc-700' : '' }}
                                "></div>
                            @endfor
                        </div>
                        <p class="text-xs text-zinc-500 mt-1">Step {{ $currentStep }} of {{ $totalSteps }}</p>
                    </div>
                    @endif

                    {{-- Rejection Reason --}}
                    @if($approval->status === 'rejected' && $approval->rejection_reason)
                    <div class="bg-red-500/10 border border-red-500/20 rounded-lg p-3">
                        <h3 class="text-xs font-medium text-red-400 uppercase tracking-wider mb-1">Rejection Reason</h3>
                        <p class="text-sm text-zinc-300">{{ $approval->rejection_reason }}</p>
                    </div>
                    @endif

                    {{-- Desktop inline action buttons --}}
                    @if($approval->status === 'pending')
                    <div class="hidden lg:flex gap-3 pt-2">
                        <button
                            wire:click="openRejectModal"
                            class="px-6 py-2.5 text-sm font-semibold text-red-400 bg-red-500/10 border border-red-500/30 rounded-lg hover:bg-red-500/20 transition-colors"
                        >
                            Reject
                        </button>
                        <button
                            wire:click="approve"
                            wire:confirm="Are you sure you want to approve this item?"
                            class="px-6 py-2.5 text-sm font-semibold text-white bg-green-600 rounded-lg hover:bg-green-500 transition-colors"
                            wire:loading.attr="disabled"
                        >
                            <span wire:loading.remove wire:target="approve">Approve</span>
                            <span wire:loading wire:target="approve">Processing...</span>
                        </button>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Sidebar column (attachments + history) --}}
        <div class="lg:col-span-1">
            {{-- Attachments --}}
            @if(count($attachments) > 0)
            <div class="bg-zinc-900 rounded-xl border border-zinc-800 p-4 mb-4">
                <h3 class="text-xs font-medium text-zinc-500 uppercase tracking-wider mb-3">Attachments ({{ count($attachments) }})</h3>
                <div class="space-y-2">
                    @foreach($attachments as $attachment)
                    <a href="{{ $attachment->getUrl() }}" target="_blank" rel="noopener"
                        class="flex items-center gap-3 p-2.5 rounded-lg bg-zinc-800 hover:bg-zinc-700 transition-colors">
                        <div class="flex-shrink-0 h-8 w-8 rounded bg-indigo-500/20 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13" />
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-white truncate">{{ $attachment->file_name }}</p>
                            <p class="text-xs text-zinc-500">{{ number_format($attachment->size / 1024, 1) }} KB</p>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- History Timeline --}}
            @if($history->isNotEmpty())
            <div class="bg-zinc-900 rounded-xl border border-zinc-800 p-4 mb-24 lg:mb-4">
                <h3 class="text-xs font-medium text-zinc-500 uppercase tracking-wider mb-3">History</h3>
                <div class="space-y-3">
                    @foreach($history as $entry)
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 mt-0.5">
                            @if($entry->action === 'approved')
                                <div class="h-6 w-6 rounded-full bg-green-500/20 flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                </div>
                            @elseif($entry->action === 'rejected')
                                <div class="h-6 w-6 rounded-full bg-red-500/20 flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </div>
                            @else
                                <div class="h-6 w-6 rounded-full bg-blue-500/20 flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-white">
                                <span class="font-medium">{{ $entry->user?->name ?? 'System' }}</span>
                                <span class="text-zinc-400">{{ $entry->action_label ?? $entry->action }}</span>
                            </p>
                            @if($entry->reason)
                                <p class="text-xs text-zinc-500 mt-0.5">{{ $entry->reason }}</p>
                            @endif
                            @if($entry->notes)
                                <p class="text-xs text-zinc-500 mt-0.5">{{ $entry->notes }}</p>
                            @endif
                            <p class="text-xs text-zinc-600 mt-0.5">{{ $entry->created_at->format('M j, Y g:i A') }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- Fixed Action Bar (mobile only, for pending) --}}
    @if($approval->status === 'pending')
    <div class="fixed bottom-16 left-0 right-0 z-40 bg-zinc-900/95 backdrop-blur border-t border-zinc-800 p-4 safe-bottom lg:hidden">
        <div class="flex gap-3 max-w-lg mx-auto">
            <button
                wire:click="openRejectModal"
                class="flex-1 py-3 text-sm font-semibold text-red-400 bg-red-500/10 border border-red-500/30 rounded-lg hover:bg-red-500/20 active:bg-red-500/30 transition-colors"
            >
                Reject
            </button>
            <button
                wire:click="approve"
                wire:confirm="Are you sure you want to approve this item?"
                class="flex-1 py-3 text-sm font-semibold text-white bg-green-600 rounded-lg hover:bg-green-500 active:bg-green-700 transition-colors"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove wire:target="approve">Approve</span>
                <span wire:loading wire:target="approve">Processing...</span>
            </button>
        </div>
    </div>
    @endif

    {{-- Reject Modal --}}
    @if($showRejectModal)
    <div class="fixed inset-0 z-[60] flex items-end sm:items-center justify-center">
        <div class="absolute inset-0 bg-black/60" wire:click="$set('showRejectModal', false)"></div>
        <div class="relative w-full sm:max-w-md bg-zinc-900 rounded-t-2xl sm:rounded-2xl border border-zinc-800 p-6 safe-bottom">
            <h3 class="text-lg font-semibold text-white mb-1">Reject Item</h3>
            <p class="text-sm text-zinc-400 mb-4">Please explain why this is being rejected.</p>

            <textarea
                wire:model="rejectionReason"
                rows="4"
                class="w-full px-3 py-2 text-base rounded-lg bg-zinc-800 border border-zinc-700 text-white placeholder-zinc-500 focus:border-red-500 focus:ring-1 focus:ring-red-500/30 focus:outline-none resize-none"
                placeholder="Reason for rejection (min 10 characters)..."
                autofocus
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
