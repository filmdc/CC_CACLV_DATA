<div class="max-w-7xl mx-auto p-6">
    <div class="bg-white dark:bg-zinc-800 shadow-lg rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">
                        {{ $enrollmentType === 'program' ? 'Program Enrollment' : 'Service Enrollment' }} Details
                    </h1>
                    <p class="text-blue-100 mt-2">
                        @if($enrollmentType === 'program')
                            {{ $enrollment->participant->full_name }} - {{ $enrollment->program->name }}
                        @else
                            {{ $enrollment->participant->full_name }} - {{ $enrollment->service->name }}
                        @endif
                    </p>
                </div>
                <div class="flex space-x-3">
                    @if ($this->can('enrollments.update'))
                        <button wire:click="editEnrollment" 
                                class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                            Edit Enrollment
                        </button>
                    @endif
                    <a href="{{ route('enrollment.management') }}" 
                       class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                        Back to List
                    </a>
                </div>
            </div>
        </div>

        <div class="p-6">
            <!-- Participant Information -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-zinc-700 pb-2">
                    Participant Information
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Participant Name</label>
                        <p class="text-gray-900 dark:text-white">
                            <a href="{{ route('participant.show', $enrollment->participant->id) }}" 
                               class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                {{ $enrollment->participant->full_name }}
                            </a>
                        </p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Household</label>
                        <p class="text-gray-900 dark:text-white">
                            @if($enrollment->participant->household)
                                <a href="{{ route('household.show', $enrollment->participant->household->id) }}" 
                                   class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                    {{ $enrollment->participant->household->household_code }}
                                </a>
                            @else
                                Not assigned
                            @endif
                        </p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Age</label>
                        <p class="text-gray-900 dark:text-white">{{ $enrollment->participant->age ?? 'N/A' }}</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Gender</label>
                        <p class="text-gray-900 dark:text-white">{{ $enrollment->participant->gender ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>

            @if($enrollmentType === 'program')
                <!-- Program Enrollment Details -->
                <div class="mb-8">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-zinc-700 pb-2">
                        Program Enrollment Details
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Program</label>
                            <p class="text-gray-900 dark:text-white">
                                <a href="{{ route('program.show', $enrollment->program->id) }}" 
                                   class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                    {{ $enrollment->program->name }}
                                </a>
                            </p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Status</label>
                            <p>
                                @if($enrollment->status === 'active')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                        Active
                                    </span>
                                @elseif($enrollment->status === 'completed')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                                        Completed
                                    </span>
                                @elseif($enrollment->status === 'withdrawn')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100">
                                        Withdrawn
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100">
                                        {{ ucfirst($enrollment->status) }}
                                    </span>
                                @endif
                            </p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Enrollment Date</label>
                            <p class="text-gray-900 dark:text-white">
                                {{ $enrollment->enrollment_date ? \Carbon\Carbon::parse($enrollment->enrollment_date)->format('M j, Y') : 'N/A' }}
                            </p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Days Enrolled</label>
                            <p class="text-gray-900 dark:text-white">{{ $enrollment->days_enrolled ?? 'N/A' }}</p>
                        </div>
                        
                        @if($enrollment->exit_date)
                            <div>
                                <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Exit Date</label>
                                <p class="text-gray-900 dark:text-white">
                                    {{ \Carbon\Carbon::parse($enrollment->exit_date)->format('M j, Y') }}
                                </p>
                            </div>
                            
                            @if($enrollment->exit_reason)
                                <div>
                                    <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Exit Reason</label>
                                    <p class="text-gray-900 dark:text-white">{{ $enrollment->exit_reason }}</p>
                                </div>
                            @endif
                        @endif
                        
                        @if($enrollment->notes)
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Notes</label>
                                <p class="text-gray-900 dark:text-white whitespace-pre-line">{{ $enrollment->notes }}</p>
                            </div>
                        @endif
                        
                        @if($enrollment->exit_notes)
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Exit Notes</label>
                                <p class="text-gray-900 dark:text-white whitespace-pre-line">{{ $enrollment->exit_notes }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Service Enrollments -->
                <div>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-zinc-700 pb-2">
                        Service Enrollments ({{ $enrollment->serviceEnrollments->count() }})
                    </h2>
                    @if($enrollment->serviceEnrollments->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Service
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Enrollment Date
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Hours
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Sessions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                                    @foreach($enrollment->serviceEnrollments as $serviceEnrollment)
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                                <a href="{{ route('service.show', $serviceEnrollment->service->id) }}" 
                                                   class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                                    {{ $serviceEnrollment->service->name }}
                                                </a>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                                {{ $serviceEnrollment->enrollment_date ? \Carbon\Carbon::parse($serviceEnrollment->enrollment_date)->format('M j, Y') : 'N/A' }}
                                            </td>
                                            <td class="px-4 py-3 text-sm">
                                                @if($serviceEnrollment->status === 'enrolled')
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                                                        Enrolled
                                                    </span>
                                                @elseif($serviceEnrollment->status === 'in_progress')
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100">
                                                        In Progress
                                                    </span>
                                                @elseif($serviceEnrollment->status === 'completed')
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                                        Completed
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100">
                                                        {{ ucfirst($serviceEnrollment->status) }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                                {{ $serviceEnrollment->hours_received ?: '-' }}
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                                {{ $serviceEnrollment->sessions_attended ?: '-' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-gray-500 dark:text-gray-400">No service enrollments for this program enrollment.</p>
                    @endif
                </div>
            @else
                <!-- Service Enrollment Details -->
                <div class="mb-8">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-zinc-700 pb-2">
                        Service Enrollment Details
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Service</label>
                            <p class="text-gray-900 dark:text-white">
                                <a href="{{ route('service.show', $enrollment->service->id) }}" 
                                   class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                    {{ $enrollment->service->name }}
                                </a>
                            </p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Program</label>
                            <p class="text-gray-900 dark:text-white">
                                @if($enrollment->service->program)
                                    <a href="{{ route('program.show', $enrollment->service->program->id) }}" 
                                       class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                        {{ $enrollment->service->program->name }}
                                    </a>
                                @else
                                    N/A
                                @endif
                            </p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Status</label>
                            <p>
                                @if($enrollment->status === 'enrolled')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                                        Enrolled
                                    </span>
                                @elseif($enrollment->status === 'in_progress')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100">
                                        In Progress
                                    </span>
                                @elseif($enrollment->status === 'completed')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                        Completed
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100">
                                        {{ ucfirst($enrollment->status) }}
                                    </span>
                                @endif
                            </p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Enrollment Date</label>
                            <p class="text-gray-900 dark:text-white">
                                {{ $enrollment->enrollment_date ? \Carbon\Carbon::parse($enrollment->enrollment_date)->format('M j, Y') : 'N/A' }}
                            </p>
                        </div>
                        
                        @if($enrollment->completion_date)
                            <div>
                                <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Completion Date</label>
                                <p class="text-gray-900 dark:text-white">
                                    {{ \Carbon\Carbon::parse($enrollment->completion_date)->format('M j, Y') }}
                                </p>
                            </div>
                        @endif
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Hours Received</label>
                            <p class="text-gray-900 dark:text-white">{{ $enrollment->hours_received ?: 'N/A' }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Sessions Attended</label>
                            <p class="text-gray-900 dark:text-white">{{ $enrollment->sessions_attended ?: 'N/A' }}</p>
                        </div>
                        
                        @if($enrollment->programEnrollment)
                            <div>
                                <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Parent Program Enrollment</label>
                                <p class="text-gray-900 dark:text-white">
                                    <a href="{{ route('enrollment.show', ['type' => 'program', 'id' => $enrollment->programEnrollment->id]) }}" 
                                       class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                        {{ $enrollment->programEnrollment->program->name }}
                                    </a>
                                </p>
                            </div>
                        @endif
                        
                        @if($enrollment->outcomes && count($enrollment->outcomes) > 0)
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Outcomes</label>
                                <ul class="list-disc list-inside text-gray-900 dark:text-white">
                                    @foreach($enrollment->outcomes as $outcome)
                                        <li>{{ $outcome }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        
                        @if($enrollment->notes)
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Notes</label>
                                <p class="text-gray-900 dark:text-white whitespace-pre-line">{{ $enrollment->notes }}</p>
                            </div>
                        @endif
                        
                        @if($enrollment->completion_notes)
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Completion Notes</label>
                                <p class="text-gray-900 dark:text-white whitespace-pre-line">{{ $enrollment->completion_notes }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>