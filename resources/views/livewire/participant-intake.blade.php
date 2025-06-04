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

            <!-- Step 3: Demographics -->
            @if ($step === 3)
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

            <!-- Step 4: Financial Information -->
            @if ($step === 4)
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

            <!-- Step 5: Program Enrollment -->
            @if ($step === 5)
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
</div>