<div class="max-w-7xl mx-auto p-6">
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
            @if($canViewSensitiveData && $programEnrollments->count() > 0)
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-zinc-700 pb-2">
                    Program Enrollments
                </h2>
                <div class="space-y-4">
                    @foreach($programEnrollments as $enrollment)
                        <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="font-medium text-gray-900 dark:text-white">{{ $enrollment->program->name }}</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        Enrolled: {{ $enrollment->enrollment_date ? \Carbon\Carbon::parse($enrollment->enrollment_date)->format('F j, Y') : 'Not specified' }}
                                    </p>
                                    @if($enrollment->notes)
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            {{ $enrollment->notes }}
                                        </p>
                                    @endif
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="px-2 py-1 text-xs rounded-full {{ $enrollment->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' }}">
                                        {{ ucfirst($enrollment->status) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Service Enrollments (Sensitive - Requires Program Access) -->
            @if($canViewSensitiveData && $serviceEnrollments->count() > 0)
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-zinc-700 pb-2">
                    Service Enrollments
                </h2>
                <div class="space-y-4">
                    @foreach($serviceEnrollments as $enrollment)
                        <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <h3 class="font-medium text-gray-900 dark:text-white">{{ $enrollment->service->name }}</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        Enrollment Date: {{ $enrollment->enrollment_date ? \Carbon\Carbon::parse($enrollment->enrollment_date)->format('F j, Y') : 'Not specified' }}
                                    </p>
                                    @if($enrollment->notes)
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            {{ $enrollment->notes }}
                                        </p>
                                    @endif
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="px-2 py-1 text-xs rounded-full {{ in_array($enrollment->status, ['enrolled', 'in_progress']) ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' }}">
                                        {{ ucfirst($enrollment->status) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
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
</div>