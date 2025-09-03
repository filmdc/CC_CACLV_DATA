<div class="max-w-4xl mx-auto p-6">
    <div class="bg-white shadow-lg rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-blue-600 text-white p-6">
            <h1 class="text-2xl font-bold">Participant Intake & Registration</h1>
            <p class="text-blue-100 mt-2">CSBG Module 4 Section C Compliant Data Collection</p>
        </div>

        <!-- Progress Bar -->
        <div class="p-4 bg-gray-50 border-b">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-700">Step {{ $step }} of {{ $totalSteps }}</span>
                <span class="text-sm text-gray-500">{{ round(($step / $totalSteps) * 100) }}% Complete</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" 
                     style="width: {{ ($step / $totalSteps) * 100 }}%"></div>
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

        <form wire:submit="submit" class="p-6">
            <!-- Step 1: Head of Household Personal Information -->
            @if ($step === 1)
                <div class="space-y-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Head of Household Information</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="first_name" class="block text-sm font-medium text-gray-700 mb-2">
                                First Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" wire:model="first_name" id="first_name" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('first_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="middle_name" class="block text-sm font-medium text-gray-700 mb-2">Middle Name</label>
                            <input type="text" wire:model="middle_name" id="middle_name" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('middle_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">
                                Last Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" wire:model="last_name" id="last_name" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('last_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="suffix" class="block text-sm font-medium text-gray-700 mb-2">Suffix</label>
                            <input type="text" wire:model="suffix" id="suffix" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('suffix') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="date_of_birth" class="block text-sm font-medium text-gray-700 mb-2">
                                Date of Birth <span class="text-red-500">*</span>
                            </label>
                            <input type="date" wire:model.blur="date_of_birth" id="date_of_birth" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('date_of_birth') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="gender" class="block text-sm font-medium text-gray-700 mb-2">
                                Gender <span class="text-red-500">*</span>
                            </label>
                            <select wire:model="gender" id="gender" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select gender...</option>
                                @foreach($genderOptions as $option)
                                    <option value="{{ $option->value }}">{{ $option->label }}</option>
                                @endforeach
                            </select>
                            @error('gender') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="telephone" class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                            <input type="tel" wire:model="telephone" id="telephone" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('telephone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                            <input type="email" wire:model="email" id="email" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
            @endif

            <!-- Step 2: Household Information -->
            @if ($step === 2)
                <div class="space-y-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Household Information</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="household_type" class="block text-sm font-medium text-gray-700 mb-2">
                                Household Type <span class="text-red-500">*</span>
                            </label>
                            <select wire:model="household_type" id="household_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select household type...</option>
                                @foreach($householdTypes as $option)
                                    <option value="{{ $option->value }}">{{ $option->label }}</option>
                                @endforeach
                            </select>
                            @error('household_type') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="household_size" class="block text-sm font-medium text-gray-700 mb-2">
                                Household Size <span class="text-red-500">*</span>
                            </label>
                            <input type="number" wire:model="household_size" id="household_size" min="1" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('household_size') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="housing_status" class="block text-sm font-medium text-gray-700 mb-2">
                                Housing Status <span class="text-red-500">*</span>
                            </label>
                            <select wire:model="housing_status" id="housing_status" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select housing status...</option>
                                @foreach($housingStatuses as $option)
                                    <option value="{{ $option->value }}">{{ $option->label }}</option>
                                @endforeach
                            </select>
                            @error('housing_status') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="monthly_income" class="block text-sm font-medium text-gray-700 mb-2">
                                Monthly Household Income
                            </label>
                            <input type="number" wire:model="monthly_income" id="monthly_income" min="0" step="0.01" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('monthly_income') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900">Address Information</h3>
                        
                        <div>
                            <label for="street_address" class="block text-sm font-medium text-gray-700 mb-2">
                                Street Address
                            </label>
                            <input type="text" wire:model="street_address" id="street_address" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('street_address') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="city" class="block text-sm font-medium text-gray-700 mb-2">City</label>
                                <input type="text" wire:model="city" id="city" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('city') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label for="state" class="block text-sm font-medium text-gray-700 mb-2">State</label>
                                <input type="text" wire:model="state" id="state" maxlength="2" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('state') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label for="zip_code" class="block text-sm font-medium text-gray-700 mb-2">ZIP Code</label>
                                <input type="text" wire:model="zip_code" id="zip_code" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('zip_code') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Step 3: Household Members -->
            @if ($step === 3)
                <div class="space-y-6">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Household Members</h2>
                        <p class="text-sm text-gray-600">Optional: Add additional household members during intake</p>
                    </div>
                    
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-blue-800">Head of Household: {{ $first_name }} {{ $last_name }}</h3>
                                <p class="text-sm text-blue-700 mt-1">Current household size: {{ $household_size }} {{ Str::plural('member', $household_size) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Current Household Members List -->
                    @if(count($householdMembers) > 0)
                        <div class="bg-white border border-gray-200 rounded-lg">
                            <div class="px-4 py-3 border-b border-gray-200">
                                <h3 class="text-sm font-medium text-gray-900">Added Household Members</h3>
                            </div>
                            <ul class="divide-y divide-gray-200">
                                @foreach($householdMembers as $index => $member)
                                    <li class="px-4 py-3">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">
                                                    {{ $member['first_name'] }} {{ $member['middle_name'] }} {{ $member['last_name'] }}
                                                    @if($member['suffix']) {{ $member['suffix'] }} @endif
                                                </p>
                                                <p class="text-xs text-gray-500">
                                                    {{ ucfirst($member['relationship_to_head']) }} • 
                                                    {{ ucfirst($member['gender']) }} • 
                                                    DOB: {{ \Carbon\Carbon::parse($member['date_of_birth'])->format('M j, Y') }}
                                                </p>
                                            </div>
                                            <button type="button" wire:click="removeMember({{ $index }})" 
                                                    class="text-red-600 hover:text-red-800 text-sm font-medium">
                                                Remove
                                            </button>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Add Member Button -->
                    @if(!$showMemberForm)
                        <div class="text-center">
                            <button type="button" wire:click="showAddMemberForm" 
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Add Household Member
                            </button>
                        </div>
                    @endif

                    <!-- Add Member Form -->
                    @if($showMemberForm)
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-900">Add Household Member</h3>
                                <button type="button" wire:click="hideAddMemberForm" 
                                        class="text-gray-400 hover:text-gray-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="memberFirstName" class="block text-sm font-medium text-gray-700 mb-2">
                                        First Name <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" wire:model="memberFirstName" id="memberFirstName" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    @error('memberFirstName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label for="memberMiddleName" class="block text-sm font-medium text-gray-700 mb-2">Middle Name</label>
                                    <input type="text" wire:model="memberMiddleName" id="memberMiddleName" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    @error('memberMiddleName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label for="memberLastName" class="block text-sm font-medium text-gray-700 mb-2">
                                        Last Name <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" wire:model="memberLastName" id="memberLastName" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    @error('memberLastName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label for="memberSuffix" class="block text-sm font-medium text-gray-700 mb-2">Suffix</label>
                                    <input type="text" wire:model="memberSuffix" id="memberSuffix" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    @error('memberSuffix') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label for="memberDateOfBirth" class="block text-sm font-medium text-gray-700 mb-2">
                                        Date of Birth <span class="text-red-500">*</span>
                                    </label>
                                    <input type="date" wire:model="memberDateOfBirth" id="memberDateOfBirth" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    @error('memberDateOfBirth') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label for="memberGender" class="block text-sm font-medium text-gray-700 mb-2">
                                        Gender <span class="text-red-500">*</span>
                                    </label>
                                    <select wire:model="memberGender" id="memberGender" 
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">Select gender...</option>
                                        @foreach($genderOptions as $option)
                                            <option value="{{ $option->value }}">{{ $option->label }}</option>
                                        @endforeach
                                    </select>
                                    @error('memberGender') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <div class="md:col-span-2">
                                    <label for="memberRelationship" class="block text-sm font-medium text-gray-700 mb-2">
                                        Relationship to Head of Household <span class="text-red-500">*</span>
                                    </label>
                                    <select wire:model="memberRelationship" id="memberRelationship" 
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">Select relationship...</option>
                                        <option value="spouse">Spouse</option>
                                        <option value="child">Child</option>
                                        <option value="parent">Parent</option>
                                        <option value="sibling">Sibling</option>
                                        <option value="grandparent">Grandparent</option>
                                        <option value="grandchild">Grandchild</option>
                                        <option value="other_relative">Other Relative</option>
                                        <option value="unrelated">Unrelated</option>
                                    </select>
                                    @error('memberRelationship') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="flex justify-end space-x-3 mt-6">
                                <button type="button" wire:click="hideAddMemberForm" 
                                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 border border-gray-300 rounded-md hover:bg-gray-300">
                                    Cancel
                                </button>
                                <button type="button" wire:click="addMember" 
                                        class="px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700">
                                    Add Member
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Step 4: Demographics -->
            @if ($step === 4)
                <div class="space-y-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Demographics (CSBG Module 4 Section C)</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="education_level" class="block text-sm font-medium text-gray-700 mb-2">Education Level</label>
                            <select wire:model="education_level" id="education_level" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select education level...</option>
                                @foreach($educationLevels as $option)
                                    <option value="{{ $option->value }}">{{ $option->label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Currently Enrolled in Education?</label>
                            <div class="space-x-4">
                                <label class="inline-flex items-center">
                                    <input type="radio" wire:model="currently_enrolled_in_education" value="1" class="form-radio">
                                    <span class="ml-2">Yes</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="radio" wire:model="currently_enrolled_in_education" value="0" class="form-radio">
                                    <span class="ml-2">No</span>
                                </label>
                            </div>
                        </div>

                        @if($currently_enrolled_in_education)
                        <div>
                            <label for="education_enrollment_type" class="block text-sm font-medium text-gray-700 mb-2">Type of Education Enrollment</label>
                            <select wire:model="education_enrollment_type" id="education_enrollment_type" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select type...</option>
                                <option value="K-12">K-12 School</option>
                                <option value="GED Program">GED Program</option>
                                <option value="Community College">Community College</option>
                                <option value="4-Year College">4-Year College</option>
                                <option value="Graduate School">Graduate School</option>
                                <option value="Vocational/Trade School">Vocational/Trade School</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        @endif

                        <div>
                            <label for="disability_status" class="block text-sm font-medium text-gray-700 mb-2">Disability Status</label>
                            <select wire:model="disability_status" id="disability_status" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select status...</option>
                                @foreach($disabilityStatuses as $option)
                                    <option value="{{ $option->value }}">{{ $option->label }}</option>
                                @endforeach
                            </select>
                        </div>

                        @if($disability_status === 'Yes')
                        <div>
                            <label for="disability_types" class="block text-sm font-medium text-gray-700 mb-2">Types of Disability (Select all that apply)</label>
                            <select wire:model="disability_types" id="disability_types" multiple 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 min-h-[100px]">
                                <option value="Physical Disability">Physical Disability</option>
                                <option value="Intellectual Disability">Intellectual Disability</option>
                                <option value="Mental Health Condition">Mental Health Condition</option>
                                <option value="Sensory Impairment">Sensory Impairment</option>
                                <option value="Learning Disability">Learning Disability</option>
                                <option value="Chronic Health Condition">Chronic Health Condition</option>
                                <option value="Other">Other</option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Hold Ctrl (Cmd on Mac) to select multiple options</p>
                        </div>

                        <div>
                            <label for="assistance_needs" class="block text-sm font-medium text-gray-700 mb-2">Types of Assistance Needed (Select all that apply)</label>
                            <select wire:model="assistance_needs" id="assistance_needs" multiple 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 min-h-[100px]">
                                <option value="Personal Care">Personal Care</option>
                                <option value="Mobility Assistance">Mobility Assistance</option>
                                <option value="Communication Support">Communication Support</option>
                                <option value="Transportation">Transportation</option>
                                <option value="Medical Equipment">Medical Equipment</option>
                                <option value="Home Modifications">Home Modifications</option>
                                <option value="Job Accommodations">Job Accommodations</option>
                                <option value="Other">Other</option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Hold Ctrl (Cmd on Mac) to select multiple options</p>
                        </div>
                        @endif

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Has Chronic Health Conditions?</label>
                            <div class="space-x-4">
                                <label class="inline-flex items-center">
                                    <input type="radio" wire:model="has_chronic_health_conditions" value="1" class="form-radio">
                                    <span class="ml-2">Yes</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="radio" wire:model="has_chronic_health_conditions" value="0" class="form-radio">
                                    <span class="ml-2">No</span>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label for="ethnicity" class="block text-sm font-medium text-gray-700 mb-2">Ethnicity</label>
                            <select wire:model="ethnicity" id="ethnicity" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select ethnicity...</option>
                                @foreach($ethnicityOptions as $option)
                                    <option value="{{ $option->value }}">{{ $option->label }}</option>
                                @endforeach
                            </select>
                        </div>


                        <div>
                            <label for="health_insurance_status" class="block text-sm font-medium text-gray-700 mb-2">Health Insurance Status</label>
                            <select wire:model="health_insurance_status" id="health_insurance_status" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select status...</option>
                                @foreach($healthInsuranceStatuses as $option)
                                    <option value="{{ $option->value }}">{{ $option->label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="race" class="block text-sm font-medium text-gray-700 mb-2">Race (Select all that apply)</label>
                            <select wire:model="race" id="race" multiple 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 min-h-[100px]">
                                @foreach($raceOptions as $option)
                                    <option value="{{ $option->value }}">{{ $option->label }}</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Hold Ctrl (Cmd on Mac) to select multiple options</p>
                        </div>

                        <div>
                            <label for="health_insurance_sources" class="block text-sm font-medium text-gray-700 mb-2">Health Insurance Sources (Select all that apply)</label>
                            <select wire:model="health_insurance_sources" id="health_insurance_sources" multiple 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 min-h-[100px]">
                                @foreach($healthInsuranceSources as $option)
                                    <option value="{{ $option->value }}">{{ $option->label }}</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Hold Ctrl (Cmd on Mac) to select multiple options</p>
                        </div>

                        <div>
                            <label for="income_sources" class="block text-sm font-medium text-gray-700 mb-2">Income Sources (Select all that apply)</label>
                            <select wire:model="income_sources" id="income_sources" multiple 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 min-h-[100px]">
                                @foreach($incomeSources as $option)
                                    <option value="{{ $option->value }}">{{ $option->label }}</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Hold Ctrl (Cmd on Mac) to select multiple options</p>
                        </div>

                        <div>
                            <label for="non_cash_benefits" class="block text-sm font-medium text-gray-700 mb-2">Non-Cash Benefits (Select all that apply)</label>
                            <select wire:model="non_cash_benefits" id="non_cash_benefits" multiple 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 min-h-[100px]">
                                @foreach($nonCashBenefits as $option)
                                    <option value="{{ $option->value }}">{{ $option->label }}</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Hold Ctrl (Cmd on Mac) to select multiple options</p>
                        </div>
                    </div>

                    <!-- Employment & Military Information -->
                    <div class="mt-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Employment & Military Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="military_status" class="block text-sm font-medium text-gray-700 mb-2">Military Status</label>
                                <select wire:model="military_status" id="military_status" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Select status...</option>
                                    @foreach($militaryStatuses as $option)
                                        <option value="{{ $option->value }}">{{ $option->label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="work_status" class="block text-sm font-medium text-gray-700 mb-2">Work Status</label>
                                <select wire:model="work_status" id="work_status" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Select status...</option>
                                    @foreach($workStatuses as $option)
                                        <option value="{{ $option->value }}">{{ $option->label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            @if(in_array($work_status, ['Employed Full-Time', 'Employed Part-Time']))
                            <div>
                                <label for="hours_worked_per_week" class="block text-sm font-medium text-gray-700 mb-2">Hours Worked Per Week</label>
                                <input type="number" wire:model="hours_worked_per_week" id="hours_worked_per_week" 
                                       min="0" max="168" step="0.5"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('hours_worked_per_week') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Is Underemployed?</label>
                                <div class="space-x-4">
                                    <label class="inline-flex items-center">
                                        <input type="radio" wire:model="is_underemployed" value="1" class="form-radio">
                                        <span class="ml-2">Yes</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="radio" wire:model="is_underemployed" value="0" class="form-radio">
                                        <span class="ml-2">No</span>
                                    </label>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Working less than desired hours or below skill level</p>
                            </div>
                            @endif

                            @if(in_array($work_status, ['Unemployed (Short-Term, 6 months or less)', 'Unemployed (Long-Term, more than 6 months)', 'Unemployed (Not in Labor Force)']))
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Is Seeking Employment?</label>
                                <div class="space-x-4">
                                    <label class="inline-flex items-center">
                                        <input type="radio" wire:model="is_seeking_employment" value="1" class="form-radio">
                                        <span class="ml-2">Yes</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="radio" wire:model="is_seeking_employment" value="0" class="form-radio">
                                        <span class="ml-2">No</span>
                                    </label>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <!-- Step 5: Financial Information -->
            @if ($step === 5)
                <div class="space-y-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Financial Information</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="other_income_sources" class="block text-sm font-medium text-gray-700 mb-2">Other Income Sources (Select all that apply)</label>
                            <select wire:model="other_income_sources" id="other_income_sources" multiple 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 min-h-[100px]">
                                @foreach($otherIncomeSources as $option)
                                    <option value="{{ $option->value }}">{{ $option->label }}</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Hold Ctrl (Cmd on Mac) to select multiple options</p>
                        </div>
                    </div>

                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">Additional Notes</label>
                        <textarea wire:model="notes" id="notes" rows="4" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                </div>
            @endif

            <!-- Step 6: Program Enrollment -->
            @if ($step === 6)
                <div class="space-y-6">
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-green-800">Participant Created Successfully!</h3>
                                <p class="text-sm text-green-700 mt-1">
                                    @if($createdParticipant)
                                        {{ $createdParticipant->full_name }} has been registered. 
                                        Household ID: {{ $createdParticipant->household->household_code }}
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Program Enrollment</h2>
                    <p class="text-gray-600 mb-6">Enroll the participant in a program to begin service delivery.</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Program Selection -->
                        <div>
                            <label for="program_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Program <span class="text-red-500">*</span>
                            </label>
                            <select wire:model.live="program_id" id="program_id" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select a program...</option>
                                @foreach($programs as $program)
                                    <option value="{{ $program->id }}">{{ $program->name }}</option>
                                @endforeach
                            </select>
                            @error('program_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Enrollment Date -->
                        <div>
                            <label for="enrollment_date" class="block text-sm font-medium text-gray-700 mb-2">
                                Enrollment Date <span class="text-red-500">*</span>
                            </label>
                            <input type="date" wire:model="enrollment_date" id="enrollment_date" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('enrollment_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Enrollment Notes -->
                    <div>
                        <label for="enrollment_notes" class="block text-sm font-medium text-gray-700 mb-2">Enrollment Notes</label>
                        <textarea wire:model="enrollment_notes" id="enrollment_notes" rows="3" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                  placeholder="Any notes about this enrollment..."></textarea>
                    </div>

                    <!-- Optional Service Enrollment -->
                    <div class="border-t pt-6">
                        <div class="flex items-center mb-4">
                            <input type="checkbox" wire:model.live="enroll_in_service" id="enroll_in_service" 
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="enroll_in_service" class="ml-2 block text-sm font-medium text-gray-700">
                                Also enroll in a specific service (optional)
                            </label>
                        </div>

                        @if($enroll_in_service)
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-gray-50 p-4 rounded-lg">
                                <!-- Service Selection -->
                                <div>
                                    <label for="service_id" class="block text-sm font-medium text-gray-700 mb-2">
                                        Service <span class="text-red-500">*</span>
                                    </label>
                                    <select wire:model="service_id" id="service_id" 
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">Select a service...</option>
                                        @foreach($services as $service)
                                            <option value="{{ $service->id }}">{{ $service->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('service_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    @if($program_id && $services->isEmpty())
                                        <p class="text-sm text-gray-500 mt-1">No services available for this program.</p>
                                    @endif
                                </div>

                                <!-- Service Enrollment Date -->
                                <div>
                                    <label for="service_enrollment_date" class="block text-sm font-medium text-gray-700 mb-2">
                                        Service Start Date <span class="text-red-500">*</span>
                                    </label>
                                    <input type="date" wire:model="service_enrollment_date" id="service_enrollment_date" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    @error('service_enrollment_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <!-- Service Notes -->
                                <div class="md:col-span-2">
                                    <label for="service_notes" class="block text-sm font-medium text-gray-700 mb-2">Service Notes</label>
                                    <textarea wire:model="service_notes" id="service_notes" rows="2" 
                                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                              placeholder="Any notes about this service enrollment..."></textarea>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Navigation Buttons -->
            <div class="flex justify-between pt-6 border-t">
                <button type="button" wire:click="previousStep" 
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 border border-gray-300 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 {{ $step === 1 ? 'invisible' : '' }}">
                    Previous
                </button>

                <div class="flex space-x-3">
                    @if ($step < $totalSteps)
                        <button type="button" wire:click="nextStep" 
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            Next
                        </button>
                    @else
                        <button type="submit" 
                                class="px-6 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                            Complete Enrollment
                        </button>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <!-- Head of Household Duplicate Check Modal -->
    @if($showHeadOfHouseholdDuplicateModal)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-900">Potential Head of Household Match Found</h3>
                        <button wire:click="cancelHeadOfHouseholdDuplicateCreation" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div class="px-6 py-4">
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-yellow-800">Duplicate Check Warning</h3>
                                <div class="mt-2 text-sm text-yellow-700">
                                    <p>We found {{ count($potentialHeadOfHouseholdDuplicates) }} existing head of household participant(s) with similar information to what you're trying to register.</p>
                                    <p class="mt-1">Please review the matches below and decide if you want to proceed with creating a new household or if one of these is the same person.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-6">
                        <h4 class="text-sm font-medium text-gray-900 mb-3">Your Input:</h4>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                <div>
                                    <span class="font-medium text-gray-700">Name:</span>
                                    <p class="text-gray-900">{{ $first_name }} {{ $last_name }}</p>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-700">Date of Birth:</span>
                                    <p class="text-gray-900">{{ $date_of_birth ? \Carbon\Carbon::parse($date_of_birth)->format('M j, Y') : 'Not provided' }}</p>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-700">Gender:</span>
                                    <p class="text-gray-900">{{ $gender ?: 'Not provided' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if(!empty($potentialHeadOfHouseholdDuplicates))
                        <div class="space-y-4">
                            <h4 class="text-sm font-medium text-gray-900">Potential Matches:</h4>
                            @foreach($potentialHeadOfHouseholdDuplicates as $duplicate)
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <div class="flex justify-between items-start mb-3">
                                        <div>
                                            <div class="flex items-center space-x-2">
                                                <h5 class="font-medium text-gray-900">{{ $duplicate['participant']['first_name'] }} {{ $duplicate['participant']['last_name'] }}</h5>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                    {{ round($duplicate['confidence_score']) }}% match
                                                </span>
                                            </div>
                                            <p class="text-sm text-gray-500">Household: {{ $duplicate['participant']['household']['household_code'] ?? 'N/A' }}</p>
                                        </div>
                                        <button wire:click="viewExistingParticipant({{ $duplicate['participant']['id'] }})" 
                                                class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                            View Details
                                        </button>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm mb-3">
                                        <div>
                                            <span class="font-medium text-gray-700">Date of Birth:</span>
                                            <p class="text-gray-900">{{ $duplicate['participant']['date_of_birth'] ? \Carbon\Carbon::parse($duplicate['participant']['date_of_birth'])->format('M j, Y') : 'Not provided' }}</p>
                                        </div>
                                        <div>
                                            <span class="font-medium text-gray-700">Gender:</span>
                                            <p class="text-gray-900">{{ $duplicate['participant']['gender'] ?? 'Not provided' }}</p>
                                        </div>
                                        <div>
                                            <span class="font-medium text-gray-700">Phone:</span>
                                            <p class="text-gray-900">{{ $duplicate['participant']['telephone'] ?? 'Not provided' }}</p>
                                        </div>
                                    </div>

                                    @if(!empty($duplicate['matching_criteria']))
                                        <div class="mt-3">
                                            <span class="text-sm font-medium text-gray-700">Matching criteria:</span>
                                            <ul class="mt-1 text-sm text-gray-600">
                                                @foreach($duplicate['matching_criteria'] as $criterion)
                                                    <li class="flex items-center">
                                                        <svg class="w-4 h-4 text-green-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                        </svg>
                                                        {{ $criterion }}
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
                
                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-end space-x-3">
                    <button wire:click="cancelHeadOfHouseholdDuplicateCreation" 
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                        Review & Revise
                    </button>
                    <button wire:click="proceedWithHeadOfHouseholdDuplicate" 
                            class="px-4 py-2 text-sm font-medium text-white bg-yellow-600 border border-transparent rounded-md hover:bg-yellow-700">
                        Create New Household Anyway
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>