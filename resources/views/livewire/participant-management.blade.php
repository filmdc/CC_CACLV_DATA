<div class="w-full px-0 py-6">
    <div class="bg-white dark:bg-zinc-800 shadow-lg rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-blue-600 text-white p-6">
            <h1 class="text-2xl font-bold">Participant Management</h1>
            <p class="text-blue-100 mt-2">Manage participant records and demographics</p>
            @if(!auth()->user()->isAdmin())
                <div class="mt-3 text-sm text-blue-100 flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    All participants visible - full details available for your assigned programs only
                </div>
            @endif
        </div>

        <!-- Search -->
        @if(!$showEditForm)
            <div class="p-4 bg-gray-50 dark:bg-zinc-700 border-b border-gray-200 dark:border-zinc-600">
                <div class="flex items-center justify-between">
                    <div class="flex-1 max-w-md">
                        <input type="text" wire:model.live="search" placeholder="Search participants..." 
                               class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
            </div>
        @endif

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

        <!-- Edit Form -->
        @if($showEditForm && $selectedParticipant)
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Edit Participant: {{ $selectedParticipant->full_name }}</h3>
                    <button wire:click="$set('showEditForm', false)" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form wire:submit="updateParticipant" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="first_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                First Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" wire:model="first_name" id="first_name" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('first_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="middle_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Middle Name</label>
                            <input type="text" wire:model="middle_name" id="middle_name" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('middle_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="last_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Last Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" wire:model="last_name" id="last_name" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('last_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="suffix" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Suffix</label>
                            <input type="text" wire:model="suffix" id="suffix" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('suffix') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="date_of_birth" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Date of Birth <span class="text-red-500">*</span>
                            </label>
                            <input type="date" wire:model.blur="date_of_birth" id="date_of_birth" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('date_of_birth') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="gender" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Gender <span class="text-red-500">*</span>
                            </label>
                            <select wire:model="gender" id="gender" 
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Gender</option>
                                @foreach($genderOptions as $option)
                                    <option value="{{ $option->value }}">{{ $option->label }}</option>
                                @endforeach
                            </select>
                            @error('gender') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="telephone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Phone Number</label>
                            <input type="tel" wire:model="telephone" id="telephone" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('telephone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email Address</label>
                            <input type="email" wire:model="email" id="email" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="education_level" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Education Level</label>
                            <select wire:model="education_level" id="education_level" 
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Education Level</option>
                                @foreach($educationLevels as $option)
                                    <option value="{{ $option->value }}">{{ $option->label }}</option>
                                @endforeach
                            </select>
                            @error('education_level') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="ethnicity" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ethnicity</label>
                            <select wire:model="ethnicity" id="ethnicity" 
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Ethnicity</option>
                                @foreach($ethnicityOptions as $option)
                                    <option value="{{ $option->value }}">{{ $option->label }}</option>
                                @endforeach
                            </select>
                            @error('ethnicity') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="race" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Race (Select all that apply)</label>
                            <select wire:model="race" id="race" multiple
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 min-h-[100px]">
                                @foreach($raceOptions as $option)
                                    <option value="{{ $option->value }}">{{ $option->label }}</option>
                                @endforeach
                            </select>
                            @error('race') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Hold Ctrl (Cmd on Mac) to select multiple options</p>
                        </div>

                        <div>
                            <label for="military_status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Military Status</label>
                            <select wire:model="military_status" id="military_status" 
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Status</option>
                                @foreach($militaryStatuses as $option)
                                    <option value="{{ $option->value }}">{{ $option->label }}</option>
                                @endforeach
                            </select>
                            @error('military_status') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="disability_status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Disability Status</label>
                            <select wire:model="disability_status" id="disability_status" 
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Status</option>
                                @foreach($disabilityStatuses as $option)
                                    <option value="{{ $option->value }}">{{ $option->label }}</option>
                                @endforeach
                            </select>
                            @error('disability_status') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="health_insurance_status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Health Insurance Status</label>
                            <select wire:model="health_insurance_status" id="health_insurance_status" 
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Status</option>
                                @foreach($healthInsuranceStatuses as $option)
                                    <option value="{{ $option->value }}">{{ $option->label }}</option>
                                @endforeach
                            </select>
                            @error('health_insurance_status') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="work_status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Work Status</label>
                            <select wire:model="work_status" id="work_status" 
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Status</option>
                                @foreach($workStatuses as $option)
                                    <option value="{{ $option->value }}">{{ $option->label }}</option>
                                @endforeach
                            </select>
                            @error('work_status') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="flex justify-between items-center pt-4">
                        <button type="button" wire:click="toggleEnrollmentSection" 
                                class="px-4 py-2 text-sm font-medium text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-600 rounded-md hover:bg-indigo-100 dark:hover:bg-indigo-900/40 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            {{ $showEnrollmentSection ? 'Hide' : 'Show' }} Enrollments
                        </button>
                        
                        <div class="flex space-x-3">
                            <button type="button" wire:click="$set('showEditForm', false)" 
                                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-zinc-700 border border-gray-300 dark:border-zinc-600 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-600 focus:outline-none focus:ring-2 focus:ring-gray-500">
                                Cancel
                            </button>
                            <button type="submit" 
                                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                Update Participant
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Enrollment Management Section -->
                @if($showEnrollmentSection)
                    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-zinc-600">
                        <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Enrollment Management</h4>
                        
                        <!-- Current Enrollments -->
                        <div class="mb-6">
                            <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Current Enrollments</h5>
                            
                            @if($selectedParticipant && $selectedParticipant->programEnrollments->where('status', 'active')->count() > 0)
                                <div class="space-y-2">
                                    @foreach($selectedParticipant->programEnrollments->where('status', 'active') as $enrollment)
                                        <div class="flex items-center justify-between p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-600 rounded-lg">
                                            <div>
                                                <div class="text-sm font-medium text-green-800 dark:text-green-200">
                                                    {{ $enrollment->program->name }}
                                                </div>
                                                <div class="text-xs text-green-600 dark:text-green-400">
                                                    Enrolled: {{ $enrollment->enrollment_date->format('M d, Y') }}
                                                    ({{ $enrollment->days_enrolled }} days)
                                                </div>
                                            </div>
                                            <div class="text-xs text-green-600 dark:text-green-400">
                                                Active Services: {{ $enrollment->serviceEnrollments->where('status', 'enrolled')->count() }}
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-gray-500 dark:text-gray-400">No active program enrollments.</p>
                            @endif
                        </div>

                        <!-- New Program Enrollment -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Program Enrollment Form -->
                            <div class="bg-gray-50 dark:bg-zinc-700/50 p-4 rounded-lg">
                                <h6 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Enroll in Program</h6>
                                
                                <div class="space-y-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Program</label>
                                        <select wire:model.live="program_id" 
                                                class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white">
                                            <option value="">Select Program</option>
                                            @foreach($programs as $program)
                                                <option value="{{ $program->id }}">{{ $program->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('program_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Enrollment Date</label>
                                        <input type="date" wire:model="enrollment_date" 
                                               class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white">
                                        @error('enrollment_date') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Notes</label>
                                        <textarea wire:model="enrollment_notes" rows="2"
                                                  class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"></textarea>
                                    </div>

                                    <button type="button" wire:click="createProgramEnrollment" 
                                            class="w-full px-3 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                                        Enroll in Program
                                    </button>
                                </div>
                            </div>

                            <!-- Service Enrollment Form -->
                            <div class="bg-gray-50 dark:bg-zinc-700/50 p-4 rounded-lg">
                                <h6 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Record Service</h6>
                                
                                <div class="space-y-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Program</label>
                                        <select wire:model.live="program_id" 
                                                class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white">
                                            <option value="">Select Program First</option>
                                            @foreach($programs as $program)
                                                <option value="{{ $program->id }}">{{ $program->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Service</label>
                                        <select wire:model="service_id" 
                                                class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white">
                                            <option value="">Select Service</option>
                                            @foreach($services as $service)
                                                <option value="{{ $service->id }}">{{ $service->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('service_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Service Date</label>
                                        <input type="date" wire:model="service_enrollment_date" 
                                               class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white">
                                        @error('service_enrollment_date') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Service Notes</label>
                                        <textarea wire:model="service_notes" rows="2"
                                                  class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"></textarea>
                                    </div>

                                    <button type="button" wire:click="createServiceEnrollment" 
                                            class="w-full px-3 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                        Record Service
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endif

        <!-- Participants Table -->
        @if(!$showEditForm)
            <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-600">
                <thead class="bg-gray-50 dark:bg-zinc-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Household</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Age</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Gender</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Contact</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-600">
                    @forelse($participants as $participant)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    <a href="{{ route('participant.show', $participant->id) }}"
                                       wire:navigate
                                       class="text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 hover:underline cursor-pointer transition-colors duration-200">
                                        <div class="text-sm font-medium">{{ $participant->full_name }}</div>
                                    </a>
                                    @if($participant->email)
                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $participant->email }}</div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $participant->household->household_code ?? 'N/A' }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $participant->household->household_type ?? 'N/A' }}</div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $participant->age ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $participant->gender ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm">
                                    @if($participant->telephone)
                                        <div>{{ $participant->telephone }}</div>
                                    @endif
                                    @if($participant->email)
                                        <div class="text-gray-500 dark:text-gray-400">{{ $participant->email }}</div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($participant->is_head_of_household)
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">Head of Household</span>
                                @else
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">Family Member</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2 items-center">
                                    @if($this->can('participants.edit'))
                                        @if($this->canEditParticipant($participant->id))
                                            <button wire:click="showEditParticipant({{ $participant->id }})"
                                                    class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300">
                                                Edit
                                            </button>
                                        @else
                                            <span class="text-gray-400 dark:text-gray-500 cursor-not-allowed"
                                                  title="You need program access to edit this participant">
                                                Edit
                                            </span>
                                        @endif
                                    @endif
                                    @if(!$participant->is_head_of_household && $this->can('participants.delete'))
                                        @if($this->canEditParticipant($participant->id))
                                            <button wire:click="deleteParticipant({{ $participant->id }})"
                                                    onclick="return confirm('Are you sure you want to delete this participant?')"
                                                    class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">
                                                Delete
                                            </button>
                                        @else
                                            <span class="text-gray-400 dark:text-gray-500 cursor-not-allowed"
                                                  title="You need program access to delete this participant">
                                                Delete
                                            </span>
                                        @endif
                                    @endif
                                    @if(!$this->canViewParticipantSensitiveData($participant->id) && !auth()->user()->isAdmin())
                                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400"
                                              title="You can view basic contact information only">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                                            </svg>
                                            Limited
                                        </span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                No participants found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>

            <!-- Pagination -->
            @if($participants->hasPages())
                <div class="px-6 py-3 border-t border-gray-200 dark:border-zinc-600">
                    {{ $participants->links() }}
                </div>
            @endif
        @endif
    </div>

    <!-- View Participant Modal -->
    @if($showViewModal && $selectedParticipant)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50" wire:click.self="$set('showViewModal', false)">
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">Participant Details</h3>
                    <button wire:click="$set('showViewModal', false)" class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Personal Information -->
                    <div class="bg-gray-50 dark:bg-zinc-700 p-4 rounded-lg">
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Personal Information</h4>
                        <div class="space-y-3">
                            <div>
                                <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Full Name</label>
                                <p class="text-sm text-gray-900 dark:text-white">{{ $selectedParticipant->full_name }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Date of Birth</label>
                                <p class="text-sm text-gray-900 dark:text-white">{{ $selectedParticipant->date_of_birth ? $selectedParticipant->date_of_birth->format('F j, Y') : 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Age</label>
                                <p class="text-sm text-gray-900 dark:text-white">{{ $selectedParticipant->age ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Gender</label>
                                <p class="text-sm text-gray-900 dark:text-white">{{ $selectedParticipant->gender ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Ethnicity</label>
                                <p class="text-sm text-gray-900 dark:text-white">{{ $selectedParticipant->ethnicity ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Race</label>
                                <p class="text-sm text-gray-900 dark:text-white">
                                    @if($selectedParticipant->race)
                                        {{ is_array($selectedParticipant->race) ? implode(', ', $selectedParticipant->race) : $selectedParticipant->race }}
                                    @else
                                        N/A
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="bg-gray-50 dark:bg-zinc-700 p-4 rounded-lg">
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Contact Information</h4>
                        <div class="space-y-3">
                            <div>
                                <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Phone Number</label>
                                <p class="text-sm text-gray-900 dark:text-white">{{ $selectedParticipant->telephone ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Email Address</label>
                                <p class="text-sm text-gray-900 dark:text-white">{{ $selectedParticipant->email ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Household</label>
                                <p class="text-sm text-gray-900 dark:text-white">{{ $selectedParticipant->household->household_code ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Household Role</label>
                                <p class="text-sm text-gray-900 dark:text-white">
                                    @if($selectedParticipant->is_head_of_household)
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">Head of Household</span>
                                    @else
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">Family Member</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Information -->
                    <div class="bg-gray-50 dark:bg-zinc-700 p-4 rounded-lg">
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Additional Information</h4>
                        <div class="space-y-3">
                            <div>
                                <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Education Level</label>
                                <p class="text-sm text-gray-900 dark:text-white">{{ $selectedParticipant->education_level ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Work Status</label>
                                <p class="text-sm text-gray-900 dark:text-white">{{ $selectedParticipant->work_status ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Military Status</label>
                                <p class="text-sm text-gray-900 dark:text-white">{{ $selectedParticipant->military_status ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Disability Status</label>
                                <p class="text-sm text-gray-900 dark:text-white">{{ $selectedParticipant->disability_status ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Health Insurance</label>
                                <p class="text-sm text-gray-900 dark:text-white">{{ $selectedParticipant->health_insurance_status ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Enrollment Information -->
                    <div class="bg-gray-50 dark:bg-zinc-700 p-4 rounded-lg">
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Enrollment Information</h4>
                        <div class="space-y-4">
                            <div>
                                <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Program Enrollments</label>
                                @if($selectedParticipant->programEnrollments && $selectedParticipant->programEnrollments->count() > 0)
                                    <div class="mt-2 space-y-2">
                                        @foreach($selectedParticipant->programEnrollments as $enrollment)
                                            <div class="flex items-center justify-between p-2 bg-white dark:bg-zinc-800 rounded border">
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $enrollment->program->name }}</div>
                                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                                        Enrolled: {{ $enrollment->enrollment_date->format('M j, Y') }}
                                                    </div>
                                                </div>
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                                    {{ $enrollment->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' }}">
                                                    {{ ucfirst($enrollment->status) }}
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-sm text-gray-500 dark:text-gray-400">No program enrollments</p>
                                @endif
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Service Enrollments</label>
                                @if($selectedParticipant->serviceEnrollments && $selectedParticipant->serviceEnrollments->count() > 0)
                                    <div class="mt-2 space-y-2">
                                        @foreach($selectedParticipant->serviceEnrollments as $enrollment)
                                            <div class="flex items-center justify-between p-2 bg-white dark:bg-zinc-800 rounded border">
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $enrollment->service->name }}</div>
                                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                                        Program: {{ $enrollment->programEnrollment->program->name ?? 'N/A' }}<br>
                                                        Enrolled: {{ $enrollment->enrollment_date->format('M j, Y') }}
                                                    </div>
                                                </div>
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                                    {{ $enrollment->status === 'enrolled' || $enrollment->status === 'in_progress' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 
                                                       ($enrollment->status === 'completed' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200') }}">
                                                    {{ ucfirst(str_replace('_', ' ', $enrollment->status)) }}
                                                </span>
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