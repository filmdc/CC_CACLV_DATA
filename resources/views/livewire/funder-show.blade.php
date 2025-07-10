<div class="max-w-7xl mx-auto p-6">
    <div class="bg-white dark:bg-zinc-800 shadow-lg rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">{{ $funder->name }}</h1>
                    <p class="text-blue-100 mt-2">Funder Details</p>
                </div>
                <div class="flex space-x-3">
                    @if ($this->can('funders.update'))
                        <button wire:click="editFunder" 
                                class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                            Edit Funder
                        </button>
                    @endif
                    <a href="{{ route('funder.management') }}" 
                       class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                        Back to List
                    </a>
                </div>
            </div>
        </div>

        <div class="p-6">
            <!-- Basic Information -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-zinc-700 pb-2">
                    Basic Information
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Funder Name</label>
                        <p class="text-gray-900 dark:text-white">{{ $funder->name }}</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Funder Code</label>
                        <p class="text-gray-900 dark:text-white">{{ $funder->code ?: 'Not specified' }}</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Status</label>
                        <p>
                            @if($funder->active)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                    Active
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100">
                                    Inactive
                                </span>
                            @endif
                        </p>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Description</label>
                        <p class="text-gray-900 dark:text-white">{{ $funder->description ?: 'No description provided' }}</p>
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-zinc-700 pb-2">
                    Contact Information
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Contact Person</label>
                        <p class="text-gray-900 dark:text-white">{{ $funder->contact_person ?: 'Not specified' }}</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Contact Email</label>
                        <p class="text-gray-900 dark:text-white">
                            @if($funder->contact_email)
                                <a href="mailto:{{ $funder->contact_email }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                    {{ $funder->contact_email }}
                                </a>
                            @else
                                Not specified
                            @endif
                        </p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Contact Phone</label>
                        <p class="text-gray-900 dark:text-white">{{ $funder->contact_phone ?: 'Not specified' }}</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Website</label>
                        <p class="text-gray-900 dark:text-white">
                            @if($funder->website)
                                <a href="{{ $funder->website }}" target="_blank" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                    {{ $funder->website }}
                                </a>
                            @else
                                Not specified
                            @endif
                        </p>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Address</label>
                        <p class="text-gray-900 dark:text-white whitespace-pre-line">{{ $funder->address ?: 'Not specified' }}</p>
                    </div>
                </div>
            </div>

            <!-- Contracts -->
            <div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-zinc-700 pb-2">
                    Contracts ({{ $funder->contracts->count() }})
                </h2>
                @if($funder->contracts->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Contract Name
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Programs
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Start Date
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        End Date
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Status
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                                @foreach($funder->contracts as $contract)
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                            <a href="{{ route('contract.show', $contract->id) }}" 
                                               class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                                {{ $contract->name }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                            {{ $contract->programs->pluck('name')->join(', ') ?: 'No programs' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                            {{ $contract->start_date ? \Carbon\Carbon::parse($contract->start_date)->format('M j, Y') : 'N/A' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                            {{ $contract->end_date ? \Carbon\Carbon::parse($contract->end_date)->format('M j, Y') : 'N/A' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            @if($contract->status === 'active')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                                    Active
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100">
                                                    {{ ucfirst($contract->status) }}
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-gray-500 dark:text-gray-400">No contracts associated with this funder.</p>
                @endif
            </div>
        </div>
    </div>
</div>