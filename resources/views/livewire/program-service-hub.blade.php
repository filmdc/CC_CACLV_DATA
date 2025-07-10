<div class="max-w-7xl mx-auto p-6">
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl p-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-8">Programs & Services</h1>
        
        <!-- Search Bar -->
        <div class="mb-6">
            <div class="relative">
                <input type="text" 
                       wire:model.live.debounce.300ms="search" 
                       class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white pl-10 pr-10 focus:border-indigo-500 focus:ring-indigo-500"
                       placeholder="Search by name, description, or number...">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                @if($search)
                <button wire:click="$set('search', '')" 
                        class="absolute inset-y-0 right-0 pr-3 flex items-center">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
                @endif
            </div>
        </div>

        <!-- Tabs -->
        <div class="border-b border-gray-200 dark:border-zinc-600 mb-6">
            <nav class="-mb-px flex space-x-8">
                @if($this->can('programs.view'))
                <button wire:click="setActiveTab('programs')"
                        class="py-2 px-1 border-b-2 font-medium text-sm transition-colors
                               {{ $activeTab === 'programs' 
                                  ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' 
                                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                    Programs & Services
                </button>
                @endif
                
                @if($this->can('work_plans.view'))
                <button wire:click="setActiveTab('workplans')"
                        class="py-2 px-1 border-b-2 font-medium text-sm transition-colors
                               {{ $activeTab === 'workplans' 
                                  ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' 
                                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                    Work Plans & Goals
                </button>
                @endif
            </nav>
        </div>

        <!-- Content -->
        <div>
            @if($activeTab === 'programs' && $this->can('programs.view'))
                <div class="space-y-4">
                    @forelse($programs as $program)
                    <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-4">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    <a href="{{ route('program.show', $program->id) }}" wire:navigate
                                       class="hover:text-indigo-600 dark:hover:text-indigo-400">
                                        {{ $program->name }}
                                    </a>
                                </h3>
                                @if($program->description)
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $program->description }}</p>
                                @endif
                                <div class="mt-2 flex items-center space-x-4 text-sm text-gray-500 dark:text-gray-400">
                                    <span>{{ $program->services_count }} services</span>
                                    <span>{{ $program->program_enrollments_count }} enrollments</span>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $program->active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                        {{ $program->active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex space-x-2">
                                @if($this->can('services.view'))
                                <a href="{{ route('service.management') }}?program={{ $program->id }}" wire:navigate
                                   class="text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300">
                                    View Services →
                                </a>
                                @endif
                            </div>
                        </div>
                    </div>
                    @empty
                    <p class="text-gray-500 dark:text-gray-400 text-center py-8">
                        No programs found.
                    </p>
                    @endforelse
                    
                    @if($programs->hasPages())
                    <div class="mt-4">
                        {{ $programs->links() }}
                    </div>
                    @endif
                </div>
            @endif

@if($activeTab === 'workplans' && $this->can('work_plans.view'))
                <div class="space-y-4">
                    @forelse($workPlans as $workPlan)
                    <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-4">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    <a href="{{ route('work-plan.show', $workPlan->id) }}" wire:navigate
                                       class="hover:text-indigo-600 dark:hover:text-indigo-400">
                                        {{ $workPlan->name }}
                                    </a>
                                </h3>
                                @if($workPlan->program)
                                <div class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                    Program: {{ $workPlan->program->name }}
                                </div>
                                @endif
                                <div class="mt-2 flex items-center space-x-4 text-sm text-gray-500 dark:text-gray-400">
                                    <span>{{ $workPlan->work_plan_goals_count }} goals</span>
                                    @if($workPlan->start_date && $workPlan->end_date)
                                    <span>{{ $workPlan->start_date->format('M d, Y') }} - {{ $workPlan->end_date->format('M d, Y') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex space-x-2">
                                @if($this->can('work_plan_goals.view'))
                                <a href="{{ route('work-plan-goal.management') }}?work_plan={{ $workPlan->id }}" wire:navigate
                                   class="text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300">
                                    View Goals →
                                </a>
                                @endif
                            </div>
                        </div>
                    </div>
                    @empty
                    <p class="text-gray-500 dark:text-gray-400 text-center py-8">
                        No work plans found.
                    </p>
                    @endforelse
                    
                    @if($workPlans->hasPages())
                    <div class="mt-4">
                        {{ $workPlans->links() }}
                    </div>
                    @endif
                </div>
            @endif
        </div>

        <!-- Quick Links -->
        <div class="mt-8 pt-8 border-t border-gray-200 dark:border-zinc-600">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Management Tools</h2>
            <div class="flex flex-wrap gap-4">
                @if($this->can('programs.view'))
                <a href="{{ route('program.management') }}" wire:navigate
                   class="inline-flex items-center px-4 py-2 bg-white dark:bg-zinc-700 border border-gray-300 dark:border-zinc-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-zinc-600">
                    Manage Programs
                </a>
                @endif
                
                @if($this->can('services.view'))
                <a href="{{ route('service.management') }}" wire:navigate
                   class="inline-flex items-center px-4 py-2 bg-white dark:bg-zinc-700 border border-gray-300 dark:border-zinc-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-zinc-600">
                    Manage Services
                </a>
                @endif
                
                @if($this->can('work_plans.view'))
                <a href="{{ route('work-plan.management') }}" wire:navigate
                   class="inline-flex items-center px-4 py-2 bg-white dark:bg-zinc-700 border border-gray-300 dark:border-zinc-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-zinc-600">
                    Manage Work Plans
                </a>
                @endif
                
                @if($this->can('work_plan_goals.view'))
                <a href="{{ route('work-plan-goal.management') }}" wire:navigate
                   class="inline-flex items-center px-4 py-2 bg-white dark:bg-zinc-700 border border-gray-300 dark:border-zinc-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-zinc-600">
                    Manage Goals
                </a>
                @endif
                
            </div>
        </div>
    </div>
</div>