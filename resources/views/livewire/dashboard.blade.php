<div class="max-w-7xl mx-auto p-6 space-y-6" wire:poll.30s="refreshMetrics">
    {{-- Welcome Header --}}
    <div class="bg-gradient-to-r from-blue-600 to-indigo-700 text-white rounded-lg shadow-xl p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">Welcome back, {{ Auth::user()->name }}!</h1>
                <p class="text-blue-100 mt-1">Here's what's happening with your programs today</p>
            </div>
            <div class="text-right">
                <div class="text-3xl font-bold">{{ now()->format('M d') }}</div>
                <div class="text-blue-200 text-sm">{{ now()->format('l, Y') }}</div>
            </div>
        </div>
    </div>

    {{-- Interactive Metric Cards (Tabs) --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {{-- My Programs Tab --}}
        <button wire:click="setActiveTab('programs')" 
                class="group bg-white dark:bg-zinc-800 rounded-lg shadow-lg p-6 text-left transition-all duration-300 transform hover:scale-105 {{ $activeTab === 'programs' ? 'ring-2 ring-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'hover:shadow-xl' }}">
            <div class="flex items-center">
                <div class="p-3 rounded-full {{ $activeTab === 'programs' ? 'bg-blue-600' : 'bg-blue-100 dark:bg-blue-900' }}">
                    <svg class="w-6 h-6 {{ $activeTab === 'programs' ? 'text-white' : 'text-blue-600 dark:text-blue-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $metrics['my_programs'] }}</p>
                    <p class="text-gray-600 dark:text-gray-300">My Programs</p>
                </div>
            </div>
        </button>

        {{-- Active Participants Tab --}}
        <button wire:click="setActiveTab('participants')" 
                class="group bg-white dark:bg-zinc-800 rounded-lg shadow-lg p-6 text-left transition-all duration-300 transform hover:scale-105 {{ $activeTab === 'participants' ? 'ring-2 ring-green-500 bg-green-50 dark:bg-green-900/20' : 'hover:shadow-xl' }}">
            <div class="flex items-center">
                <div class="p-3 rounded-full {{ $activeTab === 'participants' ? 'bg-green-600' : 'bg-green-100 dark:bg-green-900' }}">
                    <svg class="w-6 h-6 {{ $activeTab === 'participants' ? 'text-white' : 'text-green-600 dark:text-green-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $metrics['my_participants'] }}</p>
                    <p class="text-gray-600 dark:text-gray-300">Active Participants</p>
                </div>
            </div>
        </button>

        {{-- Active Work Plans Tab --}}
        <button wire:click="setActiveTab('work-plans')" 
                class="group bg-white dark:bg-zinc-800 rounded-lg shadow-lg p-6 text-left transition-all duration-300 transform hover:scale-105 {{ $activeTab === 'work-plans' ? 'ring-2 ring-purple-500 bg-purple-50 dark:bg-purple-900/20' : 'hover:shadow-xl' }}">
            <div class="flex items-center">
                <div class="p-3 rounded-full {{ $activeTab === 'work-plans' ? 'bg-purple-600' : 'bg-purple-100 dark:bg-purple-900' }}">
                    <svg class="w-6 h-6 {{ $activeTab === 'work-plans' ? 'text-white' : 'text-purple-600 dark:text-purple-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v6a2 2 0 002 2h2m0-8v8m0-8h6m-6 0V3m6 4v6a2 2 0 01-2 2h-2m0-8V3"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $metrics['my_work_plans'] }}</p>
                    <p class="text-gray-600 dark:text-gray-300">Active Work Plans</p>
                </div>
            </div>
        </button>

        {{-- Active Schedule Tab --}}
        <button wire:click="setActiveTab('schedule')" 
                class="group bg-white dark:bg-zinc-800 rounded-lg shadow-lg p-6 text-left transition-all duration-300 transform hover:scale-105 {{ $activeTab === 'schedule' ? 'ring-2 ring-orange-500 bg-orange-50 dark:bg-orange-900/20' : 'hover:shadow-xl' }}">
            <div class="flex items-center">
                <div class="p-3 rounded-full {{ $activeTab === 'schedule' ? 'bg-orange-600' : 'bg-orange-100 dark:bg-orange-900' }}">
                    <svg class="w-6 h-6 {{ $activeTab === 'schedule' ? 'text-white' : 'text-orange-600 dark:text-orange-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $metrics['my_work_plans'] }}</p>
                    <p class="text-gray-600 dark:text-gray-300">Active Schedule</p>
                </div>
            </div>
        </button>
    </div>

    {{-- Dynamic Action Buttons --}}
    @if($activeTab === 'programs')
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Quick Actions</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @if($this->can('participants.create'))
                    <a href="{{ route('participant.intake') }}" 
                       class="group bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-lg p-6 text-center transition-all duration-300 transform hover:scale-105 shadow-lg">
                        <div class="flex flex-col items-center">
                            <div class="p-3 bg-white bg-opacity-20 rounded-full mb-4 group-hover:bg-opacity-30 transition-all">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-lg mb-2">New Participant</h3>
                            <p class="text-blue-100 text-sm">Register & enroll new participants</p>
                        </div>
                    </a>
                @endif

                @if($this->can('services.view'))
                    <a href="{{ route('service.management') }}" 
                       class="group bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white rounded-lg p-6 text-center transition-all duration-300 transform hover:scale-105 shadow-lg">
                        <div class="flex flex-col items-center">
                            <div class="p-3 bg-white bg-opacity-20 rounded-full mb-4 group-hover:bg-opacity-30 transition-all">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-lg mb-2">Manage Services</h3>
                            <p class="text-green-100 text-sm">Create & track program services</p>
                        </div>
                    </a>
                @endif

                @if($this->can('work_plans.view'))
                    <a href="{{ route('work-plan.management') }}" 
                       class="group bg-gradient-to-r from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 text-white rounded-lg p-6 text-center transition-all duration-300 transform hover:scale-105 shadow-lg">
                        <div class="flex flex-col items-center">
                            <div class="p-3 bg-white bg-opacity-20 rounded-full mb-4 group-hover:bg-opacity-30 transition-all">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v6a2 2 0 002 2h2m0-8v8m0-8h6m-6 0V3m6 4v6a2 2 0 01-2 2h-2m0-8V3"></path>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-lg mb-2">Work Plans</h3>
                            <p class="text-purple-100 text-sm">Create & manage work plans</p>
                        </div>
                    </a>
                @endif

                <a href="{{ route('enrollment.management') }}" 
                   class="group bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white rounded-lg p-6 text-center transition-all duration-300 transform hover:scale-105 shadow-lg">
                    <div class="flex flex-col items-center">
                        <div class="p-3 bg-white bg-opacity-20 rounded-full mb-4 group-hover:bg-opacity-30 transition-all">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-lg mb-2">View Reports</h3>
                        <p class="text-orange-100 text-sm">Generate program reports</p>
                    </div>
                </a>
            </div>
        </div>
    @elseif($activeTab === 'participants')
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Participant Actions</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @if($this->can('participants.create'))
                    <a href="{{ route('participant.intake') }}" 
                       class="group bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white rounded-lg p-6 text-center transition-all duration-300 transform hover:scale-105 shadow-lg">
                        <div class="flex flex-col items-center">
                            <div class="p-3 bg-white bg-opacity-20 rounded-full mb-4">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-lg mb-2">New Participant</h3>
                            <p class="text-green-100 text-sm">Register new participant</p>
                        </div>
                    </a>
                @endif

                @if($this->can('enrollments.view'))
                    <a href="{{ route('enrollment.management') }}" 
                       class="group bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-lg p-6 text-center transition-all duration-300 transform hover:scale-105 shadow-lg">
                        <div class="flex flex-col items-center">
                            <div class="p-3 bg-white bg-opacity-20 rounded-full mb-4">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-lg mb-2">Enroll Participant</h3>
                            <p class="text-blue-100 text-sm">Manage program enrollments</p>
                        </div>
                    </a>
                @endif

                @if($this->can('participants.view'))
                    <a href="{{ route('participant.management') }}" 
                       class="group bg-gradient-to-r from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 text-white rounded-lg p-6 text-center transition-all duration-300 transform hover:scale-105 shadow-lg">
                        <div class="flex flex-col items-center">
                            <div class="p-3 bg-white bg-opacity-20 rounded-full mb-4">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100-4m0 4v2m0-6V4"></path>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-lg mb-2">Manage Participants</h3>
                            <p class="text-purple-100 text-sm">View & edit participants</p>
                        </div>
                    </a>
                @endif

                <a href="#" 
                   class="group bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white rounded-lg p-6 text-center transition-all duration-300 transform hover:scale-105 shadow-lg">
                    <div class="flex flex-col items-center">
                        <div class="p-3 bg-white bg-opacity-20 rounded-full mb-4">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-lg mb-2">Export Data</h3>
                        <p class="text-orange-100 text-sm">Download participant reports</p>
                    </div>
                </a>
            </div>
        </div>
    @elseif($activeTab === 'work-plans')
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Work Plan Actions</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @if($this->can('work_plans.create'))
                    <a href="{{ route('work-plan.management') }}" 
                       class="group bg-gradient-to-r from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 text-white rounded-lg p-6 text-center transition-all duration-300 transform hover:scale-105 shadow-lg">
                        <div class="flex flex-col items-center">
                            <div class="p-3 bg-white bg-opacity-20 rounded-full mb-4">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-lg mb-2">New Work Plan</h3>
                            <p class="text-purple-100 text-sm">Create new work plan</p>
                        </div>
                    </a>
                @endif

                @if($this->can('work_plan_goals.view'))
                    <a href="{{ route('work-plan-goal.management') }}" 
                       class="group bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white rounded-lg p-6 text-center transition-all duration-300 transform hover:scale-105 shadow-lg">
                        <div class="flex flex-col items-center">
                            <div class="p-3 bg-white bg-opacity-20 rounded-full mb-4">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-lg mb-2">Manage Goals</h3>
                            <p class="text-green-100 text-sm">Track work plan goals</p>
                        </div>
                    </a>
                @endif

                @if($this->can('services.view'))
                    <a href="{{ route('service.management') }}" 
                       class="group bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-lg p-6 text-center transition-all duration-300 transform hover:scale-105 shadow-lg">
                        <div class="flex flex-col items-center">
                            <div class="p-3 bg-white bg-opacity-20 rounded-full mb-4">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-lg mb-2">Services & Goals</h3>
                            <p class="text-blue-100 text-sm">Connect services to goals</p>
                        </div>
                    </a>
                @endif

                <a href="#" 
                   class="group bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white rounded-lg p-6 text-center transition-all duration-300 transform hover:scale-105 shadow-lg">
                    <div class="flex flex-col items-center">
                        <div class="p-3 bg-white bg-opacity-20 rounded-full mb-4">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-lg mb-2">Calendar View</h3>
                        <p class="text-orange-100 text-sm">Year timeline view</p>
                    </div>
                </a>
            </div>
        </div>
    @elseif($activeTab === 'schedule')
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Schedule Actions</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <button class="group bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white rounded-lg p-6 text-center transition-all duration-300 transform hover:scale-105 shadow-lg">
                    <div class="flex flex-col items-center">
                        <div class="p-3 bg-white bg-opacity-20 rounded-full mb-4">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-lg mb-2">Add Reminder</h3>
                        <p class="text-green-100 text-sm">Create custom reminder</p>
                    </div>
                </button>

                <button class="group bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-lg p-6 text-center transition-all duration-300 transform hover:scale-105 shadow-lg">
                    <div class="flex flex-col items-center">
                        <div class="p-3 bg-white bg-opacity-20 rounded-full mb-4">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-lg mb-2">View Calendar</h3>
                        <p class="text-blue-100 text-sm">Full calendar view</p>
                    </div>
                </button>

                @if($this->can('contracts.view'))
                    <a href="{{ route('contract.management') }}" 
                       class="group bg-gradient-to-r from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 text-white rounded-lg p-6 text-center transition-all duration-300 transform hover:scale-105 shadow-lg">
                        <div class="flex flex-col items-center">
                            <div class="p-3 bg-white bg-opacity-20 rounded-full mb-4">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-lg mb-2">Manage Deadlines</h3>
                            <p class="text-purple-100 text-sm">Contract deadlines</p>
                        </div>
                    </a>
                @endif

                <button class="group bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white rounded-lg p-6 text-center transition-all duration-300 transform hover:scale-105 shadow-lg">
                    <div class="flex flex-col items-center">
                        <div class="p-3 bg-white bg-opacity-20 rounded-full mb-4">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-lg mb-2">Generate Reports</h3>
                        <p class="text-orange-100 text-sm">Schedule reports</p>
                    </div>
                </a>
            </div>
        </div>
    @endif

    {{-- Tab-Specific Content --}}
    @if($activeTab === 'programs')
        {{-- Programs Tab Content --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- My Programs --}}
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">My Programs</h2>
                    <a href="{{ route('program.management') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">View All</a>
                </div>
                
                @if($userPrograms->count() > 0)
                    <div class="space-y-4">
                        @foreach($programStats as $stat)
                            <button wire:click="viewProgram({{ $stat['program']->id }})" 
                                    class="w-full text-left border border-gray-200 dark:border-zinc-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-zinc-700 transition-colors">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="font-semibold text-gray-900 dark:text-white">{{ $stat['program']->name }}</h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">{{ Str::limit($stat['program']->description, 60) }}</p>
                                    </div>
                                    @if($stat['program']->hasHousingModule())
                                        <div class="px-2 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 text-xs rounded-full">
                                            Housing
                                        </div>
                                    @endif
                                </div>
                                <div class="grid grid-cols-3 gap-4 mt-4 text-center">
                                    <div>
                                        <div class="text-2xl font-bold text-blue-600">{{ $stat['active_enrollments'] }}</div>
                                        <div class="text-xs text-gray-500">Participants</div>
                                    </div>
                                    <div>
                                        <div class="text-2xl font-bold text-green-600">{{ $stat['services_count'] }}</div>
                                        <div class="text-xs text-gray-500">Services</div>
                                    </div>
                                    <div>
                                        <div class="text-2xl font-bold text-purple-600">{{ $stat['work_plans_count'] }}</div>
                                        <div class="text-xs text-gray-500">Work Plans</div>
                                    </div>
                                </div>
                            </button>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No programs assigned</h3>
                        <p class="mt-1 text-sm text-gray-500">Contact your administrator to get assigned to programs.</p>
                    </div>
                @endif
            </div>

            {{-- Recent Activity --}}
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Recent Activity</h2>
                    <a href="#" class="text-blue-600 hover:text-blue-800 text-sm font-medium">View More</a>
                </div>
                
                @if($recentParticipants->count() > 0)
                    <div class="space-y-4">
                        @foreach($recentParticipants as $participant)
                            <button wire:click="viewParticipant({{ $participant->id }})" 
                                    class="w-full flex items-center space-x-4 p-3 border border-gray-200 dark:border-zinc-700 rounded-lg hover:bg-gray-50 dark:hover:bg-zinc-700 transition-colors">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                                        <span class="text-blue-600 dark:text-blue-400 font-semibold text-sm">
                                            {{ substr($participant->first_name, 0, 1) }}{{ substr($participant->last_name, 0, 1) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0 text-left">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                        {{ $participant->first_name }} {{ $participant->last_name }}
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
                                        Household: {{ $participant->household->household_code ?? 'N/A' }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $participant->updated_at->diffForHumans() }}
                                    </p>
                                </div>
                            </button>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No recent activity</h3>
                        <p class="mt-1 text-sm text-gray-500">Start working with participants to see recent activity.</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Work Plan Goals Progress --}}
        @if($workPlanGoals->count() > 0)
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Work Plan Goals Progress</h2>
                    <div class="flex space-x-2">
                        @if(!$expandedWorkPlans)
                            <button wire:click="toggleExpandedWorkPlans" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                View All
                            </button>
                        @else
                            <button wire:click="toggleExpandedWorkPlans" class="text-gray-600 hover:text-gray-800 text-sm font-medium">
                                Show Less
                            </button>
                        @endif
                        <a href="{{ route('work-plan-goal.management') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Manage</a>
                    </div>
                </div>
                
                @if(!$expandedWorkPlans)
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($workPlanGoals as $goal)
                            @php
                                $progress = $goal->target_number > 0 ? ($goal->actual_number / $goal->target_number) * 100 : 0;
                                $progressClass = $progress >= 100 ? 'bg-green-500' : ($progress >= 50 ? 'bg-yellow-500' : 'bg-red-500');
                            @endphp
                            <button wire:click="viewWorkPlan({{ $goal->workPlan->id }})" 
                                    class="text-left border border-gray-200 dark:border-zinc-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-zinc-700 transition-colors">
                                <div class="flex items-center justify-between mb-2">
                                    <h3 class="font-semibold text-gray-900 dark:text-white text-sm">{{ $goal->goal_name }}</h3>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ number_format($progress, 1) }}%</span>
                                </div>
                                <p class="text-xs text-gray-600 dark:text-gray-300 mb-3">{{ $goal->workPlan->program->name }}</p>
                                <div class="w-full bg-gray-200 dark:bg-zinc-700 rounded-full h-2">
                                    <div class="{{ $progressClass }} h-2 rounded-full transition-all duration-300" style="width: {{ min($progress, 100) }}%"></div>
                                </div>
                                <div class="mt-2 flex justify-between text-xs text-gray-600 dark:text-gray-400">
                                    <span>{{ $goal->actual_number }} / {{ $goal->target_number }}</span>
                                    <span>{{ $goal->service->name ?? 'General' }}</span>
                                </div>
                            </button>
                        @endforeach
                    </div>
                @else
                    {{-- Expanded Work Plans View --}}
                    <div class="space-y-6">
                        @foreach($tabData['allWorkPlans'] as $workPlan)
                            <div class="border border-gray-200 dark:border-zinc-700 rounded-lg p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $workPlan->name }}</h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-300">{{ $workPlan->program->name }} • {{ $workPlan->year }}</p>
                                    </div>
                                    <button wire:click="viewWorkPlan({{ $workPlan->id }})" 
                                            class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                        View Details
                                    </button>
                                </div>
                                @if($workPlan->goals->count() > 0)
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        @foreach($workPlan->goals as $goal)
                                            @php
                                                $progress = $goal->target_number > 0 ? ($goal->actual_number / $goal->target_number) * 100 : 0;
                                                $progressClass = $progress >= 100 ? 'bg-green-500' : ($progress >= 50 ? 'bg-yellow-500' : 'bg-red-500');
                                            @endphp
                                            <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-4">
                                                <div class="flex items-center justify-between mb-2">
                                                    <h4 class="font-medium text-gray-900 dark:text-white text-sm">{{ $goal->goal_name }}</h4>
                                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ number_format($progress, 1) }}%</span>
                                                </div>
                                                <div class="w-full bg-gray-200 dark:bg-zinc-600 rounded-full h-2 mb-2">
                                                    <div class="{{ $progressClass }} h-2 rounded-full transition-all duration-300" style="width: {{ min($progress, 100) }}%"></div>
                                                </div>
                                                <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400">
                                                    <span>{{ $goal->actual_number }} / {{ $goal->target_number }}</span>
                                                    <span>{{ $goal->service->name ?? 'General' }}</span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-gray-500 dark:text-gray-400 text-sm">No goals defined for this work plan.</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

    @elseif($activeTab === 'participants')
        {{-- Participants Tab Content --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Participant Overview</h2>
                <a href="{{ route('participant.management') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Manage All</a>
            </div>
            
            {{-- Enrollment Stats --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                @foreach($tabData['enrollmentStats'] as $status => $count)
                    <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $count }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-300 capitalize">{{ $status }} Enrollments</div>
                    </div>
                @endforeach
            </div>

            {{-- Participants List --}}
            <div class="space-y-4">
                @foreach($tabData['participants'] as $participant)
                    <button wire:click="viewParticipant({{ $participant->id }})" 
                            class="w-full flex items-center justify-between p-4 border border-gray-200 dark:border-zinc-700 rounded-lg hover:bg-gray-50 dark:hover:bg-zinc-700 transition-colors">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                                <span class="text-blue-600 dark:text-blue-400 font-semibold">
                                    {{ substr($participant->first_name, 0, 1) }}{{ substr($participant->last_name, 0, 1) }}
                                </span>
                            </div>
                            <div class="text-left">
                                <p class="font-medium text-gray-900 dark:text-white">
                                    {{ $participant->first_name }} {{ $participant->last_name }}
                                </p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Household: {{ $participant->household->household_code ?? 'N/A' }}
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="flex flex-wrap gap-1">
                                @foreach($participant->programEnrollments as $enrollment)
                                    <span class="px-2 py-1 text-xs rounded-full {{ $enrollment->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                        {{ $enrollment->program->name }}
                                    </span>
                                @endforeach
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Updated {{ $participant->updated_at->diffForHumans() }}
                            </p>
                        </div>
                    </button>
                @endforeach
            </div>
        </div>

    @elseif($activeTab === 'work-plans')
        {{-- Work Plans Tab Content --}}
        <div class="space-y-6">
            {{-- Work Plan Statistics Overview --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-6 text-center">
                    <div class="text-3xl font-bold text-purple-600">{{ $tabData['workPlanStats']['total_work_plans'] }}</div>
                    <div class="text-sm text-purple-700 dark:text-purple-300">Active Work Plans</div>
                </div>
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6 text-center">
                    <div class="text-3xl font-bold text-blue-600">{{ $tabData['workPlanStats']['total_goals'] }}</div>
                    <div class="text-sm text-blue-700 dark:text-blue-300">Total Goals</div>
                </div>
                <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-6 text-center">
                    <div class="text-3xl font-bold text-green-600">{{ $tabData['workPlanStats']['completed_goals'] }}</div>
                    <div class="text-sm text-green-700 dark:text-green-300">Completed Goals</div>
                </div>
                <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-6 text-center">
                    <div class="text-3xl font-bold text-orange-600">{{ $tabData['workPlanStats']['services_count'] }}</div>
                    <div class="text-sm text-orange-700 dark:text-orange-300">Connected Services</div>
                </div>
            </div>

            {{-- Work Plans by Year (Calendar Scale) --}}
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Work Plans by Year</h2>
                
                @if($tabData['workPlansByYear']->count() > 0)
                    <div class="space-y-6">
                        @foreach($tabData['workPlansByYear'] as $year => $yearWorkPlans)
                            <div class="border border-gray-200 dark:border-zinc-700 rounded-lg p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $year }} Work Plans</h3>
                                    <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded-full text-sm">
                                        {{ $yearWorkPlans->count() }} plans
                                    </span>
                                </div>
                                
                                {{-- Year Progress Timeline --}}
                                <div class="mb-6">
                                    <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400 mb-2">
                                        <span>Jan</span>
                                        <span>Mar</span>
                                        <span>Jun</span>
                                        <span>Sep</span>
                                        <span>Dec</span>
                                    </div>
                                    <div class="w-full bg-gray-200 dark:bg-zinc-600 rounded-full h-3 relative">
                                        @php
                                            $currentMonth = now()->month;
                                            $progressPercent = $year == now()->year ? ($currentMonth / 12) * 100 : ($year < now()->year ? 100 : 0);
                                        @endphp
                                        <div class="bg-gradient-to-r from-blue-500 to-purple-600 h-3 rounded-full transition-all duration-500" 
                                             style="width: {{ $progressPercent }}%"></div>
                                        @if($year == now()->year)
                                            <div class="absolute top-0 h-3 w-1 bg-red-500 rounded-full" 
                                                 style="left: {{ $progressPercent }}%; transform: translateX(-50%);">
                                            </div>
                                        @endif
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 text-center">
                                        @if($year == now()->year)
                                            Current: {{ now()->format('F Y') }}
                                        @elseif($year < now()->year)
                                            Completed Year
                                        @else
                                            Future Year
                                        @endif
                                    </div>
                                </div>

                                {{-- Work Plans for this year --}}
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                    @foreach($yearWorkPlans as $workPlan)
                                        <div class="border border-gray-200 dark:border-zinc-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-zinc-700 transition-colors">
                                            <div class="flex items-center justify-between mb-3">
                                                <div>
                                                    <h4 class="font-medium text-gray-900 dark:text-white">{{ $workPlan->name }}</h4>
                                                    <p class="text-sm text-gray-600 dark:text-gray-300">{{ $workPlan->program->name }}</p>
                                                </div>
                                                <button wire:click="viewWorkPlan({{ $workPlan->id }})" 
                                                        class="px-3 py-1 text-xs bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                                    View
                                                </button>
                                            </div>
                                            
                                            {{-- Goals Progress for this Work Plan --}}
                                            @if($workPlan->goals->count() > 0)
                                                <div class="space-y-2">
                                                    <div class="flex items-center justify-between text-xs text-gray-600 dark:text-gray-400">
                                                        <span>Goals Progress</span>
                                                        <span>{{ $workPlan->goals->where('target_number', '>', 0)->filter(fn($g) => $g->actual_number >= $g->target_number)->count() }} / {{ $workPlan->goals->where('target_number', '>', 0)->count() }} completed</span>
                                                    </div>
                                                    @foreach($workPlan->goals->take(3) as $goal)
                                                        @php
                                                            $progress = $goal->target_number > 0 ? ($goal->actual_number / $goal->target_number) * 100 : 0;
                                                            $progressClass = $progress >= 100 ? 'bg-green-500' : ($progress >= 50 ? 'bg-yellow-500' : 'bg-red-500');
                                                        @endphp
                                                        <div class="flex items-center justify-between text-xs">
                                                            <span class="text-gray-700 dark:text-gray-300 truncate mr-2">{{ Str::limit($goal->goal_name, 20) }}</span>
                                                            <div class="flex items-center space-x-2 flex-shrink-0">
                                                                <div class="w-16 bg-gray-200 dark:bg-zinc-600 rounded-full h-1.5">
                                                                    <div class="{{ $progressClass }} h-1.5 rounded-full transition-all duration-300" style="width: {{ min($progress, 100) }}%"></div>
                                                                </div>
                                                                <span class="text-gray-500 w-8 text-right">{{ number_format($progress, 0) }}%</span>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                    @if($workPlan->goals->count() > 3)
                                                        <div class="text-xs text-gray-500 dark:text-gray-400 text-center">
                                                            +{{ $workPlan->goals->count() - 3 }} more goals
                                                        </div>
                                                    @endif
                                                </div>
                                            @else
                                                <div class="text-xs text-gray-500 dark:text-gray-400 text-center py-2">
                                                    No goals defined
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12">
                        <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v6a2 2 0 002 2h2m0-8v8m0-8h6m-6 0V3m6 4v6a2 2 0 01-2 2h-2m0-8V3" />
                        </svg>
                        <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No Work Plans Found</h3>
                        <p class="mt-2 text-sm text-gray-500">Create work plans to track program goals and services.</p>
                        <a href="{{ route('work-plan.management') }}" 
                           class="mt-4 inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            Create Work Plan
                        </a>
                    </div>
                @endif
            </div>

            {{-- Services Connected to Work Plan Goals --}}
            @if($tabData['servicesInWorkPlans']->count() > 0)
                <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Services Connected to Work Plan Goals</h2>
                        <a href="{{ route('service.management') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Manage Services</a>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($tabData['servicesInWorkPlans'] as $service)
                            @php
                                $goalCount = $service->workPlanGoals->count();
                                $completedGoals = $service->workPlanGoals->filter(function($goal) {
                                    return $goal->target_number > 0 && $goal->actual_number >= $goal->target_number;
                                })->count();
                            @endphp
                            <div class="border border-gray-200 dark:border-zinc-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-zinc-700 transition-colors">
                                <div class="flex items-center justify-between mb-3">
                                    <div>
                                        <h3 class="font-semibold text-gray-900 dark:text-white text-sm">{{ $service->name }}</h3>
                                        <p class="text-xs text-gray-600 dark:text-gray-300">{{ $service->program->name }}</p>
                                    </div>
                                    <span class="px-2 py-1 text-xs rounded-full {{ $service->active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                        {{ $service->active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                                
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="text-gray-600 dark:text-gray-400">Connected Goals:</span>
                                        <span class="font-medium text-gray-900 dark:text-white">{{ $goalCount }}</span>
                                    </div>
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="text-gray-600 dark:text-gray-400">Completed:</span>
                                        <span class="font-medium {{ $completedGoals > 0 ? 'text-green-600' : 'text-gray-500' }}">{{ $completedGoals }}</span>
                                    </div>
                                    @if($goalCount > 0)
                                        <div class="w-full bg-gray-200 dark:bg-zinc-600 rounded-full h-1.5">
                                            <div class="bg-green-500 h-1.5 rounded-full transition-all duration-300" 
                                                 style="width: {{ $goalCount > 0 ? ($completedGoals / $goalCount) * 100 : 0 }}%"></div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

    @elseif($activeTab === 'schedule')
        {{-- Schedule Tab Content --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Upcoming Deadlines --}}
            @if($this->can('contracts.view'))
                <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl p-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Upcoming Contract Deadlines</h2>
                    
                    @if($tabData['upcomingDeadlines']->count() > 0)
                        <div class="space-y-4">
                            @foreach($tabData['upcomingDeadlines'] as $contract)
                                @php
                                    $daysUntil = now()->diffInDays($contract->end_date, false);
                                    $urgencyClass = $daysUntil <= 7 ? 'border-red-500 bg-red-50 dark:bg-red-900/20' : ($daysUntil <= 30 ? 'border-yellow-500 bg-yellow-50 dark:bg-yellow-900/20' : 'border-blue-500 bg-blue-50 dark:bg-blue-900/20');
                                @endphp
                                <div class="border-l-4 {{ $urgencyClass }} p-4 rounded-r-lg">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h3 class="font-semibold text-gray-900 dark:text-white">{{ $contract->title }}</h3>
                                            <p class="text-sm text-gray-600 dark:text-gray-300">{{ $contract->program->name }}</p>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-sm font-medium {{ $daysUntil <= 7 ? 'text-red-600' : ($daysUntil <= 30 ? 'text-yellow-600' : 'text-blue-600') }}">
                                                {{ $daysUntil > 0 ? $daysUntil . ' days' : 'Overdue' }}
                                            </div>
                                            <div class="text-xs text-gray-500">{{ $contract->end_date->format('M d, Y') }}</div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No upcoming deadlines</h3>
                            <p class="mt-1 text-sm text-gray-500">All contracts are up to date.</p>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Custom Reminders --}}
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Custom Reminders</h2>
                    <button class="text-blue-600 hover:text-blue-800 text-sm font-medium">Add Reminder</button>
                </div>
                
                <div class="space-y-4">
                    @foreach($tabData['customReminders'] as $reminder)
                        @php
                            $daysUntil = now()->diffInDays($reminder->due_date, false);
                            $priorityClass = $reminder->priority === 'high' ? 'border-red-500 bg-red-50 dark:bg-red-900/20' : ($reminder->priority === 'medium' ? 'border-yellow-500 bg-yellow-50 dark:bg-yellow-900/20' : 'border-blue-500 bg-blue-50 dark:bg-blue-900/20');
                        @endphp
                        <div class="border-l-4 {{ $priorityClass }} p-4 rounded-r-lg">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ $reminder->title }}</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-300 capitalize">{{ $reminder->type }} • {{ ucfirst($reminder->priority) }} priority</p>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-medium {{ $reminder->priority === 'high' ? 'text-red-600' : ($reminder->priority === 'medium' ? 'text-yellow-600' : 'text-blue-600') }}">
                                        {{ $daysUntil > 0 ? $daysUntil . ' days' : 'Due today' }}
                                    </div>
                                    <div class="text-xs text-gray-500">{{ $reminder->due_date->format('M d, Y') }}</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- Flash Messages --}}
    @if (session()->has('message'))
        <div class="fixed bottom-4 right-4 bg-blue-500 text-white px-6 py-3 rounded-lg shadow-lg">
            {{ session('message') }}
        </div>
    @endif
</div>