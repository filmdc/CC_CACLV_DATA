<div class="w-full px-0 py-6">
    <div class="bg-white dark:bg-zinc-800 shadow-lg rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">{{ $program->name }}</h1>
                    <p class="text-blue-100 mt-2">Program Details</p>
                </div>
                <div class="flex space-x-3">
                    @if ($this->can('enrollments.create'))
                        <button wire:click="showCreateEnrollment"
                                class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                            Enroll Participant
                        </button>
                    @endif
                    @if ($this->can('programs.update'))
                        <button wire:click="editProgram"
                                class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                            Edit Program
                        </button>
                    @endif
                    <a href="{{ route('program.management') }}"
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

            <!-- Basic Information -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-zinc-700 pb-2">
                    Basic Information
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Program Name</label>
                        <p class="text-gray-900 dark:text-white">{{ $program->name }}</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Status</label>
                        <p>
                            @if($program->active)
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
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Description</label>
                        <div class="text-gray-900 dark:text-white rich-text-content">
                            {!! $program->description ?: '<p>No description provided</p>' !!}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Services -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-zinc-700 pb-2">
                    Services ({{ $program->services->count() }})
                </h2>
                @if($program->services->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($program->services as $service)
                            <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-4">
                                <h3 class="font-medium text-gray-900 dark:text-white">{{ $service->name }}</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $service->description ?: 'No description' }}</p>
                                <div class="mt-2">
                                    @if($service->is_active)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100">
                                            Inactive
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 dark:text-gray-400">No services configured for this program.</p>
                @endif
            </div>

            <!-- Contracts -->
            <div class="mb-8">
                <div class="flex items-center justify-between mb-4 border-b border-gray-200 dark:border-zinc-700 pb-2">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                        Contracts ({{ $contracts->total() }})
                    </h2>
                    <label class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                        <input type="checkbox" 
                               wire:model.live="showExpiredContracts" 
                               class="rounded border-gray-300 dark:border-zinc-600 text-blue-600 focus:ring-blue-500 dark:bg-zinc-700 mr-2">
                        Show expired contracts from past calendar years
                    </label>
                </div>
                @if($contracts->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Contract Title
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Contract Number
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Funder
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Start Date
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        End Date
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Status
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                                @foreach($contracts as $contract)
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                            <a href="{{ route('contract.show', $contract->id) }}" 
                                               class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                                {{ $contract->title }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                            {{ $contract->contract_number ?? '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                            @if($contract->funder)
                                                <a href="{{ route('funder.show', $contract->funder->id) }}" 
                                                   class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                                    {{ $contract->funder->name }}
                                                </a>
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                            {{ $contract->start_date ? \Carbon\Carbon::parse($contract->start_date)->format('M j, Y') : 'N/A' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                            {{ $contract->end_date ? \Carbon\Carbon::parse($contract->end_date)->format('M j, Y') : 'N/A' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            @if($contract->status === 'Archived')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100">
                                                    Archived
                                                </span>
                                            @elseif($contract->status === 'Active')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                                    Active
                                                </span>
                                            @elseif($contract->status === 'Expired')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100">
                                                    Expired
                                                </span>
                                            @elseif($contract->status === 'Upcoming')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100">
                                                    Upcoming
                                                </span>
                                            @elseif($contract->status === 'Draft')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                                                    Draft
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100">
                                                    {{ $contract->status }}
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if($contracts->hasPages())
                        <div class="mt-4">
                            {{ $contracts->links() }}
                        </div>
                    @endif
                @else
                    <p class="text-gray-500 dark:text-gray-400">No contracts associated with this program.</p>
                @endif
            </div>

            <!-- Recent Enrollments -->
            <div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-zinc-700 pb-2">
                    Enrollments ({{ $enrollments->total() }})
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
                                        Household
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Enrollment Date
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Days Enrolled
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Exit Date
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                                @foreach($enrollments as $enrollment)
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                            <a href="{{ route('participant.show', $enrollment->participant_id) }}"
                                               class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                                {{ $enrollment->participant->full_name ?? 'Unknown' }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                            @if($enrollment->participant && $enrollment->participant->household)
                                                <a href="{{ route('household.show', $enrollment->participant->household->id) }}"
                                                   class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                                    {{ $enrollment->participant->household->household_code }}
                                                </a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                            {{ $enrollment->enrollment_date ? \Carbon\Carbon::parse($enrollment->enrollment_date)->format('M j, Y') : 'N/A' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                            {{ $enrollment->days_enrolled ?? '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            @if($enrollment->status === 'active')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                                    Active
                                                </span>
                                            @elseif($enrollment->status === 'completed')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                                                    Completed
                                                </span>
                                            @elseif($enrollment->status === 'withdrawn')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100">
                                                    Withdrawn
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100">
                                                    {{ ucfirst($enrollment->status) }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                            {{ $enrollment->exit_date ? \Carbon\Carbon::parse($enrollment->exit_date)->format('M j, Y') : '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            <div class="flex space-x-2">
                                                @if($this->can('enrollments.edit'))
                                                    <button wire:click="showEditEnrollment({{ $enrollment->id }})"
                                                            class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 font-medium">
                                                        Edit
                                                    </button>
                                                @endif
                                                @if($this->can('enrollments.delete'))
                                                    <button wire:click="deleteEnrollment({{ $enrollment->id }})"
                                                            wire:confirm="Are you sure you want to delete this enrollment?"
                                                            class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 font-medium">
                                                        Delete
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if($enrollments->hasPages())
                        <div class="mt-4">
                            {{ $enrollments->links() }}
                        </div>
                    @endif
                @else
                    <p class="text-gray-500 dark:text-gray-400">No enrollments recorded for this program.</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Enrollment Modal -->
    @if($showEnrollmentModal)
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-2xl w-full m-4 max-h-[90vh] overflow-y-auto">
            <!-- Modal Header -->
            <div class="bg-green-600 text-white p-6 rounded-t-lg">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-semibold">
                        {{ $isEditingEnrollment ? 'Edit Enrollment' : 'Enroll Participant in ' . $program->name }}
                    </h3>
                    <button wire:click="cancelEnrollmentModal" class="text-white hover:text-gray-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Modal Body -->
            <form wire:submit.prevent="saveEnrollment">
                <div class="p-6 space-y-4">
                    <!-- Participant Selection -->
                    <div>
                        <label for="participant_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Participant *</label>
                        <select wire:model="enrollmentForm.participant_id" id="participant_id"
                                class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500"
                                {{ $isEditingEnrollment ? 'disabled' : '' }}>
                            <option value="">Select a participant...</option>
                            @foreach($participants as $participant)
                                <option value="{{ $participant->id }}">{{ $participant->last_name }}, {{ $participant->first_name }} ({{ $participant->household?->household_code ?? 'No household' }})</option>
                            @endforeach
                        </select>
                        @error('enrollmentForm.participant_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- Enrollment Date -->
                    <div>
                        <label for="enrollment_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Enrollment Date *</label>
                        <input type="date" wire:model="enrollmentForm.enrollment_date" id="enrollment_date"
                               class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500">
                        @error('enrollmentForm.enrollment_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- Status -->
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status *</label>
                        <select wire:model="enrollmentForm.status" id="status"
                                class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500">
                            <option value="active">Active</option>
                            <option value="completed">Completed</option>
                            <option value="withdrawn">Withdrawn</option>
                            <option value="transferred">Transferred</option>
                        </select>
                        @error('enrollmentForm.status') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- Exit Date (shown when not active) -->
                    @if($enrollmentForm['status'] !== 'active')
                    <div>
                        <label for="exit_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Exit Date</label>
                        <input type="date" wire:model="enrollmentForm.exit_date" id="exit_date"
                               class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500">
                        @error('enrollmentForm.exit_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- Exit Reason -->
                    <div>
                        <label for="exit_reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Exit Reason</label>
                        <select wire:model="enrollmentForm.exit_reason" id="exit_reason"
                                class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500">
                            <option value="">Select reason...</option>
                            <option value="completed_successfully">Completed Successfully</option>
                            <option value="moved_out_of_area">Moved Out of Area</option>
                            <option value="no_longer_eligible">No Longer Eligible</option>
                            <option value="non_compliance">Non-Compliance</option>
                            <option value="participant_choice">Participant Choice</option>
                            <option value="other">Other</option>
                        </select>
                        @error('enrollmentForm.exit_reason') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- Exit Notes -->
                    <div>
                        <label for="exit_notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Exit Notes</label>
                        <textarea wire:model="enrollmentForm.exit_notes" id="exit_notes" rows="2"
                                  class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500"></textarea>
                        @error('enrollmentForm.exit_notes') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    @endif

                    <!-- Notes -->
                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                        <textarea wire:model="enrollmentForm.notes" id="notes" rows="3"
                                  class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500"></textarea>
                        @error('enrollmentForm.notes') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="bg-gray-50 dark:bg-zinc-700 px-6 py-4 rounded-b-lg flex justify-end space-x-3">
                    <button type="button" wire:click="cancelEnrollmentModal"
                            class="px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-zinc-600 transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors">
                        {{ $isEditingEnrollment ? 'Update Enrollment' : 'Enroll Participant' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>