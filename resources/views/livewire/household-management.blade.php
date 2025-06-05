<div class="max-w-7xl mx-auto p-6">
    <div class="bg-white shadow-lg rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-green-600 text-white p-6">
            <h1 class="text-2xl font-bold">Household Management</h1>
            <p class="text-green-100 mt-2">Manage household composition and family relationships</p>
        </div>

        <!-- Search and Filters -->
        <div class="p-4 bg-gray-50 border-b">
            <div class="flex items-center space-x-4">
                <div class="flex-1">
                    <input type="text" wire:model.live="search" placeholder="Search by household code or name..." 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
            </div>
        </div>

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
                                    <p class="font-medium text-gray-900">{{ $household->household_code }}</p>
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
                            <div class="mt-6 bg-blue-50 rounded-lg p-4">
                                <h4 class="text-lg font-medium text-gray-900 mb-4">Add Family Member</h4>
                                
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
    </div>
</div>