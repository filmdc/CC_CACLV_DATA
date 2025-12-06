<div class="max-w-full mx-auto p-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Project Reporting</h1>
            <p class="text-gray-600 dark:text-gray-400">Comprehensive metrics and analytics for all projects</p>
        </div>
        <div class="flex gap-2">
            <select wire:model.live="viewMode" class="rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white text-sm">
                <option value="summary">Summary View</option>
                <option value="detailed">Detailed View</option>
            </select>
            <button wire:click="exportReport" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:outline-none transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Export CSV
            </button>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if (session()->has('message'))
        <div class="mb-4 rounded-md bg-green-50 dark:bg-green-900 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('message') }}</p>
                </div>
            </div>
        </div>
    @endif

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

        {{-- Primary Filters (Always Visible) --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 gap-4 mb-4">
            {{-- Search --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search</label>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Job #, title, participant..."
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

            {{-- Status Filter --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                <select wire:model.live="filterStatus" class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white text-sm">
                    <option value="">All Statuses</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}">{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Priority Filter --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Priority</label>
                <select wire:model.live="filterPriority" class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white text-sm">
                    <option value="">All Priorities</option>
                    @foreach($priorities as $priority)
                        <option value="{{ $priority }}">{{ ucfirst($priority) }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Contractor Filter --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Contractor</label>
                <select wire:model.live="filterContractor" class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white text-sm">
                    <option value="">All Contractors</option>
                    @foreach($contractors as $contractor)
                        <option value="{{ $contractor->id }}">{{ $contractor->company_name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Budget Status Filter --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Budget Status</label>
                <select wire:model.live="filterBudgetStatus" class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white text-sm">
                    <option value="">All Budget Statuses</option>
                    @foreach($budgetStatuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Advanced Filters (Expandable) --}}
        <div x-show="expanded" x-collapse class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 gap-4 pt-4 border-t border-gray-200 dark:border-zinc-700">
            {{-- Property Filter --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Property</label>
                <select wire:model.live="filterProperty" class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white text-sm">
                    <option value="">All Properties</option>
                    @foreach($properties as $property)
                        <option value="{{ $property->id }}">{{ $property->address_line_1 }}, {{ $property->city }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Participant Filter --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Participant</label>
                <select wire:model.live="filterParticipant" class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white text-sm">
                    <option value="">All Participants</option>
                    @foreach($participants as $participant)
                        <option value="{{ $participant->id }}">{{ $participant->last_name }}, {{ $participant->first_name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Project Manager Filter --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Project Manager</label>
                <select wire:model.live="filterProjectManager" class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white text-sm">
                    <option value="">All Managers</option>
                    @foreach($projectManagers as $manager)
                        <option value="{{ $manager->id }}">{{ $manager->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Milestone Status --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Milestone Status</label>
                <select wire:model.live="filterMilestoneStatus" class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white text-sm">
                    <option value="">All Milestone Statuses</option>
                    @foreach($milestoneStatuses as $status)
                        <option value="{{ $status }}">{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Task Status --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Task Status</label>
                <select wire:model.live="filterTaskStatus" class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white text-sm">
                    <option value="">All Task Statuses</option>
                    @foreach($taskStatuses as $status)
                        <option value="{{ $status }}">{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Document Status --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Document Status</label>
                <select wire:model.live="filterDocumentStatus" class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white text-sm">
                    <option value="">All Document Statuses</option>
                    @foreach($documentStatuses as $status)
                        <option value="{{ $status }}">{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Date Range Type --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date Field</label>
                <select wire:model.live="dateRangeType" class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white text-sm">
                    <option value="">Select Date Field</option>
                    @foreach($dateRangeTypes as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Date From --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">From Date</label>
                <input type="date" wire:model.live="dateFrom" class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white text-sm">
            </div>

            {{-- Date To --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">To Date</label>
                <input type="date" wire:model.live="dateTo" class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white text-sm">
            </div>

            {{-- Group By --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Group By</label>
                <select wire:model.live="groupBy" class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white text-sm">
                    @foreach($groupByOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- Summary View --}}
    @if($viewMode === 'summary')
        {{-- Key Metrics Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            {{-- Total Projects --}}
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0 p-3 bg-indigo-500 rounded-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Projects</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($summaryMetrics['total_projects']) }}</p>
                    </div>
                </div>
            </div>

            {{-- Overdue Projects --}}
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0 p-3 bg-red-500 rounded-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Overdue</p>
                        <p class="text-2xl font-semibold text-red-600 dark:text-red-400">{{ $summaryMetrics['overdue_count'] }}</p>
                    </div>
                </div>
            </div>

            {{-- At Risk Projects --}}
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0 p-3 bg-yellow-500 rounded-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">At Risk (7 days)</p>
                        <p class="text-2xl font-semibold text-yellow-600 dark:text-yellow-400">{{ $summaryMetrics['at_risk_count'] }}</p>
                    </div>
                </div>
            </div>

            {{-- Completed This Month --}}
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0 p-3 bg-green-500 rounded-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Completed (Month)</p>
                        <p class="text-2xl font-semibold text-green-600 dark:text-green-400">{{ $summaryMetrics['completed_this_month'] }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Budget and Timeline Row --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            {{-- Budget Overview --}}
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Budget Overview</h3>
                <div class="space-y-4">
                    <div class="grid grid-cols-3 gap-4 text-center">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Total Budget</p>
                            <p class="text-xl font-bold text-gray-900 dark:text-white">${{ number_format($summaryMetrics['total_budget'], 2) }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Total Spent</p>
                            <p class="text-xl font-bold text-blue-600 dark:text-blue-400">${{ number_format($summaryMetrics['total_spent'], 2) }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Remaining</p>
                            <p class="text-xl font-bold {{ $summaryMetrics['budget_remaining'] < 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                ${{ number_format(abs($summaryMetrics['budget_remaining']), 2) }}
                            </p>
                        </div>
                    </div>

                    {{-- Budget Progress Bar --}}
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600 dark:text-gray-400">Budget Utilization</span>
                            <span class="font-medium {{ $summaryMetrics['budget_utilization'] > 100 ? 'text-red-600' : ($summaryMetrics['budget_utilization'] > 90 ? 'text-yellow-600' : 'text-green-600') }}">
                                {{ $summaryMetrics['budget_utilization'] }}%
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-zinc-700 rounded-full h-3">
                            <div class="h-3 rounded-full transition-all duration-500
                                {{ $summaryMetrics['budget_utilization'] > 100 ? 'bg-red-500' : ($summaryMetrics['budget_utilization'] > 90 ? 'bg-yellow-500' : 'bg-green-500') }}"
                                 style="width: {{ min($summaryMetrics['budget_utilization'], 100) }}%">
                            </div>
                        </div>
                    </div>

                    {{-- Budget by Category --}}
                    @if(count($budgetByCategory) > 0)
                        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-zinc-700">
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">By Category</h4>
                            <div class="space-y-2 max-h-40 overflow-y-auto">
                                @foreach($budgetByCategory as $category)
                                    <div class="flex justify-between items-center text-sm">
                                        <span class="text-gray-600 dark:text-gray-400">{{ $category['category'] }}</span>
                                        <span class="font-medium {{ $category['utilization'] > 100 ? 'text-red-600' : 'text-gray-900 dark:text-white' }}">
                                            ${{ number_format($category['total_spent'], 2) }} / ${{ number_format($category['total_budgeted'], 2) }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Status Distribution --}}
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Status Distribution</h3>
                <div class="space-y-3">
                    @php
                        $statusColors = [
                            'pending' => 'bg-yellow-500',
                            'approved' => 'bg-blue-500',
                            'in_progress' => 'bg-indigo-500',
                            'on_hold' => 'bg-orange-500',
                            'completed' => 'bg-green-500',
                            'cancelled' => 'bg-red-500',
                            'rejected' => 'bg-gray-500',
                        ];
                        $total = $summaryMetrics['total_projects'] ?: 1;
                    @endphp
                    @foreach($statuses as $status)
                        @php
                            $count = $summaryMetrics['status_counts'][$status] ?? 0;
                            $percentage = round(($count / $total) * 100, 1);
                        @endphp
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-600 dark:text-gray-400 capitalize">{{ str_replace('_', ' ', $status) }}</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ $count }} ({{ $percentage }}%)</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-zinc-700 rounded-full h-2">
                                <div class="{{ $statusColors[$status] ?? 'bg-gray-500' }} h-2 rounded-full transition-all duration-500"
                                     style="width: {{ $percentage }}%">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Progress Metrics Row --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            {{-- Milestone Progress --}}
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Milestones</h3>
                <div class="text-center mb-4">
                    <div class="inline-flex items-center justify-center w-24 h-24 rounded-full border-8 border-indigo-200 dark:border-indigo-900">
                        <span class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ $milestoneMetrics['completion_rate'] }}%</span>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Completion Rate</p>
                </div>
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <div class="text-center p-2 bg-gray-50 dark:bg-zinc-700 rounded">
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $milestoneMetrics['total'] }}</p>
                        <p class="text-gray-500 dark:text-gray-400">Total</p>
                    </div>
                    <div class="text-center p-2 bg-green-50 dark:bg-green-900/30 rounded">
                        <p class="font-semibold text-green-600 dark:text-green-400">{{ $milestoneMetrics['completed'] }}</p>
                        <p class="text-gray-500 dark:text-gray-400">Completed</p>
                    </div>
                    <div class="text-center p-2 bg-blue-50 dark:bg-blue-900/30 rounded">
                        <p class="font-semibold text-blue-600 dark:text-blue-400">{{ $milestoneMetrics['in_progress'] }}</p>
                        <p class="text-gray-500 dark:text-gray-400">In Progress</p>
                    </div>
                    <div class="text-center p-2 bg-red-50 dark:bg-red-900/30 rounded">
                        <p class="font-semibold text-red-600 dark:text-red-400">{{ $milestoneMetrics['overdue'] }}</p>
                        <p class="text-gray-500 dark:text-gray-400">Overdue</p>
                    </div>
                </div>
            </div>

            {{-- Task Progress --}}
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Tasks</h3>
                <div class="text-center mb-4">
                    <div class="inline-flex items-center justify-center w-24 h-24 rounded-full border-8 border-green-200 dark:border-green-900">
                        <span class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $taskMetrics['completion_rate'] }}%</span>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Completion Rate</p>
                </div>
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <div class="text-center p-2 bg-gray-50 dark:bg-zinc-700 rounded">
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $taskMetrics['total'] }}</p>
                        <p class="text-gray-500 dark:text-gray-400">Total</p>
                    </div>
                    <div class="text-center p-2 bg-green-50 dark:bg-green-900/30 rounded">
                        <p class="font-semibold text-green-600 dark:text-green-400">{{ $taskMetrics['completed'] }}</p>
                        <p class="text-gray-500 dark:text-gray-400">Completed</p>
                    </div>
                    <div class="text-center p-2 bg-yellow-50 dark:bg-yellow-900/30 rounded">
                        <p class="font-semibold text-yellow-600 dark:text-yellow-400">{{ $taskMetrics['pending'] }}</p>
                        <p class="text-gray-500 dark:text-gray-400">Pending</p>
                    </div>
                    <div class="text-center p-2 bg-red-50 dark:bg-red-900/30 rounded">
                        <p class="font-semibold text-red-600 dark:text-red-400">{{ $taskMetrics['overdue'] }}</p>
                        <p class="text-gray-500 dark:text-gray-400">Overdue</p>
                    </div>
                </div>
            </div>

            {{-- Document Progress --}}
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Documents</h3>
                <div class="text-center mb-4">
                    <div class="inline-flex items-center justify-center w-24 h-24 rounded-full border-8 border-purple-200 dark:border-purple-900">
                        <span class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $documentMetrics['upload_rate'] }}%</span>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Upload Rate</p>
                </div>
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <div class="text-center p-2 bg-gray-50 dark:bg-zinc-700 rounded">
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $documentMetrics['total'] }}</p>
                        <p class="text-gray-500 dark:text-gray-400">Total</p>
                    </div>
                    <div class="text-center p-2 bg-green-50 dark:bg-green-900/30 rounded">
                        <p class="font-semibold text-green-600 dark:text-green-400">{{ $documentMetrics['approved'] }}</p>
                        <p class="text-gray-500 dark:text-gray-400">Approved</p>
                    </div>
                    <div class="text-center p-2 bg-yellow-50 dark:bg-yellow-900/30 rounded">
                        <p class="font-semibold text-yellow-600 dark:text-yellow-400">{{ $documentMetrics['pending_review'] }}</p>
                        <p class="text-gray-500 dark:text-gray-400">Pending</p>
                    </div>
                    <div class="text-center p-2 bg-red-50 dark:bg-red-900/30 rounded">
                        <p class="font-semibold text-red-600 dark:text-red-400">{{ $documentMetrics['rejected'] }}</p>
                        <p class="text-gray-500 dark:text-gray-400">Rejected</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Performance Tables Row --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            {{-- Contractor Performance --}}
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Contractor Performance</h3>
                @if(count($contractorPerformance) > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                            <thead>
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Contractor</th>
                                    <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Projects</th>
                                    <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Completed</th>
                                    <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Avg Days</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                                @foreach($contractorPerformance as $contractor)
                                    <tr>
                                        <td class="px-3 py-2 text-sm text-gray-900 dark:text-white">{{ $contractor['contractor_name'] }}</td>
                                        <td class="px-3 py-2 text-sm text-center text-gray-600 dark:text-gray-300">{{ $contractor['total_projects'] }}</td>
                                        <td class="px-3 py-2 text-sm text-center">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                {{ $contractor['completion_rate'] }}%
                                            </span>
                                        </td>
                                        <td class="px-3 py-2 text-sm text-center text-gray-600 dark:text-gray-300">{{ $contractor['avg_completion_days'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-gray-500 dark:text-gray-400 text-center py-4">No contractor data available</p>
                @endif
            </div>

            {{-- Project Manager Performance --}}
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Project Manager Performance</h3>
                @if(count($projectManagerPerformance) > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                            <thead>
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Manager</th>
                                    <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Projects</th>
                                    <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Completed</th>
                                    <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Overdue</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                                @foreach($projectManagerPerformance as $manager)
                                    <tr>
                                        <td class="px-3 py-2 text-sm text-gray-900 dark:text-white">{{ $manager['manager_name'] }}</td>
                                        <td class="px-3 py-2 text-sm text-center text-gray-600 dark:text-gray-300">{{ $manager['total_projects'] }}</td>
                                        <td class="px-3 py-2 text-sm text-center">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                {{ $manager['completion_rate'] }}%
                                            </span>
                                        </td>
                                        <td class="px-3 py-2 text-sm text-center">
                                            @if($manager['overdue_projects'] > 0)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                                    {{ $manager['overdue_projects'] }}
                                                </span>
                                            @else
                                                <span class="text-gray-400">0</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-gray-500 dark:text-gray-400 text-center py-4">No project manager data available</p>
                @endif
            </div>
        </div>

        {{-- Grouped Metrics --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl p-6 mb-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Projects by {{ ucfirst(str_replace('_', ' ', $groupBy)) }}</h3>
            </div>
            @if(count($groupedMetrics) > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ ucfirst(str_replace('_', ' ', $groupBy)) }}</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Completed</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Rate</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Est. Cost</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actual Cost</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                            @foreach($groupedMetrics as $metric)
                                <tr class="hover:bg-gray-50 dark:hover:bg-zinc-700">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ $metric['label'] }}</td>
                                    <td class="px-4 py-3 text-sm text-center text-gray-600 dark:text-gray-300">{{ $metric['total_count'] }}</td>
                                    <td class="px-4 py-3 text-sm text-center text-gray-600 dark:text-gray-300">{{ $metric['completed_count'] }}</td>
                                    <td class="px-4 py-3 text-sm text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            {{ $metric['completion_rate'] >= 75 ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' :
                                               ($metric['completion_rate'] >= 50 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' :
                                               'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200') }}">
                                            {{ $metric['completion_rate'] }}%
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-600 dark:text-gray-300">${{ number_format($metric['total_estimated'] ?? 0, 2) }}</td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-600 dark:text-gray-300">${{ number_format($metric['total_actual'] ?? 0, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-gray-500 dark:text-gray-400 text-center py-4">No grouped data available</p>
            @endif
        </div>
    @endif

    {{-- Detailed View / Project List --}}
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                Project List
                <span class="text-sm font-normal text-gray-500 dark:text-gray-400">({{ $projects->total() }} projects)</span>
            </h3>
            <div class="text-sm text-gray-500 dark:text-gray-400">
                Avg. Completion Time: <span class="font-medium text-gray-900 dark:text-white">{{ $summaryMetrics['avg_completion_days'] }} days</span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                <thead class="bg-gray-50 dark:bg-zinc-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Project</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Participant</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Property</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Contractor</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Progress</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Budget</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Target</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-700">
                    @forelse($projects as $project)
                        @php
                            $totalMilestones = $project->milestones->count();
                            $completedMilestones = $project->milestones->where('status', 'completed')->count();
                            $progress = $totalMilestones > 0 ? round(($completedMilestones / $totalMilestones) * 100) : 0;
                            $isOverdue = $project->target_completion_date && $project->target_completion_date->isPast() && !in_array($project->status, ['completed', 'cancelled', 'rejected']);
                            $budgetStatus = $project->getBudgetStatus();
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-zinc-700 {{ $isOverdue ? 'bg-red-50 dark:bg-red-900/20' : '' }}">
                            <td class="px-4 py-3">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $project->job_title }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $project->job_number }}</div>
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $statusBadgeColors = [
                                        'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                        'approved' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                        'in_progress' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200',
                                        'on_hold' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
                                        'completed' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                        'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                        'rejected' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
                                    ];
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusBadgeColors[$project->status] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                                </span>
                                @if($isOverdue)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 ml-1">
                                        Overdue
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                @if($project->participant)
                                    {{ $project->participant->first_name }} {{ $project->participant->last_name }}
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                @if($project->property)
                                    <span title="{{ $project->property->address_line_1 }}, {{ $project->property->city }}">
                                        {{ Str::limit($project->property->address_line_1, 20) }}
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                @if($project->contractor)
                                    {{ Str::limit($project->contractor->company_name, 15) }}
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center">
                                    <div class="w-16 bg-gray-200 dark:bg-zinc-600 rounded-full h-2 mr-2">
                                        <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ $progress }}%"></div>
                                    </div>
                                    <span class="text-xs text-gray-600 dark:text-gray-300">{{ $progress }}%</span>
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    {{ $completedMilestones }}/{{ $totalMilestones }} milestones
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right">
                                @php
                                    $totalBudget = $project->getTotalBudget();
                                    $totalSpent = $project->getTotalSpent();
                                @endphp
                                <div class="text-sm font-medium text-gray-900 dark:text-white">${{ number_format($totalSpent, 0) }}</div>
                                <div class="text-xs {{ $budgetStatus === 'over_budget' ? 'text-red-600' : ($budgetStatus === 'at_risk' ? 'text-yellow-600' : 'text-gray-500 dark:text-gray-400') }}">
                                    / ${{ number_format($totalBudget, 0) }}
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center text-sm">
                                @if($project->target_completion_date)
                                    <span class="{{ $isOverdue ? 'text-red-600 dark:text-red-400 font-medium' : 'text-gray-600 dark:text-gray-300' }}">
                                        {{ $project->target_completion_date->format('M d, Y') }}
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <a href="{{ route('project.management', $project->id) }}"
                                   class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                    <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                No projects found matching your filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="mt-4">
            {{ $projects->links() }}
        </div>
    </div>
</div>
