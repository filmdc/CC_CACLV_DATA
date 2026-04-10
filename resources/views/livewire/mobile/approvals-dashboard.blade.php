<div class="p-4 lg:py-8 page-enter">
    {{-- Header --}}
    @php
        $hour = now()->hour;
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
        $firstName = explode(' ', auth()->user()->name)[0];
    @endphp
    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-lg lg:text-2xl font-semibold text-white">{{ $greeting }}, {{ $firstName }}</h1>
            <p class="text-sm text-zinc-400">
                @if($isApprover)
                    {{ $pendingCount }} {{ Str::plural('item', $pendingCount) }} awaiting review
                @else
                    Your approval submissions
                @endif
            </p>
        </div>
        @if($canSubmit)
        <a href="{{ route('mobile.submit') }}" wire:navigate
            class="flex items-center gap-1.5 px-3 py-2 text-sm font-medium bg-green-600 hover:bg-green-500 text-white rounded-lg transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            <span class="hidden sm:inline">New Request</span>
            <span class="sm:hidden">New</span>
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
        <div class="mb-3 lg:mb-0 lg:flex-1 lg:max-w-md">
            <input
                wire:model.live.debounce.300ms="search"
                type="search"
                placeholder="Search{{ $isApprover ? ' by name or title' : ' by title' }}..."
                class="w-full h-10 px-3 text-sm rounded-lg bg-zinc-900 border border-zinc-800 text-white placeholder-zinc-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/30 focus:outline-none"
            >
        </div>
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

    {{-- Items List --}}
    @if($items->isEmpty())
        <div class="bg-zinc-900 rounded-xl border border-zinc-800 p-8 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-zinc-600 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
            </svg>
            <p class="text-sm text-zinc-500 mb-3">
                @if($filterStatus !== 'all' || $search)
                    No matching items found
                @elseif($isApprover)
                    No approval requests yet
                @else
                    No submissions yet
                @endif
            </p>
            @if($canSubmit && !$search && $filterStatus === 'all')
            <a href="{{ route('mobile.submit') }}" wire:navigate
                class="inline-flex items-center gap-1 px-4 py-2 text-sm font-medium bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg transition-colors">
                Create Your First Request
            </a>
            @endif
        </div>
    @else
        {{-- Desktop Table --}}
        <div class="hidden lg:block bg-zinc-900 rounded-xl border border-zinc-800 overflow-hidden">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-zinc-800">
                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Title</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Type</th>
                        @if($isApprover)
                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Submitted By</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Assigned To</th>
                        @endif
                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Program</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Date</th>
                        @if($isApprover)
                        <th class="px-4 py-3 text-right text-xs font-medium text-zinc-500 uppercase tracking-wider"></th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-800">
                    @foreach($items as $item)
                    <tr class="hover:bg-zinc-800/50 transition-colors">
                        <td class="px-4 py-3">
                            <p class="text-sm font-medium text-white truncate max-w-[200px]">{{ $item->item_name ?? $item->title }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-xs text-zinc-400">{{ $item->approvalType?->display_name ?? 'Unknown' }}</span>
                        </td>
                        @if($isApprover)
                        <td class="px-4 py-3">
                            <span class="text-sm text-zinc-300">{{ $item->submittedBy?->name ?? '-' }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-sm text-zinc-300">{{ $item->approver?->name ?? 'All Approvers' }}</span>
                        </td>
                        @endif
                        <td class="px-4 py-3">
                            <span class="text-xs text-zinc-400">{{ $item->program?->name ?? '-' }}</span>
                        </td>
                        <td class="px-4 py-3">
                            @php $status = $item->status; @endphp
                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold
                                {{ $status === 'pending' ? 'status-pending' : '' }}
                                {{ $status === 'approved' ? 'status-approved' : '' }}
                                {{ $status === 'rejected' ? 'status-rejected' : '' }}
                                {{ $status === 'draft' ? 'bg-zinc-700 text-zinc-300' : '' }}
                                {{ $status === 'cancelled' ? 'bg-zinc-700 text-zinc-400' : '' }}
                            ">{{ ucfirst($status) }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-xs text-zinc-500">{{ ($item->submitted_at ?? $item->created_at)->diffForHumans() }}</span>
                        </td>
                        @if($isApprover)
                        <td class="px-4 py-3 text-right">
                            @if($item->status === 'pending')
                            <a href="{{ route('mobile.review.detail', $item->id) }}" wire:navigate
                                class="text-xs font-medium text-indigo-400 hover:text-indigo-300 transition-colors">
                                Review
                            </a>
                            @endif
                        </td>
                        @endif
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Mobile Cards --}}
        <div class="lg:hidden space-y-3">
            @foreach($items as $item)
            @if($isApprover)
                <a href="{{ $item->status === 'pending' ? route('mobile.review.detail', $item->id) : '#' }}"
                    @if($item->status === 'pending') wire:navigate @endif
                    class="block approval-card bg-zinc-900 rounded-xl border border-zinc-800 p-4 {{ $item->status === 'pending' ? '' : 'pointer-events-none' }}">
            @else
                <div class="approval-card bg-zinc-900 rounded-xl border border-zinc-800 p-4">
            @endif
                    <div class="flex items-start justify-between gap-3 mb-2">
                        <h3 class="text-sm font-medium text-white flex-1 min-w-0 truncate">{{ $item->item_name ?? $item->title }}</h3>
                        @php $status = $item->status; @endphp
                        <span class="flex-shrink-0 px-2 py-0.5 rounded text-[10px] font-semibold
                            {{ $status === 'pending' ? 'status-pending' : '' }}
                            {{ $status === 'approved' ? 'status-approved' : '' }}
                            {{ $status === 'rejected' ? 'status-rejected' : '' }}
                            {{ $status === 'draft' ? 'bg-zinc-700 text-zinc-300' : '' }}
                            {{ $status === 'cancelled' ? 'bg-zinc-700 text-zinc-400' : '' }}
                        ">{{ ucfirst($status) }}</span>
                    </div>

                    <div class="text-xs text-zinc-500 space-y-1">
                        <div class="flex items-center gap-2">
                            <span>{{ $item->approvalType?->display_name ?? 'Unknown' }}</span>
                            <span>&middot;</span>
                            <span>{{ ($item->submitted_at ?? $item->created_at)->diffForHumans() }}</span>
                        </div>

                        @if($isApprover)
                        <div class="flex items-center gap-2">
                            <span class="text-zinc-400">From:</span>
                            <span class="text-zinc-300">{{ $item->submittedBy?->name ?? '-' }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-zinc-400">To:</span>
                            <span class="text-zinc-300">{{ $item->approver?->name ?? 'All Approvers' }}</span>
                        </div>
                        @endif

                        @if($item->program)
                        <div class="flex items-center gap-2">
                            <span class="text-zinc-400">Program:</span>
                            <span class="text-zinc-300 truncate">{{ $item->program->name }}</span>
                        </div>
                        @endif
                    </div>

                    @if(!$isApprover && $item->status === 'rejected' && $item->rejection_reason)
                    <div class="mt-2 p-2 rounded bg-red-500/10 border border-red-500/20">
                        <p class="text-xs text-red-400">{{ $item->rejection_reason }}</p>
                    </div>
                    @endif

                    @if($isApprover && $item->status === 'pending')
                    <div class="mt-2 flex justify-end">
                        <span class="text-xs font-medium text-indigo-400">Tap to review</span>
                    </div>
                    @endif
            @if($isApprover)
                </a>
            @else
                </div>
            @endif
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-4">
            {{ $items->links() }}
        </div>
    @endif
</div>
