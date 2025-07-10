<div class="max-w-7xl mx-auto p-6">
    <div class="bg-white dark:bg-zinc-800 shadow-lg rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">{{ $service->name }}</h1>
                    <p class="text-blue-100 mt-2">Service Details</p>
                </div>
                <div class="flex space-x-3">
                    @if ($this->can('services.update'))
                        <button wire:click="editService" 
                                class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                            Edit Service
                        </button>
                    @endif
                    <a href="{{ route('service.management') }}" 
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
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Service Name</label>
                        <p class="text-gray-900 dark:text-white">{{ $service->name }}</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Program</label>
                        <p class="text-gray-900 dark:text-white">
                            @if($service->program)
                                <a href="{{ route('program.show', $service->program_id) }}" 
                                   class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                    {{ $service->program->name }}
                                </a>
                            @else
                                <span class="text-gray-500">No program assigned</span>
                            @endif
                        </p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Status</label>
                        <p>
                            @if($service->is_active)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                    Active
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100">
                                    Inactive
                                </span>
                            @endif
                        </p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Service Code</label>
                        <p class="text-gray-900 dark:text-white">{{ $service->service_code ?: 'Not specified' }}</p>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Description</label>
                        <p class="text-gray-900 dark:text-white">{{ $service->description ?: 'No description provided' }}</p>
                    </div>
                </div>
            </div>

            <!-- Service Configuration -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-zinc-700 pb-2">
                    Service Configuration
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Duration Type</label>
                        <p class="text-gray-900 dark:text-white">{{ ucfirst($service->duration_type ?: 'Not specified') }}</p>
                    </div>
                    
                    @if($service->default_duration_days)
                        <div>
                            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Default Duration</label>
                            <p class="text-gray-900 dark:text-white">{{ $service->default_duration_days }} days</p>
                        </div>
                    @endif
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Requires Enrollment</label>
                        <p class="text-gray-900 dark:text-white">{{ $service->requires_enrollment ? 'Yes' : 'No' }}</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Tracks Sessions</label>
                        <p class="text-gray-900 dark:text-white">{{ $service->tracks_sessions ? 'Yes' : 'No' }}</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Tracks Hours</label>
                        <p class="text-gray-900 dark:text-white">{{ $service->tracks_hours ? 'Yes' : 'No' }}</p>
                    </div>
                    
                    @if($service->max_sessions)
                        <div>
                            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Maximum Sessions</label>
                            <p class="text-gray-900 dark:text-white">{{ $service->max_sessions }}</p>
                        </div>
                    @endif
                    
                    @if($service->max_hours)
                        <div>
                            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Maximum Hours</label>
                            <p class="text-gray-900 dark:text-white">{{ $service->max_hours }}</p>
                        </div>
                    @endif
                    
                    @if($service->eligibility_criteria)
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Eligibility Criteria</label>
                            <p class="text-gray-900 dark:text-white">{{ $service->eligibility_criteria }}</p>
                        </div>
                    @endif
                    
                    @if($service->outcome_metrics && count($service->outcome_metrics) > 0)
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Outcome Metrics</label>
                            <ul class="list-disc list-inside text-gray-900 dark:text-white">
                                @foreach($service->outcome_metrics as $metric)
                                    <li>{{ $metric }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Recent Enrollments -->
            <div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-zinc-700 pb-2">
                    Recent Service Enrollments
                </h2>
                @if($service->serviceEnrollments->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Participant
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Enrollment Date
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Completion Date
                                    </th>
                                    @if($service->tracks_sessions)
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Sessions
                                        </th>
                                    @endif
                                    @if($service->tracks_hours)
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Hours
                                        </th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                                @foreach($service->serviceEnrollments as $enrollment)
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                            <a href="{{ route('participant.show', $enrollment->participant_id) }}" 
                                               class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                                {{ $enrollment->participant->full_name ?? 'Unknown' }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                            {{ $enrollment->enrollment_date ? \Carbon\Carbon::parse($enrollment->enrollment_date)->format('M j, Y') : 'N/A' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            @if(in_array($enrollment->status, ['enrolled', 'in_progress']))
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                                    {{ ucfirst(str_replace('_', ' ', $enrollment->status)) }}
                                                </span>
                                            @elseif($enrollment->status === 'completed')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                                                    Completed
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100">
                                                    {{ ucfirst($enrollment->status) }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                            {{ $enrollment->completion_date ? \Carbon\Carbon::parse($enrollment->completion_date)->format('M j, Y') : '-' }}
                                        </td>
                                        @if($service->tracks_sessions)
                                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                                {{ $enrollment->sessions_attended ?? 0 }}{{ $service->max_sessions ? ' / ' . $service->max_sessions : '' }}
                                            </td>
                                        @endif
                                        @if($service->tracks_hours)
                                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                                {{ $enrollment->hours_received ?? 0 }}{{ $service->max_hours ? ' / ' . $service->max_hours : '' }}
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                        Showing last 10 enrollments. View enrollment management for complete list.
                    </p>
                @else
                    <p class="text-gray-500 dark:text-gray-400">No enrollments recorded for this service.</p>
                @endif
            </div>
        </div>
    </div>
</div>