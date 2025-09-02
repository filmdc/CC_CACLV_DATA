<div class="max-w-7xl mx-auto p-6">
    <div class="bg-white dark:bg-zinc-800 shadow-lg rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-teal-600 to-teal-700 text-white p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">{{ $workPlan->name }}</h1>
                    <p class="text-teal-100 mt-2">{{ $workPlan->program->name }} - {{ $workPlan->year }}</p>
                </div>
                <div class="flex space-x-3">
                    <span class="px-3 py-1 text-sm rounded-full {{ $workPlan->active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $workPlan->active ? 'Active' : 'Inactive' }}
                    </span>
                    @if ($this->can('work-plans.edit'))
                        <button wire:click="editWorkPlan" 
                                class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                            Edit Work Plan
                        </button>
                    @endif
                    <a href="{{ route('work-plan.management') }}" 
                       class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                        Back to List
                    </a>
                </div>
            </div>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Work Plan Information -->
                <div class="space-y-6">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-zinc-700 pb-2">
                            Work Plan Information
                        </h2>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Name</label>
                                <p class="text-gray-900 dark:text-white">{{ $workPlan->name }}</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Program</label>
                                <p class="text-gray-900 dark:text-white">
                                    <a href="{{ route('program.show', $workPlan->program->id) }}" 
                                       class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                        {{ $workPlan->program->name }}
                                    </a>
                                </p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Year</label>
                                <p class="text-gray-900 dark:text-white">{{ $workPlan->year }}</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Status</label>
                                <p>
                                    @if($workPlan->active)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100">
                                            Inactive
                                        </span>
                                    @endif
                                </p>
                            </div>
                            
                            @if($workPlan->description)
                                <div>
                                    <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Description</label>
                                    <div class="text-gray-900 dark:text-white rich-text-content">
                                        {!! $workPlan->description !!}
                                    </div>
                                </div>
                            @endif
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Created</label>
                                <p class="text-gray-900 dark:text-white">{{ $workPlan->created_at->format('M j, Y g:i A') }}</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Last Updated</label>
                                <p class="text-gray-900 dark:text-white">{{ $workPlan->updated_at->format('M j, Y g:i A') }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Program Information -->
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-zinc-700 pb-2">
                            Program Information
                        </h2>
                        <div class="bg-gray-50 dark:bg-zinc-700 p-4 rounded-lg space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Program Name</label>
                                <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $workPlan->program->name }}</p>
                            </div>
                            @if($workPlan->program->description)
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Program Description</label>
                                    <div class="text-sm text-gray-900 dark:text-white mt-1 rich-text-content">
                                        {!! $workPlan->program->description !!}
                                    </div>
                                </div>
                            @endif
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Program Status</label>
                                <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $workPlan->program->active ? 'Active' : 'Inactive' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Goals Section -->
                <div>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-zinc-700 pb-2">
                        Goals ({{ $workPlan->goals->count() }})
                        @if($workPlan->goals->count() > 0)
                            <span class="text-sm font-normal text-gray-600 dark:text-gray-400 ml-2">
                                Average Progress: {{ number_format($workPlan->goals->avg('progress_percentage'), 1) }}%
                            </span>
                        @endif
                    </h2>
                    
                    @if($workPlan->goals->count() > 0)
                        <div class="space-y-4">
                            @foreach($workPlan->goals as $goal)
                                <div class="border border-gray-200 dark:border-zinc-700 rounded-lg p-4 hover:shadow-md transition-shadow">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <h3 class="font-medium text-gray-900 dark:text-white">
                                                <a href="{{ route('work-plan-goal.show', $goal->id) }}" 
                                                   class="hover:text-blue-600 dark:hover:text-blue-400">
                                                    {{ $goal->goal_name }}
                                                </a>
                                            </h3>
                                            @if($goal->description)
                                                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1 rich-text-content">
                                                    {!! $goal->description !!}
                                                </div>
                                            @endif
                                            
                                            <div class="mt-3 space-y-2">
                                                <!-- Progress Bar -->
                                                <div class="relative pt-1">
                                                    <div class="flex mb-2 items-center justify-between">
                                                        <div class="text-xs font-semibold inline-block text-teal-600">
                                                            Progress
                                                        </div>
                                                        <div class="text-right">
                                                            <span class="text-xs font-semibold inline-block {{ $goal->progress_percentage >= 100 ? 'text-green-600 dark:text-green-400' : ($goal->progress_percentage >= 75 ? 'text-yellow-600 dark:text-yellow-400' : 'text-gray-600 dark:text-gray-400') }}">
                                                                {{ number_format($goal->progress_percentage, 1) }}%
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="overflow-hidden h-2 mb-2 text-xs flex rounded bg-gray-200 dark:bg-zinc-600">
                                                        <div style="width:{{ min($goal->progress_percentage, 100) }}%" 
                                                             class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center {{ $goal->progress_percentage >= 100 ? 'bg-green-500' : ($goal->progress_percentage >= 75 ? 'bg-yellow-500' : 'bg-teal-500') }}">
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="flex items-center space-x-4 text-sm">
                                                    <span class="text-gray-600 dark:text-gray-400">
                                                        Target: <strong class="text-gray-900 dark:text-white">{{ number_format($goal->target_number) }}</strong>
                                                    </span>
                                                    <span class="text-gray-600 dark:text-gray-400">
                                                        Actual: <strong class="text-gray-900 dark:text-white">{{ number_format($goal->actual_number) }}</strong>
                                                    </span>
                                                </div>
                                                
                                                @if($goal->service)
                                                    <p class="text-sm">
                                                        <span class="text-gray-600 dark:text-gray-400">Service:</span>
                                                        <a href="{{ route('service.show', $goal->service->id) }}" 
                                                           class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                                            {{ $goal->service->name }}
                                                        </a>
                                                    </p>
                                                @else
                                                    <p class="text-sm text-purple-600 dark:text-purple-400">
                                                        <span class="inline-flex items-center">
                                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                                            </svg>
                                                            Program-wide goal
                                                        </span>
                                                    </p>
                                                @endif
                                                
                                                <!-- Status Badge -->
                                                <div class="mt-2">
                                                    @if($goal->progress_percentage >= 100)
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                                            Target Met
                                                        </span>
                                                    @elseif($goal->progress_percentage >= 75)
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100">
                                                            Close to Target
                                                        </span>
                                                    @elseif($goal->is_on_track)
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                                                            On Track
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100">
                                                            Needs Attention
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 dark:text-gray-400 text-center py-8">
                            No goals have been added to this work plan yet.
                        </p>
                    @endif
                </div>
            </div>

            <!-- Final Reports Section -->
            @if($workPlan->final_reports && count($workPlan->final_reports) > 0)
                <div class="mt-8">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-zinc-700 pb-2">
                        Final Reports
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($workPlan->final_reports as $report)
                            <div class="border border-gray-200 dark:border-zinc-700 rounded-lg p-4">
                                <h4 class="font-medium text-gray-900 dark:text-white">{{ $report['title'] ?? 'Report' }}</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $report['date'] ?? '' }}</p>
                                @if(isset($report['file']))
                                    <a href="{{ $report['file'] }}" 
                                       class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm mt-2 inline-block">
                                        Download Report
                                    </a>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>