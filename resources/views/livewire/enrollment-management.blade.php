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

    <!-- Enrollments List -->
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
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $enrollment->participant->full_name }}
                                </div>
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

    <!-- Program Enrollment Form Modal -->
    @if($showProgramEnrollmentForm)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white dark:bg-zinc-800">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                        {{ $selectedProgramEnrollment ? 'Edit Program Enrollment' : 'Create Program Enrollment' }}
                    </h3>
                    
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
            </div>
        </div>
    @endif
</div>