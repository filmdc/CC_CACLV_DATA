<div class="w-full px-0 py-6">
    <div class="bg-white dark:bg-zinc-800 shadow-lg rounded-lg overflow-hidden">
        <!-- Project Header -->
        <div class="bg-indigo-600 text-white p-6">
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center space-x-3">
                        <a href="{{ route('projects.management') }}" wire:navigate class="text-indigo-100 hover:text-white">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                        </a>
                        <div>
                            <h1 class="text-2xl font-bold">{{ $job->job_number }} - {{ $job->job_title }}</h1>
                            <p class="text-indigo-100 mt-1">
                                {{ $job->participant ? $job->participant->first_name . ' ' . $job->participant->last_name : 'No Participant' }}
                                | {{ $job->property ? $job->property->address_line_1 : 'No Property' }}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    @if($this->can('projects.manage'))
                        <button wire:click="showEditProject"
                                class="px-3 py-1 text-sm bg-white/20 hover:bg-white/30 text-white rounded-md border border-white/30">
                            ✏️ Edit Project
                        </button>
                    @endif
                    @if($job->milestones()->count() > 0 && $this->can('projects.manage'))
                        <button wire:click="showSaveAsTemplate"
                                class="px-3 py-1 text-sm bg-white/20 hover:bg-white/30 text-white rounded-md border border-white/30">
                            📋 Save as Template
                        </button>
                    @endif
                    <div class="text-right">
                        <div class="text-sm text-indigo-100">Program</div>
                        <div class="text-lg font-semibold">{{ $job->program ? $job->program->name : 'N/A' }}</div>
                    </div>
                </div>
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

        <!-- Project Info Summary Bar -->
        <div class="bg-gray-50 dark:bg-zinc-900 border-b border-gray-200 dark:border-zinc-700 px-6 py-3">
            <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm">
                <div class="flex items-center">
                    <span class="text-gray-500 dark:text-gray-400 mr-2">Status:</span>
                    <span class="px-2 py-0.5 rounded text-xs font-medium
                        @if($job->status === 'completed') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300
                        @elseif($job->status === 'in_progress') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300
                        @elseif($job->status === 'pending') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300
                        @elseif($job->status === 'on_hold') bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300
                        @elseif($job->status === 'cancelled') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300
                        @else bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300
                        @endif">
                        {{ ucfirst(str_replace('_', ' ', $job->status)) }}
                    </span>
                </div>
                <div class="flex items-center">
                    <span class="text-gray-500 dark:text-gray-400 mr-2">Priority:</span>
                    <span class="px-2 py-0.5 rounded text-xs font-medium
                        @if($job->priority === 'emergency') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300
                        @elseif($job->priority === 'high') bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300
                        @elseif($job->priority === 'medium') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300
                        @else bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300
                        @endif">
                        {{ ucfirst($job->priority) }}
                    </span>
                </div>
                @if($job->contractor)
                    <div class="flex items-center">
                        <span class="text-gray-500 dark:text-gray-400 mr-2">Contractor:</span>
                        <span class="text-gray-900 dark:text-white">{{ $job->contractor->company_name }}</span>
                    </div>
                @endif
                @if($job->target_completion_date)
                    <div class="flex items-center">
                        <span class="text-gray-500 dark:text-gray-400 mr-2">Target:</span>
                        <span class="{{ $job->isOverdue() ? 'text-red-600 dark:text-red-400 font-medium' : 'text-gray-900 dark:text-white' }}">
                            {{ $job->target_completion_date->format('M d, Y') }}
                            @if($job->isOverdue())
                                <span class="text-xs">({{ $job->days_overdue }} days overdue)</span>
                            @endif
                        </span>
                    </div>
                @endif
                @if($job->estimated_cost)
                    <div class="flex items-center">
                        <span class="text-gray-500 dark:text-gray-400 mr-2">Est. Cost:</span>
                        <span class="text-gray-900 dark:text-white">${{ number_format($job->estimated_cost, 2) }}</span>
                    </div>
                @endif
                <div class="flex-1"></div>
                @if($this->can('projects.manage'))
                    <button wire:click="showEditProject" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 text-sm font-medium">
                        Edit Details
                    </button>
                @endif
            </div>
        </div>

        <!-- Tabs -->
        <div class="border-b border-gray-200 dark:border-zinc-700">
            <nav class="flex -mb-px">
                <button wire:click="$set('activeTab', 'milestones')"
                        class="py-4 px-6 text-center border-b-2 font-medium text-sm
                        @if($activeTab === 'milestones')
                            border-indigo-500 text-indigo-600 dark:text-indigo-400
                        @else
                            border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300
                        @endif">
                    Milestones & Tasks
                </button>
                <button wire:click="$set('activeTab', 'budget')"
                        class="ml-8 py-4 px-6 text-center border-b-2 font-medium text-sm
                        @if($activeTab === 'budget')
                            border-indigo-500 text-indigo-600 dark:text-indigo-400
                        @else
                            border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300
                        @endif">
                    Budget & Expenses
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-6">
            @if($activeTab === 'milestones')
                <!-- Milestones Tab -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Left: Milestone List -->
                    <div class="lg:col-span-1">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Milestones</h2>
                            <button wire:click="showCreateMilestone"
                                    class="px-3 py-1 text-sm bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                                + Add
                            </button>
                        </div>

                        @if($job->milestones->count() > 0)
                            <div class="space-y-2">
                                @foreach($job->milestones as $milestone)
                                    <div wire:click="selectMilestone({{ $milestone->id }})"
                                         class="p-4 rounded-lg cursor-pointer transition-colors
                                         @if($selectedMilestone == $milestone->id)
                                             bg-indigo-50 dark:bg-indigo-900/20 border-2 border-indigo-500
                                         @else
                                             bg-gray-50 dark:bg-zinc-700 border border-gray-200 dark:border-zinc-600 hover:bg-gray-100 dark:hover:bg-zinc-600
                                         @endif">
                                        <div class="flex items-start justify-between">
                                            <div class="flex items-start space-x-3 flex-1">
                                                <div class="w-8 h-8 rounded-full flex items-center justify-center"
                                                     style="background-color: {{ $milestone->color }}22">
                                                    <span style="color: {{ $milestone->color }}">●</span>
                                                </div>
                                                <div class="flex-1">
                                                    <div class="font-medium text-gray-900 dark:text-white">{{ $milestone->name }}</div>
                                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                        Step {{ $milestone->sequence_order }}
                                                    </div>
                                                    <div class="mt-2">
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                                            @if($milestone->status === 'completed') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300
                                                            @elseif($milestone->status === 'in_progress') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300
                                                            @else bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300
                                                            @endif">
                                                            {{ ucfirst(str_replace('_', ' ', $milestone->status)) }}
                                                        </span>
                                                    </div>
                                                    @if($milestone->status !== 'completed')
                                                        <div class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                                                            {{ $milestone->completion_percentage }}% complete
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-12 bg-gray-50 dark:bg-zinc-700 rounded-lg">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">No milestones yet</p>
                                <button wire:click="showCreateMilestone"
                                        class="mt-4 px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                                    Create First Milestone
                                </button>
                            </div>
                        @endif
                    </div>

                    <!-- Right: Tasks & Documents -->
                    <div class="lg:col-span-2">
                        @if($currentMilestone)
                            <div class="space-y-6">
                                <!-- Milestone Header -->
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{ $currentMilestone->name }}</h2>
                                        @if($currentMilestone->description)
                                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $currentMilestone->description }}</p>
                                        @endif
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        @if($currentMilestone->status !== 'completed')
                                            <button wire:click="updateMilestoneStatus({{ $currentMilestone->id }}, 'completed')"
                                                    class="px-3 py-1 text-sm bg-green-600 text-white rounded-md hover:bg-green-700">
                                                Mark Complete
                                            </button>
                                        @endif
                                        <button wire:click="showEditMilestone({{ $currentMilestone->id }})"
                                                class="px-3 py-1 text-sm bg-gray-200 dark:bg-zinc-600 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-500">
                                            Edit
                                        </button>
                                        <button wire:click="deleteMilestone({{ $currentMilestone->id }})"
                                                wire:confirm="Are you sure you want to delete this milestone?"
                                                class="px-3 py-1 text-sm bg-red-600 text-white rounded-md hover:bg-red-700">
                                            Delete
                                        </button>
                                    </div>
                                </div>

                                <!-- Tasks Section -->
                                <div>
                                    <div class="flex items-center justify-between mb-3">
                                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Tasks</h3>
                                        <button wire:click="showCreateTask"
                                                class="px-3 py-1 text-sm bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                                            + Add Task
                                        </button>
                                    </div>

                                    @if($currentMilestone->tasks->count() > 0)
                                        <div class="space-y-2">
                                            @foreach($currentMilestone->tasks as $task)
                                                <div class="p-4 bg-gray-50 dark:bg-zinc-700 rounded-lg">
                                                    <div class="flex items-start justify-between">
                                                        <div class="flex items-start space-x-3 flex-1">
                                                            <input type="checkbox"
                                                                   wire:click="updateTaskStatus({{ $task->id }}, '{{ $task->status === 'completed' ? 'pending' : 'completed' }}')"
                                                                   {{ $task->status === 'completed' ? 'checked' : '' }}
                                                                   class="mt-1 h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                                            <div class="flex-1">
                                                                <div class="font-medium text-gray-900 dark:text-white {{ $task->status === 'completed' ? 'line-through' : '' }}">
                                                                    {{ $task->name }}
                                                                </div>
                                                                @if($task->description)
                                                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $task->description }}</p>
                                                                @endif
                                                                <div class="flex items-center space-x-4 mt-2 text-xs text-gray-500 dark:text-gray-400">
                                                                    @if($task->assignedTo)
                                                                        <span>Assigned: {{ $task->assignedTo->name }}</span>
                                                                    @endif
                                                                    @if($task->deadline)
                                                                        <span class="{{ $task->is_overdue ? 'text-red-600 dark:text-red-400 font-semibold' : '' }}">
                                                                            Due: {{ $task->deadline->format('M d, Y') }}
                                                                        </span>
                                                                    @endif
                                                                    <span class="px-2 py-0.5 rounded {{ $task->status_color === 'green' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : ($task->status_color === 'blue' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300') }}">
                                                                        {{ ucfirst($task->status) }}
                                                                    </span>
                                                                    @if($task->requires_approval)
                                                                        <span class="px-2 py-0.5 rounded text-xs bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300">
                                                                            Needs Approval
                                                                        </span>
                                                                    @endif
                                                                    @if($task->status === 'completed' && $task->approved_by)
                                                                        <span class="text-green-600 dark:text-green-400 flex items-center">
                                                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                                            </svg>
                                                                            Approved by {{ $task->approvedBy->name }}
                                                                        </span>
                                                                    @endif
                                                                </div>
                                                                @if($task->rejection_reason)
                                                                    <div class="mt-2 p-2 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded">
                                                                        <p class="text-xs font-semibold text-red-800 dark:text-red-200">Rejected</p>
                                                                        <p class="text-xs text-red-700 dark:text-red-300">{{ $task->rejection_reason }}</p>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <div class="flex items-center space-x-2 ml-4">
                                                            @if($task->requires_approval && $task->status === 'completed' && !$task->approved_by)
                                                                <button wire:click="approveTask({{ $task->id }})"
                                                                        class="px-3 py-1.5 text-xs bg-green-600 text-white rounded hover:bg-green-700">
                                                                    Approve
                                                                </button>
                                                                <button wire:click="$set('editingTask', {{ $task->id }}); $set('showTaskForm', true)"
                                                                        class="px-3 py-1.5 text-xs bg-red-600 text-white rounded hover:bg-red-700">
                                                                    Reject
                                                                </button>
                                                            @endif
                                                            <button wire:click="deleteTask({{ $task->id }})"
                                                                    wire:confirm="Delete this task?"
                                                                    class="text-red-600 hover:text-red-800 dark:text-red-400">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="text-center py-8 bg-gray-50 dark:bg-zinc-700 rounded-lg">
                                            <p class="text-sm text-gray-600 dark:text-gray-400">No tasks yet</p>
                                            <button wire:click="showCreateTask"
                                                    class="mt-2 text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                                                Add first task
                                            </button>
                                        </div>
                                    @endif
                                </div>

                                <!-- Documents Section -->
                                <div>
                                    <div class="flex items-center justify-between mb-3">
                                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Required Documents</h3>
                                        <button wire:click="showCreateDocument"
                                                class="px-3 py-1 text-sm bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                                            + Add Document
                                        </button>
                                    </div>

                                    @if($currentMilestone->documents->count() > 0)
                                        <div class="space-y-2">
                                            @foreach($currentMilestone->documents as $document)
                                                @php
                                                    $validMedia = $document->valid_document_media;
                                                    $missingMedia = $document->missing_document_media;
                                                    $hasFiles = $validMedia->isNotEmpty();
                                                @endphp
                                                <div class="p-4 bg-gray-50 dark:bg-zinc-700 rounded-lg">
                                                    <div class="flex items-start justify-between">
                                                        <div class="flex-1">
                                                            <div class="font-medium text-gray-900 dark:text-white">{{ $document->document_type }}</div>
                                                            @if($document->description)
                                                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $document->description }}</p>
                                                            @endif
                                                            <div class="flex items-center space-x-4 mt-2 text-xs text-gray-500 dark:text-gray-400">
                                                                @if($document->assignedTo)
                                                                    <span>Assigned: {{ $document->assignedTo->name }}</span>
                                                                @endif
                                                                @if($document->isGeneratedFromTemplate())
                                                                    <span class="px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300">
                                                                        Generated Form
                                                                    </span>
                                                                @else
                                                                    <span class="px-2 py-0.5 rounded {{ $document->status_color === 'green' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : ($document->status_color === 'blue' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' : ($document->status_color === 'yellow' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300' : ($document->status_color === 'red' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300'))) }}">
                                                                        {{ ucfirst(str_replace('_', ' ', $document->status)) }}
                                                                    </span>
                                                                @endif
                                                                @if($document->requires_approval)
                                                                    <span class="px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300">
                                                                        Needs Approval
                                                                    </span>
                                                                @endif
                                                                @if($hasFiles)
                                                                    <span class="flex items-center">
                                                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                            <path d="M8 4a3 3 0 00-3 3v4a5 5 0 0010 0V7a1 1 0 112 0v4a7 7 0 11-14 0V7a5 5 0 0110 0v4a3 3 0 11-6 0V7a1 1 0 012 0v4a1 1 0 102 0V7a3 3 0 00-3-3z"></path>
                                                                        </svg>
                                                                        {{ $validMedia->count() }} file(s)
                                                                    </span>
                                                                @endif
                                                            </div>
                                                            @if($document->approved_by && $document->approvedBy)
                                                                <div class="mt-2 flex items-center text-xs text-green-600 dark:text-green-400">
                                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                                    </svg>
                                                                    Approved by {{ $document->approvedBy->name }} on {{ $document->approved_at?->format('M d, Y') }}
                                                                </div>
                                                            @endif
                                                            @if($document->rejection_reason)
                                                                <div class="mt-2 p-2 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded">
                                                                    <p class="text-xs font-semibold text-red-800 dark:text-red-200">Rejected</p>
                                                                    <p class="text-xs text-red-700 dark:text-red-300">{{ $document->rejection_reason }}</p>
                                                                </div>
                                                            @endif
                                                            @if($missingMedia->isNotEmpty())
                                                                <p class="mt-2 text-xs text-red-600 dark:text-red-300">
                                                                    {{ $missingMedia->count() }} stored file(s) are unavailable. Please upload replacements.
                                                                </p>
                                                            @endif

                                                            {{-- PM/Admin can toggle requires approval --}}
                                                            @if(auth()->user()->hasRole('project_manager') || auth()->user()->hasRole('admin'))
                                                                <div class="mt-2">
                                                                    <label class="flex items-center space-x-2 cursor-pointer">
                                                                        <input type="checkbox"
                                                                               wire:click="toggleDocumentApproval({{ $document->id }})"
                                                                               {{ $document->requires_approval ? 'checked' : '' }}
                                                                               class="rounded border-gray-300 dark:border-zinc-600 text-indigo-600 focus:ring-indigo-500">
                                                                        <span class="text-xs text-gray-600 dark:text-gray-400">Requires Approval</span>
                                                                    </label>
                                                                </div>
                                                            @endif
                                                        </div>
                                                        <div class="flex items-center space-x-2">
                                                            {{-- Show Approve/Reject for pending documents --}}
                                                            @if($hasFiles && $document->status === 'pending_review' && $document->requires_approval && (auth()->user()->hasRole('project_manager') || auth()->user()->hasRole('admin')))
                                                                <button wire:click="$set('viewingDocumentId', {{ $document->id }}); approveDocumentFromViewer()"
                                                                        class="px-3 py-1.5 text-xs bg-green-600 text-white rounded hover:bg-green-700 flex items-center">
                                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                                    </svg>
                                                                    Approve
                                                                </button>
                                                                <button wire:click="showDocumentViewer({{ $document->id }})"
                                                                        class="px-3 py-1.5 text-xs bg-red-600 text-white rounded hover:bg-red-700">
                                                                    Reject
                                                                </button>
                                                            @endif

                                                            {{-- Show Regenerate if PDF was already generated from template --}}
                                                            @if($document->isGeneratedFromTemplate())
                                                                <button wire:click="regeneratePdf({{ $document->id }})"
                                                                        wire:confirm="Regenerate PDF with current project data?"
                                                                        class="px-3 py-1 text-xs bg-purple-600 text-white rounded hover:bg-purple-700 flex items-center">
                                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                                    </svg>
                                                                    Regenerate
                                                                </button>
                                                            @endif

                                                            {{-- Show Upload if no files exist OR if status is explicitly rejected --}}
                                                            @if(!$hasFiles || $document->status === 'rejected')
                                                                <button wire:click="showUploadDocument({{ $document->id }})"
                                                                        class="px-3 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700">
                                                                    {{ $hasFiles ? 'Replace' : 'Upload' }}
                                                                </button>
                                                            @endif

                                                            {{-- Show View if files exist (and not showing approve/reject) --}}
                                                            @if($hasFiles && !($document->status === 'pending_review' && $document->requires_approval && (auth()->user()->hasRole('project_manager') || auth()->user()->hasRole('admin'))))
                                                                <button wire:click="showDocumentViewer({{ $document->id }})"
                                                                        class="px-3 py-1 text-xs bg-gray-600 text-white rounded hover:bg-gray-700 flex items-center">
                                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                                    </svg>
                                                                    View
                                                                </button>
                                                            @endif

                                                            @if($document->status !== 'pending_review' || !$document->requires_approval)
                                                                <button wire:click="deleteDocument({{ $document->id }})"
                                                                        wire:confirm="Delete this document?"
                                                                        class="text-red-600 hover:text-red-800 dark:text-red-400">
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                                    </svg>
                                                                </button>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="text-center py-8 bg-gray-50 dark:bg-zinc-700 rounded-lg">
                                            <p class="text-sm text-gray-600 dark:text-gray-400">No documents required yet</p>
                                            <button wire:click="showCreateDocument"
                                                    class="mt-2 text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                                                Add first document
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="lg:col-span-2 text-center py-12 text-gray-500 dark:text-gray-400">
                                Select a milestone to view its tasks and documents
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <!-- Budget Tab -->
                <div class="space-y-6">
                    <!-- Budget Categories -->
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Budget Categories</h2>
                            <button wire:click="showCreateBudget"
                                    class="px-3 py-1 text-sm bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                                + Add Category
                            </button>
                        </div>

                        @if($job->budgets->count() > 0)
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                                    <thead class="bg-gray-50 dark:bg-zinc-900">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Category</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Fund Code</th>
                                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Budgeted</th>
                                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Spent</th>
                                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Remaining</th>
                                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">% Used</th>
                                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-700">
                                        @foreach($job->budgets as $budget)
                                            <tr>
                                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $budget->category }}</td>
                                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                                    @if($budget->contract)
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300" title="{{ $budget->contract->title }}">
                                                            {{ $budget->contract->grant_code ?: Str::limit($budget->contract->title, 15) }}
                                                        </span>
                                                    @else
                                                        <span class="text-gray-400 dark:text-gray-500">-</span>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-white">${{ number_format($budget->budgeted_amount, 2) }}</td>
                                                <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-white">${{ number_format($budget->spent_amount, 2) }}</td>
                                                <td class="px-4 py-3 text-sm text-right {{ $budget->remaining_amount < 0 ? 'text-red-600 dark:text-red-400 font-semibold' : 'text-gray-900 dark:text-white' }}">
                                                    ${{ number_format($budget->remaining_amount, 2) }}
                                                </td>
                                                <td class="px-4 py-3 text-sm text-right">
                                                    <span class="px-2 py-1 rounded text-xs {{ $budget->is_over_budget ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' : ($budget->percentage_spent > 90 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300' : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300') }}">
                                                        {{ $budget->percentage_spent }}%
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-sm text-right">
                                                    <button wire:click="deleteBudget({{ $budget->id }})"
                                                            wire:confirm="Delete this budget category?"
                                                            class="text-red-600 hover:text-red-800 dark:text-red-400">
                                                        Delete
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                        <tr class="bg-gray-100 dark:bg-zinc-900 font-semibold">
                                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">TOTAL</td>
                                            <td class="px-4 py-3"></td>
                                            <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-white">${{ number_format($job->getTotalBudget(), 2) }}</td>
                                            <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-white">${{ number_format($job->getTotalSpent(), 2) }}</td>
                                            <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-white">${{ number_format($job->getTotalBudget() - $job->getTotalSpent(), 2) }}</td>
                                            <td class="px-4 py-3 text-sm text-right"></td>
                                            <td class="px-4 py-3"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-8 bg-gray-50 dark:bg-zinc-700 rounded-lg">
                                <p class="text-sm text-gray-600 dark:text-gray-400">No budget categories defined</p>
                                <button wire:click="showCreateBudget"
                                        class="mt-2 text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                                    Add first category
                                </button>
                            </div>
                        @endif
                    </div>

                    <!-- Expenses -->
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Expenses</h2>
                            <button wire:click="showCreateExpense"
                                    class="px-3 py-1 text-sm bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                                + Record Expense
                            </button>
                        </div>

                        @if($job->expenses->count() > 0)
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                                    <thead class="bg-gray-50 dark:bg-zinc-900">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Date</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Category</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Fund Code</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Description</th>
                                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Amount</th>
                                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-700">
                                        @foreach($job->expenses->sortByDesc('expense_date') as $expense)
                                            <tr>
                                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $expense->expense_date->format('M d, Y') }}</td>
                                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $expense->category }}</td>
                                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                                    @if($expense->contract)
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300" title="{{ $expense->contract->title }}">
                                                            {{ $expense->contract->grant_code ?: Str::limit($expense->contract->title, 15) }}
                                                        </span>
                                                    @else
                                                        <span class="text-gray-400 dark:text-gray-500">-</span>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $expense->description ?: '-' }}</td>
                                                <td class="px-4 py-3 text-sm text-right {{ $expense->is_credit ? 'text-green-600 dark:text-green-400' : 'text-gray-900 dark:text-white' }}">
                                                    {{ $expense->formatted_amount }}
                                                </td>
                                                <td class="px-4 py-3 text-sm text-right">
                                                    <button wire:click="deleteExpense({{ $expense->id }})"
                                                            wire:confirm="Delete this expense?"
                                                            class="text-red-600 hover:text-red-800 dark:text-red-400">
                                                        Delete
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-8 bg-gray-50 dark:bg-zinc-700 rounded-lg">
                                <p class="text-sm text-gray-600 dark:text-gray-400">No expenses recorded</p>
                                <button wire:click="showCreateExpense"
                                        class="mt-2 text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                                    Record first expense
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Milestone Form Modal -->
    @if($showMilestoneForm)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    {{ $editingMilestone ? 'Edit Milestone' : 'Create Milestone' }}
                </h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name *</label>
                        <input type="text" wire:model="milestone_name"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                        @error('milestone_name') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                        <textarea wire:model="milestone_description" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Target Date</label>
                        <input type="date" wire:model="milestone_target_date"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Color</label>
                        <input type="color" wire:model="milestone_color"
                               class="w-full h-10 border border-gray-300 dark:border-zinc-600 rounded-md">
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button wire:click="$set('showMilestoneForm', false)"
                            class="px-4 py-2 bg-gray-200 dark:bg-zinc-600 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-500">
                        Cancel
                    </button>
                    <button wire:click="saveMilestone"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                        Save
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Task Form Modal -->
    @if($showTaskForm)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    {{ $editingTask ? 'Edit Task' : 'Create Task' }}
                </h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Task Name *</label>
                        <input type="text" wire:model="task_name"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                        @error('task_name') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                        <textarea wire:model="task_description" rows="2"
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Assign To</label>
                        <select wire:model="task_assigned_to"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                            <option value="">Unassigned</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Deadline</label>
                        <input type="date" wire:model="task_deadline"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                    </div>
                    <div>
                        <label class="flex items-center space-x-2">
                            <input type="checkbox" wire:model="task_requires_approval"
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Requires Approval</span>
                        </label>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button wire:click="$set('showTaskForm', false)"
                            class="px-4 py-2 bg-gray-200 dark:bg-zinc-600 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-500">
                        Cancel
                    </button>
                    <button wire:click="saveTask"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                        Save
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Document Form Modal -->
    @if($showDocumentForm)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Add Required Document</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Document Type *</label>
                        <input type="text" wire:model="document_type" placeholder="e.g., Building Permit, Invoice, Photo"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                        @error('document_type') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                        <textarea wire:model="document_description" rows="2"
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Assign To</label>
                        <select wire:model="document_assigned_to"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                            <option value="">Unassigned</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">PDF Form Template (Optional)</label>
                        <select wire:model="document_pdf_template_id"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                            <option value="">No Template (Manual Upload Only)</option>
                            @foreach($pdfTemplates as $template)
                                <option value="{{ $template->id }}">{{ $template->name }} ({{ $template->field_count }} fields)</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Select a PDF form template to auto-fill with project data
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Upload Files (Optional)</label>
                        <input type="file" wire:model="documentFiles" multiple
                               class="w-full text-sm text-gray-500 dark:text-gray-400
                                      file:mr-4 file:py-2 file:px-4
                                      file:rounded-md file:border-0
                                      file:text-sm file:font-semibold
                                      file:bg-indigo-50 file:text-indigo-700
                                      hover:file:bg-indigo-100">
                    </div>
                    <div>
                        <label class="flex items-center space-x-2">
                            <input type="checkbox" wire:model="document_requires_approval"
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Requires Approval</span>
                        </label>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button wire:click="$set('showDocumentForm', false)"
                            class="px-4 py-2 bg-gray-200 dark:bg-zinc-600 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-500">
                        Cancel
                    </button>
                    <button wire:click="saveDocument"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                        Save
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Budget Form Modal -->
    @if($showBudgetForm)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Add Budget Category</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category Name *</label>
                        <input type="text" wire:model="budget_category" placeholder="e.g., Labor, Materials, Permits"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                        @error('budget_category') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Budgeted Amount *</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 dark:text-gray-400">$</span>
                            <input type="number" step="0.01" wire:model="budget_amount"
                                   class="w-full pl-7 px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                        </div>
                        @error('budget_amount') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Funding Source (Contract)</label>
                        <select wire:model="budget_contract_id"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                            <option value="">Select a contract (optional)</option>
                            @foreach($contracts as $contract)
                                <option value="{{ $contract->id }}">{{ $contract->title }}{{ $contract->grant_code ? ' (' . $contract->grant_code . ')' : '' }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Associate this budget with a funding source</p>
                        @error('budget_contract_id') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button wire:click="$set('showBudgetForm', false)"
                            class="px-4 py-2 bg-gray-200 dark:bg-zinc-600 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-500">
                        Cancel
                    </button>
                    <button wire:click="saveBudget"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                        Save
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Expense Form Modal -->
    @if($showExpenseForm)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Record Expense</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category *</label>
                        <select wire:model="expense_category"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                            <option value="">Select a category</option>
                            @foreach($job->budgets as $budget)
                                <option value="{{ $budget->category }}">{{ $budget->category }}</option>
                            @endforeach
                        </select>
                        @error('expense_category') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Funding Source (Contract)</label>
                        <select wire:model="expense_contract_id"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                            <option value="">Select a contract (optional)</option>
                            @foreach($contracts as $contract)
                                <option value="{{ $contract->id }}">{{ $contract->title }}{{ $contract->grant_code ? ' (' . $contract->grant_code . ')' : '' }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Which funding source covers this expense</p>
                        @error('expense_contract_id') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Amount *</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 dark:text-gray-400">$</span>
                            <input type="number" step="0.01" wire:model="expense_amount"
                                   class="w-full pl-7 px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Use negative amount for credits</p>
                        @error('expense_amount') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date *</label>
                        <input type="date" wire:model="expense_date"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                        @error('expense_date') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                        <textarea wire:model="expense_description" rows="2"
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md"></textarea>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button wire:click="$set('showExpenseForm', false)"
                            class="px-4 py-2 bg-gray-200 dark:bg-zinc-600 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-500">
                        Cancel
                    </button>
                    <button wire:click="saveExpense"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                        Save
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Edit Project Modal -->
    @if($showEditProjectForm)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Edit Project Information</h3>

                <!-- Project Summary (Read-Only) -->
                <div class="mb-6 p-4 bg-gray-50 dark:bg-zinc-700 rounded-lg">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="font-medium text-gray-700 dark:text-gray-300">Job Number:</span>
                            <span class="ml-2 text-gray-900 dark:text-white">{{ $job->job_number }}</span>
                        </div>
                        <div>
                            <span class="font-medium text-gray-700 dark:text-gray-300">Program:</span>
                            <span class="ml-2 text-gray-900 dark:text-white">{{ $job->program?->name ?? 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="font-medium text-gray-700 dark:text-gray-300">Property:</span>
                            <span class="ml-2 text-gray-900 dark:text-white">{{ $job->property?->address_line_1 ?? 'N/A' }}</span>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <!-- Row 1: Title -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Project Title *</label>
                        <input type="text" wire:model="edit_job_title"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                        @error('edit_job_title') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <!-- Participant Selection -->
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
                            :selected="$edit_participant_id"
                            name="edit_participant"
                            placeholder="Search participants by name..."
                            display-field="display"
                            secondary-field="secondary"
                            :required="true"
                            empty-message="No heads of household found. Create a new participant above."
                            :key="'edit-participant-select-' . ($edit_participant_id ?: 'none')"
                        />
                        @error('edit_participant_id') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Changing participant will also update the associated household.</p>
                    </div>

                    <!-- Row 2: Status & Priority -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status *</label>
                            <select wire:model="edit_status"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="in_progress">In Progress</option>
                                <option value="on_hold">On Hold</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            @error('edit_status') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Priority *</label>
                            <select wire:model="edit_priority"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="emergency">Emergency</option>
                            </select>
                            @error('edit_priority') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Row 3: Dates -->
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Request Date</label>
                            <input type="date" wire:model="edit_request_date"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start Date</label>
                            <input type="date" wire:model="edit_start_date"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Target Completion</label>
                            <input type="date" wire:model="edit_target_completion_date"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                        </div>
                    </div>

                    <!-- Row 4: Contractor & Estimated Cost -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Contractor</label>
                            <select wire:model="edit_contractor_id"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                                <option value="">No Contractor Assigned</option>
                                @foreach($contractors as $contractor)
                                    <option value="{{ $contractor->id }}">{{ $contractor->company_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Estimated Cost</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 dark:text-gray-400">$</span>
                                <input type="number" step="0.01" wire:model="edit_estimated_cost"
                                       class="w-full pl-7 px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                            </div>
                        </div>
                    </div>

                    <!-- Row 5: Description -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                        <textarea wire:model="edit_job_description" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md"></textarea>
                    </div>

                    <!-- Row 6: Internal Notes -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Internal Notes</label>
                        <textarea wire:model="edit_internal_notes" rows="2" placeholder="Notes visible only to staff..."
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md"></textarea>
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button wire:click="$set('showEditProjectForm', false)"
                            class="px-4 py-2 bg-gray-200 dark:bg-zinc-600 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-500">
                        Cancel
                    </button>
                    <button wire:click="updateProjectInfo"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                        Save Changes
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Quick Participant Creation Modal -->
    @if($showQuickParticipantForm)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-[60]">
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-md w-full mx-4 max-h-[90vh] overflow-y-auto">
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

    <!-- Save as Template Modal -->
    @if($showSaveAsTemplateForm)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Save as Template</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    This will create a reusable template with {{ $job->milestones()->count() }} milestones,
                    {{ $job->milestones->sum(fn($m) => $m->tasks->count()) }} tasks, and
                    {{ $job->milestones->sum(fn($m) => $m->documents->count()) }} document requirements.
                </p>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Template Name *</label>
                        <input type="text" wire:model="template_name" placeholder="e.g., Standard Whole Home Rehab"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                        @error('template_name') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                        <textarea wire:model="template_description" rows="3" placeholder="Describe when to use this template..."
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md"></textarea>
                    </div>
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-md p-3">
                        <p class="text-xs text-blue-800 dark:text-blue-300">
                            <strong>What's copied:</strong> Milestone structure, tasks, documents, budget categories<br>
                            <strong>What's NOT copied:</strong> Assignments, dates, status, uploaded files, expenses
                        </p>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" wire:click="$set('showSaveAsTemplateForm', false)"
                            class="px-4 py-2 bg-gray-200 dark:bg-zinc-600 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-300">
                        Cancel
                    </button>
                    <button type="button" wire:click="saveAsTemplate"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-50 cursor-not-allowed"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                        <span wire:loading.remove wire:target="saveAsTemplate">Create Template</span>
                        <span wire:loading wire:target="saveAsTemplate">Creating...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Document Upload Modal -->
    @if($showDocumentUploadForm)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-lg w-full mx-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Upload Document Files</h3>
                    <button wire:click="$set('showDocumentUploadForm', false)"
                            class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Select Files *</label>
                        <input type="file" wire:model="uploadFiles" multiple
                               class="w-full text-sm text-gray-900 dark:text-gray-300 border border-gray-300 dark:border-zinc-600 rounded-lg cursor-pointer bg-gray-50 dark:bg-zinc-700">
                        @error('uploadFiles.*') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Max 50MB per file. Multiple files allowed.</p>
                    </div>

                    @if($uploadFiles)
                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
                            <p class="text-xs font-medium text-blue-800 dark:text-blue-300 mb-2">Selected Files ({{ count($uploadFiles) }}):</p>
                            <ul class="text-xs text-blue-700 dark:text-blue-400 space-y-1">
                                @foreach($uploadFiles as $file)
                                    <li class="flex items-center">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M8 4a3 3 0 00-3 3v4a5 5 0 0010 0V7a1 1 0 112 0v4a7 7 0 11-14 0V7a5 5 0 0110 0v4a3 3 0 11-6 0V7a1 1 0 012 0v4a1 1 0 102 0V7a3 3 0 00-3-3z"></path>
                                        </svg>
                                        {{ $file->getClientOriginalName() }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div wire:loading wire:target="uploadFiles" class="text-sm text-gray-600 dark:text-gray-400">
                        Loading files...
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" wire:click="$set('showDocumentUploadForm', false)"
                            class="px-4 py-2 bg-gray-200 dark:bg-zinc-600 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-300">
                        Cancel
                    </button>
                    <button type="button" wire:click="uploadDocumentFiles"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-50 cursor-not-allowed"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        <span wire:loading.remove wire:target="uploadDocumentFiles">Upload Files</span>
                        <span wire:loading wire:target="uploadDocumentFiles">Uploading...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Document Viewer/Review Modal -->
    @if($showDocumentViewerModal)
        @php
            $viewingDocument = $job->milestones->flatMap->documents->firstWhere('id', $viewingDocumentId);
        @endphp
        @if($viewingDocument)
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
                <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                    <!-- Header -->
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-900">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $viewingDocument->document_type }}</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    Status: <span class="px-2 py-0.5 rounded {{ $viewingDocument->status_color === 'green' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : ($viewingDocument->status_color === 'yellow' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300' : ($viewingDocument->status_color === 'red' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300')) }}">
                                        {{ ucfirst(str_replace('_', ' ', $viewingDocument->status)) }}
                                    </span>
                                </p>
                            </div>
                            <button wire:click="$set('showDocumentViewerModal', false)"
                                    class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Document Details -->
                    <div class="px-6 py-4">
                        @if($viewingDocument->description)
                            <div class="mb-4">
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $viewingDocument->description }}</p>
                            </div>
                        @endif

                        <div class="grid grid-cols-2 gap-4 mb-4 text-sm">
                            @if($viewingDocument->assignedTo)
                                <div>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">Assigned To:</span>
                                    <p class="text-gray-600 dark:text-gray-400">{{ $viewingDocument->assignedTo->name }}</p>
                                </div>
                            @endif
                            @if($viewingDocument->uploaded_date)
                                <div>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">Uploaded:</span>
                                    <p class="text-gray-600 dark:text-gray-400">{{ $viewingDocument->uploaded_date->format('M j, Y g:i A') }}</p>
                                </div>
                            @endif
                            @if($viewingDocument->approved_at)
                                <div>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">Approved:</span>
                                    <p class="text-gray-600 dark:text-gray-400">{{ $viewingDocument->approved_at->format('M j, Y g:i A') }}</p>
                                </div>
                            @endif
                            @if($viewingDocument->approvedBy)
                                <div>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">Approved By:</span>
                                    <p class="text-gray-600 dark:text-gray-400">{{ $viewingDocument->approvedBy->name }}</p>
                                </div>
                            @endif
                        </div>

                        @if($viewingDocument->rejection_reason)
                            <div class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded">
                                <p class="text-sm font-medium text-red-800 dark:text-red-300">Rejection Reason:</p>
                                <p class="text-sm text-red-700 dark:text-red-400 mt-1">{{ $viewingDocument->rejection_reason }}</p>
                            </div>
                        @endif

                        <!-- Uploaded Files -->
                        @php
                            $viewerMedia = $viewingDocument->valid_document_media;
                            $missingViewerMedia = $viewingDocument->missing_document_media;
                        @endphp
                        @if($viewerMedia->count() > 0)
                            <div>
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                    Uploaded Files ({{ $viewerMedia->count() }})
                                </h4>
                                <div class="space-y-2">
                                    @foreach($viewerMedia as $media)
                                        <div class="p-4 bg-gray-50 dark:bg-zinc-700 rounded-lg">
                                            <div class="flex items-start justify-between">
                                                <div class="flex items-start space-x-3 flex-1">
                                                    <svg class="w-8 h-8 text-gray-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M8 4a3 3 0 00-3 3v4a5 5 0 0010 0V7a1 1 0 112 0v4a7 7 0 11-14 0V7a5 5 0 0110 0v4a3 3 0 11-6 0V7a1 1 0 012 0v4a1 1 0 102 0V7a3 3 0 00-3-3z"></path>
                                                    </svg>
                                                    <div class="flex-1 min-w-0">
                                                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $media->name }}</p>
                                                        <div class="flex items-center space-x-3 mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                            <span>{{ $media->human_readable_size }}</span>
                                                            <span>{{ $media->mime_type }}</span>
                                                            <span>Uploaded {{ $media->created_at->diffForHumans() }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="flex items-center space-x-2 ml-4">
                                                    <a href="{{ route('media.preview', $media) }}" target="_blank"
                                                       class="px-3 py-1 text-xs bg-green-600 text-white rounded hover:bg-green-700">
                                                        Preview
                                                    </a>
                                                    <a href="{{ route('media.download', $media) }}"
                                                       class="px-3 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700">
                                                        Download
                                                    </a>
                                                    @if($viewingDocument->status !== 'approved')
                                                        <button wire:click="deleteDocumentFile({{ $viewingDocument->id }}, {{ $media->id }})"
                                                                wire:confirm="Delete this file?"
                                                                class="text-red-600 hover:text-red-800 dark:text-red-400">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                            </svg>
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                @if($missingViewerMedia->isNotEmpty())
                                    <div class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded">
                                        <p class="text-xs text-red-700 dark:text-red-300">
                                            The following files are missing and should be re-uploaded:
                                            {{ $missingViewerMedia->pluck('name')->join(', ') }}
                                        </p>
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="text-center py-8 bg-gray-50 dark:bg-zinc-700 rounded-lg">
                                <svg class="w-12 h-12 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">No files uploaded yet</p>
                            </div>
                        @endif
                    </div>

                    <!-- Action Buttons -->
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-900">
                        @if($viewingDocument->status === 'pending_review' && $viewingDocument->requires_approval)
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Rejection Reason (if rejecting)</label>
                                <textarea wire:model="rejection_reason" rows="2"
                                          class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md text-sm"
                                          placeholder="Enter reason for rejection (required if rejecting)..."></textarea>
                                @error('rejection_reason') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                            </div>
                        @endif

                        <div class="flex justify-between items-center">
                            <div class="space-x-2">
                                @if($viewingDocument->status !== 'approved' && $viewerMedia->count() > 0)
                                    <button wire:click="$set('showDocumentViewerModal', false); showUploadDocument({{ $viewingDocument->id }})"
                                            class="px-4 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                        Add More Files
                                    </button>
                                @endif
                            </div>

                            <div class="flex space-x-3">
                                @if($viewingDocument->status === 'pending_review' && $viewingDocument->requires_approval)
                                    <button wire:click="rejectDocumentFromViewer"
                                            class="px-4 py-2 text-sm bg-red-600 text-white rounded-md hover:bg-red-700">
                                        Reject
                                    </button>
                                    <button wire:click="approveDocumentFromViewer"
                                            class="px-4 py-2 text-sm bg-green-600 text-white rounded-md hover:bg-green-700">
                                        Approve
                                    </button>
                                @endif
                                <button wire:click="$set('showDocumentViewerModal', false)"
                                        class="px-4 py-2 text-sm bg-gray-200 dark:bg-zinc-600 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-300">
                                    Close
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif

</div>
