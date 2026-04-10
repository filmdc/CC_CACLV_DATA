<div class="px-4 py-5 lg:px-0">
    {{-- Header --}}
    <div class="mb-5">
        <h1 class="text-xl font-bold text-white">Executive Overview</h1>
        <p class="text-sm text-zinc-400 mt-0.5">Organization-wide approval status and metrics</p>
    </div>

    {{-- Summary stat cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
        <div class="bg-zinc-900 border border-amber-800/40 rounded-lg p-4">
            <div class="text-2xl font-bold text-amber-400">{{ number_format($summaryStats['pending']) }}</div>
            <div class="text-xs text-zinc-500 uppercase tracking-wide mt-1">Pending</div>
        </div>
        <div class="bg-zinc-900 border border-emerald-800/40 rounded-lg p-4">
            <div class="text-2xl font-bold text-emerald-400">{{ number_format($summaryStats['approved']) }}</div>
            <div class="text-xs text-zinc-500 uppercase tracking-wide mt-1">Approved</div>
        </div>
        <div class="bg-zinc-900 border border-red-800/40 rounded-lg p-4">
            <div class="text-2xl font-bold text-red-400">{{ number_format($summaryStats['rejected']) }}</div>
            <div class="text-xs text-zinc-500 uppercase tracking-wide mt-1">Rejected</div>
        </div>
        <div class="bg-zinc-900 border border-indigo-800/40 rounded-lg p-4">
            <div class="text-2xl font-bold text-indigo-400">{{ $summaryStats['avgResponseTime'] }}</div>
            <div class="text-xs text-zinc-500 uppercase tracking-wide mt-1">Avg Response</div>
        </div>
    </div>

    {{-- Search & status filter chips --}}
    <div class="mb-4">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search approvals..." class="w-full rounded-lg bg-zinc-800 border-zinc-700 text-white text-sm px-3 py-2 mb-3 focus:border-indigo-500 focus:ring-indigo-500">

        <div class="flex gap-2 overflow-x-auto pb-2 scrollbar-hide">
            @foreach([
                'all' => 'All',
                'pending' => 'Pending',
                'approved' => 'Approved',
                'rejected' => 'Rejected',
            ] as $key => $label)
                <button
                    wire:click="$set('filterStatus', '{{ $key }}')"
                    class="flex-shrink-0 px-3 py-1.5 rounded-full text-xs font-medium transition-colors {{ $filterStatus === $key ? 'bg-indigo-600 text-white' : 'bg-zinc-800 text-zinc-400 hover:bg-zinc-700 hover:text-white' }}"
                >{{ $label }}</button>
            @endforeach
        </div>
    </div>

    {{-- Advanced filters --}}
    <div x-data="{ filtersOpen: window.innerWidth >= 1024 }" class="mb-4">
        <button @click="filtersOpen = !filtersOpen" class="flex items-center gap-2 text-sm text-zinc-400 hover:text-white mb-2 lg:hidden">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" /></svg>
            Advanced Filters
            <svg xmlns="http://www.w3.org/2000/svg" :class="filtersOpen ? 'rotate-180' : ''" class="h-3 w-3 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
        </button>
        <div x-show="filtersOpen" x-transition class="space-y-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div>
                    <label class="block text-xs text-zinc-400 mb-1">Program</label>
                    <select wire:model.live="filterProgram" class="w-full rounded-lg bg-zinc-800 border-zinc-700 text-white text-sm px-3 py-2 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Programs</option>
                        @foreach($programs as $program)
                            <option value="{{ $program->id }}">{{ $program->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-zinc-400 mb-1">Type</label>
                    <select wire:model.live="filterType" class="w-full rounded-lg bg-zinc-800 border-zinc-700 text-white text-sm px-3 py-2 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Types</option>
                        @foreach($approvalTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->display_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-zinc-400 mb-1">Group By</label>
                    <select wire:model.live="groupBy" class="w-full rounded-lg bg-zinc-800 border-zinc-700 text-white text-sm px-3 py-2 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="none">No Grouping</option>
                        <option value="program">Program</option>
                        <option value="type">Approval Type</option>
                        <option value="approver">Approver</option>
                        <option value="month">Month</option>
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button wire:click="toggleDateFilter" class="px-3 py-2 text-sm rounded-lg transition-colors {{ $showDateFilter ? 'bg-indigo-600/20 text-indigo-400' : 'text-zinc-400 hover:text-white hover:bg-zinc-800' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>
                        Date Range
                    </button>
                    <button wire:click="resetFilters" class="px-3 py-2 text-sm text-zinc-400 hover:text-white hover:bg-zinc-800 rounded-lg transition-colors">Reset</button>
                </div>
            </div>

            @if($showDateFilter)
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs text-zinc-400 mb-1">From</label>
                    <input type="date" wire:model.live="dateFrom" class="w-full rounded-lg bg-zinc-800 border-zinc-700 text-white text-sm px-3 py-2 focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs text-zinc-400 mb-1">To</label>
                    <input type="date" wire:model.live="dateTo" class="w-full rounded-lg bg-zinc-800 border-zinc-700 text-white text-sm px-3 py-2 focus:border-indigo-500 focus:ring-indigo-500">
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- Grouped view --}}
    @if($grouped)
        <div class="space-y-4 mb-4">
            @forelse($grouped as $groupName => $groupItems)
                <div class="bg-zinc-900 border border-zinc-800 rounded-lg overflow-hidden">
                    <div class="px-4 py-3 bg-zinc-800/50 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-white">{{ $groupName }}</h3>
                        <span class="text-xs text-zinc-400">{{ $groupItems->count() }} {{ Str::plural('item', $groupItems->count()) }}</span>
                    </div>
                    <div class="px-4 py-3">
                        <div class="flex gap-4 text-xs">
                            @php
                                $gPending = $groupItems->where('status', 'pending')->count();
                                $gApproved = $groupItems->where('status', 'approved')->count();
                                $gRejected = $groupItems->where('status', 'rejected')->count();
                            @endphp
                            @if($gPending)
                                <span class="text-amber-400">{{ $gPending }} pending</span>
                            @endif
                            @if($gApproved)
                                <span class="text-emerald-400">{{ $gApproved }} approved</span>
                            @endif
                            @if($gRejected)
                                <span class="text-red-400">{{ $gRejected }} rejected</span>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-8 text-sm text-zinc-500">No records found for the selected filters.</div>
            @endforelse
        </div>
    @else
        {{-- Flat view: Desktop table --}}
        <div class="hidden lg:block bg-zinc-900 border border-zinc-800 rounded-lg overflow-hidden mb-4">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-800">
                    <thead>
                        <tr class="bg-zinc-800/50">
                            <th class="px-4 py-3 text-left text-xs font-medium text-zinc-400 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-zinc-400 uppercase tracking-wider">Item</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-zinc-400 uppercase tracking-wider">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-zinc-400 uppercase tracking-wider">Submitted By</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-zinc-400 uppercase tracking-wider">Approver</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-zinc-400 uppercase tracking-wider">Program</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-zinc-400 uppercase tracking-wider">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-800">
                        @forelse($items as $item)
                        <tr class="hover:bg-zinc-800/30">
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                    {{ $item->status === 'pending' ? 'bg-amber-900/50 text-amber-400' : '' }}
                                    {{ $item->status === 'approved' ? 'bg-emerald-900/50 text-emerald-400' : '' }}
                                    {{ $item->status === 'rejected' ? 'bg-red-900/50 text-red-400' : '' }}
                                ">{{ ucfirst($item->status) }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-zinc-300 max-w-[200px] truncate">{{ $item->getDisplayName() }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-400">{{ $item->approvalType?->display_name ?? $item->getTypeLabel() }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-300">{{ $item->submittedBy?->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-300">{{ $item->approver?->name ?? 'Unassigned' }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-400">{{ $item->program?->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-400 whitespace-nowrap">{{ $item->created_at->format('M j, Y') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-sm text-zinc-500">No approvals found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Flat view: Mobile cards --}}
        <div class="lg:hidden space-y-3 mb-4">
            @forelse($items as $item)
            <div class="bg-zinc-900 border border-zinc-800 rounded-lg p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                        {{ $item->status === 'pending' ? 'bg-amber-900/50 text-amber-400' : '' }}
                        {{ $item->status === 'approved' ? 'bg-emerald-900/50 text-emerald-400' : '' }}
                        {{ $item->status === 'rejected' ? 'bg-red-900/50 text-red-400' : '' }}
                    ">{{ ucfirst($item->status) }}</span>
                    <span class="text-xs text-zinc-500">{{ $item->created_at->format('M j, Y') }}</span>
                </div>
                <p class="text-sm font-medium text-white truncate">{{ $item->getDisplayName() }}</p>
                <p class="text-xs text-zinc-400 mt-1">{{ $item->approvalType?->display_name ?? $item->getTypeLabel() }}</p>
                <div class="flex items-center justify-between mt-2 pt-2 border-t border-zinc-800">
                    <span class="text-xs text-zinc-500">{{ $item->submittedBy?->name ?? '-' }}</span>
                    <span class="text-xs text-zinc-500">{{ $item->program?->name ?? '' }}</span>
                </div>
            </div>
            @empty
            <div class="text-center py-8 text-sm text-zinc-500">No approvals found.</div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($items && $items->hasPages())
        <div class="mt-4">
            {{ $items->links() }}
        </div>
        @endif
    @endif
</div>
