<div class="w-full px-0 py-6">
    <div class="bg-white dark:bg-zinc-800 shadow-lg rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-orange-600 to-orange-700 text-white p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">{{ $goal->goal_name }}</h1>
                    <p class="text-orange-100 mt-2">{{ $goal->workPlan->name }} - {{ $goal->workPlan->program->name }}</p>
                </div>
                <div class="flex space-x-3">
                    <span class="px-3 py-1 text-sm rounded-full {{ $goal->progress_percentage >= 100 ? 'bg-green-100 text-green-800' : ($goal->progress_percentage >= 75 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                        {{ number_format($goal->progress_percentage, 1) }}% Complete
                    </span>
                    @if ($this->can('work-plan-goals.edit'))
                        <button wire:click="editGoal" 
                                class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                            Edit Goal
                        </button>
                    @endif
                    <a href="{{ route('work-plan-goal.management') }}" 
                       class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                        Back to List
                    </a>
                </div>
            </div>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Goal Information -->
                <div class="space-y-6">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-zinc-700 pb-2">
                            Goal Details
                        </h2>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Goal Name</label>
                                <p class="text-gray-900 dark:text-white">{{ $goal->goal_name }}</p>
                            </div>
                            
                            @if($goal->description)
                                <div>
                                    <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Description</label>
                                    <div class="text-gray-900 dark:text-white rich-text-content">
                                        {!! $goal->description !!}
                                    </div>
                                </div>
                            @endif
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Target Number</label>
                                    <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($goal->target_number) }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Actual Number</label>
                                    <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($goal->actual_number) }}</p>
                                </div>
                            </div>
                            
                            <!-- Progress Bar -->
                            <div>
                                <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Progress</label>
                                <div class="relative pt-1">
                                    <div class="flex mb-2 items-center justify-between">
                                        <div>
                                            <span class="text-xs font-semibold inline-block py-1 px-2 uppercase rounded-full {{ $goal->progress_percentage >= 100 ? 'text-green-600 bg-green-200' : ($goal->progress_percentage >= 75 ? 'text-yellow-600 bg-yellow-200' : 'text-orange-600 bg-orange-200') }}">
                                                {{ number_format($goal->progress_percentage, 1) }}%
                                            </span>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-xs font-semibold inline-block text-gray-600 dark:text-gray-400">
                                                {{ number_format($goal->actual_number) }} / {{ number_format($goal->target_number) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="overflow-hidden h-4 mb-4 text-xs flex rounded bg-gray-200 dark:bg-zinc-600">
                                        <div style="width:{{ min($goal->progress_percentage, 100) }}%" 
                                             class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center {{ $goal->progress_percentage >= 100 ? 'bg-green-500' : ($goal->progress_percentage >= 75 ? 'bg-yellow-500' : 'bg-orange-500') }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Status</label>
                                <p>
                                    @if($goal->progress_percentage >= 100)
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                            Target Met!
                                        </span>
                                    @elseif($goal->progress_percentage >= 75)
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100">
                                            Close to Target
                                        </span>
                                    @elseif($goal->is_on_track)
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                                            On Track
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100">
                                            Needs Attention
                                        </span>
                                    @endif
                                </p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Created</label>
                                <p class="text-gray-900 dark:text-white">{{ $goal->created_at->format('M j, Y g:i A') }}</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Last Updated</label>
                                <p class="text-gray-900 dark:text-white">{{ $goal->updated_at->format('M j, Y g:i A') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <!-- Work Plan Information -->
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-zinc-700 pb-2">
                            Work Plan Information
                        </h2>
                        <div class="bg-gray-50 dark:bg-zinc-700 p-4 rounded-lg space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Work Plan</label>
                                <p class="text-sm text-gray-900 dark:text-white mt-1">
                                    <a href="{{ route('work-plan.show', $goal->workPlan->id) }}" 
                                       class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                        {{ $goal->workPlan->name }}
                                    </a>
                                </p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Program</label>
                                <p class="text-sm text-gray-900 dark:text-white mt-1">
                                    <a href="{{ route('program.show', $goal->workPlan->program->id) }}" 
                                       class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                        {{ $goal->workPlan->program->name }}
                                    </a>
                                </p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Year</label>
                                <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $goal->workPlan->year }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Work Plan Status</label>
                                <p class="text-sm text-gray-900 dark:text-white mt-1">
                                    @if($goal->workPlan->active)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100">
                                            Inactive
                                        </span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Service Information -->
                    @if($goal->service)
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-zinc-700 pb-2">
                                Associated Service
                            </h2>
                            <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Service Name</label>
                                    <p class="text-sm text-gray-900 dark:text-white mt-1">
                                        <a href="{{ route('service.show', $goal->service->id) }}" 
                                           class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                            {{ $goal->service->name }}
                                        </a>
                                    </p>
                                </div>
                                @if($goal->service->description)
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Service Description</label>
                                        <div class="text-sm text-gray-900 dark:text-white mt-1 rich-text-content">
                                            {!! $goal->service->description !!}
                                        </div>
                                    </div>
                                @endif
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Service Status</label>
                                    <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $goal->service->active ? 'Active' : 'Inactive' }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Service Type</label>
                                    <p class="text-sm text-gray-900 dark:text-white mt-1">{{ ucwords(str_replace('_', ' ', $goal->service->service_type)) }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Current Enrollments</label>
                                    <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $goal->service->getEnrolledParticipantsCount() }} participants</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Completed Enrollments</label>
                                    <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $goal->service->getCompletedEnrollmentsCount() }} participants</p>
                                </div>
                            </div>
                        </div>
                    @else
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-zinc-700 pb-2">
                                Goal Type
                            </h2>
                            <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg">
                                <div class="flex items-center">
                                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                    </svg>
                                    <div>
                                        <p class="text-lg font-medium text-purple-900 dark:text-purple-100">Program-wide Goal</p>
                                        <p class="text-sm text-purple-700 dark:text-purple-300 mt-1">This goal applies to all services within the {{ $goal->workPlan->program->name }} program.</p>
                                    </div>
                                </div>
                                <div class="mt-4 space-y-2">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Total Service Enrollments</label>
                                        <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $goal->getProgramWideServiceCount() }} participants</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Total Service Completions</label>
                                        <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $goal->getProgramWideCompletionCount() }} participants</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Activity Metrics -->
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-zinc-700 pb-2">
                            Activity Metrics
                        </h2>
                        <div class="bg-gray-50 dark:bg-zinc-700 p-4 rounded-lg">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="text-center">
                                    <p class="text-3xl font-bold text-orange-600 dark:text-orange-400">{{ number_format($goal->actual_number) }}</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Actual</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-3xl font-bold text-gray-600 dark:text-gray-400">{{ number_format($goal->target_number) }}</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Target</p>
                                </div>
                            </div>
                            @if($goal->service)
                                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-zinc-600">
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        Total Service Activity: <span class="font-medium text-gray-900 dark:text-white">{{ $goal->total_service_activity }}</span>
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>