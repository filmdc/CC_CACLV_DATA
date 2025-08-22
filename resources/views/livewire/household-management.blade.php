<div class="max-w-7xl mx-auto p-6">
    <div class="bg-white dark:bg-zinc-800 shadow-lg rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-green-600 text-white p-6">
            <h1 class="text-2xl font-bold">Household Management</h1>
            <p class="text-green-100 mt-2">Manage household composition and family relationships</p>
        </div>

        <!-- Search and Filters -->
        <div class="p-4 bg-gray-50 dark:bg-zinc-700 border-b border-gray-200 dark:border-zinc-600">
            <div class="flex items-center justify-between">
                <div class="flex-1 max-w-md">
                    <input type="text" wire:model.live="search" placeholder="Search by family name or household code..." 
                           class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        @if (session()->has('success'))
            <div class="p-4 bg-green-50 dark:bg-green-900/20 border-l-4 border-green-400">
                <div class="flex">
                    <div class="ml-3">
                        <p class="text-sm text-green-700 dark:text-green-300">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="p-4 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-400">
                <div class="flex">
                    <div class="ml-3">
                        <p class="text-sm text-red-700 dark:text-red-300">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Households Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-600">
                <thead class="bg-gray-50 dark:bg-zinc-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Family Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Members</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Program Enrollments</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Service Enrollments</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Housing Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Income/Poverty</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-600">
                    @forelse($households as $household)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    <button wire:click="viewHousehold({{ $household->id }})" 
                                            class="text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 hover:underline cursor-pointer transition-colors duration-200">
                                        <div class="text-sm font-medium">
                                            {{ $household->headOfHousehold?->last_name ?? 'Unknown' }} Family
                                        </div>
                                    </button>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        Code: {{ $household->household_code }}
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        {{ $household->household_size }} {{ Str::plural('member', $household->household_size) }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $household->household_type }}
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $activeProgramEnrollments = $household->participants->flatMap->programEnrollments->where('status', 'active');
                                    $programCount = $activeProgramEnrollments->pluck('program.name')->unique()->count();
                                @endphp
                                <div class="text-sm">
                                    @if($programCount > 0)
                                        <span class="text-green-600 dark:text-green-400 font-medium">{{ $programCount }} {{ Str::plural('program', $programCount) }}</span>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $activeProgramEnrollments->pluck('program.name')->unique()->take(2)->join(', ') }}
                                            @if($programCount > 2)
                                                ...
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500">None</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $activeServiceEnrollments = $household->participants->flatMap->serviceEnrollments->whereIn('status', ['enrolled', 'in_progress']);
                                    $serviceCount = $activeServiceEnrollments->pluck('service.name')->unique()->count();
                                @endphp
                                <div class="text-sm">
                                    @if($serviceCount > 0)
                                        <span class="text-blue-600 dark:text-blue-400 font-medium">{{ $serviceCount }} {{ Str::plural('service', $serviceCount) }}</span>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $activeServiceEnrollments->pluck('service.name')->unique()->take(2)->join(', ') }}
                                            @if($serviceCount > 2)
                                                ...
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500">None</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                {{ $household->housing_status ?? 'Not specified' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm">
                                    @if($household->monthly_income)
                                        <div class="text-gray-900 dark:text-white">${{ number_format($household->monthly_income, 0) }}/mo</div>
                                    @endif
                                    @if($household->hhs_poverty_percentage)
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $household->hhs_poverty_percentage }}% FPL</div>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500">Not calculated</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <button wire:click="manageHousehold({{ $household->id }})" 
                                            wire:loading.attr="disabled"
                                            wire:loading.class="opacity-50 cursor-wait"
                                            wire:target="manageHousehold({{ $household->id }})"
                                            class="px-3 py-1 bg-green-100 dark:bg-green-900/20 text-green-700 dark:text-green-300 text-sm rounded hover:bg-green-200 dark:hover:bg-green-900/40 focus:outline-none focus:ring-2 focus:ring-green-500 font-medium transition-all">
                                        <span wire:loading.class="hidden" wire:target="manageHousehold({{ $household->id }})">Manage</span>
                                        <span wire:loading.class.remove="hidden" class="hidden" wire:target="manageHousehold({{ $household->id }})">Loading...</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                No households found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($households->hasPages())
            <div class="px-6 py-3 border-t border-gray-200 dark:border-zinc-600">
                {{ $households->links() }}
            </div>
        @endif
    </div>
</div>