<div class="p-4 lg:py-8 page-enter">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-lg lg:text-2xl font-semibold text-white">My Submissions</h1>
        @if(auth()->user()->hasPermission('approvals.submit'))
        <a href="{{ route('mobile.submit') }}" wire:navigate
            class="flex items-center gap-1 px-3 py-1.5 text-xs font-medium bg-green-600 hover:bg-green-500 text-white rounded-lg transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            New
        </a>
        @endif
    </div>

    {{-- Flash Messages --}}
    @if (session()->has('message'))
        <div class="mb-4 p-3 rounded-lg bg-green-500/20 border border-green-500/30 text-sm text-green-400">
            {{ session('message') }}
        </div>
    @endif

    {{-- Search & Filters --}}
    <div class="lg:flex lg:items-center lg:gap-4 mb-4">
        {{-- Search --}}
        <div class="mb-3 lg:mb-0 lg:flex-1 lg:max-w-md">
            <input
                wire:model.live.debounce.300ms="search"
                type="search"
                placeholder="Search by title..."
                class="w-full h-10 px-3 text-sm rounded-lg bg-zinc-900 border border-zinc-800 text-white placeholder-zinc-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/30 focus:outline-none"
            >
        </div>

        {{-- Status Filter Tabs --}}
        <div class="flex gap-2 overflow-x-auto pb-1 lg:overflow-visible lg:pb-0">
            @foreach(['all' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $value => $label)
                <button
                    wire:click="$set('filterStatus', '{{ $value }}')"
                    class="flex-shrink-0 px-3 py-1.5 text-xs font-medium rounded-full transition-colors
                        {{ $filterStatus === $value ? 'bg-indigo-600 text-white' : 'bg-zinc-800 text-zinc-400 hover:bg-zinc-700' }}"
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- Submission Cards --}}
    @if($submissions->isEmpty())
        <div class="bg-zinc-900 rounded-xl border border-zinc-800 p-8 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-zinc-600 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
            </svg>
            <p class="text-sm text-zinc-500 mb-3">No submissions yet</p>
            @if(auth()->user()->hasPermission('approvals.submit'))
            <a href="{{ route('mobile.submit') }}" wire:navigate
                class="inline-flex items-center gap-1 px-4 py-2 text-sm font-medium bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg transition-colors">
                Create Your First Submission
            </a>
            @endif
        </div>
    @else
        <div class="space-y-3 lg:grid lg:grid-cols-2 lg:gap-4 lg:space-y-0" x-data="{ expanded: null }">
            @foreach($submissions as $submission)
            <div class="approval-card bg-zinc-900 rounded-xl border border-zinc-800 overflow-hidden">
                {{-- Card Header (tappable to expand) --}}
                <button
                    @click="expanded = expanded === {{ $submission->id }} ? null : {{ $submission->id }}"
                    class="w-full text-left p-4"
                >
                    <div class="flex items-start justify-between gap-3 mb-1.5">
                        <h3 class="text-sm font-medium text-white flex-1 min-w-0 truncate">{{ $submission->title }}</h3>
                        <span class="flex-shrink-0 px-2 py-0.5 rounded text-[10px] font-semibold
                            {{ $submission->status === 'draft' ? 'bg-zinc-700 text-zinc-300' : '' }}
                            {{ $submission->status === 'pending' ? 'status-pending' : '' }}
                            {{ $submission->status === 'approved' ? 'status-approved' : '' }}
                            {{ $submission->status === 'rejected' ? 'status-rejected' : '' }}
                            {{ $submission->status === 'cancelled' ? 'bg-zinc-700 text-zinc-400' : '' }}
                        ">
                            {{ ucfirst($submission->status) }}
                        </span>
                    </div>
                    <div class="flex items-center gap-3 text-xs text-zinc-500">
                        <span>{{ $submission->approvalType?->display_name ?? 'Unknown' }}</span>
                        <span>&middot;</span>
                        <span>{{ ($submission->submitted_at ?? $submission->created_at)->diffForHumans() }}</span>
                        @if($submission->program)
                            <span>&middot;</span>
                            <span class="truncate">{{ $submission->program->name }}</span>
                        @endif
                    </div>

                    {{-- Expand indicator --}}
                    <div class="flex justify-center mt-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-zinc-600 transition-transform" :class="expanded === {{ $submission->id }} ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                        </svg>
                    </div>
                </button>

                {{-- Expanded Details --}}
                <div x-show="expanded === {{ $submission->id }}" x-collapse class="border-t border-zinc-800">
                    <div class="p-4 space-y-3">
                        @if($submission->description)
                        <div>
                            <span class="text-xs text-zinc-500">Description</span>
                            <p class="text-sm text-zinc-300 mt-0.5">{{ $submission->description }}</p>
                        </div>
                        @endif

                        @if($submission->submitter_notes)
                        <div>
                            <span class="text-xs text-zinc-500">Notes</span>
                            <p class="text-sm text-zinc-300 mt-0.5">{{ $submission->submitter_notes }}</p>
                        </div>
                        @endif

                        @if($submission->approvedBy)
                        <div>
                            <span class="text-xs text-zinc-500">Reviewed by</span>
                            <p class="text-sm text-white mt-0.5">{{ $submission->approvedBy->name }}</p>
                        </div>
                        @endif

                        @if($submission->status === 'rejected' && $submission->rejection_reason)
                        <div class="bg-red-500/10 border border-red-500/20 rounded-lg p-3">
                            <span class="text-xs font-medium text-red-400">Rejection Reason</span>
                            <p class="text-sm text-zinc-300 mt-0.5">{{ $submission->rejection_reason }}</p>
                        </div>
                        @endif

                        @if($submission->status === 'rejected' && auth()->user()->hasPermission('approvals.submit'))
                        <a href="{{ route('mobile.submit') }}" wire:navigate
                            class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182" />
                            </svg>
                            Resubmit
                        </a>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-4">
            {{ $submissions->links() }}
        </div>
    @endif
</div>
