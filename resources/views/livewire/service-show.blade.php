<div class="w-full px-0 py-6">
    <!-- Flash Messages -->
    @if (session()->has('success'))
        <div class="mb-4 rounded-md bg-green-50 dark:bg-green-900 p-4">
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
        <div class="mb-4 rounded-md bg-red-50 dark:bg-red-900 p-4">
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

    <div class="bg-white dark:bg-zinc-800 shadow-lg rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">{{ $service->name }}</h1>
                    <p class="text-blue-100 mt-2">Service Details</p>
                </div>
                <div class="flex space-x-3">
                    @if ($this->can('enrollments.create'))
                        <button wire:click="showCreateEnrollment"
                                class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                            Enroll Participant
                        </button>
                    @endif
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
                        <div class="text-gray-900 dark:text-white rich-text-content">
                            {!! $service->description ?: '<p>No description provided</p>' !!}
                        </div>
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

            <!-- Service Enrollments -->
            <div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-zinc-700 pb-2">
                    Service Enrollments
                </h2>
                @if($enrollments->count() > 0)
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
                                    @if($this->can('enrollments.edit') || $this->can('enrollments.delete'))
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                                @foreach($enrollments as $enrollment)
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                            <a href="{{ route('participant.show', $enrollment->participant_id) }}"
                                               wire:navigate
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
                                        @if($this->can('enrollments.edit') || $this->can('enrollments.delete'))
                                            <td class="px-4 py-3 text-sm">
                                                <div class="flex space-x-2">
                                                    @if($this->can('enrollments.edit'))
                                                        <button wire:click="showEditEnrollment({{ $enrollment->id }})"
                                                                class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                                            Edit
                                                        </button>
                                                    @endif
                                                    @if($this->can('enrollments.delete'))
                                                        <button wire:click="deleteEnrollment({{ $enrollment->id }})"
                                                                wire:confirm="Are you sure you want to delete this enrollment?"
                                                                class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                                            Delete
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        {{ $enrollments->links() }}
                    </div>
                @else
                    <p class="text-gray-500 dark:text-gray-400">No enrollments recorded for this service.</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Service Enrollment Modal -->
    @if($showEnrollmentModal)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50" wire:click.self="cancelEnrollmentModal">
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-lg w-full mx-4 max-h-[90vh] overflow-y-auto">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                    {{ $isEditingEnrollment ? 'Edit Service Enrollment' : 'Enroll Participant in Service' }}
                </h3>

                <form wire:submit="saveEnrollment">
                    <!-- Participant Selection -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Participant <span class="text-red-500">*</span>
                        </label>
                        <select wire:model.live="enrollmentForm.participant_id"
                                class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                                {{ $isEditingEnrollment ? 'disabled' : '' }}>
                            <option value="">Select Participant</option>
                            @foreach($participants as $participant)
                                <option value="{{ $participant->id }}">{{ $participant->last_name }}, {{ $participant->first_name }}</option>
                            @endforeach
                        </select>
                        @error('enrollmentForm.participant_id')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Program Enrollment Selection (Cascading) -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Program Enrollment <span class="text-red-500">*</span>
                        </label>
                        @if(count($availableProgramEnrollments) > 0)
                            <select wire:model="enrollmentForm.program_enrollment_id"
                                    class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Select Program Enrollment</option>
                                @foreach($availableProgramEnrollments as $pe)
                                    <option value="{{ $pe->id }}">
                                        {{ $service->program->name }} - Enrolled {{ $pe->enrollment_date?->format('M j, Y') ?? 'N/A' }}
                                        ({{ ucfirst($pe->status) }})
                                    </option>
                                @endforeach
                            </select>
                        @elseif($enrollmentForm['participant_id'])
                            <p class="text-sm text-amber-600 dark:text-amber-400 p-2 bg-amber-50 dark:bg-amber-900/30 rounded">
                                This participant is not enrolled in {{ $service->program->name ?? 'the parent program' }}.
                                Please enroll them in the program first.
                            </p>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400 italic">
                                Select a participant first to see available program enrollments.
                            </p>
                        @endif
                        @error('enrollmentForm.program_enrollment_id')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Enrollment Date -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Enrollment Date <span class="text-red-500">*</span>
                        </label>
                        <input type="date" wire:model="enrollmentForm.enrollment_date"
                               class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                        @error('enrollmentForm.enrollment_date')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Status -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Status <span class="text-red-500">*</span>
                        </label>
                        <select wire:model="enrollmentForm.status"
                                class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="enrolled">Enrolled</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                            <option value="withdrawn">Withdrawn</option>
                            <option value="transferred">Transferred</option>
                        </select>
                        @error('enrollmentForm.status')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Completion Date -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Completion Date
                        </label>
                        <input type="date" wire:model="enrollmentForm.completion_date"
                               class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                        @error('enrollmentForm.completion_date')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Sessions Attended (if service tracks sessions) -->
                    @if($service->tracks_sessions)
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Sessions Attended {{ $service->max_sessions ? '(Max: ' . $service->max_sessions . ')' : '' }}
                            </label>
                            <input type="number" wire:model="enrollmentForm.sessions_attended" min="0"
                                   class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                            @error('enrollmentForm.sessions_attended')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    <!-- Hours Received (if service tracks hours) -->
                    @if($service->tracks_hours)
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Hours Received {{ $service->max_hours ? '(Max: ' . $service->max_hours . ')' : '' }}
                            </label>
                            <input type="number" wire:model="enrollmentForm.hours_received" min="0" step="0.25"
                                   class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                            @error('enrollmentForm.hours_received')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    <!-- Notes -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Notes
                        </label>
                        <textarea wire:model="enrollmentForm.notes" rows="3"
                                  class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                        @error('enrollmentForm.notes')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" wire:click="cancelEnrollmentModal"
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-zinc-700 rounded-md hover:bg-gray-200 dark:hover:bg-zinc-600 transition-colors">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 transition-colors">
                            {{ $isEditingEnrollment ? 'Update Enrollment' : 'Create Enrollment' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>