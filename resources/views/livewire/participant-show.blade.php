<div class="w-full px-0 py-6">
    <div class="bg-white dark:bg-zinc-800 shadow-lg rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">{{ $participant->full_name }}</h1>
                    <p class="text-blue-100 mt-2">Participant Details</p>
                </div>
                <div class="flex space-x-3">
                    @if($canViewSensitiveData)
                        <button wire:click="editParticipant"
                                class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                            Edit Participant
                        </button>
                    @else
                        <button disabled
                                class="bg-white/10 text-white/50 px-4 py-2 rounded-lg font-medium cursor-not-allowed"
                                title="You need program access to edit this participant">
                            Edit Participant
                        </button>
                    @endif
                    <a href="{{ route('participant.management') }}"
                       class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                        Back to List
                    </a>
                </div>
            </div>
        </div>

        <div class="p-6">
            <!-- Flash Messages -->
            @if (session()->has('success'))
                <div class="mb-4 rounded-md bg-green-50 dark:bg-green-900/30 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            @if (session()->has('error'))
                <div class="mb-4 rounded-md bg-red-50 dark:bg-red-900/30 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-red-800 dark:text-red-200">{{ session('error') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Access Level Indicator -->
            @if(!$canViewSensitiveData)
            <div class="mb-6 bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400 p-4 rounded-r-lg">
                <div class="flex items-start">
                    <svg class="h-5 w-5 text-yellow-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">Limited Access View</h3>
                        <p class="mt-1 text-sm text-yellow-700 dark:text-yellow-300">
                            You are viewing public information only. To access full participant details and enrollment data, you must be assigned to one of this participant's programs.
                        </p>
                    </div>
                </div>
            </div>
            @else
            <div class="mb-6 bg-green-50 dark:bg-green-900/20 border-l-4 border-green-400 p-4 rounded-r-lg">
                <div class="flex items-start">
                    <svg class="h-5 w-5 text-green-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-green-800 dark:text-green-200">Full Access</h3>
                        <p class="mt-1 text-sm text-green-700 dark:text-green-300">
                            You have full access to this participant's information and enrollment data.
                        </p>
                    </div>
                </div>
            </div>
            @endif

            <!-- Basic Information -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-zinc-700 pb-2">
                    Basic Information
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Full Name</label>
                        <p class="text-gray-900 dark:text-white">{{ $participant->full_name }}</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Date of Birth</label>
                        <p class="text-gray-900 dark:text-white">
                            {{ $participant->date_of_birth ? \Carbon\Carbon::parse($participant->date_of_birth)->format('F j, Y') : 'Not provided' }}
                            @if($participant->date_of_birth)
                                <span class="text-gray-500 dark:text-gray-400 text-sm">
                                    ({{ \Carbon\Carbon::parse($participant->date_of_birth)->age }} years old)
                                </span>
                            @endif
                        </p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Gender</label>
                        <p class="text-gray-900 dark:text-white">
                            {{ $referenceData['genders']->firstWhere('value', $participant->gender)?->label ?? 'Not specified' }}
                        </p>
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
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Phone Number</label>
                        <p class="text-gray-900 dark:text-white">{{ $participant->telephone ?: 'Not provided' }}</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Email Address</label>
                        <p class="text-gray-900 dark:text-white">{{ $participant->email ?: 'Not provided' }}</p>
                    </div>
                </div>
            </div>

            <!-- Household Information -->
            @if($participant->household)
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-zinc-700 pb-2">
                    Household Information
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Household Code</label>
                        <p class="text-gray-900 dark:text-white font-mono">{{ $participant->household->household_code }}</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Head of Household</label>
                        <p class="text-gray-900 dark:text-white">
                            {{ $participant->household->headOfHousehold?->full_name ?? 'Not assigned' }}
                        </p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Household Size</label>
                        <p class="text-gray-900 dark:text-white">{{ $participant->household->household_size ?? 'Not specified' }}</p>
                    </div>
                </div>
            </div>
            @endif

            <!-- Demographics (Sensitive - Requires Program Access) -->
            @if($canViewSensitiveData)
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-zinc-700 pb-2">
                    Demographics
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Ethnicity</label>
                        <p class="text-gray-900 dark:text-white">
                            {{ $referenceData['ethnicities']->firstWhere('value', $participant->ethnicity)?->label ?? 'Not specified' }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Race</label>
                        <p class="text-gray-900 dark:text-white">
                            @if($participant->race && is_array($participant->race))
                                @foreach($participant->race as $raceValue)
                                    {{ $referenceData['races']->firstWhere('value', $raceValue)?->label }}@if(!$loop->last), @endif
                                @endforeach
                            @else
                                Not specified
                            @endif
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Education Level</label>
                        <p class="text-gray-900 dark:text-white">
                            {{ $referenceData['education_levels']->firstWhere('value', $participant->education_level)?->label ?? 'Not specified' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Health & Status Information (Sensitive - Requires Program Access) -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-zinc-700 pb-2">
                    Health & Status Information
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Disability Status</label>
                        <p class="text-gray-900 dark:text-white">
                            {{ $referenceData['disability_statuses']->firstWhere('value', $participant->disability_status)?->label ?? 'Not specified' }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Health Insurance Status</label>
                        <p class="text-gray-900 dark:text-white">
                            {{ $referenceData['health_insurance_statuses']->firstWhere('value', $participant->health_insurance_status)?->label ?? 'Not specified' }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Military Status</label>
                        <p class="text-gray-900 dark:text-white">
                            {{ $referenceData['military_statuses']->firstWhere('value', $participant->military_status)?->label ?? 'Not specified' }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Work Status</label>
                        <p class="text-gray-900 dark:text-white">
                            {{ $referenceData['work_statuses']->firstWhere('value', $participant->work_status)?->label ?? 'Not specified' }}
                        </p>
                    </div>
                </div>
            </div>
            @else
            <!-- Restricted Access Message for Sensitive Data -->
            <div class="mb-8">
                <div class="bg-gray-50 dark:bg-zinc-700/50 border-2 border-dashed border-gray-300 dark:border-zinc-600 rounded-lg p-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Additional Information Restricted</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 max-w-md mx-auto">
                        Demographic, health, and enrollment information is only accessible to staff assigned to this participant's programs.
                    </p>
                </div>
            </div>
            @endif

            <!-- Program Enrollments (Sensitive - Requires Program Access) -->
            @if($canViewSensitiveData)
            <div class="mb-8">
                <div class="flex items-center justify-between mb-4 border-b border-gray-200 dark:border-zinc-700 pb-2">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                        Program Enrollments
                    </h2>
                    @if($this->can('enrollments.create'))
                        <button wire:click="showCreateProgramEnrollment"
                                class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md transition-colors">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add Enrollment
                        </button>
                    @endif
                </div>
                @if($programEnrollments->count() > 0)
                <div class="space-y-4">
                    @foreach($programEnrollments as $enrollment)
                        <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <h3 class="font-medium text-gray-900 dark:text-white">{{ $enrollment->program->name }}</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        Enrolled: {{ $enrollment->enrollment_date ? \Carbon\Carbon::parse($enrollment->enrollment_date)->format('F j, Y') : 'Not specified' }}
                                        @if($enrollment->exit_date)
                                            <span class="mx-1">|</span>
                                            Exited: {{ \Carbon\Carbon::parse($enrollment->exit_date)->format('F j, Y') }}
                                        @endif
                                    </p>
                                    @if($enrollment->notes)
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 italic">
                                            {{ $enrollment->notes }}
                                        </p>
                                    @endif
                                </div>
                                <div class="flex items-center space-x-3">
                                    <span class="px-2 py-1 text-xs rounded-full {{ $enrollment->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' }}">
                                        {{ ucfirst($enrollment->status) }}
                                    </span>
                                    @if($this->can('enrollments.edit'))
                                        <button wire:click="showEditProgramEnrollment({{ $enrollment->id }})"
                                                class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium">
                                            Edit
                                        </button>
                                    @endif
                                    @if($this->can('enrollments.delete'))
                                        <button wire:click="deleteProgramEnrollment({{ $enrollment->id }})"
                                                wire:confirm="Are you sure you want to delete this program enrollment? This action cannot be undone."
                                                class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 text-sm font-medium">
                                            Delete
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                @else
                <p class="text-gray-500 dark:text-gray-400 text-sm">No program enrollments found.</p>
                @endif
            </div>
            @endif

            <!-- Service Enrollments (Sensitive - Requires Program Access) -->
            @if($canViewSensitiveData)
            <div class="mb-8">
                <div class="flex items-center justify-between mb-4 border-b border-gray-200 dark:border-zinc-700 pb-2">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                        Service Enrollments
                    </h2>
                    @if($this->can('enrollments.create') && $activeProgramEnrollments->count() > 0)
                        <button wire:click="showCreateServiceEnrollment"
                                class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-md transition-colors">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add Service
                        </button>
                    @endif
                </div>
                @if($serviceEnrollments->count() > 0)
                <div class="space-y-4">
                    @foreach($serviceEnrollments as $enrollment)
                        <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <h3 class="font-medium text-gray-900 dark:text-white">{{ $enrollment->service->name }}</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        Enrolled: {{ $enrollment->enrollment_date ? \Carbon\Carbon::parse($enrollment->enrollment_date)->format('F j, Y') : 'Not specified' }}
                                        @if($enrollment->completion_date)
                                            <span class="mx-1">|</span>
                                            Completed: {{ \Carbon\Carbon::parse($enrollment->completion_date)->format('F j, Y') }}
                                        @endif
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                        via {{ $enrollment->programEnrollment?->program?->name ?? 'Unknown Program' }}
                                    </p>
                                    @if($enrollment->hours_received || $enrollment->sessions_attended)
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            @if($enrollment->hours_received)Hours: {{ $enrollment->hours_received }}@endif
                                            @if($enrollment->hours_received && $enrollment->sessions_attended) | @endif
                                            @if($enrollment->sessions_attended)Sessions: {{ $enrollment->sessions_attended }}@endif
                                        </p>
                                    @endif
                                    @if($enrollment->notes)
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 italic">
                                            {{ $enrollment->notes }}
                                        </p>
                                    @endif
                                </div>
                                <div class="flex items-center space-x-3">
                                    <span class="px-2 py-1 text-xs rounded-full {{ in_array($enrollment->status, ['enrolled', 'in_progress']) ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' }}">
                                        {{ ucfirst(str_replace('_', ' ', $enrollment->status)) }}
                                    </span>
                                    @if($this->can('enrollments.edit'))
                                        <button wire:click="showEditServiceEnrollment({{ $enrollment->id }})"
                                                class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium">
                                            Edit
                                        </button>
                                    @endif
                                    @if($this->can('enrollments.delete'))
                                        <button wire:click="deleteServiceEnrollment({{ $enrollment->id }})"
                                                wire:confirm="Are you sure you want to delete this service enrollment? This action cannot be undone."
                                                class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 text-sm font-medium">
                                            Delete
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                @else
                <p class="text-gray-500 dark:text-gray-400 text-sm">No service enrollments found. @if($activeProgramEnrollments->count() == 0)Enroll in a program first to add services.@endif</p>
                @endif
            </div>
            @endif

            <!-- Record Information -->
            <div class="border-t border-gray-200 dark:border-zinc-700 pt-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Record Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Created</label>
                        <p class="text-gray-900 dark:text-white">{{ $participant->created_at->format('F j, Y \a\t g:i A') }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Last Updated</label>
                        <p class="text-gray-900 dark:text-white">{{ $participant->updated_at->format('F j, Y \a\t g:i A') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Program Enrollment Modal -->
    @if($showProgramEnrollmentModal)
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-2xl w-full m-4 max-h-[90vh] overflow-y-auto">
            <!-- Modal Header -->
            <div class="bg-blue-600 text-white p-6 rounded-t-lg">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-semibold">
                        {{ $isEditingProgramEnrollment ? 'Edit Program Enrollment' : 'Add Program Enrollment' }}
                    </h3>
                    <button wire:click="cancelProgramEnrollmentModal" class="text-white hover:text-gray-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Modal Body -->
            <form wire:submit.prevent="saveProgramEnrollment">
                <div class="p-6 space-y-4">
                    <!-- Program Selection -->
                    <div>
                        <label for="program_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Program *</label>
                        <select wire:model="programEnrollmentForm.program_id" id="program_id"
                                class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                {{ $isEditingProgramEnrollment ? 'disabled' : '' }}>
                            <option value="">Select a program...</option>
                            @foreach($programs as $program)
                                <option value="{{ $program->id }}">{{ $program->name }}</option>
                            @endforeach
                        </select>
                        @error('programEnrollmentForm.program_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- Enrollment Date -->
                    <div>
                        <label for="enrollment_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Enrollment Date *</label>
                        <input type="date" wire:model="programEnrollmentForm.enrollment_date" id="enrollment_date"
                               class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('programEnrollmentForm.enrollment_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- Status -->
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status *</label>
                        <select wire:model="programEnrollmentForm.status" id="status"
                                class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="active">Active</option>
                            <option value="completed">Completed</option>
                            <option value="withdrawn">Withdrawn</option>
                            <option value="transferred">Transferred</option>
                        </select>
                        @error('programEnrollmentForm.status') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- Exit Date (shown when not active) -->
                    @if($programEnrollmentForm['status'] !== 'active')
                    <div>
                        <label for="exit_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Exit Date</label>
                        <input type="date" wire:model="programEnrollmentForm.exit_date" id="exit_date"
                               class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('programEnrollmentForm.exit_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- Exit Reason -->
                    <div>
                        <label for="exit_reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Exit Reason</label>
                        <select wire:model="programEnrollmentForm.exit_reason" id="exit_reason"
                                class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Select reason...</option>
                            <option value="completed_successfully">Completed Successfully</option>
                            <option value="moved_out_of_area">Moved Out of Area</option>
                            <option value="no_longer_eligible">No Longer Eligible</option>
                            <option value="non_compliance">Non-Compliance</option>
                            <option value="participant_choice">Participant Choice</option>
                            <option value="other">Other</option>
                        </select>
                        @error('programEnrollmentForm.exit_reason') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- Exit Notes -->
                    <div>
                        <label for="exit_notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Exit Notes</label>
                        <textarea wire:model="programEnrollmentForm.exit_notes" id="exit_notes" rows="2"
                                  class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                        @error('programEnrollmentForm.exit_notes') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    @endif

                    <!-- Notes -->
                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                        <textarea wire:model="programEnrollmentForm.notes" id="notes" rows="3"
                                  class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                        @error('programEnrollmentForm.notes') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="bg-gray-50 dark:bg-zinc-700 px-6 py-4 rounded-b-lg flex justify-end space-x-3">
                    <button type="button" wire:click="cancelProgramEnrollmentModal"
                            class="px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-zinc-600 transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                        {{ $isEditingProgramEnrollment ? 'Update Enrollment' : 'Create Enrollment' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- Service Enrollment Modal -->
    @if($showServiceEnrollmentModal)
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-2xl w-full m-4 max-h-[90vh] overflow-y-auto">
            <!-- Modal Header -->
            <div class="bg-green-600 text-white p-6 rounded-t-lg">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-semibold">
                        {{ $isEditingServiceEnrollment ? 'Edit Service Enrollment' : 'Add Service Enrollment' }}
                    </h3>
                    <button wire:click="cancelServiceEnrollmentModal" class="text-white hover:text-gray-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Modal Body -->
            <form wire:submit.prevent="saveServiceEnrollment">
                <div class="p-6 space-y-4">
                    <!-- Program Enrollment Selection -->
                    <div>
                        <label for="program_enrollment_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Program Enrollment *</label>
                        <select wire:model.live="serviceEnrollmentForm.program_enrollment_id" id="program_enrollment_id"
                                class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500"
                                {{ $isEditingServiceEnrollment ? 'disabled' : '' }}>
                            <option value="">Select a program enrollment...</option>
                            @foreach($activeProgramEnrollments as $programEnrollment)
                                <option value="{{ $programEnrollment->id }}">
                                    {{ $programEnrollment->program->name }} ({{ $programEnrollment->enrollment_date?->format('M j, Y') ?? 'No date' }})
                                </option>
                            @endforeach
                        </select>
                        @error('serviceEnrollmentForm.program_enrollment_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- Service Selection -->
                    <div>
                        <label for="service_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Service *</label>
                        <select wire:model="serviceEnrollmentForm.service_id" id="service_id"
                                class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500"
                                {{ count($availableServices) == 0 ? 'disabled' : '' }}>
                            <option value="">{{ count($availableServices) == 0 ? 'Select a program first...' : 'Select a service...' }}</option>
                            @foreach($availableServices as $service)
                                <option value="{{ $service['id'] }}">{{ $service['name'] }}</option>
                            @endforeach
                        </select>
                        @error('serviceEnrollmentForm.service_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- Enrollment Date -->
                    <div>
                        <label for="service_enrollment_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Enrollment Date *</label>
                        <input type="date" wire:model="serviceEnrollmentForm.enrollment_date" id="service_enrollment_date"
                               class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500">
                        @error('serviceEnrollmentForm.enrollment_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- Status -->
                    <div>
                        <label for="service_status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status *</label>
                        <select wire:model="serviceEnrollmentForm.status" id="service_status"
                                class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500">
                            <option value="enrolled">Enrolled</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                            <option value="withdrawn">Withdrawn</option>
                        </select>
                        @error('serviceEnrollmentForm.status') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- Completion Date (shown when completed) -->
                    @if($serviceEnrollmentForm['status'] === 'completed')
                    <div>
                        <label for="completion_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Completion Date</label>
                        <input type="date" wire:model="serviceEnrollmentForm.completion_date" id="completion_date"
                               class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500">
                        @error('serviceEnrollmentForm.completion_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    @endif

                    <!-- Hours and Sessions (side by side) -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="hours_received" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hours Received</label>
                            <input type="number" step="0.01" min="0" wire:model="serviceEnrollmentForm.hours_received" id="hours_received"
                                   class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500">
                            @error('serviceEnrollmentForm.hours_received') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label for="sessions_attended" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sessions Attended</label>
                            <input type="number" min="0" wire:model="serviceEnrollmentForm.sessions_attended" id="sessions_attended"
                                   class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500">
                            @error('serviceEnrollmentForm.sessions_attended') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Completion Notes (shown when completed) -->
                    @if($serviceEnrollmentForm['status'] === 'completed')
                    <div>
                        <label for="completion_notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Completion Notes</label>
                        <textarea wire:model="serviceEnrollmentForm.completion_notes" id="completion_notes" rows="2"
                                  class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500"></textarea>
                        @error('serviceEnrollmentForm.completion_notes') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    @endif

                    <!-- Notes -->
                    <div>
                        <label for="service_notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                        <textarea wire:model="serviceEnrollmentForm.notes" id="service_notes" rows="3"
                                  class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500"></textarea>
                        @error('serviceEnrollmentForm.notes') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="bg-gray-50 dark:bg-zinc-700 px-6 py-4 rounded-b-lg flex justify-end space-x-3">
                    <button type="button" wire:click="cancelServiceEnrollmentModal"
                            class="px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-zinc-600 transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors">
                        {{ $isEditingServiceEnrollment ? 'Update Service' : 'Add Service' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>