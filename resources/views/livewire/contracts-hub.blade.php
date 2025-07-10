<div class="max-w-7xl mx-auto p-6">
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl p-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-8">Contract Management</h1>
        
        <!-- Search Bar -->
        <div class="mb-6">
            <div class="relative">
                <input type="text" 
                       wire:model.live.debounce.300ms="search" 
                       class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white pl-10 pr-10 focus:border-indigo-500 focus:ring-indigo-500"
                       placeholder="Search by name, number, or funder...">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                @if($search)
                <button wire:click="$set('search', '')" 
                        class="absolute inset-y-0 right-0 pr-3 flex items-center">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
                @endif
            </div>
        </div>

        <!-- Tabs -->
        <div class="border-b border-gray-200 dark:border-zinc-600 mb-6">
            <nav class="-mb-px flex space-x-8">
                @if($this->can('contracts.view'))
                <button wire:click="setActiveTab('contracts')"
                        class="py-2 px-1 border-b-2 font-medium text-sm transition-colors
                               {{ $activeTab === 'contracts' 
                                  ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' 
                                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                    Contracts
                </button>
                @endif
                
                @if($this->can('funders.view'))
                <button wire:click="setActiveTab('funders')"
                        class="py-2 px-1 border-b-2 font-medium text-sm transition-colors
                               {{ $activeTab === 'funders' 
                                  ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' 
                                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                    Funders
                </button>
                @endif
                
                @if($this->can('due_dates.view'))
                <button wire:click="setActiveTab('duedates')"
                        class="py-2 px-1 border-b-2 font-medium text-sm transition-colors
                               {{ $activeTab === 'duedates' 
                                  ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' 
                                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                    Report Due Dates
                </button>
                @endif
            </nav>
        </div>

        <!-- Content -->
        <div>
            @if($activeTab === 'contracts' && $this->can('contracts.view'))
                <div class="space-y-4">
                    @forelse($contracts as $contract)
                    <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-4">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    <a href="{{ route('contract.show', $contract->id) }}" wire:navigate
                                       class="hover:text-indigo-600 dark:hover:text-indigo-400">
                                        {{ $contract->name }}
                                    </a>
                                </h3>
                                <div class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                    Contract #{{ $contract->contract_number }}
                                    @if($contract->funder)
                                    • Funder: {{ $contract->funder->name }}
                                    @endif
                                </div>
                                <div class="mt-2 flex items-center space-x-4 text-sm text-gray-500 dark:text-gray-400">
                                    <span>{{ $contract->programs->count() }} programs</span>
                                    @if($contract->start_date && $contract->end_date)
                                    <span>{{ $contract->start_date->format('M Y') }} - {{ $contract->end_date->format('M Y') }}</span>
                                    @endif
                                    @if($contract->total_amount)
                                    <span>${{ number_format($contract->total_amount, 2) }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $contract->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                    {{ ucfirst($contract->status) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    @empty
                    <p class="text-gray-500 dark:text-gray-400 text-center py-8">
                        No contracts found.
                    </p>
                    @endforelse
                    
                    @if($contracts->hasPages())
                    <div class="mt-4">
                        {{ $contracts->links() }}
                    </div>
                    @endif
                </div>
            @endif

            @if($activeTab === 'funders' && $this->can('funders.view'))
                <div class="space-y-4">
                    @forelse($funders as $funder)
                    <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-4">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    <a href="{{ route('funder.show', $funder->id) }}" wire:navigate
                                       class="hover:text-indigo-600 dark:hover:text-indigo-400">
                                        {{ $funder->name }}
                                    </a>
                                </h3>
                                <div class="mt-1 space-y-1">
                                    @if($funder->contact_name)
                                    <div class="text-sm text-gray-600 dark:text-gray-300">
                                        Contact: {{ $funder->contact_name }}
                                    </div>
                                    @endif
                                    @if($funder->email)
                                    <div class="text-sm text-gray-600 dark:text-gray-300">
                                        Email: {{ $funder->email }}
                                    </div>
                                    @endif
                                    @if($funder->phone)
                                    <div class="text-sm text-gray-600 dark:text-gray-300">
                                        Phone: {{ $funder->phone }}
                                    </div>
                                    @endif
                                </div>
                                <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $funder->contracts_count }} contract{{ $funder->contracts_count !== 1 ? 's' : '' }}
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $funder->active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                    {{ $funder->active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                        </div>
                    </div>
                    @empty
                    <p class="text-gray-500 dark:text-gray-400 text-center py-8">
                        No funders found.
                    </p>
                    @endforelse
                    
                    @if($funders->hasPages())
                    <div class="mt-4">
                        {{ $funders->links() }}
                    </div>
                    @endif
                </div>
            @endif

            @if($activeTab === 'duedates' && $this->can('due_dates.view'))
                <div class="space-y-4">
                    @forelse($dueDates as $dueDate)
                    <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-4">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    {{ $dueDate->name }}
                                </h3>
                                @if($dueDate->contract)
                                <div class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                    Contract: {{ $dueDate->contract->name }} (#{{ $dueDate->contract->contract_number }})
                                    @if($dueDate->contract->funder)
                                    • {{ $dueDate->contract->funder->name }}
                                    @endif
                                </div>
                                @endif
                                <div class="mt-2 flex items-center space-x-4 text-sm">
                                    <span class="text-gray-500 dark:text-gray-400">
                                        Due: {{ $dueDate->due_date->format('M d, Y') }}
                                    </span>
                                    @if($dueDate->frequency)
                                    <span class="text-gray-500 dark:text-gray-400">
                                        Frequency: {{ ucfirst($dueDate->frequency) }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                @php
                                    $daysUntilDue = now()->diffInDays($dueDate->due_date, false);
                                @endphp
                                @if($daysUntilDue < 0)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">
                                        Overdue
                                    </span>
                                @elseif($daysUntilDue <= 30)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300">
                                        Due Soon
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                        {{ $daysUntilDue }} days
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    @empty
                    <p class="text-gray-500 dark:text-gray-400 text-center py-8">
                        No due dates found.
                    </p>
                    @endforelse
                    
                    @if($dueDates->hasPages())
                    <div class="mt-4">
                        {{ $dueDates->links() }}
                    </div>
                    @endif
                </div>
            @endif
        </div>

        <!-- Quick Links -->
        <div class="mt-8 pt-8 border-t border-gray-200 dark:border-zinc-600">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Management Tools</h2>
            <div class="flex flex-wrap gap-4">
                @if($this->can('contracts.view'))
                <a href="{{ route('contract.management') }}" wire:navigate
                   class="inline-flex items-center px-4 py-2 bg-white dark:bg-zinc-700 border border-gray-300 dark:border-zinc-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-zinc-600">
                    Manage Contracts
                </a>
                @endif
                
                @if($this->can('funders.view'))
                <a href="{{ route('funder.management') }}" wire:navigate
                   class="inline-flex items-center px-4 py-2 bg-white dark:bg-zinc-700 border border-gray-300 dark:border-zinc-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-zinc-600">
                    Manage Funders
                </a>
                @endif
                
                @if($this->can('due_dates.view'))
                <a href="{{ route('contract-report-due-dates.management') }}" wire:navigate
                   class="inline-flex items-center px-4 py-2 bg-white dark:bg-zinc-700 border border-gray-300 dark:border-zinc-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-zinc-600">
                    Manage Due Dates
                </a>
                @endif
            </div>
        </div>
    </div>
</div>