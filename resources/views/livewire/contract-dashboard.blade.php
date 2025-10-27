<div>
    {{-- Contract Dashboard Header --}}
    <div class="w-full px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Contract Dashboard</h1>
            
            {{-- Date Filter --}}
            <div class="flex items-center space-x-4">
                <div class="relative">
                    <select wire:model.live="dateFilter" 
                            class="rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="this_month">This Month</option>
                        <option value="last_month">Last Month</option>
                        <option value="this_quarter">This Quarter</option>
                        <option value="this_year">This Year</option>
                        <option value="custom">Custom Range</option>
                    </select>
                </div>
                
                @if($dateFilter === 'custom')
                    <input type="date" wire:model.live="startDate" 
                           class="rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                    <span class="text-gray-500">to</span>
                    <input type="date" wire:model.live="endDate" 
                           class="rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                @endif
            </div>
        </div>

        {{-- Metrics Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            {{-- Upcoming Reports --}}
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Upcoming Reports</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $contractMetrics['total_upcoming_reports'] }}</p>
                    </div>
                    <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-full">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Overdue Reports --}}
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Overdue Reports</p>
                        <p class="text-2xl font-bold {{ $contractMetrics['overdue_reports'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">
                            {{ $contractMetrics['overdue_reports'] }}
                        </p>
                    </div>
                    <div class="p-3 {{ $contractMetrics['overdue_reports'] > 0 ? 'bg-red-100 dark:bg-red-900' : 'bg-gray-100 dark:bg-gray-900' }} rounded-full">
                        <svg class="w-6 h-6 {{ $contractMetrics['overdue_reports'] > 0 ? 'text-red-600 dark:text-red-300' : 'text-gray-600 dark:text-gray-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Due This Week --}}
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Due This Week</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $contractMetrics['reports_due_this_week'] }}</p>
                    </div>
                    <div class="p-3 bg-yellow-100 dark:bg-yellow-900 rounded-full">
                        <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Submitted This Period --}}
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Submitted This Period</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $contractMetrics['reports_submitted_this_period'] }}</p>
                    </div>
                    <div class="p-3 bg-green-100 dark:bg-green-900 rounded-full">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- Main Content Grid --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Left Column: Recent Activity & Stats --}}
            <div class="lg:col-span-1 space-y-6">
                {{-- Recent Contract Activity --}}
                <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Recent Activity</h2>
                    <div class="space-y-3">
                        @forelse($recentContractActivity as $activity)
                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0">
                                    @if($activity['type'] === 'report_submitted')
                                        <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                                            <svg class="w-4 h-4 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                            </svg>
                                        </div>
                                    @else
                                        <div class="w-8 h-8 bg-gray-100 dark:bg-gray-900 rounded-full flex items-center justify-center">
                                            <svg class="w-4 h-4 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-gray-900 dark:text-white">{{ $activity['description'] }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::parse($activity['timestamp'])->diffForHumans() }}</p>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 dark:text-gray-400">No recent activity</p>
                        @endforelse
                    </div>
                </div>

                {{-- Report Statistics --}}
                <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Report Statistics</h2>
                    <div class="space-y-4">
                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Completion Rate</span>
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $contractStats['completion_rate'] }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $contractStats['completion_rate'] }}%"></div>
                            </div>
                        </div>

                        <div class="pt-2">
                            <p class="text-sm font-medium text-gray-900 dark:text-white mb-2">Reports by Status</p>
                            @foreach(\App\Models\ContractReport::STATUS_OPTIONS as $status => $label)
                                @if(isset($contractStats['reports_by_status'][$status]))
                                    <div class="flex justify-between items-center py-1">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ $label }}</span>
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $contractStats['reports_by_status'][$status] ?? 0 }}</span>
                                    </div>
                                @endif
                            @endforeach
                        </div>

                        <div class="pt-2 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Total Contracts Assigned</span>
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $contractStats['total_contracts'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right Column: Upcoming & Overdue Reports --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Overdue Reports (if any) --}}
                @if($overdueReportDueDates->count() > 0)
                    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold text-red-900 dark:text-red-300 mb-4">Overdue Reports</h2>
                        <div class="space-y-3">
                            @foreach($overdueReportDueDates as $dueDate)
                                <div class="bg-white dark:bg-zinc-800 rounded-lg p-4 border border-red-200 dark:border-red-800">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h3 class="font-medium text-gray-900 dark:text-white">{{ $dueDate->report_name }}</h3>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $dueDate->contract->title }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                                {{ $dueDate->contract->program->name ?? 'No Program' }} • 
                                                {{ $dueDate->contract->funder->name ?? 'No Funder' }}
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-semibold text-red-600 dark:text-red-400">
                                                {{ abs($dueDate->days_until_due) }} days overdue
                                            </p>
                                            <p class="text-xs text-gray-500">Due {{ $dueDate->due_date->format('M d, Y') }}</p>
                                            <button wire:click="viewReportDueDate({{ $dueDate->id }})" 
                                               class="inline-flex items-center px-3 py-1 mt-2 text-xs font-medium text-red-700 bg-red-100 dark:bg-red-800 dark:text-red-200 rounded-md hover:bg-red-200 dark:hover:bg-red-700">
                                                Submit Report
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Upcoming Report Due Dates --}}
                <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Upcoming Report Due Dates</h2>
                    <div class="space-y-3">
                        @forelse($upcomingReportDueDates as $dueDate)
                            <div class="border border-gray-200 dark:border-zinc-700 rounded-lg p-4 hover:border-blue-300 dark:hover:border-blue-700 transition-colors">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="font-medium text-gray-900 dark:text-white">{{ $dueDate->report_name }}</h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $dueDate->contract->title }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                            {{ $dueDate->contract->program->name ?? 'No Program' }} • 
                                            {{ $dueDate->contract->funder->name ?? 'No Funder' }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">{{ $dueDate->description }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-semibold {{ $dueDate->days_until_due <= 7 ? 'text-orange-600 dark:text-orange-400' : 'text-gray-900 dark:text-white' }}">
                                            {{ $dueDate->days_until_due }} days
                                        </p>
                                        <p class="text-xs text-gray-500">Due {{ $dueDate->due_date->format('M d, Y') }}</p>
                                        <button wire:click="viewReportDueDate({{ $dueDate->id }})" 
                                           class="inline-flex items-center px-3 py-1 mt-2 text-xs font-medium text-blue-700 bg-blue-100 dark:bg-blue-800 dark:text-blue-200 rounded-md hover:bg-blue-200 dark:hover:bg-blue-700">
                                            View Details
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 dark:text-gray-400">No upcoming report due dates</p>
                        @endforelse
                    </div>
                </div>

                {{-- Recently Submitted Reports --}}
                <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Recently Submitted Reports</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Report</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Contract</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Submitted</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                                @forelse($recentlySubmittedReports as $report)
                                    <tr>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $report->title }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $report->contractReportDueDate->report_type ?? 'N/A' }}</div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="text-sm text-gray-900 dark:text-white">{{ Str::limit($report->contract->title, 30) }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $report->contract->contract_number }}</div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ $report->submitted_date->format('M d, Y') }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                                @if($report->status === 'approved') bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100
                                                @elseif($report->status === 'submitted') bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100
                                                @elseif($report->status === 'under_review') bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100
                                                @else bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100
                                                @endif">
                                                {{ \App\Models\ContractReport::STATUS_OPTIONS[$report->status] ?? $report->status }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm">
                                            <button wire:click="viewReport({{ $report->id }})" 
                                               class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300">
                                                View
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-3 text-center text-sm text-gray-500 dark:text-gray-400">
                                            No reports submitted in this period
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>