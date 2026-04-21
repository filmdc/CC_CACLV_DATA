<div class="max-w-full mx-auto p-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Contract Utilization Dashboard</h1>
            <p class="text-gray-600 dark:text-gray-400">Track budget utilization across all contracts</p>
        </div>
        <div class="flex flex-wrap gap-2 items-center">
            <button wire:click="syncFromIntacct"
                    wire:loading.attr="disabled"
                    wire:target="syncFromIntacct"
                    class="inline-flex items-center gap-2 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white text-sm rounded-md shadow-sm">
                <svg wire:loading.remove wire:target="syncFromIntacct" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <svg wire:loading wire:target="syncFromIntacct" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span wire:loading.remove wire:target="syncFromIntacct">Sync from Intacct</span>
                <span wire:loading wire:target="syncFromIntacct">Dispatching…</span>
            </button>
            <select wire:model.live="viewMode" class="rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white text-sm">
                <option value="cards">Card View</option>
                <option value="table">Table View</option>
            </select>
        </div>
    </div>

    {{-- Flash messages --}}
    @if (session()->has('message'))
        <div class="rounded-md bg-green-50 dark:bg-green-900/40 border border-green-200 dark:border-green-800 p-3 mb-4 text-sm text-green-800 dark:text-green-200">
            {{ session('message') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="rounded-md bg-red-50 dark:bg-red-900/40 border border-red-200 dark:border-red-800 p-3 mb-4 text-sm text-red-800 dark:text-red-200">
            {{ session('error') }}
        </div>
    @endif

    {{-- Date Range (drives Intacct window for totals + sync) --}}
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl p-4 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-end gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Period — from</label>
                <input type="date" wire:model.live.debounce.500ms="dateFrom"
                       class="rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">through</label>
                <input type="date" wire:model.live.debounce.500ms="dateTo"
                       class="rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white text-sm">
            </div>
            <button wire:click="resetDateRange" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline self-end pb-2">
                Reset to current FY
            </button>
            <div class="text-xs text-gray-500 dark:text-gray-400 sm:ml-auto pb-2">
                Intacct-sourced totals below are scoped to this window. Manual-entry contracts show lifetime totals.
            </div>
        </div>
    </div>

    {{-- System Overview Metrics Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        {{-- Total Contracts --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 bg-indigo-500 rounded-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Contracts</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($systemMetrics['total_contracts']) }}</p>
                </div>
            </div>
        </div>

        {{-- Total Awarded --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 bg-green-500 rounded-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Awarded</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white">${{ number_format($systemMetrics['total_awarded'], 0) }}</p>
                </div>
            </div>
        </div>

        {{-- Total Spent --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 bg-blue-500 rounded-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Spent</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white">${{ number_format($systemMetrics['total_spent'], 0) }}</p>
                </div>
            </div>
        </div>

        {{-- At Risk Contracts --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 {{ $systemMetrics['at_risk_count'] > 0 ? 'bg-red-500' : 'bg-gray-500' }} rounded-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">At Risk</p>
                    <p class="text-2xl font-semibold {{ $systemMetrics['at_risk_count'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">{{ $systemMetrics['at_risk_count'] }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Overall Utilization Bar --}}
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl p-6 mb-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-2 gap-2">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Overall Budget Utilization</h3>
            <div class="flex flex-wrap gap-4 text-sm">
                <span class="text-gray-600 dark:text-gray-400">
                    Remaining: <span class="font-semibold text-green-600">${{ number_format($systemMetrics['total_remaining'], 0) }}</span>
                </span>
                <span class="text-gray-600 dark:text-gray-400">
                    Critical: <span class="font-semibold text-red-600">{{ $systemMetrics['critical_count'] }}</span>
                </span>
                <span class="text-gray-600 dark:text-gray-400">
                    Warning: <span class="font-semibold text-yellow-600">{{ $systemMetrics['warning_count'] }}</span>
                </span>
                <span class="text-gray-600 dark:text-gray-400">
                    Depleted: <span class="font-semibold text-red-800">{{ $systemMetrics['depleted_count'] }}</span>
                </span>
            </div>
        </div>
        <div class="w-full bg-gray-200 dark:bg-zinc-700 rounded-full h-4">
            <div class="h-4 rounded-full transition-all duration-500
                {{ $systemMetrics['overall_utilization'] > 90 ? 'bg-red-500' : ($systemMetrics['overall_utilization'] > 75 ? 'bg-yellow-500' : 'bg-green-500') }}"
                 style="width: {{ min($systemMetrics['overall_utilization'], 100) }}%">
            </div>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $systemMetrics['overall_utilization'] }}% of total budget utilized</p>
    </div>

    {{-- Filters Panel --}}
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl p-4 mb-6" x-data="{ expanded: false }">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Filters</h2>
            <div class="flex gap-2">
                <button wire:click="resetFilters" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                    Reset Filters
                </button>
                <button @click="expanded = !expanded" class="text-sm text-indigo-600 dark:text-indigo-400">
                    <span x-show="!expanded">Show More</span>
                    <span x-show="expanded">Show Less</span>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            {{-- Search --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search</label>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Title, number, grant code..."
                       class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white text-sm">
            </div>

            {{-- Program Filter --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Program</label>
                <select wire:model.live="filterProgram" class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white text-sm">
                    <option value="">All Programs</option>
                    @foreach($programs as $program)
                        <option value="{{ $program->id }}">{{ $program->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Funder Filter --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Funder</label>
                <select wire:model.live="filterFunder" class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white text-sm">
                    <option value="">All Funders</option>
                    @foreach($funders as $funder)
                        <option value="{{ $funder->id }}">{{ $funder->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Status Filter --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                <select wire:model.live="filterStatus" class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white text-sm">
                    <option value="">Active (Default)</option>
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Utilization Level Filter --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Utilization Level</label>
                <select wire:model.live="filterUtilization" class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white text-sm">
                    <option value="">All Levels</option>
                    @foreach($utilizationOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Sort Options (Expandable) --}}
        <div x-show="expanded" x-collapse class="mt-4 pt-4 border-t border-gray-200 dark:border-zinc-700">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sort By</label>
                    <select wire:model.live="sortBy" class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white text-sm">
                        @foreach($sortOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Contract Cards Grid (when viewMode === 'cards') --}}
    @if($viewMode === 'cards')
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 mb-6">
            @forelse($contracts as $contract)
                @php
                    $utilization = $this->calculateUtilization($contract);
                    $status = $this->getUtilizationStatus($contract);
                    $colorClass = $this->getUtilizationColorClass($contract);
                    $spent = $this->getEffectiveSpent($contract);
                    $effectiveBudget = $this->getEffectiveBudget($contract);
                    $intacctBudget = (float) ($contract->intacct_budget_amount ?? 0);
                    $manualBudgeted = (float) ($contract->project_budgets_sum_budgeted_amount ?? 0);
                    // "Budgeted" always matches the utilization denominator so
                    // the card's math stays self-consistent. Source-badge tells
                    // the user where the figure came from.
                    $budgeted = $effectiveBudget;
                    if ($intacctBudget > 0) {
                        $budgetSource = 'intacct';
                    } elseif ($manualBudgeted > 0) {
                        $budgetSource = 'manual';
                        $budgeted = $manualBudgeted;
                    } elseif ((float) $contract->amount > 0) {
                        $budgetSource = 'awarded';
                    } else {
                        $budgetSource = 'none';
                    }
                    $remaining = $effectiveBudget - $spent;

                    $statusColors = [
                        'Depleted' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                        'Critical' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
                        'Warning' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                        'Caution' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                        'Healthy' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                        'No Budget' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
                    ];
                @endphp
                <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl p-6 hover:shadow-2xl transition-shadow">
                    {{-- Contract Header --}}
                    <div class="flex justify-between items-start mb-4">
                        <div class="flex-1 min-w-0">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white truncate" title="{{ $contract->title }}">
                                {{ Str::limit($contract->title, 35) }}
                            </h3>
                            <div class="flex flex-wrap gap-2 mt-1 items-center">
                                @if($contract->contract_number)
                                    <span class="text-xs text-gray-500 dark:text-gray-400">#{{ $contract->contract_number }}</span>
                                @endif
                                @if($contract->grant_code)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                        {{ $contract->grant_code }}@if($contract->service_code) / {{ $contract->service_code }}@endif
                                    </span>
                                @endif
                                @if(($contract->intacct_expenses_count ?? 0) > 0)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200"
                                          title="Intacct-synced{{ $contract->intacct_last_synced_at ? ' — last synced '.\Carbon\Carbon::parse($contract->intacct_last_synced_at)->diffForHumans() : '' }}">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                        Intacct
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700 dark:bg-zinc-700 dark:text-gray-300" title="Manual expense entries only (no Intacct sync in this window)">
                                        Manual
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            @if($status === 'Depleted')
                                <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                                </svg>
                            @elseif($status === 'Critical')
                                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            @elseif($status === 'Warning')
                                <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            @endif
                            <span class="px-2 py-1 rounded-full text-xs font-medium {{ $statusColors[$status] ?? $statusColors['No Budget'] }}">
                                {{ $status }}
                            </span>
                        </div>
                    </div>

                    {{-- Program & Funder --}}
                    <div class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        <p>{{ $contract->program?->name ?? 'No Program' }}</p>
                        <p class="text-xs">{{ $contract->funder?->name ?? 'No Funder' }}</p>
                    </div>

                    {{-- Budget Metrics --}}
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Awarded</p>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">${{ number_format($contract->amount, 0) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Budgeted
                                @if($budgetSource === 'intacct')
                                    <span class="ml-1 text-[10px] font-medium text-emerald-600 dark:text-emerald-400" title="Sourced from Intacct GLBUDGETITEM{{ $contract->intacct_budget_source ? ' ('.$contract->intacct_budget_source.')' : '' }}">
                                        Intacct
                                    </span>
                                @elseif($budgetSource === 'manual')
                                    <span class="ml-1 text-[10px] font-medium text-blue-600 dark:text-blue-400" title="Sum of manually entered project_budgets rows">
                                        Manual
                                    </span>
                                @elseif($budgetSource === 'awarded')
                                    <span class="ml-1 text-[10px] font-medium text-gray-500 dark:text-gray-400" title="No Intacct or manual budget — using the contract's awarded amount">
                                        = Awarded
                                    </span>
                                @endif
                            </p>
                            <p class="text-lg font-bold text-blue-600 dark:text-blue-400">${{ number_format($budgeted, 0) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Spent</p>
                            <p class="text-lg font-bold text-purple-600 dark:text-purple-400">${{ number_format($spent, 0) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Remaining</p>
                            <p class="text-lg font-bold {{ $remaining < 0 ? 'text-red-600' : 'text-green-600' }}">
                                ${{ number_format(abs($remaining), 0) }}{{ $remaining < 0 ? ' over' : '' }}
                            </p>
                        </div>
                    </div>

                    {{-- Utilization Progress Bar --}}
                    <div class="mb-4">
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600 dark:text-gray-400">Utilization</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ $utilization }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-zinc-700 rounded-full h-3">
                            <div class="h-3 rounded-full transition-all duration-500 {{ $colorClass }}"
                                 style="width: {{ min($utilization, 100) }}%">
                            </div>
                        </div>
                    </div>

                    {{-- Footer: sync info + View Transactions + View Details --}}
                    <div class="flex flex-wrap justify-between items-center gap-2 text-xs">
                        @if($contract->intacct_last_synced_at)
                            <span class="text-gray-500 dark:text-gray-400">
                                Intacct sync: {{ \Carbon\Carbon::parse($contract->intacct_last_synced_at)->diffForHumans() }}
                            </span>
                        @else
                            <span></span>
                        @endif
                        <div class="flex gap-3">
                            @if($spent > 0 || ($contract->intacct_expenses_count ?? 0) > 0)
                                <button wire:click="viewTransactions({{ $contract->id }})"
                                        class="text-emerald-700 hover:text-emerald-900 dark:text-emerald-400 dark:hover:text-emerald-300 text-sm font-medium">
                                    View Transactions
                                </button>
                            @endif
                            <button wire:click="viewContract({{ $contract->id }})"
                                    class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 text-sm font-medium">
                                View Details &rarr;
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No contracts found matching your filters</p>
                </div>
            @endforelse
        </div>
    @else
        {{-- Table View --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl overflow-hidden mb-6">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                    <thead class="bg-gray-50 dark:bg-zinc-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Contract</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Program / Funder</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Budget</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Spent</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Remaining</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Utilization</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                        @forelse($contracts as $contract)
                            @php
                                $utilization = $this->calculateUtilization($contract);
                                $status = $this->getUtilizationStatus($contract);
                                $colorClass = $this->getUtilizationColorClass($contract);
                                $spent = $this->getEffectiveSpent($contract);
                                $effectiveBudget = $this->getEffectiveBudget($contract);
                                $intacctBudget = (float) ($contract->intacct_budget_amount ?? 0);
                                $remaining = $effectiveBudget - $spent;

                                $statusColors = [
                                    'Depleted' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                    'Critical' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
                                    'Warning' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                    'Caution' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                    'Healthy' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                    'No Budget' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
                                ];
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-zinc-700">
                                <td class="px-4 py-3">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ Str::limit($contract->title, 30) }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $contract->grant_code ?? $contract->contract_number ?? 'N/A' }}
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-sm text-gray-900 dark:text-white">{{ $contract->program?->name ?? 'N/A' }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $contract->funder?->name ?? 'N/A' }}</div>
                                </td>
                                <td class="px-4 py-3 text-right text-sm font-medium text-gray-900 dark:text-white whitespace-nowrap">
                                    ${{ number_format($effectiveBudget, 0) }}
                                    @if($intacctBudget > 0 && abs($intacctBudget - (float)$contract->amount) > 0.01)
                                        <div class="text-[10px] text-emerald-600 dark:text-emerald-400" title="Sourced from Intacct; awarded amount on contract is ${{ number_format($contract->amount, 0) }}">
                                            Intacct
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right text-sm text-purple-600 dark:text-purple-400 whitespace-nowrap">
                                    ${{ number_format($spent, 0) }}
                                </td>
                                <td class="px-4 py-3 text-right text-sm font-medium whitespace-nowrap {{ $remaining < 0 ? 'text-red-600' : 'text-green-600' }}">
                                    @if($remaining < 0)
                                        (${{ number_format(abs($remaining), 0) }}) over
                                    @else
                                        ${{ number_format($remaining, 0) }}
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center">
                                        <div class="w-16 bg-gray-200 dark:bg-zinc-600 rounded-full h-2 mr-2">
                                            <div class="{{ $colorClass }} h-2 rounded-full" style="width: {{ min($utilization, 100) }}%"></div>
                                        </div>
                                        <span class="text-xs text-gray-600 dark:text-gray-300">{{ $utilization }}%</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium {{ $statusColors[$status] ?? $statusColors['No Budget'] }}">
                                        {{ $status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex justify-center items-center gap-2">
                                        @if($spent > 0 || ($contract->intacct_expenses_count ?? 0) > 0)
                                            <button wire:click="viewTransactions({{ $contract->id }})"
                                                    title="View transactions"
                                                    class="text-emerald-600 hover:text-emerald-800 dark:text-emerald-400">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                                </svg>
                                            </button>
                                        @endif
                                        <button wire:click="viewContract({{ $contract->id }})"
                                                title="View contract"
                                                class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    No contracts found matching your filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Pagination --}}
    <div class="mt-4">
        {{ $contracts->links() }}
    </div>

    {{-- Transactions drill-down modal --}}
    @if($showTransactionsModal && $transactionsPayload && $transactionsPayload['contract'])
        @php
            $tContract = $transactionsPayload['contract'];
            $tSource = $transactionsPayload['source'];
            $tLines = $transactionsPayload['lines'];
            $tTotal = $transactionsPayload['total'];
        @endphp
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4"
             wire:click.self="closeTransactionsModal">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-2xl w-full max-w-5xl max-h-[90vh] flex flex-col">
                {{-- Header --}}
                <div class="px-6 py-4 border-b border-gray-200 dark:border-zinc-700 flex justify-between items-start">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                            Transactions — {{ $tContract->title }}
                        </h2>
                        <div class="mt-1 flex flex-wrap gap-2 items-center text-sm">
                            @if($tContract->grant_code)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                    {{ $tContract->grant_code }}@if($tContract->service_code) / {{ $tContract->service_code }}@endif
                                </span>
                            @endif
                            @if($tSource === 'intacct')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                    Sourced from Intacct GL
                                </span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    Window: {{ $dateFrom }} &mdash; {{ $dateTo }}
                                </span>
                            @elseif($tSource === 'manual')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700 dark:bg-zinc-700 dark:text-gray-300">
                                    Manual entries (no Intacct sync)
                                </span>
                            @endif
                        </div>
                        @if(!empty($transactionsPayload['manual_also_present']))
                            <p class="mt-2 text-xs text-amber-700 dark:text-amber-300">
                                Note: this contract also has manual project_expenses rows. Because Intacct-synced data exists, those manual entries are <strong>excluded</strong> from the total shown here and on the card.
                            </p>
                        @endif
                    </div>
                    <button wire:click="closeTransactionsModal"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="flex-1 overflow-auto">
                    @if($tLines->isEmpty())
                        <div class="p-12 text-center">
                            <p class="text-gray-500 dark:text-gray-400">No transactions found in the selected window.</p>
                        </div>
                    @else
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                            <thead class="bg-gray-50 dark:bg-zinc-900/40 sticky top-0">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Date</th>
                                    @if($tSource === 'intacct')
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Account</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Description</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Document</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Intacct #</th>
                                    @else
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Category</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Description</th>
                                    @endif
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                                @foreach($tLines as $line)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-zinc-700/40">
                                        @if($tSource === 'intacct')
                                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-white whitespace-nowrap">
                                                {{ $line->posting_date?->format('M d, Y') ?? '—' }}
                                            </td>
                                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-white whitespace-nowrap">
                                                <span class="font-mono">{{ $line->gl_account }}</span>
                                                @if($line->gl_account_title)
                                                    <span class="text-xs text-gray-500 dark:text-gray-400 block">{{ $line->gl_account_title }}</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">
                                                {{ Str::limit($line->description, 80) ?: '—' }}
                                            </td>
                                            <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                                {{ $line->document_no ?? '—' }}
                                            </td>
                                            <td class="px-4 py-2 text-xs text-gray-500 dark:text-gray-400 font-mono whitespace-nowrap">
                                                {{ $line->intacct_record_no }}
                                            </td>
                                        @else
                                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-white whitespace-nowrap">
                                                {{ $line->expense_date?->format('M d, Y') ?? '—' }}
                                            </td>
                                            <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                                {{ $line->category ?? '—' }}
                                            </td>
                                            <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">
                                                {{ Str::limit($line->description, 80) ?: '—' }}
                                            </td>
                                        @endif
                                        <td class="px-4 py-2 text-right text-sm font-medium whitespace-nowrap {{ $line->amount < 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">
                                            ${{ number_format($line->amount, 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="px-6 py-3 border-t border-gray-200 dark:border-zinc-700 flex flex-wrap justify-between items-center gap-3 bg-gray-50 dark:bg-zinc-900/40 rounded-b-lg">
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        {{ $tLines->count() }} {{ Str::plural('transaction', $tLines->count()) }}
                    </div>
                    <div class="flex items-center gap-4">
                        @if($tLines->isNotEmpty())
                            <button wire:click="exportTransactions"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-emerald-600 text-emerald-700 dark:text-emerald-400 text-sm font-medium rounded-md hover:bg-emerald-50 dark:hover:bg-emerald-900/20">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4"/>
                                </svg>
                                Export CSV
                            </button>
                        @endif
                        <div class="text-base font-semibold text-gray-900 dark:text-white">
                            Total: ${{ number_format($tTotal, 2) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
