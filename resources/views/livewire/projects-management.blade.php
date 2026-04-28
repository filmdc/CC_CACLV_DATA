<div class="w-full px-0 py-6">
    <div class="bg-white dark:bg-zinc-800 shadow-lg rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-indigo-600 text-white p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Housing Projects</h1>
                    <p class="text-indigo-100 mt-2">Construction, rehabilitation, and weatherization jobs across housing programs</p>
                </div>
                @if($this->canAny(['housing_jobs.create', 'projects.create']))
                    <div class="flex items-center space-x-3">
                        <button wire:click="showTemplateLibrary"
                                class="px-4 py-2 bg-white text-indigo-600 rounded-md hover:bg-indigo-50 font-semibold border-2 border-indigo-600">
                            📋 From Template
                        </button>
                        <button wire:click="showCreateJob"
                                class="px-4 py-2 bg-white text-indigo-600 rounded-md hover:bg-indigo-50 font-semibold">
                            + Create New Project
                        </button>
                    </div>
                @endif
            </div>
        </div>

        <!-- Success/Error Messages -->
        @if (session()->has('success'))
            <div class="p-4 bg-green-50 dark:bg-green-900/20 border-l-4 border-green-400">
                <p class="text-sm text-green-700 dark:text-green-300">{{ session('success') }}</p>
            </div>
        @endif
        @if (session()->has('error'))
            <div class="p-4 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-400">
                <p class="text-sm text-red-700 dark:text-red-300">{{ session('error') }}</p>
            </div>
        @endif

        <!-- Filters & Search -->
        @if(!$showCreateForm && !$showEditForm)
        <div class="p-4 bg-gray-50 dark:bg-zinc-700 border-b dark:border-zinc-600">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                <div class="md:col-span-2">
                    <input type="text" wire:model.live="search" placeholder="Search projects, participants, properties..."
                           class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <select wire:model.live="filterProgram"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                        <option value="">All Programs</option>
                        @foreach($programs as $program)
                            <option value="{{ $program->id }}">{{ $program->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <select wire:model.live="filterService"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                        <option value="">All Services</option>
                        @foreach($servicesForFilter as $service)
                            <option value="{{ $service->id }}">{{ $service->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <select wire:model.live="filterStatus"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="in_progress">In Progress</option>
                        <option value="on_hold">On Hold</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
            </div>

            <!-- Secondary Filters -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-3">
                <div>
                    <select wire:model.live="filterPriority"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                        <option value="">All Priorities</option>
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="emergency">Emergency</option>
                    </select>
                </div>

                <!-- Bulk Actions (show when items selected) -->
                @if(count($selectedJobs) > 0)
                    <div class="md:col-span-2 flex items-center space-x-3">
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ count($selectedJobs) }} selected:</span>
                        <select wire:change="bulkUpdateStatus($event.target.value)"
                                class="px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md text-sm">
                            <option value="">Update Status...</option>
                            <option value="approved">Mark Approved</option>
                            <option value="in_progress">Mark In Progress</option>
                            <option value="on_hold">Mark On Hold</option>
                            <option value="completed">Mark Completed</option>
                        </select>
                        <button wire:click="bulkDelete" wire:confirm="Delete {{ count($selectedJobs) }} projects?"
                                class="px-3 py-1 text-sm bg-red-600 text-white rounded-md hover:bg-red-700">
                            Delete Selected
                        </button>
                    </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Projects Table -->
        @if(!$showCreateForm && !$showEditForm)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                <thead class="bg-gray-100 dark:bg-zinc-900">
                    <tr>
                        <th class="px-4 py-2 text-left w-12">
                            <input type="checkbox" wire:model="selectAll" class="rounded border-gray-300">
                        </th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Property</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Participant</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Program</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Service</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Fund Codes</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Current Milestone</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Budget</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Status</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-700">
                    @forelse($projects as $project)
                        @php
                            $hasMilestones = $project->milestones->count() > 0;
                            $currentMilestone = $hasMilestones ? $project->getCurrentMilestone() : null;
                            $totalBudget = $project->getTotalBudget();
                            $totalSpent = $project->getTotalSpent();
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-zinc-700 {{ in_array($project->id, $selectedJobs) ? 'bg-indigo-50 dark:bg-indigo-900/20' : '' }}">
                            <td class="px-4 py-2">
                                <input type="checkbox"
                                       wire:click="toggleSelection({{ $project->id }})"
                                       {{ in_array($project->id, $selectedJobs) ? 'checked' : '' }}
                                       class="rounded border-gray-300">
                            </td>
                            <td class="px-4 py-2">
                                {{-- The property address is the project's leftmost identifier (P-10).
                                     The click takes the user to the project detail view, same
                                     destination as the job-title link below — they're two visual
                                     anchors for the same row. The earlier property.show link
                                     pointed at an unfinished Livewire surface that 500'd; not
                                     restoring it until that surface is properly built. --}}
                                @if($project->property)
                                    <a href="{{ route('project.management', $project->id) }}" wire:navigate
                                       class="text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                                        {{ $project->property->address_line_1 }}
                                    </a>
                                @else
                                    <span class="text-sm font-semibold text-gray-400">—</span>
                                @endif
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    <a href="{{ route('project.management', $project->id) }}" wire:navigate class="hover:underline">
                                        {{ Str::limit($project->job_title, 40) }}
                                    </a>
                                </div>
                            </td>
                            <td class="px-4 py-2">
                                @if($project->participant)
                                    <a href="{{ route('participant.show', $project->participant_id) }}" wire:navigate
                                       class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                                        {{ $project->participant->first_name }} {{ $project->participant->last_name }}
                                    </a>
                                @else
                                    <span class="text-sm text-gray-400">No participant</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">
                                    {{ $project->program ? $project->program->name : 'N/A' }}
                                </div>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">
                                    {{ $project->service ? $project->service->name : '-' }}
                                </div>
                            </td>
                            <td class="px-4 py-2">
                                @if($project->fundCodes->isNotEmpty())
                                    <span class="text-sm text-gray-900 dark:text-white">
                                        {{ $project->fundCodes->pluck('code')->implode(', ') }}
                                    </span>
                                @else
                                    <span class="text-sm text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                @if($hasMilestones && $currentMilestone)
                                    <a href="{{ route('project.management', $project->id) }}#milestone-{{ $currentMilestone->id }}" wire:navigate
                                       class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium hover:opacity-80"
                                       style="background-color: {{ $currentMilestone->color }}22; color: {{ $currentMilestone->color }}">
                                        {{ Str::limit($currentMilestone->name, 20) }}
                                    </a>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        {{ $currentMilestone->completion_percentage }}% complete
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        Started: {{ $currentMilestone->started_at?->format('M d, Y') ?: '—' }}
                                    </div>
                                @else
                                    <a href="{{ route('project.management', $project->id) }}" wire:navigate
                                       class="text-xs text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400">
                                        + Add Milestones
                                    </a>
                                @endif
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                @if($hasMilestones && $totalBudget > 0)
                                    <a href="{{ route('project.management', $project->id) }}?tab=budget" wire:navigate
                                       class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                                        ${{ number_format($totalBudget, 0) }}
                                    </a>
                                    <div class="text-xs {{ $totalSpent > $totalBudget ? 'text-red-600 dark:text-red-400 font-semibold' : 'text-gray-500 dark:text-gray-400' }} mt-1">
                                        ${{ number_format($totalSpent, 0) }} spent
                                    </div>
                                @else
                                    <span class="text-sm text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @if($project->status === 'completed') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300
                                    @elseif($project->status === 'in_progress') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300
                                    @elseif($project->status === 'on_hold') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300
                                    @elseif($project->status === 'cancelled') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300
                                    @else bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300
                                    @endif">
                                    {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                                </span>
                                @if($hasMilestones)
                                    <div class="mt-1">
                                        <span class="text-xs px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300">
                                            PM
                                        </span>
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-sm">
                                <div class="flex items-center space-x-2">
                                    <a href="{{ route('project.management', $project->id) }}" wire:navigate
                                       class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                        {{ $hasMilestones ? 'Manage' : 'View' }}
                                    </a>
                                    @if($this->can('housing_jobs.edit'))
                                        <button wire:click="showEditJob({{ $project->id }})"
                                                class="text-gray-600 dark:text-gray-400 hover:text-gray-900">
                                            Edit
                                        </button>
                                    @endif
                                    @if($this->can('housing_jobs.delete'))
                                        <button wire:click="deleteJob({{ $project->id }})"
                                                wire:confirm="Delete this project?"
                                                class="text-red-600 dark:text-red-400 hover:text-red-800">
                                            Delete
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                    </svg>
                                    <p class="text-lg font-medium text-gray-500 dark:text-gray-400">No projects found</p>
                                    <p class="text-sm text-gray-400 mt-1">Create your first construction project to get started</p>
                                    @if($this->canAny(['housing_jobs.create', 'projects.create']))
                                        <button wire:click="showCreateJob"
                                                class="mt-4 px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                                            Create First Project
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($projects->hasPages())
            <div class="bg-white dark:bg-zinc-800 px-4 py-3 border-t border-gray-200 dark:border-zinc-700">
                {{ $projects->links() }}
            </div>
        @endif
        @endif

        <!-- Create/Edit Form Modal -->
        @if($showCreateForm || $showEditForm)
            <div class="p-6">
                <div class="mb-6 flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                        {{ $showEditForm ? 'Edit Project' : 'Create New Project' }}
                    </h2>
                    <button wire:click="$set('showCreateForm', false); $set('showEditForm', false)"
                            class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Basic Information -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Basic Information</h3>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Project Title *</label>
                            <input type="text" wire:model="job_title"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                            @error('job_title') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                            <textarea wire:model="job_description" rows="3"
                                      class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md"></textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status *</label>
                                <select wire:model="status"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                                    <option value="pending">Pending</option>
                                    <option value="approved">Approved</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="on_hold">On Hold</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Priority *</label>
                                <select wire:model="priority"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                    <option value="emergency">Emergency</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Participant & Location -->
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Participant & Location</h3>
                            <span class="text-xs text-gray-500 dark:text-gray-400 italic">Search existing or click + buttons to create new</span>
                        </div>

                        <!-- Participant (Head of Household) - FIRST -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Participant (Head of Household) *
                                </label>
                                <button type="button" wire:click="showQuickParticipantModal"
                                        class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline flex items-center">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    New Participant
                                </button>
                            </div>
                            <livewire:searchable-select
                                :items="$participants"
                                :selected="$participant_id"
                                name="participant"
                                placeholder="Search heads of household..."
                                display-field="display"
                                secondary-field="secondary"
                                :required="true"
                                empty-message="No heads of household found. Create a new participant above."
                                :key="'participant-select-create'"
                            />
                            @error('participant_id') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Only heads of household are shown. Additional contacts can be added after project creation.
                            </p>
                        </div>

                        <!-- Property - SECOND -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Property *</label>
                                <div class="flex items-center space-x-2">
                                    <button type="button" wire:click="showQuickPropertyModal"
                                            class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline flex items-center">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                        New Property
                                    </button>
                                    @if($participant_id)
                                        <span class="text-xs text-gray-400">or</span>
                                        <button type="button" wire:click="copyPropertyFromParticipant"
                                                class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">
                                            📍 Copy from Participant's Household
                                        </button>
                                    @endif
                                </div>
                            </div>
                            @if(!$participant_id)
                                <div class="mb-2 text-xs text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 px-2 py-1 rounded">
                                    Select or create a participant first to add properties
                                </div>
                            @endif
                            <livewire:searchable-select
                                :items="$properties"
                                :selected="$property_id"
                                name="property"
                                placeholder="Search properties by address..."
                                display-field="display"
                                secondary-field="secondary"
                                :required="true"
                                :key="'property-select-' . ($participant_id ?: 'none')"
                            />
                            @error('property_id') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <!-- Contractor - THIRD -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Contractor</label>
                            <livewire:searchable-select
                                :items="$contractors"
                                :selected="$contractor_id"
                                name="contractor"
                                placeholder="Search contractors by company name..."
                                display-field="display"
                                secondary-field="secondary"
                                :key="'contractor-select-create'"
                            />
                        </div>
                    </div>

                    <!-- Program & Service -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Program & Funding</h3>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Program *</label>
                            <select wire:model.live="program_id"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                                <option value="">Select Program</option>
                                @foreach($programs as $program)
                                    <option value="{{ $program->id }}">{{ $program->name }}</option>
                                @endforeach
                            </select>
                            @error('program_id') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Housing Program (Service)</label>
                            <livewire:searchable-select
                                :items="$services"
                                :selected="$service_id"
                                name="service"
                                placeholder="Search housing programs..."
                                display-field="display"
                                secondary-field="secondary"
                                empty-message="Select a program first"
                                :key="'service-select-' . ($program_id ?: 'all')"
                            />
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Select the specific funded service (e.g., Whole Homes, Lead Abatement)</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Total Budget</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">$</span>
                                <input type="number" step="0.01" wire:model="estimated_cost"
                                       class="w-full pl-8 px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                            </div>
                        </div>
                    </div>

                    <!-- Dates -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Timeline</h3>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Request Date *</label>
                            <input type="date" wire:model="request_date"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                            @error('request_date') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start Date</label>
                            <input type="date" wire:model="start_date"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Target Completion</label>
                            <input type="date" wire:model="target_completion_date"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="mt-6 flex justify-end space-x-3 border-t dark:border-zinc-700 pt-6">
                    <button wire:click="$set('showCreateForm', false); $set('showEditForm', false)"
                            class="px-4 py-2 bg-gray-200 dark:bg-zinc-600 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-300">
                        Cancel
                    </button>
                    <button wire:click="saveJob"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                        {{ $showEditForm ? 'Update Project' : 'Create Project' }}
                    </button>
                </div>
            </div>
        @endif
    </div>

    <!-- Template Library Modal -->
    @if($showTemplateModal)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50" wire:click.self="$set('showTemplateModal', false)">
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-3xl w-full mx-4 max-h-[80vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Project Templates</h3>
                    <button wire:click="$set('showTemplateModal', false)"
                            class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                @if($templates->count() > 0)
                    <div class="space-y-3">
                        @foreach($templates as $template)
                            <div class="border border-gray-200 dark:border-zinc-600 rounded-lg p-4 hover:border-indigo-500 dark:hover:border-indigo-400 transition-colors">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <h4 class="font-semibold text-gray-900 dark:text-white">{{ $template->template_name }}</h4>
                                        @if($template->template_description)
                                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $template->template_description }}</p>
                                        @endif
                                        <div class="flex items-center space-x-4 mt-3 text-xs text-gray-500 dark:text-gray-400">
                                            <span>{{ $template->milestones->count() }} milestones</span>
                                            <span>{{ $template->milestones->sum(fn($m) => $m->tasks->count()) }} tasks</span>
                                            <span>{{ $template->budgets->count() }} budget categories</span>
                                            @if($template->times_used > 0)
                                                <span class="text-green-600 dark:text-green-400">✓ Used {{ $template->times_used }} times</span>
                                            @endif
                                        </div>
                                    </div>
                                    <button wire:click="createFromTemplate({{ $template->id }})"
                                            class="ml-4 px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 text-sm">
                                        Use Template
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">No templates available yet</p>
                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">Create a project with milestones, then use "Save as Template"</p>
                    </div>
                @endif

                <div class="mt-6 flex justify-end">
                    <button wire:click="$set('showTemplateModal', false)"
                            class="px-4 py-2 bg-gray-200 dark:bg-zinc-600 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-300">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Quick Participant Creation Modal -->
    @if($showQuickParticipantForm)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50" wire:click.self="$set('showQuickParticipantForm', false)">
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Create New Participant</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Creates a new participant as head of household. A new household will be automatically created. Full CSBG intake can be completed later.</p>
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">First Name *</label>
                            <input type="text" wire:model="quick_participant_first_name"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                            @error('quick_participant_first_name') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Last Name *</label>
                            <input type="text" wire:model="quick_participant_last_name"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                            @error('quick_participant_last_name') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Phone</label>
                        <input type="tel" wire:model="quick_participant_phone"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                        <input type="email" wire:model="quick_participant_email"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date of Birth</label>
                        <input type="date" wire:model="quick_participant_dob"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                    </div>

                    <!-- Household Address Section -->
                    <div class="border-t border-gray-200 dark:border-zinc-600 pt-4">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Household Address</h4>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Address *</label>
                                <input type="text" wire:model="quick_participant_address" placeholder="123 Main Street"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                                @error('quick_participant_address') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div class="grid grid-cols-3 gap-3">
                                <div class="col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">City *</label>
                                    <input type="text" wire:model="quick_participant_city" placeholder="Las Vegas"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                                    @error('quick_participant_city') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">State *</label>
                                    <input type="text" wire:model="quick_participant_state" maxlength="2" placeholder="NV"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md uppercase">
                                    @error('quick_participant_state') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Zip Code *</label>
                                <input type="text" wire:model="quick_participant_zip" placeholder="89101"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                                @error('quick_participant_zip') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-md p-3">
                        <p class="text-xs text-yellow-800 dark:text-yellow-300">
                            This creates a basic participant record with household address. Full CSBG intake should be completed later for reporting compliance.
                        </p>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" wire:click="$set('showQuickParticipantForm', false)"
                            class="px-4 py-2 bg-gray-200 dark:bg-zinc-600 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-300">
                        Cancel
                    </button>
                    <button type="button" wire:click="saveQuickParticipant"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                        Create Participant
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Quick Property Creation Modal -->
    @if($showQuickPropertyForm)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50" wire:click.self="$set('showQuickPropertyForm', false)">
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Create New Property</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Address *</label>
                        <input type="text" wire:model="quick_property_address" placeholder="123 Main Street"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                        @error('quick_property_address') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">City *</label>
                            <input type="text" wire:model="quick_property_city" placeholder="Las Vegas"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">State *</label>
                            <input type="text" wire:model="quick_property_state" maxlength="2" placeholder="NV"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md uppercase">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Zip Code *</label>
                        <input type="text" wire:model="quick_property_zip" placeholder="89101"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" wire:click="$set('showQuickPropertyForm', false)"
                            class="px-4 py-2 bg-gray-200 dark:bg-zinc-600 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-300">
                        Cancel
                    </button>
                    <button type="button" wire:click="saveQuickProperty"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                        Create Property
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
