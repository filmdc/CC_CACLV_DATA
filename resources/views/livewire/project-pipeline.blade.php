<div class="w-full px-0 py-6">
    <div class="bg-white dark:bg-zinc-800 shadow-lg rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-indigo-600 text-white p-6">
            <h1 class="text-2xl font-bold">Project Pipeline</h1>
            <p class="text-indigo-100 mt-2">Overview of all active construction projects with milestone tracking</p>
        </div>

        <!-- Search and Filters -->
        <div class="p-4 bg-gray-50 dark:bg-zinc-700 border-b dark:border-zinc-600">
            <div class="flex items-center justify-between mb-4">
                <div class="flex-1 grid grid-cols-1 md:grid-cols-3 gap-4 mr-4">
                    <div>
                        <input type="text" wire:model.live="search" placeholder="Search projects, participants, or properties..."
                               class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <select wire:model.live="filterProgram"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">All Programs</option>
                            @foreach($programs as $program)
                                <option value="{{ $program->id }}">{{ $program->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <select wire:model.live="filterStatus"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="in_progress">In Progress</option>
                            <option value="on_hold">On Hold</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                </div>
                <div>
                    <a href="{{ route('housing-job.management') }}" wire:navigate
                       class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Create New Project
                    </a>
                </div>
            </div>
        </div>

        <!-- Projects Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                <thead class="bg-gray-100 dark:bg-zinc-900">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider cursor-pointer" wire:click="sortBy('job_number')">
                            Project #
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Participant
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Property
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Program
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Current Milestone
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Last Completed
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider cursor-pointer" wire:click="sortBy('target_completion_date')">
                            Next Deadline
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-700">
                    @forelse($projects as $project)
                        @php
                            $currentMilestone = $project->getCurrentMilestone();
                            $lastCompleted = $project->getLastCompletedMilestone();
                            $nextDeadline = $project->getNextDeadline();
                            $risk = $this->getRiskStatus($project);
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-zinc-700">
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $project->job_number }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ Str::limit($project->job_title, 30) }}
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">
                                    {{ $project->participant ? $project->participant->first_name . ' ' . $project->participant->last_name : 'N/A' }}
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">
                                    {{ $project->property ? Str::limit($project->property->address, 30) : 'N/A' }}
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">
                                    {{ $project->program ? $project->program->name : 'N/A' }}
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if($currentMilestone)
                                    <div class="flex items-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                              style="background-color: {{ $currentMilestone->color }}22; color: {{ $currentMilestone->color }}">
                                            {{ $currentMilestone->name }}
                                        </span>
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        {{ $currentMilestone->completion_percentage }}% complete
                                    </div>
                                @else
                                    <span class="text-sm text-gray-500 dark:text-gray-400">No active milestone</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if($lastCompleted)
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        {{ $lastCompleted->name }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $lastCompleted->completed_date?->format('M d, Y') }}
                                    </div>
                                @else
                                    <span class="text-sm text-gray-500 dark:text-gray-400">None</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if($nextDeadline)
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        {{ \Carbon\Carbon::parse($nextDeadline)->format('M d, Y') }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ \Carbon\Carbon::parse($nextDeadline)->diffForHumans() }}
                                    </div>
                                @else
                                    <span class="text-sm text-gray-500 dark:text-gray-400">No deadline</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @if($risk['color'] === 'green') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300
                                    @elseif($risk['color'] === 'yellow') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300
                                    @elseif($risk['color'] === 'red') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300
                                    @else bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300
                                    @endif">
                                    {{ $risk['label'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm">
                                <a href="{{ route('project.management', $project->id) }}" wire:navigate
                                   class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">
                                    View Details
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                <div class="flex flex-col items-center">
                                    <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                    </svg>
                                    <p class="text-lg font-medium">No projects found</p>
                                    <p class="text-sm mt-1">Create a housing job and add milestones to see it in the pipeline</p>
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
    </div>
</div>
