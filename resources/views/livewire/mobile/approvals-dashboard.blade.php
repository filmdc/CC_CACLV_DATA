<div class="p-4 page-enter">
    {{-- Greeting --}}
    @php
        $hour = now()->hour;
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
        $firstName = explode(' ', auth()->user()->name)[0];
    @endphp
    <h1 class="text-xl font-semibold text-white mb-1">{{ $greeting }}, {{ $firstName }}</h1>
    <p class="text-sm text-zinc-400 mb-5">Here's your approvals overview</p>

    {{-- Stat Cards --}}
    <div class="grid grid-cols-2 gap-3 mb-6">
        @if($isApprover)
        <a href="{{ route('mobile.review') }}" wire:navigate class="approval-card bg-zinc-900 rounded-xl p-4 border border-zinc-800">
            <div class="flex items-center gap-2 mb-2">
                <div class="flex items-center justify-center h-8 w-8 min-h-0 min-w-0 rounded-lg bg-amber-500/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span class="text-xs text-zinc-400">Pending Review</span>
            </div>
            <p class="text-2xl font-bold text-amber-400">{{ $pendingReviewCount }}</p>
        </a>
        @endif

        <a href="{{ route('mobile.my-submissions') }}" wire:navigate class="approval-card bg-zinc-900 rounded-xl p-4 border border-zinc-800">
            <div class="flex items-center gap-2 mb-2">
                <div class="flex items-center justify-center h-8 w-8 min-h-0 min-w-0 rounded-lg bg-blue-500/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span class="text-xs text-zinc-400">My Pending</span>
            </div>
            <p class="text-2xl font-bold text-blue-400">{{ $myPendingCount }}</p>
        </a>

        <div class="approval-card bg-zinc-900 rounded-xl p-4 border border-zinc-800">
            <div class="flex items-center gap-2 mb-2">
                <div class="flex items-center justify-center h-8 w-8 min-h-0 min-w-0 rounded-lg bg-green-500/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span class="text-xs text-zinc-400">Approved</span>
            </div>
            <p class="text-2xl font-bold text-green-400">{{ $myApprovedCount }}</p>
        </div>

        <div class="approval-card bg-zinc-900 rounded-xl p-4 border border-zinc-800">
            <div class="flex items-center gap-2 mb-2">
                <div class="flex items-center justify-center h-8 w-8 min-h-0 min-w-0 rounded-lg bg-red-500/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span class="text-xs text-zinc-400">Rejected</span>
            </div>
            <p class="text-2xl font-bold text-red-400">{{ $myRejectedCount }}</p>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="flex gap-3 mb-6 overflow-x-auto pb-1">
        @if($isApprover)
        <a href="{{ route('mobile.review') }}" wire:navigate
            class="flex-shrink-0 flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 rounded-lg text-sm font-medium text-white transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z" />
            </svg>
            Review Queue
        </a>
        @endif

        @if($canSubmit)
        <a href="{{ route('mobile.submit') }}" wire:navigate
            class="flex-shrink-0 flex items-center gap-2 px-4 py-2.5 bg-green-600 hover:bg-green-500 active:bg-green-700 rounded-lg text-sm font-medium text-white transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            New Request
        </a>
        @endif

        <a href="{{ route('mobile.my-submissions') }}" wire:navigate
            class="flex-shrink-0 flex items-center gap-2 px-4 py-2.5 bg-zinc-700 hover:bg-zinc-600 active:bg-zinc-800 rounded-lg text-sm font-medium text-white transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
            </svg>
            My Submissions
        </a>
    </div>

    {{-- Recent Activity --}}
    <div>
        <h2 class="text-sm font-semibold text-zinc-300 uppercase tracking-wider mb-3">Recent Activity</h2>

        @if($recentActivity->isEmpty())
            <div class="bg-zinc-900 rounded-xl border border-zinc-800 p-6 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-zinc-600 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-sm text-zinc-500">No recent activity</p>
            </div>
        @else
            <div class="space-y-2">
                @foreach($recentActivity as $activity)
                <div class="bg-zinc-900 rounded-xl border border-zinc-800 p-3 flex items-start gap-3">
                    {{-- Action icon --}}
                    <div class="flex-shrink-0 mt-0.5">
                        @if($activity->action === 'approved')
                            <div class="h-8 w-8 rounded-full bg-green-500/20 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                            </div>
                        @elseif($activity->action === 'rejected')
                            <div class="h-8 w-8 rounded-full bg-red-500/20 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </div>
                        @else
                            <div class="h-8 w-8 rounded-full bg-blue-500/20 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        @endif
                    </div>

                    {{-- Activity details --}}
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-white">
                            <span class="font-medium">{{ $activity->user?->name ?? 'System' }}</span>
                            <span class="text-zinc-400">{{ $activity->action_label ?? $activity->action }}</span>
                        </p>
                        @if($activity->reason)
                            <p class="text-xs text-zinc-500 truncate mt-0.5">{{ $activity->reason }}</p>
                        @endif
                        <p class="text-xs text-zinc-600 mt-0.5">{{ $activity->created_at->diffForHumans() }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
