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
                                    <button wire:click="selectHousehold({{ $household->id }})" 
                                            wire:loading.attr="disabled"
                                            wire:loading.class="opacity-50 cursor-wait"
                                            wire:target="selectHousehold({{ $household->id }})"
                                            class="px-3 py-1 bg-green-100 dark:bg-green-900/20 text-green-700 dark:text-green-300 text-sm rounded hover:bg-green-200 dark:hover:bg-green-900/40 focus:outline-none focus:ring-2 focus:ring-green-500 font-medium transition-all">
                                        <span wire:loading.class="hidden" wire:target="selectHousehold({{ $household->id }})">Manage</span>
                                        <span wire:loading.class.remove="hidden" class="hidden" wire:target="selectHousehold({{ $household->id }})">Loading...</span>
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

    <!-- Household Management Panel -->
    @if($selectedHousehold)
        <div class="mt-6 bg-white dark:bg-zinc-800 shadow-lg rounded-lg overflow-hidden" 
             x-data="{ 
                 scrolled: false,
                 scrollToView() {
                     if (!this.scrolled) {
                         if (window.ScrollControl) {
                             window.ScrollControl.scrollOnce($el, { behavior: 'smooth', block: 'start' });
                         } else {
                             $el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                         }
                         this.scrolled = true;
                     }
                 }
             }"
             x-init="$nextTick(() => scrollToView())">
            <div class="bg-gray-50 dark:bg-zinc-700 px-6 py-4 border-b border-gray-200 dark:border-zinc-600">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Managing: {{ $selectedHousehold->headOfHousehold?->last_name ?? 'Unknown' }} Family
                    </h2>
                    <button wire:click="$set('selectedHousehold', null)" 
                            class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="p-6">
                <!-- Household Info -->
                <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Household Code</label>
                        <p class="text-sm text-gray-900 dark:text-white">{{ $selectedHousehold->household_code }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Type</label>
                        <p class="text-sm text-gray-900 dark:text-white">{{ $selectedHousehold->household_type }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Size</label>
                        <p class="text-sm text-gray-900 dark:text-white">{{ $selectedHousehold->household_size }} {{ Str::plural('member', $selectedHousehold->household_size) }}</p>
                    </div>
                </div>

                <!-- Family Members -->
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Family Members</h3>
                        <div class="flex space-x-2">
                            @if($this->can('households.edit'))
                                <button wire:click="showEditHouseholdForm" 
                                        class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    Edit Household
                                </button>
                            @endif
                            <button wire:click="showAddMemberForm" 
                                    class="px-4 py-2 bg-green-600 text-white text-sm rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                                Add Family Member
                            </button>
                        </div>
                    </div>
                    
                    <div class="space-y-3">
                        @foreach ($selectedHousehold->participants as $participant)
                            <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-4">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-2">
                                            <h4 class="font-medium text-gray-900 dark:text-white">{{ $participant->full_name }}</h4>
                                            @if($participant->is_head_of_household)
                                                <span class="px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 text-xs rounded-full">Head of Household</span>
                                            @endif
                                        </div>
                                        <div class="mt-1 space-y-1 text-sm text-gray-600 dark:text-gray-400">
                                            <p>Age: {{ $participant->age }} • Gender: {{ $participant->gender }}</p>
                                            <p>DOB: {{ $participant->date_of_birth->format('M j, Y') }}</p>
                                            @if($participant->telephone)
                                                <p>Phone: {{ $participant->telephone }}</p>
                                            @endif
                                            @if($participant->email)
                                                <p>Email: {{ $participant->email }}</p>
                                            @endif
                                        </div>
                                    </div>
                                    @if(!$participant->is_head_of_household)
                                        <button wire:click="removeParticipant({{ $participant->id }})" 
                                                wire:confirm="Are you sure you want to remove this family member?"
                                                class="px-3 py-1 bg-red-100 dark:bg-red-900/20 text-red-700 dark:text-red-300 text-sm rounded hover:bg-red-200 dark:hover:bg-red-900/40 focus:outline-none focus:ring-2 focus:ring-red-500">
                                            Remove
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Add Family Member Form -->
                @if ($showAddMember)
                    <div class="mt-6 border-t border-gray-200 dark:border-zinc-600 pt-6">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-lg font-medium text-gray-900 dark:text-white">Add Family Member</h4>
                            <button wire:click="$set('showAddMember', false)" class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        
                        <form wire:submit="addFamilyMember" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="first_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        First Name <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" wire:model="first_name" id="first_name" 
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500">
                                    @error('first_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label for="last_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Last Name <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" wire:model="last_name" id="last_name" 
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500">
                                    @error('last_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label for="date_of_birth" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Date of Birth <span class="text-red-500">*</span>
                                    </label>
                                    <input type="date" wire:model="date_of_birth" id="date_of_birth" 
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500">
                                    @error('date_of_birth') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label for="gender" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Gender <span class="text-red-500">*</span>
                                    </label>
                                    <select wire:model="gender" id="gender" 
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500">
                                        <option value="">Select gender...</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Non-binary">Non-binary</option>
                                        <option value="Other">Other</option>
                                        <option value="Prefer not to say">Prefer not to say</option>
                                    </select>
                                    @error('gender') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label for="telephone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Phone Number</label>
                                    <input type="tel" wire:model="telephone" id="telephone" 
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500">
                                </div>

                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email Address</label>
                                    <input type="email" wire:model="email" id="email" 
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500">
                                </div>
                            </div>

                            <div class="flex justify-end space-x-3">
                                <button type="button" wire:click="$set('showAddMember', false)" 
                                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-zinc-700 border border-gray-300 dark:border-zinc-600 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-600 focus:outline-none focus:ring-2 focus:ring-gray-500">
                                    Cancel
                                </button>
                                <button type="submit" 
                                        class="px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                                    Add Member
                                </button>
                            </div>
                        </form>
                    </div>
                @endif
                
                <!-- Edit Household Form -->
                @if ($showEditHousehold)
                    <div class="mt-6 border-t border-gray-200 dark:border-zinc-600 pt-6">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-lg font-medium text-gray-900 dark:text-white">Edit Household Information</h4>
                            <button wire:click="$set('showEditHousehold', false)" class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        
                        <form wire:submit="updateHousehold" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Household Type -->
                                <div>
                                    <label for="editHouseholdType" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Household Type <span class="text-red-500">*</span>
                                    </label>
                                    <select wire:model="editHouseholdType" id="editHouseholdType" 
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">Select type...</option>
                                        <option value="Single Person">Single Person</option>
                                        <option value="Two Adults NO Children">Two Adults NO Children</option>
                                        <option value="Single Parent Female">Single Parent Female</option>
                                        <option value="Single Parent Male">Single Parent Male</option>
                                        <option value="Two Parent Household">Two Parent Household</option>
                                        <option value="Non-related Adults with Children">Non-related Adults with Children</option>
                                        <option value="Multigenerational Household">Multigenerational Household</option>
                                        <option value="Other">Other</option>
                                    </select>
                                    @error('editHouseholdType') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                
                                <!-- Housing Status -->
                                <div>
                                    <label for="editHousingStatus" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Housing Status
                                    </label>
                                    <select wire:model="editHousingStatus" id="editHousingStatus" 
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">Select status...</option>
                                        <option value="Own">Own</option>
                                        <option value="Rent">Rent</option>
                                        <option value="Other permanent housing">Other permanent housing</option>
                                        <option value="Homeless">Homeless</option>
                                        <option value="Other">Other</option>
                                    </select>
                                    @error('editHousingStatus') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                
                                <!-- Monthly Income -->
                                <div>
                                    <label for="editMonthlyIncome" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Monthly Income
                                    </label>
                                    <input type="number" wire:model="editMonthlyIncome" id="editMonthlyIncome" step="0.01" min="0"
                                           placeholder="0.00"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    @error('editMonthlyIncome') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                
                                <!-- Primary Phone -->
                                <div>
                                    <label for="editPrimaryPhone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Primary Phone
                                    </label>
                                    <input type="tel" wire:model="editPrimaryPhone" id="editPrimaryPhone" 
                                           placeholder="(555) 123-4567"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    @error('editPrimaryPhone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                
                                <!-- Address -->
                                <div class="md:col-span-2">
                                    <label for="editAddress" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Street Address <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" wire:model="editAddress" id="editAddress" 
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    @error('editAddress') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                
                                <!-- City -->
                                <div>
                                    <label for="editCity" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        City <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" wire:model="editCity" id="editCity" 
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    @error('editCity') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                
                                <!-- State -->
                                <div>
                                    <label for="editState" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        State <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" wire:model="editState" id="editState" maxlength="2" 
                                           placeholder="PA"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    @error('editState') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                
                                <!-- Zip Code -->
                                <div>
                                    <label for="editZipCode" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Zip Code <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" wire:model="editZipCode" id="editZipCode" 
                                           placeholder="12345"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    @error('editZipCode') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                
                                <!-- County -->
                                <div>
                                    <label for="editCounty" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        County
                                    </label>
                                    <input type="text" wire:model="editCounty" id="editCounty" 
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    @error('editCounty') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                
                                <!-- Area -->
                                <div>
                                    <label for="editArea" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Area
                                    </label>
                                    <input type="text" wire:model="editArea" id="editArea" 
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    @error('editArea') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            
                            <div class="flex justify-end space-x-3">
                                <button type="button" wire:click="resetEditForm" 
                                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-zinc-700 border border-gray-300 dark:border-zinc-600 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-600 focus:outline-none focus:ring-2 focus:ring-gray-500">
                                    Cancel
                                </button>
                                <button type="submit" 
                                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    Update Household
                                </button>
                            </div>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- View Household Modal -->
    @if($showViewModal && $selectedHousehold)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-6xl w-full max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">Household Details</h3>
                    <button wire:click="$set('showViewModal', false)" class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Household Information -->
                    <div class="bg-gray-50 dark:bg-zinc-700 p-4 rounded-lg">
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Household Information</h4>
                        <div class="space-y-3">
                            <div>
                                <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Household Code</label>
                                <p class="text-sm text-gray-900 dark:text-white">{{ $selectedHousehold->household_code }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Household Type</label>
                                <p class="text-sm text-gray-900 dark:text-white">{{ $selectedHousehold->household_type ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Household Size</label>
                                <p class="text-sm text-gray-900 dark:text-white">{{ $selectedHousehold->household_size }} {{ Str::plural('member', $selectedHousehold->household_size) }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Housing Status</label>
                                <p class="text-sm text-gray-900 dark:text-white">{{ $selectedHousehold->housing_status ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Monthly Income</label>
                                <p class="text-sm text-gray-900 dark:text-white">
                                    @if($selectedHousehold->monthly_income)
                                        ${{ number_format($selectedHousehold->monthly_income, 2) }}
                                    @else
                                        N/A
                                    @endif
                                </p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Poverty Level</label>
                                <p class="text-sm text-gray-900 dark:text-white">
                                    @if($selectedHousehold->hhs_poverty_percentage)
                                        {{ $selectedHousehold->hhs_poverty_percentage }}% of federal guidelines
                                    @else
                                        N/A
                                    @endif
                                </p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Created</label>
                                <p class="text-sm text-gray-900 dark:text-white">{{ $selectedHousehold->created_at->format('F j, Y g:i A') }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Head of Household -->
                    <div class="bg-gray-50 dark:bg-zinc-700 p-4 rounded-lg">
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Head of Household</h4>
                        @if($selectedHousehold->headOfHousehold)
                            <div class="space-y-3">
                                <div>
                                    <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Full Name</label>
                                    <p class="text-sm text-gray-900 dark:text-white">{{ $selectedHousehold->headOfHousehold->full_name }}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Date of Birth</label>
                                    <p class="text-sm text-gray-900 dark:text-white">{{ $selectedHousehold->headOfHousehold->date_of_birth ? $selectedHousehold->headOfHousehold->date_of_birth->format('F j, Y') : 'N/A' }}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Age</label>
                                    <p class="text-sm text-gray-900 dark:text-white">{{ $selectedHousehold->headOfHousehold->age ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Gender</label>
                                    <p class="text-sm text-gray-900 dark:text-white">{{ $selectedHousehold->headOfHousehold->gender ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Phone Number</label>
                                    <p class="text-sm text-gray-900 dark:text-white">{{ $selectedHousehold->headOfHousehold->telephone ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Email</label>
                                    <p class="text-sm text-gray-900 dark:text-white">{{ $selectedHousehold->headOfHousehold->email ?? 'N/A' }}</p>
                                </div>
                            </div>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400">No head of household assigned</p>
                        @endif
                    </div>

                    <!-- Family Members -->
                    <div class="bg-gray-50 dark:bg-zinc-700 p-4 rounded-lg lg:col-span-2">
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Family Members</h4>
                        @if($selectedHousehold->participants && $selectedHousehold->participants->count() > 0)
                            <div class="space-y-3">
                                @foreach($selectedHousehold->participants as $participant)
                                    <div class="flex items-center justify-between p-3 bg-white dark:bg-zinc-800 rounded border">
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-2">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $participant->full_name }}</div>
                                                @if($participant->is_head_of_household)
                                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">Head</span>
                                                @endif
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                Age: {{ $participant->age ?? 'N/A' }} • Gender: {{ $participant->gender ?? 'N/A' }}
                                                @if($participant->telephone)
                                                    • Phone: {{ $participant->telephone }}
                                                @endif
                                            </div>
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            <div>Programs: {{ $participant->programEnrollments->where('status', 'active')->count() }}</div>
                                            <div>Services: {{ $participant->serviceEnrollments->whereIn('status', ['enrolled', 'in_progress'])->count() }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400">No family members found</p>
                        @endif
                    </div>

                    <!-- Enrollment Summary -->
                    <div class="bg-gray-50 dark:bg-zinc-700 p-4 rounded-lg lg:col-span-2">
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Enrollment Summary</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <h5 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Program Enrollments</h5>
                                @php
                                    $programEnrollments = $selectedHousehold->participants->flatMap->programEnrollments;
                                @endphp
                                @if($programEnrollments->count() > 0)
                                    <div class="space-y-2">
                                        @foreach($programEnrollments->groupBy('program.name') as $programName => $enrollments)
                                            <div class="flex items-center justify-between p-2 bg-white dark:bg-zinc-800 rounded border">
                                                <div class="text-sm text-gray-900 dark:text-white">{{ $programName }}</div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $enrollments->count() }} {{ Str::plural('enrollment', $enrollments->count()) }}
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-sm text-gray-500 dark:text-gray-400">No program enrollments</p>
                                @endif
                            </div>

                            <div>
                                <h5 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Service Enrollments</h5>
                                @php
                                    $serviceEnrollments = $selectedHousehold->participants->flatMap->serviceEnrollments;
                                @endphp
                                @if($serviceEnrollments->count() > 0)
                                    <div class="space-y-2">
                                        @foreach($serviceEnrollments->groupBy('service.name') as $serviceName => $enrollments)
                                            <div class="flex items-center justify-between p-2 bg-white dark:bg-zinc-800 rounded border">
                                                <div class="text-sm text-gray-900 dark:text-white">{{ $serviceName }}</div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $enrollments->count() }} {{ Str::plural('enrollment', $enrollments->count()) }}
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-sm text-gray-500 dark:text-gray-400">No service enrollments</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button wire:click="$set('showViewModal', false)" 
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-zinc-700 border border-gray-300 dark:border-zinc-600 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-600 focus:outline-none focus:ring-2 focus:ring-gray-500">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>