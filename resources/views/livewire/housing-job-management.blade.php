<div class="max-w-7xl mx-auto p-6">
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl">
        <!-- Header -->
        <div class="bg-amber-600 text-white p-6">
            <h1 class="text-2xl font-bold">Housing Job Management</h1>
            <p class="text-amber-100 mt-2">Manage housing rehabilitation jobs and track progress</p>
        </div>

        <!-- Filters and Search -->
        <div class="p-4 bg-gray-50 dark:bg-zinc-700 border-b border-gray-200 dark:border-zinc-600">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Search -->
                <div class="md:col-span-2">
                    <input type="text" wire:model.live="search" 
                           placeholder="Search by job #, title, address, contractor, or work order..." 
                           class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white focus:border-amber-500 focus:ring-amber-500">
                </div>
                
                <!-- Status Filter -->
                <div>
                    <select wire:model.live="filterStatus" 
                            class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white focus:border-amber-500 focus:ring-amber-500">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="in_progress">In Progress</option>
                        <option value="on_hold">On Hold</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                
                <!-- Priority Filter -->
                <div>
                    <select wire:model.live="filterPriority" 
                            class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white focus:border-amber-500 focus:ring-amber-500">
                        <option value="">All Priorities</option>
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="emergency">Emergency</option>
                    </select>
                </div>
            </div>
            
            <!-- Additional Filters -->
            <div class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Program Filter -->
                <div>
                    <select wire:model.live="filterProgram" 
                            class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white focus:border-amber-500 focus:ring-amber-500">
                        <option value="">All Programs</option>
                        @foreach($housingPrograms as $program)
                            <option value="{{ $program->id }}">{{ $program->name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <!-- Contractor Filter -->
                <div>
                    <select wire:model.live="filterContractor" 
                            class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white focus:border-amber-500 focus:ring-amber-500">
                        <option value="">All Contractors</option>
                        @foreach($activeContractors as $contractor)
                            <option value="{{ $contractor->id }}">{{ $contractor->company_name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <!-- Overdue Filter -->
                <div class="flex items-center">
                    <label class="flex items-center">
                        <input type="checkbox" wire:model.live="filterOverdue" 
                               class="rounded border-gray-300 text-amber-600 focus:ring-amber-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Overdue Jobs Only</span>
                    </label>
                </div>
                
                <!-- Create Button -->
                <div class="flex justify-end">
                    @can('housing_jobs.create')
                        <button wire:click="showHousingJobCreateForm" 
                                class="px-4 py-2 bg-amber-600 text-white rounded-md hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500">
                            <svg class="inline-block w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Create Job
                        </button>
                    @endcan
                </div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        @if (session()->has('message'))
            <div class="p-4 bg-green-50 dark:bg-green-900 border-l-4 border-green-400">
                <div class="flex">
                    <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="ml-3 text-sm text-green-700 dark:text-green-200">{{ session('message') }}</p>
                </div>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="p-4 bg-red-50 dark:bg-red-900 border-l-4 border-red-400">
                <div class="flex">
                    <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="ml-3 text-sm text-red-700 dark:text-red-200">{{ session('error') }}</p>
                </div>
            </div>
        @endif

        <!-- Jobs Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                <thead class="bg-gray-50 dark:bg-zinc-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Job #</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Property</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Contractor</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Priority</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Target Date</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-700">
                    @forelse($jobs as $job)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $job->job_number }}
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $job->job_title }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($job->property)
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        {{ $job->property->address_line_1 }}
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $job->property->city }}, {{ $job->property->state }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($job->contractor)
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        {{ $job->contractor->company_name }}
                                    </div>
                                @else
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Not Assigned</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">
                                    {{ $job->jobType->name ?? 'N/A' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    @if($job->priority === 'emergency') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                    @elseif($job->priority === 'high') bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200
                                    @elseif($job->priority === 'medium') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                    @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200
                                    @endif">
                                    {{ ucfirst($job->priority) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    @if($job->status === 'completed') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                    @elseif($job->status === 'in_progress') bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200
                                    @elseif($job->status === 'approved') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                    @elseif($job->status === 'pending') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                    @elseif($job->status === 'on_hold') bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200
                                    @elseif($job->status === 'cancelled') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                    @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200
                                    @endif">
                                    {{ str_replace('_', ' ', ucfirst($job->status)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($job->target_completion_date)
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        {{ $job->target_completion_date->format('M d, Y') }}
                                    </div>
                                    @if($job->isOverdue())
                                        <div class="text-xs text-red-600 dark:text-red-400">
                                            {{ $job->days_overdue }} days overdue
                                        </div>
                                    @endif
                                @else
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Not Set</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                <button wire:click="viewDetails({{ $job->id }})" 
                                        class="text-amber-600 dark:text-amber-400 hover:text-amber-900 dark:hover:text-amber-300 mr-3">
                                    View
                                </button>
                                @can('housing_jobs.update')
                                    <button wire:click="showEditForm({{ $job->id }})" 
                                            class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 mr-3">
                                        Edit
                                    </button>
                                @endcan
                                @can('housing_jobs.delete')
                                    @if(!in_array($job->status, ['in_progress', 'completed']))
                                        <button wire:click="delete({{ $job->id }})" 
                                                wire:confirm="Are you sure you want to delete this job?"
                                                class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">
                                            Delete
                                        </button>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                No housing jobs found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="p-4 border-t border-gray-200 dark:border-zinc-700">
            {{ $jobs->links() }}
        </div>
    </div>

    <!-- Create/Edit Form Modal -->
    @if($showCreateForm || $showEditForm)
        <div class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-zinc-800 rounded-lg max-w-6xl w-full max-h-[90vh] overflow-y-auto shadow-2xl">
                <div class="sticky top-0 bg-white dark:bg-zinc-800 border-b border-gray-200 dark:border-zinc-700 px-6 py-4 rounded-t-lg">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                            <svg class="inline-block w-6 h-6 mr-2 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                            </svg>
                            {{ $showCreateForm ? 'Create New Housing Job' : 'Edit Housing Job' }}
                        </h3>
                        <button type="button" wire:click="resetForm" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div class="p-6">
                    <form wire:submit="{{ $showCreateForm ? 'save' : 'update' }}">
                        <!-- Property & Location Section -->
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 mb-6">
                            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-3 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                Property & Location
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <!-- Property -->
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                            </svg>
                                            Property <span class="text-red-500">*</span>
                                        </span>
                                    </label>
                                    <select wire:model.live="property_id" 
                                            class="w-full rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-amber-500 focus:ring-amber-500 transition-colors">
                                        <option value="">Choose a property...</option>
                                        @foreach($properties as $property)
                                            <option value="{{ $property->id }}">
                                                {{ $property->address_line_1 }}, {{ $property->city }} - 
                                                HH: {{ $property->household->household_code ?? 'N/A' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('property_id') <span class="text-red-500 text-xs flex items-center mt-1"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
                                </div>

                                <!-- Household (Auto-filled) -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                            </svg>
                                            Household <span class="text-red-500">*</span>
                                        </span>
                                    </label>
                                    <select wire:model="household_id" disabled
                                            class="w-full rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white bg-gray-100 dark:bg-zinc-600 transition-colors">
                                        <option value="">Auto-filled from Property</option>
                                        @foreach($households as $household)
                                            <option value="{{ $household->id }}">{{ $household->household_code }}</option>
                                        @endforeach
                                    </select>
                                    @error('household_id') <span class="text-red-500 text-xs flex items-center mt-1"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Job Information Section -->
                        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 mb-6">
                            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-3 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                                </svg>
                                Job Information
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Job Title -->
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                            </svg>
                                            Job Title <span class="text-red-500">*</span>
                                        </span>
                                    </label>
                                    <input type="text" wire:model="job_title" placeholder="Roof Repair - Emergency Leak"
                                           class="w-full rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-amber-500 focus:ring-amber-500 transition-colors">
                                    @error('job_title') <span class="text-red-500 text-xs flex items-center mt-1"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
                                </div>

                                <!-- Job Type -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                            </svg>
                                            Job Type <span class="text-red-500">*</span>
                                        </span>
                                    </label>
                                    <select wire:model="job_type_id" 
                                            class="w-full rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-amber-500 focus:ring-amber-500 transition-colors">
                                        <option value="">Select job type...</option>
                                        @foreach($jobTypes as $type)
                                            <option value="{{ $type->id }}">{{ $type->label }}</option>
                                        @endforeach
                                    </select>
                                    @error('job_type_id') <span class="text-red-500 text-xs flex items-center mt-1"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
                                </div>

                                <!-- Priority -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            Priority <span class="text-red-500">*</span>
                                        </span>
                                    </label>
                                    <select wire:model="priority" 
                                            class="w-full rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-amber-500 focus:ring-amber-500 transition-colors">
                                        <option value="low">Low</option>
                                        <option value="medium">Medium</option>
                                        <option value="high">High</option>
                                        <option value="emergency">Emergency</option>
                                    </select>
                                    @error('priority') <span class="text-red-500 text-xs flex items-center mt-1"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
                                </div>

                                <!-- Description -->
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
                                            </svg>
                                            Job Description <span class="text-gray-400">(Optional)</span>
                                        </span>
                                    </label>
                                    <textarea wire:model="job_description" rows="3" placeholder="Detailed description of the work required..."
                                              class="w-full rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-amber-500 focus:ring-amber-500 transition-colors resize-none"></textarea>
                                    @error('job_description') <span class="text-red-500 text-xs flex items-center mt-1"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Program & Service Section -->
                        <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4 mb-6">
                            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-3 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                </svg>
                                Program & Service Assignment
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Program -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                            </svg>
                                            Program <span class="text-red-500">*</span>
                                        </span>
                                    </label>
                                    <select wire:model.live="program_id" 
                                            class="w-full rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-amber-500 focus:ring-amber-500 transition-colors">
                                        <option value="">Select program...</option>
                                        @foreach($housingPrograms as $program)
                                            <option value="{{ $program->id }}">{{ $program->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('program_id') <span class="text-red-500 text-xs flex items-center mt-1"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
                                </div>

                                <!-- Service -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                            </svg>
                                            Service <span class="text-gray-400">(Optional)</span>
                                        </span>
                                    </label>
                                    <select wire:model="service_id" 
                                            class="w-full rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-amber-500 focus:ring-amber-500 transition-colors">
                                        <option value="">Select service (Optional)...</option>
                                        @foreach($services as $service)
                                            <option value="{{ $service->id }}">{{ $service->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('service_id') <span class="text-red-500 text-xs flex items-center mt-1"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Contractor Assignment Section -->
                        <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-4 mb-6">
                            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-3 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 112 2v6a2 2 0 01-2 2H8a2 2 0 01-2-2V8a2 2 0 012-2z"></path>
                                </svg>
                                Contractor Assignment
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <!-- Contractor -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                            </svg>
                                            Contractor <span class="text-gray-400">(Optional)</span>
                                        </span>
                                    </label>
                                    <select wire:model="contractor_id" 
                                            class="w-full rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-amber-500 focus:ring-amber-500 transition-colors">
                                        <option value="">Select contractor...</option>
                                        @foreach($contractors as $contractor)
                                            <option value="{{ $contractor->id }}">{{ $contractor->company_name }}</option>
                                        @endforeach
                                    </select>
                                    @error('contractor_id') <span class="text-red-500 text-xs flex items-center mt-1"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
                                </div>

                                <!-- Work Order Number -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            Work Order Number
                                        </span>
                                    </label>
                                    <input type="text" wire:model="work_order_number" placeholder="WO-2025-001"
                                           class="w-full rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-amber-500 focus:ring-amber-500 transition-colors">
                                    @error('work_order_number') <span class="text-red-500 text-xs flex items-center mt-1"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
                                </div>

                                <!-- Contract Number -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            Contract Number
                                        </span>
                                    </label>
                                    <input type="text" wire:model="contract_number" placeholder="CONTRACT-2025-001"
                                           class="w-full rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-amber-500 focus:ring-amber-500 transition-colors">
                                    @error('contract_number') <span class="text-red-500 text-xs flex items-center mt-1"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Important Dates Section -->
                        <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-lg p-4 mb-6">
                            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-3 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 0h8m-6 0V5a2 2 0 012-2h2a2 2 0 012 2v2m-6 0V3a2 2 0 012-2h2a2 2 0 012 2v2"></path>
                                </svg>
                                Important Dates
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <!-- Request Date -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Request Date <span class="text-red-500">*</span>
                                    </label>
                                    <input type="date" wire:model="request_date" 
                                           class="w-full rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-amber-500 focus:ring-amber-500 transition-colors">
                                    @error('request_date') <span class="text-red-500 text-xs flex items-center mt-1"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
                                </div>

                                <!-- Approval Date -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Approval Date
                                    </label>
                                    <input type="date" wire:model="approval_date" 
                                           class="w-full rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-amber-500 focus:ring-amber-500 transition-colors">
                                    @error('approval_date') <span class="text-red-500 text-xs flex items-center mt-1"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
                                </div>

                                <!-- Start Date -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Start Date
                                    </label>
                                    <input type="date" wire:model="start_date" 
                                           class="w-full rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-amber-500 focus:ring-amber-500 transition-colors">
                                    @error('start_date') <span class="text-red-500 text-xs flex items-center mt-1"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
                                </div>

                                <!-- Target Completion Date -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Target Completion Date
                                    </label>
                                    <input type="date" wire:model="target_completion_date" 
                                           class="w-full rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-amber-500 focus:ring-amber-500 transition-colors">
                                    @error('target_completion_date') <span class="text-red-500 text-xs flex items-center mt-1"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
                                </div>

                                <!-- Actual Completion Date -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Actual Completion Date
                                    </label>
                                    <input type="date" wire:model="actual_completion_date" 
                                           class="w-full rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-amber-500 focus:ring-amber-500 transition-colors">
                                    @error('actual_completion_date') <span class="text-red-500 text-xs flex items-center mt-1"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Financial Information Section -->
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4 mb-6">
                            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-3 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Financial Information
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <!-- Estimated Cost -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                            </svg>
                                            Estimated Cost
                                        </span>
                                    </label>
                                    <input type="number" wire:model="estimated_cost" step="0.01" placeholder="5000.00"
                                           class="w-full rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-amber-500 focus:ring-amber-500 transition-colors">
                                    @error('estimated_cost') <span class="text-red-500 text-xs flex items-center mt-1"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
                                </div>

                                <!-- Approved Amount -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Approved Amount
                                    </label>
                                    <input type="number" wire:model="approved_amount" step="0.01" placeholder="4500.00"
                                           class="w-full rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-amber-500 focus:ring-amber-500 transition-colors">
                                    @error('approved_amount') <span class="text-red-500 text-xs flex items-center mt-1"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
                                </div>

                                <!-- Actual Cost -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Actual Cost
                                    </label>
                                    <input type="number" wire:model="actual_cost" step="0.01" placeholder="4200.00"
                                           class="w-full rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-amber-500 focus:ring-amber-500 transition-colors">
                                    @error('actual_cost') <span class="text-red-500 text-xs flex items-center mt-1"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
                                </div>

                                <!-- Funding Source -->
                                <div class="md:col-span-3">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                            </svg>
                                            Funding Source
                                        </span>
                                    </label>
                                    <input type="text" wire:model="funding_source" placeholder="CDBG Grant 2025"
                                           class="w-full rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-amber-500 focus:ring-amber-500 transition-colors">
                                    @error('funding_source') <span class="text-red-500 text-xs flex items-center mt-1"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Scope of Work Section -->
                        <div class="bg-teal-50 dark:bg-teal-900/20 rounded-lg p-4 mb-6">
                            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-3 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                </svg>
                                Scope of Work
                            </h4>
                            <div class="space-y-2">
                                @foreach($scope_of_work as $index => $item)
                                    <div class="flex items-center space-x-2 p-2 bg-white dark:bg-zinc-700 rounded border border-gray-200 dark:border-zinc-600">
                                        <span class="text-sm text-gray-600 dark:text-gray-400 font-medium">{{ $index + 1 }}.</span>
                                        <span class="flex-1 text-sm text-gray-900 dark:text-white">{{ $item }}</span>
                                        <button type="button" wire:click="removeScopeItem({{ $index }})"
                                                class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 transition-colors">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </div>
                                @endforeach
                                <div class="flex items-center space-x-2 mt-3">
                                    <input type="text" wire:model="newScopeItem" 
                                           wire:keydown.enter.prevent="addScopeItem"
                                           placeholder="Add scope item... (press Enter to add)"
                                           class="flex-1 rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-amber-500 focus:ring-amber-500 transition-colors">
                                    <button type="button" wire:click="addScopeItem"
                                            class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 transition-all duration-200 flex items-center font-medium">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                        Add
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Status and Notes Section -->
                        <div class="bg-gray-50 dark:bg-zinc-700/30 rounded-lg p-4 mb-6">
                            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-3 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Status & Additional Information
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Status -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            Status <span class="text-red-500">*</span>
                                        </span>
                                    </label>
                                    <select wire:model="status" 
                                            class="w-full rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-amber-500 focus:ring-amber-500 transition-colors">
                                        <option value="pending">Pending</option>
                                        <option value="approved">Approved</option>
                                        <option value="in_progress">In Progress</option>
                                        <option value="on_hold">On Hold</option>
                                        <option value="completed">Completed</option>
                                        <option value="cancelled">Cancelled</option>
                                        <option value="rejected">Rejected</option>
                                    </select>
                                    @error('status') <span class="text-red-500 text-xs flex items-center mt-1"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
                                </div>

                                <!-- Cancellation Reason -->
                                @if($status === 'cancelled')
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            <span class="flex items-center">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                Cancellation Reason
                                            </span>
                                        </label>
                                        <input type="text" wire:model="cancellation_reason" placeholder="Reason for cancellation..."
                                               class="w-full rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-amber-500 focus:ring-amber-500 transition-colors">
                                        @error('cancellation_reason') <span class="text-red-500 text-xs flex items-center mt-1"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
                                    </div>
                                @endif

                                <!-- Internal Notes -->
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
                                            </svg>
                                            Internal Notes <span class="text-gray-400">(Optional)</span>
                                        </span>
                                    </label>
                                    <textarea wire:model="internal_notes" rows="3" placeholder="Internal notes for this job..."
                                              class="w-full rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-amber-500 focus:ring-amber-500 transition-colors resize-none"></textarea>
                                    @error('internal_notes') <span class="text-red-500 text-xs flex items-center mt-1"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="bg-gray-50 dark:bg-zinc-700/50 rounded-lg p-4 flex justify-between items-center">
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                <span class="text-red-500">*</span> Required fields
                            </div>
                            <div class="flex space-x-3">
                                <button type="button" wire:click="resetForm" 
                                        class="px-6 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-zinc-600 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-all duration-200 flex items-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                    Cancel
                                </button>
                                <button type="submit" 
                                        class="px-6 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition-all duration-200 flex items-center font-medium shadow-lg">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        @if($showCreateForm)
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        @endif
                                    </svg>
                                    {{ $showCreateForm ? 'Create Housing Job' : 'Update Housing Job' }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Job Details Modal -->
    @if($showDetails && $selectedJob)
        <div class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-zinc-800 rounded-lg max-w-6xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                Job #{{ $selectedJob->job_number }}
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $selectedJob->job_title }}</p>
                        </div>
                        <button wire:click="$set('showDetails', false)" 
                                class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Status Actions -->
                    @can('housing_jobs.update')
                        <div class="mb-4 flex items-center space-x-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Quick Actions:</span>
                            @if($selectedJob->status === 'pending')
                                <button wire:click="updateStatus({{ $selectedJob->id }}, 'approved')"
                                        class="px-3 py-1 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                                    Approve
                                </button>
                                <button wire:click="updateStatus({{ $selectedJob->id }}, 'rejected')"
                                        class="px-3 py-1 bg-red-600 text-white text-sm rounded-md hover:bg-red-700">
                                    Reject
                                </button>
                            @elseif($selectedJob->status === 'approved')
                                <button wire:click="updateStatus({{ $selectedJob->id }}, 'in_progress')"
                                        class="px-3 py-1 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">
                                    Start Work
                                </button>
                            @elseif($selectedJob->status === 'in_progress')
                                <button wire:click="updateStatus({{ $selectedJob->id }}, 'on_hold')"
                                        class="px-3 py-1 bg-orange-600 text-white text-sm rounded-md hover:bg-orange-700">
                                    Put On Hold
                                </button>
                                <button wire:click="updateStatus({{ $selectedJob->id }}, 'completed')"
                                        class="px-3 py-1 bg-green-600 text-white text-sm rounded-md hover:bg-green-700">
                                    Mark Complete
                                </button>
                            @elseif($selectedJob->status === 'on_hold')
                                <button wire:click="updateStatus({{ $selectedJob->id }}, 'in_progress')"
                                        class="px-3 py-1 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">
                                    Resume Work
                                </button>
                            @endif
                        </div>
                    @endcan

                    <!-- Tabs -->
                    <div class="border-b border-gray-200 dark:border-zinc-700">
                        <nav class="-mb-px flex space-x-8">
                            <button wire:click="$set('activeTab', 'details')"
                                    class="py-2 px-1 border-b-2 font-medium text-sm
                                    {{ $activeTab === 'details' 
                                        ? 'border-amber-500 text-amber-600 dark:text-amber-400' 
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                                Details
                            </button>
                            <button wire:click="$set('activeTab', 'contacts')"
                                    class="py-2 px-1 border-b-2 font-medium text-sm
                                    {{ $activeTab === 'contacts' 
                                        ? 'border-amber-500 text-amber-600 dark:text-amber-400' 
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                                Contact Log ({{ $selectedJob->contactLedgers->count() }})
                            </button>
                            <button wire:click="$set('activeTab', 'documents')"
                                    class="py-2 px-1 border-b-2 font-medium text-sm
                                    {{ $activeTab === 'documents' 
                                        ? 'border-amber-500 text-amber-600 dark:text-amber-400' 
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                                Documents ({{ $selectedJob->getUploadedDocumentsCount() }}/{{ $selectedJob->getRequiredDocumentsCount() }})
                            </button>
                        </nav>
                    </div>

                    <!-- Tab Content -->
                    <div class="mt-4">
                        @if($activeTab === 'details')
                            <!-- Details Tab -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Job Information -->
                                <div>
                                    <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">Job Information</h4>
                                    <dl class="space-y-2">
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Job Type</dt>
                                            <dd class="text-sm text-gray-900 dark:text-white">{{ $selectedJob->jobType->name ?? 'N/A' }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Priority</dt>
                                            <dd class="text-sm">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    @if($selectedJob->priority === 'emergency') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                                    @elseif($selectedJob->priority === 'high') bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200
                                                    @elseif($selectedJob->priority === 'medium') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                                    @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200
                                                    @endif">
                                                    {{ ucfirst($selectedJob->priority) }}
                                                </span>
                                            </dd>
                                        </div>
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                                            <dd class="text-sm">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    @if($selectedJob->status === 'completed') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                                    @elseif($selectedJob->status === 'in_progress') bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200
                                                    @elseif($selectedJob->status === 'approved') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                                    @elseif($selectedJob->status === 'pending') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                                    @elseif($selectedJob->status === 'on_hold') bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200
                                                    @elseif($selectedJob->status === 'cancelled') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                                    @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200
                                                    @endif">
                                                    {{ str_replace('_', ' ', ucfirst($selectedJob->status)) }}
                                                </span>
                                            </dd>
                                        </div>
                                        @if($selectedJob->description)
                                            <div>
                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Description</dt>
                                                <dd class="text-sm text-gray-900 dark:text-white">{{ $selectedJob->job_description }}</dd>
                                            </div>
                                        @endif
                                    </dl>
                                </div>

                                <!-- Property Information -->
                                <div>
                                    <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">Property Information</h4>
                                    <dl class="space-y-2">
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Address</dt>
                                            <dd class="text-sm text-gray-900 dark:text-white">
                                                {{ $selectedJob->property->address_line_1 }}<br>
                                                {{ $selectedJob->property->city }}, {{ $selectedJob->property->state }} {{ $selectedJob->property->zip_code }}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Household</dt>
                                            <dd class="text-sm text-gray-900 dark:text-white">
                                                Code: {{ $selectedJob->household->household_code }}
                                                @if($selectedJob->participant)
                                                    <br>Primary: {{ $selectedJob->participant->first_name }} {{ $selectedJob->participant->last_name }}
                                                @endif
                                            </dd>
                                        </div>
                                    </dl>
                                </div>

                                <!-- Contractor Information -->
                                <div>
                                    <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">Contractor Information</h4>
                                    @if($selectedJob->contractor)
                                        <dl class="space-y-2">
                                            <div>
                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Company</dt>
                                                <dd class="text-sm text-gray-900 dark:text-white">{{ $selectedJob->contractor->company_name }}</dd>
                                            </div>
                                            <div>
                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Contact</dt>
                                                <dd class="text-sm text-gray-900 dark:text-white">
                                                    {{ $selectedJob->contractor->contact_name ?? 'N/A' }}<br>
                                                    {{ $selectedJob->contractor->phone }}
                                                </dd>
                                            </div>
                                            @if($selectedJob->work_order_number)
                                                <div>
                                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Work Order #</dt>
                                                    <dd class="text-sm text-gray-900 dark:text-white">{{ $selectedJob->work_order_number }}</dd>
                                                </div>
                                            @endif
                                            @if($selectedJob->contract_number)
                                                <div>
                                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Contract #</dt>
                                                    <dd class="text-sm text-gray-900 dark:text-white">{{ $selectedJob->contract_number }}</dd>
                                                </div>
                                            @endif
                                        </dl>
                                    @else
                                        <p class="text-sm text-gray-500 dark:text-gray-400">No contractor assigned</p>
                                    @endif
                                </div>

                                <!-- Important Dates -->
                                <div>
                                    <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">Important Dates</h4>
                                    <dl class="space-y-2">
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Request Date</dt>
                                            <dd class="text-sm text-gray-900 dark:text-white">{{ $selectedJob->request_date->format('M d, Y') }}</dd>
                                        </div>
                                        @if($selectedJob->approval_date)
                                            <div>
                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Approval Date</dt>
                                                <dd class="text-sm text-gray-900 dark:text-white">{{ $selectedJob->approval_date->format('M d, Y') }}</dd>
                                            </div>
                                        @endif
                                        @if($selectedJob->start_date)
                                            <div>
                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Start Date</dt>
                                                <dd class="text-sm text-gray-900 dark:text-white">{{ $selectedJob->start_date->format('M d, Y') }}</dd>
                                            </div>
                                        @endif
                                        @if($selectedJob->target_completion_date)
                                            <div>
                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Target Completion</dt>
                                                <dd class="text-sm text-gray-900 dark:text-white">
                                                    {{ $selectedJob->target_completion_date->format('M d, Y') }}
                                                    @if($selectedJob->isOverdue())
                                                        <span class="ml-2 text-xs text-red-600 dark:text-red-400">
                                                            ({{ $selectedJob->days_overdue }} days overdue)
                                                        </span>
                                                    @endif
                                                </dd>
                                            </div>
                                        @endif
                                        @if($selectedJob->actual_completion_date)
                                            <div>
                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Actual Completion</dt>
                                                <dd class="text-sm text-gray-900 dark:text-white">{{ $selectedJob->actual_completion_date->format('M d, Y') }}</dd>
                                            </div>
                                        @endif
                                    </dl>
                                </div>

                                <!-- Financial Information -->
                                <div>
                                    <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">Financial Information</h4>
                                    <dl class="space-y-2">
                                        @if($selectedJob->estimated_cost)
                                            <div>
                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Estimated Cost</dt>
                                                <dd class="text-sm text-gray-900 dark:text-white">${{ number_format($selectedJob->estimated_cost, 2) }}</dd>
                                            </div>
                                        @endif
                                        @if($selectedJob->approved_amount)
                                            <div>
                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Approved Amount</dt>
                                                <dd class="text-sm text-gray-900 dark:text-white">${{ number_format($selectedJob->approved_amount, 2) }}</dd>
                                            </div>
                                        @endif
                                        @if($selectedJob->actual_cost)
                                            <div>
                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Actual Cost</dt>
                                                <dd class="text-sm text-gray-900 dark:text-white">${{ number_format($selectedJob->actual_cost, 2) }}</dd>
                                            </div>
                                        @endif
                                        @if($selectedJob->funding_source)
                                            <div>
                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Funding Source</dt>
                                                <dd class="text-sm text-gray-900 dark:text-white">{{ $selectedJob->funding_source }}</dd>
                                            </div>
                                        @endif
                                    </dl>
                                </div>

                                <!-- Program & Service -->
                                <div>
                                    <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">Program & Service</h4>
                                    <dl class="space-y-2">
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Program</dt>
                                            <dd class="text-sm text-gray-900 dark:text-white">{{ $selectedJob->program->name }}</dd>
                                        </div>
                                        @if($selectedJob->service)
                                            <div>
                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Service</dt>
                                                <dd class="text-sm text-gray-900 dark:text-white">{{ $selectedJob->service->name }}</dd>
                                            </div>
                                        @endif
                                    </dl>
                                </div>
                            </div>

                            <!-- Scope of Work -->
                            @if($selectedJob->scope_of_work && count($selectedJob->scope_of_work) > 0)
                                <div class="mt-6">
                                    <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">Scope of Work</h4>
                                    <ol class="list-decimal list-inside space-y-1">
                                        @foreach($selectedJob->scope_of_work as $item)
                                            <li class="text-sm text-gray-700 dark:text-gray-300">{{ $item }}</li>
                                        @endforeach
                                    </ol>
                                </div>
                            @endif

                            <!-- Internal Notes -->
                            @if($selectedJob->internal_notes)
                                <div class="mt-6">
                                    <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">Internal Notes</h4>
                                    <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $selectedJob->internal_notes }}</p>
                                </div>
                            @endif

                            <!-- Cancellation Reason -->
                            @if($selectedJob->status === 'cancelled' && $selectedJob->cancellation_reason)
                                <div class="mt-6">
                                    <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">Cancellation Reason</h4>
                                    <p class="text-sm text-gray-700 dark:text-gray-300">{{ $selectedJob->cancellation_reason }}</p>
                                </div>
                            @endif

                            <!-- Audit Information -->
                            <div class="mt-6 pt-4 border-t border-gray-200 dark:border-zinc-700">
                                <dl class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                    <div>
                                        <dt class="font-medium text-gray-500 dark:text-gray-400">Created By</dt>
                                        <dd class="text-gray-900 dark:text-white">
                                            {{ $selectedJob->createdBy->name }}<br>
                                            {{ $selectedJob->created_at->format('M d, Y g:i A') }}
                                        </dd>
                                    </div>
                                    @if($selectedJob->approved_by)
                                        <div>
                                            <dt class="font-medium text-gray-500 dark:text-gray-400">Approved By</dt>
                                            <dd class="text-gray-900 dark:text-white">
                                                {{ $selectedJob->approvedBy->name }}<br>
                                                {{ $selectedJob->approval_date->format('M d, Y') }}
                                            </dd>
                                        </div>
                                    @endif
                                    <div>
                                        <dt class="font-medium text-gray-500 dark:text-gray-400">Last Updated By</dt>
                                        <dd class="text-gray-900 dark:text-white">
                                            {{ $selectedJob->updatedBy->name }}<br>
                                            {{ $selectedJob->updated_at->format('M d, Y g:i A') }}
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        @elseif($activeTab === 'contacts')
                            <!-- Contact Log Tab -->
                            <div>
                                @can('housing_jobs.update')
                                    <div class="mb-4">
                                        <button wire:click="$toggle('showContactForm')"
                                                class="px-4 py-2 bg-amber-600 text-white rounded-md hover:bg-amber-700">
                                            Add Contact Log
                                        </button>
                                    </div>

                                    @if($showContactForm)
                                        <div class="mb-4 p-4 bg-gray-50 dark:bg-zinc-700 rounded-lg">
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                        Contact Type <span class="text-red-500">*</span>
                                                    </label>
                                                    <select wire:model="contactType" 
                                                            class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                                                        <option value="">Select Type</option>
                                                        @foreach($contactTypes as $type)
                                                            <option value="{{ $type->code }}">{{ $type->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    @error('contactType') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                        Contact Date <span class="text-red-500">*</span>
                                                    </label>
                                                    <input type="date" wire:model="contactDate" 
                                                           class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                                                    @error('contactDate') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                                </div>
                                                <div class="md:col-span-2">
                                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                        Notes <span class="text-red-500">*</span>
                                                    </label>
                                                    <textarea wire:model="contactNotes" rows="3"
                                                              class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"></textarea>
                                                    @error('contactNotes') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                                </div>
                                                <div class="md:col-span-2">
                                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                        Next Action
                                                    </label>
                                                    <input type="text" wire:model="contactNextAction" 
                                                           class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                                                </div>
                                            </div>
                                            <div class="mt-4 flex justify-end space-x-2">
                                                <button wire:click="resetContactForm" 
                                                        class="px-3 py-1 border border-gray-300 dark:border-zinc-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-zinc-600">
                                                    Cancel
                                                </button>
                                                <button wire:click="addContact" 
                                                        class="px-3 py-1 bg-amber-600 text-white rounded-md hover:bg-amber-700">
                                                    Add Contact
                                                </button>
                                            </div>
                                        </div>
                                    @endif
                                @endcan

                                <div class="space-y-3">
                                    @forelse($selectedJob->contactLedgers->sortByDesc('contact_date') as $contact)
                                        <div class="bg-gray-50 dark:bg-zinc-700 p-4 rounded-lg">
                                            <div class="flex justify-between items-start">
                                                <div>
                                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                        {{ ucfirst($contact->contact_type) }} Contact
                                                    </p>
                                                    <p class="text-sm text-gray-700 dark:text-gray-300 mt-1">
                                                        {{ $contact->notes }}
                                                    </p>
                                                    @if($contact->next_action)
                                                        <p class="text-sm text-amber-600 dark:text-amber-400 mt-1">
                                                            Next: {{ $contact->next_action }}
                                                        </p>
                                                    @endif
                                                </div>
                                                <div class="text-right">
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                                        {{ $contact->contact_date->format('M d, Y') }}
                                                    </p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                                        by {{ $contact->createdBy->name }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <p class="text-sm text-gray-500 dark:text-gray-400">No contact logs recorded.</p>
                                    @endforelse
                                </div>
                            </div>
                        @elseif($activeTab === 'documents')
                            <!-- Documents Tab -->
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                    Document checklist shows required documents for this job. 
                                    {{ $selectedJob->getUploadedDocumentsCount() }} of {{ $selectedJob->getRequiredDocumentsCount() }} required documents uploaded.
                                </p>

                                @if($selectedJob->documentChecklists->count() > 0)
                                    <div class="space-y-2">
                                        @foreach($selectedJob->documentChecklists as $doc)
                                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-zinc-700 rounded-lg">
                                                <div class="flex items-center">
                                                    @if(in_array($doc->status, ['uploaded', 'verified']))
                                                        <svg class="h-5 w-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                        </svg>
                                                    @else
                                                        <svg class="h-5 w-5 text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"></circle>
                                                        </svg>
                                                    @endif
                                                    <div>
                                                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                            {{ $doc->document_name }}
                                                            @if($doc->is_required)
                                                                <span class="text-red-500">*</span>
                                                            @endif
                                                        </p>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                                            Status: {{ ucfirst($doc->status) }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-sm text-gray-500 dark:text-gray-400">No document checklist created for this job.</p>
                                @endif
                            </div>
                        @endif
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button wire:click="$set('showDetails', false)" 
                                class="px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-zinc-700">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>