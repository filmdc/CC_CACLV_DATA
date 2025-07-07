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
                    <button wire:click="editParticipant" 
                            class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                        Edit Participant
                    </button>
                    <a href="{{ route('participant.management') }}" 
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

            <!-- Demographics -->
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

            <!-- Health & Status Information -->
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

            <!-- Program Enrollments -->
            @if($participant->programEnrollments->count() > 0)
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-zinc-700 pb-2">
                    Program Enrollments
                </h2>
                <div class="space-y-4">
                    @foreach($participant->programEnrollments as $enrollment)
                        <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="font-medium text-gray-900 dark:text-white">{{ $enrollment->program->name }}</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        Enrolled: {{ $enrollment->enrollment_date ? \Carbon\Carbon::parse($enrollment->enrollment_date)->format('F j, Y') : 'Not specified' }}
                                    </p>
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

            <!-- Service Enrollments -->
            @if($participant->serviceEnrollments->count() > 0)
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-zinc-700 pb-2">
                    Service Enrollments
                </h2>
                <div class="space-y-4">
                    @foreach($participant->serviceEnrollments as $enrollment)
                        <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="font-medium text-gray-900 dark:text-white">{{ $enrollment->service->name }}</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        Service Start: {{ $enrollment->service_start_date ? \Carbon\Carbon::parse($enrollment->service_start_date)->format('F j, Y') : 'Not specified' }}
                                    </p>
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