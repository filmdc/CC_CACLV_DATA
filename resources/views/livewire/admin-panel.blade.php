<div class="max-w-7xl mx-auto p-6">
    <div class="bg-white shadow-lg rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-purple-600 text-white p-6">
            <h1 class="text-2xl font-bold">CAP TRACK Administration</h1>
            <p class="text-purple-100 mt-2">System configuration and management dashboard</p>
        </div>

        <!-- Tab Navigation -->
        <div class="border-b bg-gray-50">
            <nav class="flex space-x-8 px-6" aria-label="Tabs">
                <button wire:click="setActiveTab('overview')" 
                        class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'overview' ? 'border-purple-500 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    System Overview
                </button>
                <button wire:click="setActiveTab('programs')" 
                        class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'programs' ? 'border-purple-500 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Programs
                </button>
                <button wire:click="setActiveTab('users')" 
                        class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'users' ? 'border-purple-500 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Users
                </button>
                <button wire:click="setActiveTab('reports')" 
                        class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'reports' ? 'border-purple-500 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Reports
                </button>
                <button wire:click="setActiveTab('settings')" 
                        class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'settings' ? 'border-purple-500 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Site Settings
                </button>
            </nav>
        </div>

        <!-- Success/Error Messages -->
        @if (session()->has('success'))
            <div class="p-4 bg-green-50 border-l-4 border-green-400">
                <div class="flex">
                    <div class="ml-3">
                        <p class="text-sm text-green-700">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="p-4 bg-red-50 border-l-4 border-red-400">
                <div class="flex">
                    <div class="ml-3">
                        <p class="text-sm text-red-700">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Tab Content -->
        <div class="p-6">
            @if ($activeTab === 'overview')
                <!-- System Overview -->
                <div class="space-y-6">
                    <h2 class="text-xl font-semibold text-gray-900">System Statistics</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div class="bg-blue-50 rounded-lg p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Total Participants</dt>
                                        <dd class="text-lg font-medium text-gray-900">{{ number_format($systemStats['total_participants']) }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>

                        <div class="bg-green-50 rounded-lg p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Total Households</dt>
                                        <dd class="text-lg font-medium text-gray-900">{{ number_format($systemStats['total_households']) }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>

                        <div class="bg-purple-50 rounded-lg p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Active Programs</dt>
                                        <dd class="text-lg font-medium text-gray-900">{{ number_format($systemStats['active_programs']) }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>

                        <div class="bg-yellow-50 rounded-lg p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">System Users</dt>
                                        <dd class="text-lg font-medium text-gray-900">{{ number_format($systemStats['total_users']) }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Additional System Info -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h3 class="text-sm font-medium text-gray-900">Services</h3>
                            <p class="text-2xl font-bold text-gray-900">{{ number_format($systemStats['total_services']) }}</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h3 class="text-sm font-medium text-gray-900">Contracts</h3>
                            <p class="text-2xl font-bold text-gray-900">{{ number_format($systemStats['total_contracts']) }}</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h3 class="text-sm font-medium text-gray-900">Work Plans</h3>
                            <p class="text-2xl font-bold text-gray-900">{{ number_format($systemStats['total_work_plans']) }}</p>
                        </div>
                    </div>
                </div>

            @elseif ($activeTab === 'programs')
                <!-- Programs Management -->
                <div class="space-y-6">
                    <div class="flex justify-between items-center">
                        <h2 class="text-xl font-semibold text-gray-900">Program Management</h2>
                        <button wire:click="showCreateProgram" 
                                class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500">
                            Create New Program
                        </button>
                    </div>

                    <!-- Search -->
                    <div class="max-w-md">
                        <input type="text" wire:model.live="search" placeholder="Search programs..." 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                    </div>

                    <!-- Create Program Form -->
                    @if ($showCreateForm)
                        <div class="bg-blue-50 rounded-lg p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Create New Program</h3>
                            
                            <form wire:submit="createProgram" class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="program_name" class="block text-sm font-medium text-gray-700 mb-1">
                                            Program Name <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" wire:model.defer="program_name" id="program_name" 
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                                        @error('program_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label for="program_start_date" class="block text-sm font-medium text-gray-700 mb-1">
                                            Start Date <span class="text-red-500">*</span>
                                        </label>
                                        <input type="date" wire:model="program_start_date" id="program_start_date" 
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500"
                                               required>
                                        @error('program_start_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label for="program_end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                                        <input type="date" wire:model="program_end_date" id="program_end_date" 
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                                        @error('program_end_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="flex items-center">
                                        <input type="checkbox" wire:model.defer="active" id="active" class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                                        <label for="active" class="ml-2 block text-sm text-gray-900">Active Program</label>
                                    </div>
                                </div>

                                <div>
                                    <label for="program_description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                    <textarea wire:model.defer="program_description" id="program_description" rows="3" 
                                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500"></textarea>
                                </div>

                                <div class="flex justify-end space-x-3">
                                    <button type="button" wire:click="$set('showCreateForm', false)" 
                                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 border border-gray-300 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500">
                                        Cancel
                                    </button>
                                    <button type="submit" 
                                            class="px-4 py-2 text-sm font-medium text-white bg-purple-600 border border-transparent rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500">
                                        Create Program
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endif

                    <!-- Programs List -->
                    <div class="bg-white shadow overflow-hidden sm:rounded-md">
                        <ul class="divide-y divide-gray-200">
                            @forelse ($programs as $program)
                                <li class="px-6 py-4">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-3">
                                                <h3 class="text-lg font-medium text-gray-900">{{ $program->name }}</h3>
                                                <span class="px-2 py-1 text-xs rounded-full {{ $program->active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                    {{ $program->active ? 'Active' : 'Inactive' }}
                                                </span>
                                            </div>
                                            <p class="text-sm text-gray-600 mt-1">{{ $program->description }}</p>
                                            <div class="mt-2 flex items-center text-sm text-gray-500 space-x-4">
                                                <span>Start: {{ $program->start_date->format('M j, Y') }}</span>
                                                @if($program->end_date)
                                                    <span>End: {{ $program->end_date->format('M j, Y') }}</span>
                                                @endif
                                                <span>Services: {{ $program->services_count }}</span>
                                                <span>Work Plans: {{ $program->work_plans_count }}</span>
                                            </div>
                                        </div>
                                        <button wire:click="toggleProgramStatus({{ $program->id }})" 
                                                class="px-3 py-1 text-sm rounded {{ $program->active ? 'bg-red-100 text-red-700 hover:bg-red-200' : 'bg-green-100 text-green-700 hover:bg-green-200' }}">
                                            {{ $program->active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </div>
                                </li>
                            @empty
                                <li class="px-6 py-4 text-center text-gray-500">
                                    No programs found.
                                </li>
                            @endforelse
                        </ul>
                    </div>

                    <!-- Pagination -->
                    <div>
                        {{ $programs->links() }}
                    </div>
                </div>

            @elseif ($activeTab === 'users')
                <!-- User Management -->
                <div class="space-y-6">
                    <div class="flex justify-between items-center">
                        <h2 class="text-xl font-semibold text-gray-900">User Management</h2>
                        <button wire:click="showCreateUser" class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500">
                            Create User
                        </button>
                    </div>

                    <!-- Success/Error Messages -->
                    @if (session()->has('success'))
                        <div class="p-4 bg-green-50 border-l-4 border-green-400">
                            <div class="flex">
                                <div class="ml-3">
                                    <p class="text-sm text-green-700">{{ session('success') }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if (session()->has('error'))
                        <div class="p-4 bg-red-50 border-l-4 border-red-400">
                            <div class="flex">
                                <div class="ml-3">
                                    <p class="text-sm text-red-700">{{ session('error') }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Create User Form -->
                    @if($showCreateUserForm)
                        <div class="bg-purple-50 border border-purple-200 rounded-lg p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Create New User</h3>
                            
                            <form wire:submit="createUser" class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="user_name" class="block text-sm font-medium text-gray-700 mb-1">
                                            Full Name <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" wire:model.defer="user_name" id="user_name" 
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                                        @error('user_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label for="user_email" class="block text-sm font-medium text-gray-700 mb-1">
                                            Email Address <span class="text-red-500">*</span>
                                        </label>
                                        <input type="email" wire:model.defer="user_email" id="user_email" 
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                                        @error('user_email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label for="user_password" class="block text-sm font-medium text-gray-700 mb-1">
                                            Password <span class="text-red-500">*</span>
                                        </label>
                                        <input type="password" wire:model.defer="user_password" id="user_password" 
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                                        @error('user_password') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label for="user_password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
                                            Confirm Password <span class="text-red-500">*</span>
                                        </label>
                                        <input type="password" wire:model.defer="user_password_confirmation" id="user_password_confirmation" 
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                                        @error('user_password_confirmation') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <div class="flex justify-end space-x-3">
                                    <button type="button" wire:click="$set('showCreateUserForm', false)" 
                                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 border border-gray-300 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500">
                                        Cancel
                                    </button>
                                    <button type="submit" 
                                            class="px-4 py-2 text-sm font-medium text-white bg-purple-600 border border-transparent rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500">
                                        Create User
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endif

                    <!-- Search -->
                    <div class="max-w-md">
                        <input type="text" wire:model.live="search" placeholder="Search users..." 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                    </div>

                    <!-- Users List -->
                    <div class="bg-white shadow overflow-hidden sm:rounded-md">
                        <ul class="divide-y divide-gray-200">
                            @forelse ($users as $user)
                                <li class="px-6 py-4">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <span class="h-10 w-10 rounded-full bg-purple-500 flex items-center justify-center text-white font-medium">
                                                    {{ $user->initials() }}
                                                </span>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">{{ $user->name }}</div>
                                                <div class="text-sm text-gray-500">{{ $user->email }}</div>
                                            </div>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            Joined {{ $user->created_at->format('M j, Y') }}
                                        </div>
                                    </div>
                                </li>
                            @empty
                                <li class="px-6 py-4 text-center text-gray-500">
                                    No users found.
                                </li>
                            @endforelse
                        </ul>
                    </div>

                    <!-- Pagination -->
                    <div>
                        {{ $users->links() }}
                    </div>
                </div>

            @elseif ($activeTab === 'reports')
                <!-- Reports -->
                <div class="space-y-6">
                    <h2 class="text-xl font-semibold text-gray-900">System Reports</h2>
                    
                    <!-- Report Type Selection -->
                    <div class="flex space-x-4 mb-6">
                        <button wire:click="$set('reportType', 'overview')" 
                                class="px-4 py-2 rounded-md {{ $reportType === 'overview' ? 'bg-purple-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                            Overview Reports
                        </button>
                        <button wire:click="$set('reportType', 'csbg')" 
                                class="px-4 py-2 rounded-md {{ $reportType === 'csbg' ? 'bg-purple-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                            CSBG Demographics Report
                        </button>
                    </div>

                    @if ($reportType === 'overview')
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Participant Registration Trends -->
                        <div class="bg-white p-6 rounded-lg border">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Participant Registration by Month</h3>
                            <div class="space-y-2">
                                @foreach ($reportData['participants_by_month'] as $data)
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">{{ date('M Y', strtotime($data->month . '-01')) }}</span>
                                        <span class="text-sm font-medium">{{ $data->count }} participants</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Poverty Level Distribution -->
                        <div class="bg-white p-6 rounded-lg border">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Households by Poverty Level</h3>
                            <div class="space-y-2">
                                @foreach ($reportData['households_by_poverty_level'] as $data)
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">{{ $data->poverty_range }} of Federal Guidelines</span>
                                        <span class="text-sm font-medium">{{ $data->count }} households</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    
                    @elseif ($reportType === 'csbg')
                    <!-- CSBG Demographics Report -->
                    <div class="bg-white p-6 rounded-lg border dark:bg-zinc-800 dark:border-zinc-700">
                        <div class="mb-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Module 4, Section C: All Characteristics Report - Data Entry Form</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Goal 1: Individuals and Families with low-incomes are stable and achieve economic security.</p>
                        </div>

                        <!-- CALV Program Reporting Totals -->
                        <div class="mb-6 bg-yellow-50 dark:bg-yellow-900 p-4 rounded">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <span class="font-semibold">A. Total unduplicated number of all INDIVIDUALS:</span>
                                    <span class="ml-2 text-lg font-bold">{{ $csbgData['total_individuals'] ?? 0 }}</span>
                                </div>
                                <div>
                                    <span class="font-semibold">B. Total unduplicated number of all HOUSEHOLDS:</span>
                                    <span class="ml-2 text-lg font-bold">{{ $csbgData['total_households'] ?? 0 }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Individual Level Characteristics -->
                        <div class="mb-8">
                            <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-4 bg-blue-100 dark:bg-blue-900 p-2">C. INDIVIDUAL LEVEL CHARACTERISTICS</h4>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <!-- 1. Gender -->
                                <div>
                                    <h5 class="font-medium text-gray-700 dark:text-gray-300 mb-2">1. Gender</h5>
                                    <div class="space-y-1 text-sm">
                                        @foreach($csbgData['gender'] ?? [] as $key => $count)
                                            @if($key !== 'total')
                                            <div class="flex justify-between">
                                                <span>{{ $key === 'Unknown/not reported' ? 'Unknown/not reported' : ucfirst($key) }}</span>
                                                <span class="font-medium">{{ $count }}</span>
                                            </div>
                                            @endif
                                        @endforeach
                                        <div class="flex justify-between border-t pt-1">
                                            <span class="font-semibold">TOTAL</span>
                                            <span class="font-bold">{{ $csbgData['gender']['total'] ?? 0 }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- 2. Age -->
                                <div>
                                    <h5 class="font-medium text-gray-700 dark:text-gray-300 mb-2">2. Age</h5>
                                    <div class="space-y-1 text-sm">
                                        @foreach($csbgData['age_groups'] ?? [] as $ageGroup => $count)
                                        <div class="flex justify-between">
                                            <span>{{ $ageGroup }}</span>
                                            <span class="font-medium">{{ $count }}</span>
                                        </div>
                                        @endforeach
                                        <div class="flex justify-between border-t pt-1">
                                            <span class="font-semibold">TOTAL</span>
                                            <span class="font-bold">{{ $csbgData['age']['total'] ?? 0 }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- 3. Education Levels -->
                                <div>
                                    <h5 class="font-medium text-gray-700 dark:text-gray-300 mb-2">3. Education Levels</h5>
                                    <div class="space-y-1 text-sm">
                                        @foreach($csbgData['education'] ?? [] as $level => $count)
                                            @if($level !== 'total')
                                            <div class="flex justify-between">
                                                <span>{{ $level }}</span>
                                                <span class="font-medium">{{ $count }}</span>
                                            </div>
                                            @endif
                                        @endforeach
                                        <div class="flex justify-between border-t pt-1">
                                            <span class="font-semibold">TOTAL</span>
                                            <span class="font-bold">{{ $csbgData['education']['total'] ?? 0 }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Second Row -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
                                <!-- 4. Disconnected Youth -->
                                <div>
                                    <h5 class="font-medium text-gray-700 dark:text-gray-300 mb-2">4. Disconnected Youth</h5>
                                    <div class="space-y-1 text-sm">
                                        @foreach($csbgData['disconnected_youth'] ?? [] as $type => $count)
                                            @if($type !== 'total')
                                            <div class="flex justify-between">
                                                <span>{{ $type }}</span>
                                                <span class="font-medium">{{ $count }}</span>
                                            </div>
                                            @endif
                                        @endforeach
                                        <div class="flex justify-between border-t pt-1">
                                            <span class="font-semibold">TOTAL</span>
                                            <span class="font-bold">{{ $csbgData['disconnected_youth']['total'] ?? 0 }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- 5. Health -->
                                <div>
                                    <h5 class="font-medium text-gray-700 dark:text-gray-300 mb-2">5. Health</h5>
                                    <div class="space-y-1 text-sm">
                                        <div class="font-semibold mb-1">a. Disabling Condition</div>
                                        @foreach($csbgData['health']['disabling_condition'] ?? [] as $status => $count)
                                        <div class="flex justify-between">
                                            <span class="ml-3">{{ $status }}</span>
                                            <span class="font-medium">{{ $count }}</span>
                                        </div>
                                        @endforeach
                                        <div class="font-semibold mb-1 mt-2">b. Health Insurance</div>
                                        @foreach($csbgData['health']['health_insurance'] ?? [] as $status => $count)
                                        <div class="flex justify-between">
                                            <span class="ml-3">{{ $status }}</span>
                                            <span class="font-medium">{{ $count }}</span>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>

                                <!-- Health Insurance Sources -->
                                <div>
                                    <h5 class="font-medium text-gray-700 dark:text-gray-300 mb-2">Health Insurance Sources</h5>
                                    <div class="space-y-1 text-sm">
                                        @foreach($csbgData['health_insurance_sources'] ?? [] as $source => $count)
                                            @if($source !== 'total')
                                            <div class="flex justify-between">
                                                <span>{{ $source }}</span>
                                                <span class="font-medium">{{ $count }}</span>
                                            </div>
                                            @endif
                                        @endforeach
                                        <div class="flex justify-between border-t pt-1">
                                            <span class="font-semibold">TOTAL</span>
                                            <span class="font-bold">{{ $csbgData['health_insurance_sources']['total'] ?? 0 }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Third Row -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
                                <!-- 6. Ethnicity/Race -->
                                <div>
                                    <h5 class="font-medium text-gray-700 dark:text-gray-300 mb-2">6. Ethnicity/Race</h5>
                                    <div class="space-y-1 text-sm">
                                        <div class="font-semibold mb-1">a. Ethnicity</div>
                                        @foreach($csbgData['ethnicity'] ?? [] as $ethnicity => $count)
                                            @if($ethnicity !== 'total')
                                            <div class="flex justify-between">
                                                <span class="ml-3">{{ $ethnicity }}</span>
                                                <span class="font-medium">{{ $count }}</span>
                                            </div>
                                            @endif
                                        @endforeach
                                        <div class="font-semibold mb-1 mt-2">b. Race</div>
                                        @foreach($csbgData['race'] ?? [] as $race => $count)
                                            @if($race !== 'total')
                                            <div class="flex justify-between">
                                                <span class="ml-3">{{ $race }}</span>
                                                <span class="font-medium">{{ $count }}</span>
                                            </div>
                                            @endif
                                        @endforeach
                                        <div class="flex justify-between border-t pt-1">
                                            <span class="font-semibold">TOTAL</span>
                                            <span class="font-bold">{{ $csbgData['race']['total'] ?? 0 }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- 7. Military Status -->
                                <div>
                                    <h5 class="font-medium text-gray-700 dark:text-gray-300 mb-2">7. Military Status</h5>
                                    <div class="space-y-1 text-sm">
                                        @foreach($csbgData['military_status'] ?? [] as $status => $count)
                                            @if($status !== 'total')
                                            <div class="flex justify-between">
                                                <span>{{ $status }}</span>
                                                <span class="font-medium">{{ $count }}</span>
                                            </div>
                                            @endif
                                        @endforeach
                                        <div class="flex justify-between border-t pt-1">
                                            <span class="font-semibold">TOTAL</span>
                                            <span class="font-bold">{{ $csbgData['military_status']['total'] ?? 0 }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- 8. Work Status (Individuals 18+) -->
                                <div>
                                    <h5 class="font-medium text-gray-700 dark:text-gray-300 mb-2">8. Work Status (Individuals 18+)</h5>
                                    <div class="space-y-1 text-sm">
                                        @foreach($csbgData['work_status'] ?? [] as $status => $count)
                                            @if($status !== 'total')
                                            <div class="flex justify-between">
                                                <span>{{ $status }}</span>
                                                <span class="font-medium">{{ $count }}</span>
                                            </div>
                                            @endif
                                        @endforeach
                                        <div class="flex justify-between border-t pt-1">
                                            <span class="font-semibold">TOTAL</span>
                                            <span class="font-bold">{{ $csbgData['work_status']['total'] ?? 0 }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Household Level Characteristics -->
                        <div class="mb-8">
                            <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-4 bg-blue-100 dark:bg-blue-900 p-2">D. HOUSEHOLD LEVEL CHARACTERISTICS</h4>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <!-- 9. Household Type -->
                                <div>
                                    <h5 class="font-medium text-gray-700 dark:text-gray-300 mb-2">9. Household Type</h5>
                                    <div class="space-y-1 text-sm">
                                        @foreach($csbgData['household_type'] ?? [] as $type => $count)
                                            @if($type !== 'total')
                                            <div class="flex justify-between">
                                                <span>{{ $type }}</span>
                                                <span class="font-medium">{{ $count }}</span>
                                            </div>
                                            @endif
                                        @endforeach
                                        <div class="flex justify-between border-t pt-1">
                                            <span class="font-semibold">TOTAL</span>
                                            <span class="font-bold">{{ $csbgData['household_type']['total'] ?? 0 }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- 10. Household Size -->
                                <div>
                                    <h5 class="font-medium text-gray-700 dark:text-gray-300 mb-2">10. Household Size</h5>
                                    <div class="space-y-1 text-sm">
                                        @foreach($csbgData['household_size'] ?? [] as $size => $count)
                                            @if($size !== 'total')
                                            <div class="flex justify-between">
                                                <span>{{ $size }}</span>
                                                <span class="font-medium">{{ $count }}</span>
                                            </div>
                                            @endif
                                        @endforeach
                                        <div class="flex justify-between border-t pt-1">
                                            <span class="font-semibold">TOTAL</span>
                                            <span class="font-bold">{{ $csbgData['household_size']['total'] ?? 0 }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- 11. Housing -->
                                <div>
                                    <h5 class="font-medium text-gray-700 dark:text-gray-300 mb-2">11. Housing</h5>
                                    <div class="space-y-1 text-sm">
                                        @foreach($csbgData['housing'] ?? [] as $type => $count)
                                            @if($type !== 'total')
                                            <div class="flex justify-between">
                                                <span>{{ $type }}</span>
                                                <span class="font-medium">{{ $count }}</span>
                                            </div>
                                            @endif
                                        @endforeach
                                        <div class="flex justify-between border-t pt-1">
                                            <span class="font-semibold">TOTAL</span>
                                            <span class="font-bold">{{ $csbgData['housing']['total'] ?? 0 }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Second Row of Household Data -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
                                <!-- 12. Level of Household Income -->
                                <div>
                                    <h5 class="font-medium text-gray-700 dark:text-gray-300 mb-2">12. Level of Household Income (% of HHS Guideline)</h5>
                                    <div class="space-y-1 text-sm">
                                        @foreach($csbgData['income_level'] ?? [] as $level => $count)
                                            @if($level !== 'total')
                                            <div class="flex justify-between">
                                                <span>{{ $level }}</span>
                                                <span class="font-medium">{{ $count }}</span>
                                            </div>
                                            @endif
                                        @endforeach
                                        <div class="flex justify-between border-t pt-1">
                                            <span class="font-semibold">TOTAL</span>
                                            <span class="font-bold">{{ $csbgData['income_level']['total'] ?? 0 }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- 13. Sources of Household Income -->
                                <div>
                                    <h5 class="font-medium text-gray-700 dark:text-gray-300 mb-2">13. Sources of Household Income</h5>
                                    <div class="space-y-1 text-sm">
                                        @foreach($csbgData['income_sources'] ?? [] as $source => $count)
                                            @if($source !== 'total')
                                            <div class="flex justify-between">
                                                <span>{{ $source }}</span>
                                                <span class="font-medium">{{ $count }}</span>
                                            </div>
                                            @endif
                                        @endforeach
                                        <div class="flex justify-between border-t pt-1">
                                            <span class="font-semibold">TOTAL</span>
                                            <span class="font-bold">{{ $csbgData['income_sources']['total'] ?? 0 }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- 14. Other Income Source -->
                                <div>
                                    <h5 class="font-medium text-gray-700 dark:text-gray-300 mb-2">14. Other Income Source</h5>
                                    <div class="space-y-1 text-sm">
                                        @foreach($csbgData['other_income_sources'] ?? [] as $source => $count)
                                        <div class="flex justify-between">
                                            <span>{{ $source }}</span>
                                            <span class="font-medium">{{ $count }}</span>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <!-- 15. Non-Cash Benefits -->
                            <div class="mt-6">
                                <h5 class="font-medium text-gray-700 dark:text-gray-300 mb-2">15. Non-Cash Benefits</h5>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    @foreach($csbgData['non_cash_benefits'] ?? [] as $benefit => $count)
                                    <div class="flex justify-between text-sm">
                                        <span>{{ $benefit }}</span>
                                        <span class="font-medium">{{ $count }}</span>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- Print/Export Button -->
                        <div class="flex justify-end mt-6">
                            <button wire:click="exportCSBGReport" class="bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700 transition duration-150 ease-in-out">
                                Export CSBG Report
                            </button>
                        </div>
                    </div>
                    @endif
                </div>
            @elseif ($activeTab === 'settings')
                <!-- Site Settings -->
                <div class="space-y-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Site Settings</h2>
                    
                    <form wire:submit.prevent="updateSiteSettings" class="space-y-6">
                        <!-- Site Name -->
                        <div>
                            <label for="site_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Site Name</label>
                            <input type="text" wire:model="site_name" id="site_name" 
                                   class="mt-1 block w-full border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                            @error('site_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <!-- Site Logo -->
                        <div>
                            <label for="site_logo" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Site Logo</label>
                            <div class="mt-1 flex items-center space-x-5">
                                @if($current_logo_path)
                                    <div class="flex-shrink-0">
                                        <img class="h-16 w-auto" src="{{ Storage::url($current_logo_path) }}" alt="Current Logo">
                                    </div>
                                @endif
                                <div class="flex-grow">
                                    <input type="file" wire:model="site_logo" id="site_logo" accept="image/*"
                                           class="block w-full text-sm text-gray-500 dark:text-gray-400
                                                  file:mr-4 file:py-2 file:px-4
                                                  file:rounded-md file:border-0
                                                  file:text-sm file:font-semibold
                                                  file:bg-purple-50 file:text-purple-700
                                                  hover:file:bg-purple-100
                                                  dark:file:bg-zinc-700 dark:file:text-purple-400">
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">PNG, JPG, JPEG, SVG up to 2MB</p>
                                </div>
                            </div>
                            @error('site_logo') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <!-- Site Favicon -->
                        <div>
                            <label for="site_favicon" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Site Favicon</label>
                            <div class="mt-1 flex items-center space-x-5">
                                @if($current_favicon_path)
                                    <div class="flex-shrink-0">
                                        <img class="h-8 w-8" src="{{ Storage::url($current_favicon_path) }}" alt="Current Favicon">
                                    </div>
                                @endif
                                <div class="flex-grow">
                                    <input type="file" wire:model="site_favicon" id="site_favicon" accept="image/x-icon,image/png,image/jpeg"
                                           class="block w-full text-sm text-gray-500 dark:text-gray-400
                                                  file:mr-4 file:py-2 file:px-4
                                                  file:rounded-md file:border-0
                                                  file:text-sm file:font-semibold
                                                  file:bg-purple-50 file:text-purple-700
                                                  hover:file:bg-purple-100
                                                  dark:file:bg-zinc-700 dark:file:text-purple-400">
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">ICO, PNG, JPG up to 512KB</p>
                                </div>
                            </div>
                            @error('site_favicon') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end">
                            <button type="submit" 
                                    class="bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition duration-150 ease-in-out">
                                Update Settings
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </div>
</div>