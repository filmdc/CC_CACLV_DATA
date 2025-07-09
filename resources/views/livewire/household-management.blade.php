<div class="max-w-7xl mx-auto p-6">
    <div class="bg-white shadow-lg rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-green-600 text-white p-6">
            <h1 class="text-2xl font-bold">Household Management</h1>
            <p class="text-green-100 mt-2">Manage household composition and family relationships</p>
        </div>

        <!-- Search and Filters -->
        @if(!$showAddMember)
        <div class="p-4 bg-gray-50 border-b">
            <div class="flex items-center space-x-4">
                <div class="flex-1">
                    <input type="text" wire:model.live="search" placeholder="Search by household code or name..." 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
            </div>
        </div>
        @endif

        <!-- Success/Error Messages -->
        @if (session()->has('success'))
            <div class="p-4 bg-green-50 border-l-4 border-green-400">
                <div class="flex">
                    <div class="ml-3">
                        <p class="text-sm text-green-700">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="p-4 bg-red-50 border-l-4 border-red-400">
                <div class="flex">
                    <div class="ml-3">
                        <p class="text-sm text-red-700">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if(!$showAddMember)
        <div class="flex">
            <!-- Household List -->
            <div class="w-1/3 border-r">
                <div class="p-4 border-b bg-gray-50">
                    <h3 class="text-lg font-medium">Households</h3>
                </div>
                
                <div class="divide-y max-h-96 overflow-y-auto">
                    @forelse ($households as $household)
                        <div class="p-4 cursor-pointer hover:bg-gray-50 {{ $selectedHousehold && $selectedHousehold->id === $household->id ? 'bg-blue-50 border-l-4 border-blue-500' : '' }}" 
                             wire:click="selectHousehold({{ $household->id }})">
                            <div class="flex justify-between items-start">
                                <div>
                                    <button wire:click="viewHousehold({{ $household->id }})" 
                                            class="text-left w-full text-gray-900 hover:text-blue-600 hover:underline cursor-pointer transition-colors duration-200">
                                        <p class="font-medium">{{ $household->household_code }}</p>
                                    </button>
                                    <p class="text-sm text-gray-600">
                                        {{ $household->headOfHousehold?->full_name ?? 'No head of household' }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{ $household->household_size }} {{ Str::plural('member', $household->household_size) }} • 
                                        {{ $household->household_type }}
                                    </p>
                                </div>
                                <span class="text-xs text-gray-400">
                                    {{ $household->created_at->format('M j') }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="p-4 text-center text-gray-500">
                            No households found.
                        </div>
                    @endforelse
                </div>

                <!-- Pagination -->
                <div class="p-4 border-t">
                    {{ $households->links() }}
                </div>
            </div>

            <!-- Household Details -->
            <div class="flex-1">
                @if ($selectedHousehold)
                    <div class="p-6">
                        <!-- Household Info Header -->
                        <div class="flex justify-between items-start mb-6">
                            <div>
                                <h2 class="text-xl font-bold text-gray-900">{{ $selectedHousehold->household_code }}</h2>
                                <p class="text-gray-600">{{ $selectedHousehold->household_type }}</p>
                                <div class="mt-2 space-y-1 text-sm text-gray-500">
                                    <p>Size: {{ $selectedHousehold->household_size }} {{ Str::plural('member', $selectedHousehold->household_size) }}</p>
                                    <p>Housing: {{ $selectedHousehold->housing_status }}</p>
                                    @if($selectedHousehold->monthly_income)
                                        <p>Monthly Income: ${{ number_format($selectedHousehold->monthly_income, 2) }}</p>
                                    @endif
                                    @if($selectedHousehold->hhs_poverty_percentage)
                                        <p>Poverty Level: {{ $selectedHousehold->hhs_poverty_percentage }}% of federal guidelines</p>
                                    @endif
                                </div>
                            </div>
                            <button wire:click="showAddMemberForm" 
                                    class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                                Add Family Member
                            </button>
                        </div>

                        <!-- Family Members -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-medium text-gray-900">Family Members</h3>
                            
                            @foreach ($selectedHousehold->participants as $participant)
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-2">
                                                <h4 class="font-medium text-gray-900">{{ $participant->full_name }}</h4>
                                                @if($participant->is_head_of_household)
                                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">Head of Household</span>
                                                @endif
                                            </div>
                                            <div class="mt-1 space-y-1 text-sm text-gray-600">
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
                                                    class="px-3 py-1 bg-red-100 text-red-700 text-sm rounded hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-red-500">
                                                Remove
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Add Family Member Form -->
                        @if ($showAddMember)
                            <div class="mt-6 rounded-lg p-4">
                                <div class="flex items-center justify-between mb-4">
                                    <h4 class="text-lg font-medium text-gray-900">Add Family Member</h4>
                                    <button wire:click="$set('showAddMember', false)" class="text-gray-400 hover:text-gray-600">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                                
                                <form wire:submit="addFamilyMember" class="space-y-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">
                                                First Name <span class="text-red-500">*</span>
                                            </label>
                                            <input type="text" wire:model="first_name" id="first_name" 
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            @error('first_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                        </div>

                                        <div>
                                            <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">
                                                Last Name <span class="text-red-500">*</span>
                                            </label>
                                            <input type="text" wire:model="last_name" id="last_name" 
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            @error('last_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                        </div>

                                        <div>
                                            <label for="date_of_birth" class="block text-sm font-medium text-gray-700 mb-1">
                                                Date of Birth <span class="text-red-500">*</span>
                                            </label>
                                            <input type="date" wire:model="date_of_birth" id="date_of_birth" 
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            @error('date_of_birth') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                        </div>

                                        <div>
                                            <label for="gender" class="block text-sm font-medium text-gray-700 mb-1">
                                                Gender <span class="text-red-500">*</span>
                                            </label>
                                            <select wire:model="gender" id="gender" 
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
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
                                            <label for="telephone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                            <input type="tel" wire:model="telephone" id="telephone" 
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        </div>

                                        <div>
                                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                                            <input type="email" wire:model="email" id="email" 
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        </div>
                                    </div>

                                    <div class="flex justify-end space-x-3">
                                        <button type="button" wire:click="$set('showAddMember', false)" 
                                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 border border-gray-300 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500">
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
                    </div>
                @else
                    <div class="p-6 text-center text-gray-500">
                        <p>Select a household to view details and manage family members.</p>
                    </div>
                @endif
            </div>
        </div>
        @endif
    </div>

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