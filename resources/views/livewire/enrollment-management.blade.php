<div class="max-w-7xl mx-auto p-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-800 dark:from-blue-700 dark:to-blue-900 rounded-lg p-6 mb-6">
        <h1 class="text-2xl font-bold text-white mb-2">Enrollment Management</h1>
        <p class="text-blue-100">Manage participant enrollments in programs and services</p>
    </div>

    <!-- Flash Messages -->
    @if (session()->has('success'))
        <div class="mb-4 p-4 bg-green-100 dark:bg-green-900 border border-green-400 dark:border-green-600 text-green-700 dark:text-green-200 rounded">
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-200 rounded">
            {{ session('error') }}
        </div>
    @endif

    <!-- Controls -->
    @if(!$showProgramEnrollmentForm && !$showViewModal)
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6 mb-6">
        <!-- Enrollment Type Toggle -->
        <div class="flex space-x-4 mb-6">
            <button wire:click="$set('enrollmentType', 'program')" 
                    class="px-4 py-2 rounded-lg font-medium {{ $enrollmentType === 'program' ? 'bg-blue-600 text-white' : 'bg-gray-200 dark:bg-zinc-700 text-gray-700 dark:text-gray-300' }}">
                Program Enrollments
            </button>
            <button wire:click="$set('enrollmentType', 'service')" 
                    class="px-4 py-2 rounded-lg font-medium {{ $enrollmentType === 'service' ? 'bg-blue-600 text-white' : 'bg-gray-200 dark:bg-zinc-700 text-gray-700 dark:text-gray-300' }}">
                Service Enrollments
            </button>
        </div>

        <!-- Search and Filters -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
            <input type="text" wire:model.live="search" placeholder="Search participants..." 
                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-700 text-gray-900 dark:text-white">
            
            <select wire:model.live="statusFilter" class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-700 text-gray-900 dark:text-white">
                <option value="">All Statuses</option>
                @if($enrollmentType === 'program')
                    <option value="active">Active</option>
                    <option value="completed">Completed</option>
                    <option value="withdrawn">Withdrawn</option>
                    <option value="transferred">Transferred</option>
                @else
                    <option value="enrolled">Enrolled</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                    <option value="withdrawn">Withdrawn</option>
                @endif
            </select>

            <select wire:model.live="programFilter" class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-700 text-gray-900 dark:text-white">
                <option value="">All Programs</option>
                @foreach($programs as $program)
                    <option value="{{ $program->id }}">{{ $program->name }}</option>
                @endforeach
            </select>

            @if($enrollmentType === 'program')
                <button wire:click="showCreateProgramEnrollment" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                    New Program Enrollment
                </button>
            @endif
        </div>

        <button wire:click="resetFilters" class="text-blue-600 dark:text-blue-400 hover:underline">
            Clear Filters
        </button>
    </div>
    @endif

    <!-- Enrollments List -->
    @if(!$showProgramEnrollmentForm && !$showViewModal)
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-zinc-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Participant
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            {{ $enrollmentType === 'program' ? 'Program' : 'Service' }}
                        </th>
                        @if($enrollmentType === 'service')
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Program
                            </th>
                        @endif
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Enrollment Date
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Duration
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-600">
                    @forelse($enrollments as $enrollment)
                        <tr class="hover:bg-gray-50 dark:hover:bg-zinc-700">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button wire:click="viewEnrollment('{{ $enrollmentType }}', {{ $enrollment->id }})" 
                                        class="text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 hover:underline cursor-pointer transition-colors duration-200">
                                    <div class="text-sm font-medium">{{ $enrollment->participant->full_name }}</div>
                                </button>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    Household: {{ $enrollment->participant->household->household_code }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">
                                    @if($enrollmentType === 'program')
                                        {{ $enrollment->program->name }}
                                    @else
                                        {{ $enrollment->service->name }}
                                    @endif
                                </div>
                            </td>
                            @if($enrollmentType === 'service')
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        {{ $enrollment->programEnrollment->program->name }}
                                    </div>
                                </td>
                            @endif
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                {{ $enrollment->enrollment_date->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full
                                    {{ $enrollment->status === 'active' || $enrollment->status === 'enrolled' || $enrollment->status === 'in_progress' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 
                                       ($enrollment->status === 'completed' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 
                                        'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200') }}">
                                    {{ ucfirst(str_replace('_', ' ', $enrollment->status)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                @if($enrollmentType === 'program')
                                    {{ $enrollment->days_enrolled }} days
                                @else
                                    {{ $enrollment->days_in_service }} days
                                    @if($enrollment->hours_received)
                                        <br><span class="text-gray-500 dark:text-gray-400">{{ $enrollment->hours_received }}h</span>
                                    @endif
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <button wire:click="viewEnrollment('{{ $enrollmentType }}', {{ $enrollment->id }})" 
                                            class="text-green-600 dark:text-green-400 hover:underline">
                                        View
                                    </button>
                                    @if($enrollmentType === 'program')
                                        <button wire:click="showEditProgramEnrollment({{ $enrollment->id }})" 
                                                class="text-blue-600 dark:text-blue-400 hover:underline">
                                            Edit
                                        </button>
                                        <button wire:click="deleteProgramEnrollment({{ $enrollment->id }})" 
                                                class="text-red-600 dark:text-red-400 hover:underline"
                                                onclick="return confirm('Are you sure you want to delete this enrollment?')">
                                            Delete
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $enrollmentType === 'service' ? '7' : '6' }}" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                No enrollments found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-3 border-t border-gray-200 dark:border-zinc-600">
            {{ $enrollments->links() }}
        </div>
    </div>
    @endif

    <!-- Program Enrollment Form Modal -->
    @if($showProgramEnrollmentForm)
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                    {{ $selectedProgramEnrollment ? 'Edit Program Enrollment' : 'Create Program Enrollment' }}
                </h3>
                <button wire:click="cancelProgramEnrollmentForm" class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
                    
                    @error('general') 
                        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-200 rounded">
                            {{ $message }}
                        </div>
                    @enderror
                    
                    <form wire:submit.prevent="{{ $selectedProgramEnrollment ? 'updateProgramEnrollment' : 'createProgramEnrollment' }}">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Participant -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Participant *</label>
                                <select wire:model="participant_id" required 
                                        class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-700 text-gray-900 dark:text-white">
                                    <option value="">Select Participant</option>
                                    @foreach($participants as $participant)
                                        <option value="{{ $participant->id }}">{{ $participant->full_name }}</option>
                                    @endforeach
                                </select>
                                @error('participant_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- Program -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Program *</label>
                                <select wire:model="program_id" required 
                                        class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-700 text-gray-900 dark:text-white">
                                    <option value="">Select Program</option>
                                    @foreach($programs as $program)
                                        <option value="{{ $program->id }}">{{ $program->name }}</option>
                                    @endforeach
                                </select>
                                @error('program_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- Enrollment Date -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Enrollment Date *</label>
                                <input type="date" wire:model="enrollment_date" required 
                                       class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-700 text-gray-900 dark:text-white">
                                @error('enrollment_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- Status -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status *</label>
                                <select wire:model="status" required 
                                        class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-700 text-gray-900 dark:text-white">
                                    <option value="active">Active</option>
                                    <option value="completed">Completed</option>
                                    <option value="withdrawn">Withdrawn</option>
                                    <option value="transferred">Transferred</option>
                                </select>
                                @error('status') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- Exit Date -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Exit Date</label>
                                <input type="date" wire:model="exit_date" 
                                       class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-700 text-gray-900 dark:text-white">
                                @error('exit_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- Exit Reason -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Exit Reason</label>
                                <select wire:model="exit_reason" 
                                        class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-700 text-gray-900 dark:text-white">
                                    <option value="">Select Reason</option>
                                    <option value="completed_successfully">Completed Successfully</option>
                                    <option value="moved_out_of_area">Moved Out of Area</option>
                                    <option value="no_longer_eligible">No Longer Eligible</option>
                                    <option value="non_compliance">Non-Compliance</option>
                                    <option value="participant_choice">Participant Choice</option>
                                    <option value="other">Other</option>
                                </select>
                                @error('exit_reason') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Exit Notes -->
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Exit Notes</label>
                            <textarea wire:model="exit_notes" rows="3" 
                                      class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-700 text-gray-900 dark:text-white"></textarea>
                            @error('exit_notes') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Notes -->
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Notes</label>
                            <textarea wire:model="notes" rows="3" 
                                      class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-700 text-gray-900 dark:text-white"></textarea>
                            @error('notes') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Form Actions -->
                        <div class="flex justify-end space-x-3 mt-6">
                            <button type="button" wire:click="cancelProgramEnrollmentForm" 
                                    class="px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-zinc-700">
                                Cancel
                            </button>
                            <button type="submit" 
                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md">
                                {{ $selectedProgramEnrollment ? 'Update' : 'Create' }} Enrollment
                            </button>
                        </div>
                    </form>
        </div>
    @endif

    <!-- View Modal -->
    @if($showViewModal && $selectedViewEnrollment)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-4xl w-full m-4 max-h-[90vh] overflow-y-auto">
                <!-- Modal Header -->
                <div class="bg-blue-600 text-white p-6 rounded-t-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-semibold">{{ $enrollmentType === 'program' ? 'Program' : 'Service' }} Enrollment Details</h3>
                            <p class="text-blue-100 mt-1">{{ $selectedViewEnrollment->participant->full_name }}</p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="px-3 py-1 text-sm rounded-full {{ $selectedViewEnrollment->status === 'active' || $selectedViewEnrollment->status === 'enrolled' || $selectedViewEnrollment->status === 'in_progress' ? 'bg-green-100 text-green-800' : ($selectedViewEnrollment->status === 'completed' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800') }}">
                                {{ ucfirst(str_replace('_', ' ', $selectedViewEnrollment->status)) }}
                            </span>
                            <button wire:click="$set('showViewModal', false)" class="text-white hover:text-gray-300">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Modal Content -->
                <div class="p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Enrollment Information -->
                        <div class="space-y-6">
                            <div>
                                <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-3">Enrollment Information</h4>
                                <div class="bg-gray-50 dark:bg-zinc-700 p-4 rounded-lg space-y-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Participant</label>
                                        <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $selectedViewEnrollment->participant->full_name }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Household Code</label>
                                        <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $selectedViewEnrollment->participant->household->household_code }}</p>
                                    </div>
                                    @if($enrollmentType === 'program')
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Program</label>
                                            <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $selectedViewEnrollment->program->name }}</p>
                                        </div>
                                    @else
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Service</label>
                                            <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $selectedViewEnrollment->service->name }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Program</label>
                                            <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $selectedViewEnrollment->programEnrollment->program->name }}</p>
                                        </div>
                                    @endif
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Enrollment Date</label>
                                            <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $selectedViewEnrollment->enrollment_date->format('M j, Y') }}</p>
                                        </div>
                                        @if($selectedViewEnrollment->exit_date || $selectedViewEnrollment->completion_date)
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $enrollmentType === 'program' ? 'Exit Date' : 'Completion Date' }}</label>
                                                <p class="text-sm text-gray-900 dark:text-white mt-1">{{ ($selectedViewEnrollment->exit_date ?? $selectedViewEnrollment->completion_date)->format('M j, Y') }}</p>
                                            </div>
                                        @endif
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                                        <p class="text-sm text-gray-900 dark:text-white mt-1">{{ ucfirst(str_replace('_', ' ', $selectedViewEnrollment->status)) }}</p>
                                    </div>
                                    @if($enrollmentType === 'program')
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Duration</label>
                                            <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $selectedViewEnrollment->days_enrolled }} days</p>
                                        </div>
                                        @if($selectedViewEnrollment->exit_reason)
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Exit Reason</label>
                                                <p class="text-sm text-gray-900 dark:text-white mt-1">{{ ucfirst(str_replace('_', ' ', $selectedViewEnrollment->exit_reason)) }}</p>
                                            </div>
                                        @endif
                                        @if($selectedViewEnrollment->exit_notes)
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Exit Notes</label>
                                                <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $selectedViewEnrollment->exit_notes }}</p>
                                            </div>
                                        @endif
                                    @else
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Duration</label>
                                            <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $selectedViewEnrollment->days_in_service }} days</p>
                                        </div>
                                        @if($selectedViewEnrollment->hours_received)
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Hours Received</label>
                                                <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $selectedViewEnrollment->hours_received }}</p>
                                            </div>
                                        @endif
                                        @if($selectedViewEnrollment->sessions_attended)
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Sessions Attended</label>
                                                <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $selectedViewEnrollment->sessions_attended }}</p>
                                            </div>
                                        @endif
                                    @endif
                                    @if($selectedViewEnrollment->notes || $selectedViewEnrollment->completion_notes)
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Notes</label>
                                            <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $selectedViewEnrollment->notes ?? $selectedViewEnrollment->completion_notes }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Related Information -->
                        <div class="space-y-6">
                            @if($enrollmentType === 'program' && $selectedViewEnrollment->serviceEnrollments->count() > 0)
                                <div>
                                    <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-3">Service Enrollments</h4>
                                    <div class="bg-gray-50 dark:bg-zinc-700 p-4 rounded-lg">
                                        <div class="space-y-3">
                                            @foreach($selectedViewEnrollment->serviceEnrollments as $serviceEnrollment)
                                                <div class="border-b border-gray-200 dark:border-zinc-600 pb-2 last:border-b-0">
                                                    <div class="flex items-center justify-between">
                                                        <div>
                                                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $serviceEnrollment->service->name }}</p>
                                                            <p class="text-xs text-gray-500 dark:text-gray-400">Enrolled: {{ $serviceEnrollment->enrollment_date->format('M j, Y') }}</p>
                                                        </div>
                                                        <span class="px-2 py-1 text-xs rounded-full {{ $serviceEnrollment->status === 'enrolled' || $serviceEnrollment->status === 'in_progress' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : ($serviceEnrollment->status === 'completed' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200') }}">
                                                            {{ ucfirst($serviceEnrollment->status) }}
                                                        </span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Participant Information -->
                            <div>
                                <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-3">Participant Information</h4>
                                <div class="bg-gray-50 dark:bg-zinc-700 p-4 rounded-lg space-y-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Full Name</label>
                                        <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $selectedViewEnrollment->participant->full_name }}</p>
                                    </div>
                                    @if($selectedViewEnrollment->participant->date_of_birth)
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Date of Birth</label>
                                            <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $selectedViewEnrollment->participant->date_of_birth->format('M j, Y') }} ({{ $selectedViewEnrollment->participant->age }} years old)</p>
                                        </div>
                                    @endif
                                    @if($selectedViewEnrollment->participant->gender)
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Gender</label>
                                            <p class="text-sm text-gray-900 dark:text-white mt-1">{{ ucfirst($selectedViewEnrollment->participant->gender) }}</p>
                                        </div>
                                    @endif
                                    @if($selectedViewEnrollment->participant->telephone)
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Phone</label>
                                            <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $selectedViewEnrollment->participant->telephone }}</p>
                                        </div>
                                    @endif
                                    @if($selectedViewEnrollment->participant->email)
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                                            <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $selectedViewEnrollment->participant->email }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex justify-end mt-6">
                        <button wire:click="$set('showViewModal', false)" 
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-zinc-600 border border-gray-300 dark:border-zinc-500 rounded-md hover:bg-gray-50 dark:hover:bg-zinc-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>