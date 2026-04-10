<div class="px-4 py-5 lg:px-0">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
        <div>
            <h1 class="text-xl font-bold text-white">Timeline Report</h1>
            <p class="text-sm text-zinc-400 mt-0.5">Approval activity for the selected period</p>
        </div>
        <div x-data="{ open: false }" class="relative">
            <button @click="open = !open" class="flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                Export
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
            </button>
            <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 mt-1 w-40 bg-zinc-800 border border-zinc-700 rounded-lg shadow-xl py-1 z-30" x-cloak>
                <button wire:click="exportToPdf" @click="open = false" class="w-full text-left px-4 py-2 text-sm text-zinc-300 hover:bg-zinc-700 hover:text-white">PDF</button>
                <button wire:click="exportToExcel" @click="open = false" class="w-full text-left px-4 py-2 text-sm text-zinc-300 hover:bg-zinc-700 hover:text-white">Excel (.xlsx)</button>
                <button wire:click="exportToCsv" @click="open = false" class="w-full text-left px-4 py-2 text-sm text-zinc-300 hover:bg-zinc-700 hover:text-white">CSV</button>
            </div>
        </div>
    </div>

    {{-- Flash messages --}}
    @if(session()->has('error'))
        <div class="mb-4 p-3 bg-red-900/50 border border-red-700 rounded-lg text-sm text-red-300">{{ session('error') }}</div>
    @endif

    {{-- Date preset chips --}}
    <div class="flex gap-2 overflow-x-auto pb-2 mb-4 scrollbar-hide">
        @foreach([
            'this_week' => 'This Week',
            'this_month' => 'This Month',
            'last_month' => 'Last Month',
            'this_quarter' => 'This Quarter',
            'custom' => 'Custom',
        ] as $key => $label)
            <button
                wire:click="applyDatePreset('{{ $key }}')"
                class="flex-shrink-0 px-3 py-1.5 rounded-full text-xs font-medium transition-colors {{ $datePreset === $key ? 'bg-indigo-600 text-white' : 'bg-zinc-800 text-zinc-400 hover:bg-zinc-700 hover:text-white' }}"
            >{{ $label }}</button>
        @endforeach
    </div>

    {{-- Custom date inputs --}}
    @if($datePreset === 'custom')
    <div class="grid grid-cols-2 gap-3 mb-4">
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

    {{-- Filters --}}
    <div x-data="{ filtersOpen: window.innerWidth >= 1024 }" class="mb-4">
        <button @click="filtersOpen = !filtersOpen" class="flex items-center gap-2 text-sm text-zinc-400 hover:text-white mb-2 lg:hidden">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" /></svg>
            Filters
            <svg xmlns="http://www.w3.org/2000/svg" :class="filtersOpen ? 'rotate-180' : ''" class="h-3 w-3 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
        </button>
        <div x-show="filtersOpen" x-transition class="space-y-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div>
                    <label class="block text-xs text-zinc-400 mb-1">Search</label>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search..." class="w-full rounded-lg bg-zinc-800 border-zinc-700 text-white text-sm px-3 py-2 focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs text-zinc-400 mb-1">Status</label>
                    <select wire:model.live="filterStatus" class="w-full rounded-lg bg-zinc-800 border-zinc-700 text-white text-sm px-3 py-2 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="all">All Statuses</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                        <option value="resubmitted">Resubmitted</option>
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
                    <label class="block text-xs text-zinc-400 mb-1">Program</label>
                    <select wire:model.live="filterProgram" class="w-full rounded-lg bg-zinc-800 border-zinc-700 text-white text-sm px-3 py-2 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Programs</option>
                        @foreach($programs as $program)
                            <option value="{{ $program->id }}">{{ $program->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div>
                    <label class="block text-xs text-zinc-400 mb-1">Approver</label>
                    <select wire:model.live="filterApprover" class="w-full rounded-lg bg-zinc-800 border-zinc-700 text-white text-sm px-3 py-2 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Approvers</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-zinc-400 mb-1">Detail Level</label>
                    <select wire:model.live="detailLevel" class="w-full rounded-lg bg-zinc-800 border-zinc-700 text-white text-sm px-3 py-2 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="summary">Summary Only</option>
                        <option value="standard">Standard</option>
                        <option value="detailed">Detailed</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button wire:click="resetFilters" class="px-3 py-2 text-sm text-zinc-400 hover:text-white hover:bg-zinc-800 rounded-lg transition-colors">Reset Filters</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Stat cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
        <div class="bg-zinc-900 border border-zinc-800 rounded-lg p-4">
            <div class="text-2xl font-bold text-indigo-400">{{ number_format($stats['total']) }}</div>
            <div class="text-xs text-zinc-500 uppercase tracking-wide mt-1">Total Actions</div>
        </div>
        <div class="bg-zinc-900 border border-zinc-800 rounded-lg p-4">
            <div class="text-2xl font-bold text-emerald-400">{{ number_format($stats['approved']) }}</div>
            <div class="text-xs text-zinc-500 uppercase tracking-wide mt-1">Approved</div>
        </div>
        <div class="bg-zinc-900 border border-zinc-800 rounded-lg p-4">
            <div class="text-2xl font-bold text-red-400">{{ number_format($stats['rejected']) }}</div>
            <div class="text-xs text-zinc-500 uppercase tracking-wide mt-1">Rejected</div>
        </div>
        <div class="bg-zinc-900 border border-zinc-800 rounded-lg p-4">
            <div class="text-2xl font-bold text-amber-400">{{ number_format($stats['resubmitted']) }}</div>
            <div class="text-xs text-zinc-500 uppercase tracking-wide mt-1">Resubmitted</div>
        </div>
    </div>

    {{-- Summary breakdown tables --}}
    @if($detailLevel === 'summary')
        @if($stats['byType']->isNotEmpty())
        <div class="bg-zinc-900 border border-zinc-800 rounded-lg p-4 mb-4">
            <h3 class="text-sm font-semibold text-white mb-3">By Type</h3>
            <div class="space-y-2">
                @foreach($stats['byType'] as $item)
                <div class="flex items-center justify-between">
                    <span class="text-sm text-zinc-300">{{ $item['name'] }}</span>
                    <span class="text-sm font-semibold text-white">{{ $item['count'] }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        @if(is_countable($stats['byProgram']) && count($stats['byProgram']) > 0)
        <div class="bg-zinc-900 border border-zinc-800 rounded-lg p-4 mb-4">
            <h3 class="text-sm font-semibold text-white mb-3">By Program</h3>
            <div class="space-y-2">
                @foreach($stats['byProgram'] as $item)
                <div class="flex items-center justify-between">
                    <span class="text-sm text-zinc-300">{{ $item->name }}</span>
                    <span class="text-sm font-semibold text-white">{{ $item->count }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        @if($stats['byApprover']->isNotEmpty())
        <div class="bg-zinc-900 border border-zinc-800 rounded-lg p-4 mb-4">
            <h3 class="text-sm font-semibold text-white mb-3">By Approver</h3>
            <div class="space-y-2">
                @foreach($stats['byApprover'] as $item)
                <div class="flex items-center justify-between">
                    <span class="text-sm text-zinc-300">{{ $item['name'] }}</span>
                    <span class="text-sm font-semibold text-white">{{ $item['count'] }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    @endif

    {{-- Data table (standard/detailed) --}}
    @if($detailLevel !== 'summary' && $timeline)
        {{-- Desktop table --}}
        <div class="hidden lg:block bg-zinc-900 border border-zinc-800 rounded-lg overflow-hidden mb-4">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-800">
                    <thead>
                        <tr class="bg-zinc-800/50">
                            <th class="px-4 py-3 text-left text-xs font-medium text-zinc-400 uppercase tracking-wider">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-zinc-400 uppercase tracking-wider">Action</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-zinc-400 uppercase tracking-wider">Item</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-zinc-400 uppercase tracking-wider">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-zinc-400 uppercase tracking-wider">Actioned By</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-zinc-400 uppercase tracking-wider">Program</th>
                            @if($detailLevel === 'detailed')
                                <th class="px-4 py-3 text-left text-xs font-medium text-zinc-400 uppercase tracking-wider">Category</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-zinc-400 uppercase tracking-wider">Step</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-zinc-400 uppercase tracking-wider">Reason/Notes</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-800">
                        @forelse($timeline as $record)
                        <tr class="hover:bg-zinc-800/30">
                            <td class="px-4 py-3 text-sm text-zinc-300 whitespace-nowrap">{{ $record->created_at->format('M j, Y g:ia') }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                    {{ $record->action === 'approved' ? 'bg-emerald-900/50 text-emerald-400' : '' }}
                                    {{ $record->action === 'rejected' ? 'bg-red-900/50 text-red-400' : '' }}
                                    {{ $record->action === 'resubmitted' ? 'bg-amber-900/50 text-amber-400' : '' }}
                                ">{{ $record->action_label }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-zinc-300 max-w-[200px] truncate">{{ $this->resolveApprovableName($record) }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-400">{{ $this->resolveApprovableLabel($record) }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-300">{{ $record->user?->name ?? 'System' }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-400">{{ $this->resolveProgramName($record) }}</td>
                            @if($detailLevel === 'detailed')
                                <td class="px-4 py-3 text-sm text-zinc-400">{{ $this->resolveCategory($record) }}</td>
                                <td class="px-4 py-3 text-sm text-zinc-400">{{ $record->chain_step ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-zinc-400 max-w-[200px] truncate">{{ $record->reason ?? $record->notes ?? '-' }}</td>
                            @endif
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ $detailLevel === 'detailed' ? 9 : 6 }}" class="px-4 py-8 text-center text-sm text-zinc-500">No records found for the selected filters.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Mobile cards --}}
        <div class="lg:hidden space-y-3 mb-4">
            @forelse($timeline as $record)
            <div class="bg-zinc-900 border border-zinc-800 rounded-lg p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                        {{ $record->action === 'approved' ? 'bg-emerald-900/50 text-emerald-400' : '' }}
                        {{ $record->action === 'rejected' ? 'bg-red-900/50 text-red-400' : '' }}
                        {{ $record->action === 'resubmitted' ? 'bg-amber-900/50 text-amber-400' : '' }}
                    ">{{ $record->action_label }}</span>
                    <span class="text-xs text-zinc-500">{{ $record->created_at->format('M j, g:ia') }}</span>
                </div>
                <p class="text-sm font-medium text-white truncate">{{ $this->resolveApprovableName($record) }}</p>
                <p class="text-xs text-zinc-400 mt-1">{{ $this->resolveApprovableLabel($record) }} &middot; {{ $record->user?->name ?? 'System' }}</p>
                @if($this->resolveProgramName($record) !== '-')
                    <p class="text-xs text-zinc-500 mt-1">{{ $this->resolveProgramName($record) }}</p>
                @endif
                @if($detailLevel === 'detailed')
                    <div class="mt-2 pt-2 border-t border-zinc-800 space-y-1">
                        <p class="text-xs text-zinc-500">Category: {{ $this->resolveCategory($record) }}</p>
                        @if($record->chain_step)<p class="text-xs text-zinc-500">Step: {{ $record->chain_step }}</p>@endif
                        @if($record->reason ?? $record->notes)<p class="text-xs text-zinc-400">{{ $record->reason ?? $record->notes }}</p>@endif
                    </div>
                @endif
            </div>
            @empty
            <div class="text-center py-8 text-sm text-zinc-500">No records found for the selected filters.</div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($timeline->hasPages())
        <div class="mt-4">
            {{ $timeline->links() }}
        </div>
        @endif
    @endif
</div>
