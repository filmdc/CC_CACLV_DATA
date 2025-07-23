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
<!--                 <button wire:click="setActiveTab('reports')" 
                        class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'reports' ? 'border-purple-500 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Reports
                </button>
 -->                <button wire:click="setActiveTab('data_management')" 
                        class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'data_management' ? 'border-purple-500 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Data Management
                </button>
                <button wire:click="setActiveTab('settings')" 
                        class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'settings' ? 'border-purple-500 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Site Settings
                </button>
                <button wire:click="setActiveTab('contract_settings')" 
                        class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'contract_settings' ? 'border-purple-500 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Contract Settings
                </button>
                <button wire:click="setActiveTab('roles')" 
                        class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'roles' ? 'border-purple-500 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Roles & Permissions
                </button>
                <button wire:click="setActiveTab('reference')" 
                        class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'reference' ? 'border-purple-500 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Reference Data
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
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">User Management</h2>
                        <a href="{{ route('user.management') }}" wire:navigate
                           class="inline-flex items-center px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-md hover:bg-purple-700">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            Advanced User Management
                        </a>
                    </div>

                    <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-6">
                        <p class="text-gray-600 dark:text-gray-300 mb-4">
                            Manage system users, assign roles and programs, and control access permissions.
                        </p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="bg-white dark:bg-zinc-800 rounded-lg p-4 shadow">
                                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Users</h3>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $systemStats['total_users'] ?? 0 }}</p>
                            </div>
                            
                            <div class="bg-white dark:bg-zinc-800 rounded-lg p-4 shadow">
                                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Active Users</h3>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $systemStats['active_users'] ?? 0 }}</p>
                            </div>
                            
                            <div class="bg-white dark:bg-zinc-800 rounded-lg p-4 shadow">
                                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Roles</h3>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ \App\Models\Role::count() }}</p>
                            </div>
                        </div>
                        
                        <div class="mt-6 space-y-3">
                            <h3 class="font-medium text-gray-900 dark:text-white">Quick Actions</h3>
                            <div class="flex flex-wrap gap-3">
                                <a href="{{ route('user.management') }}" wire:navigate
                                   class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-purple-600 rounded-md hover:bg-purple-700">
                                    View All Users
                                </a>
                                <a href="{{ route('user.management') }}" wire:navigate
                                   class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-zinc-600 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-500">
                                    Bulk User Assignment
                                </a>
                                <a href="{{ route('role-permission.management') }}" wire:navigate
                                   class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-zinc-600 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-500">
                                    Manage Permissions
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Users -->
                    <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 shadow">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Recent Users</h3>
                        <div class="space-y-3">
                            @foreach($users->take(5) as $user)
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <span class="h-10 w-10 rounded-full bg-purple-500 flex items-center justify-center text-white font-medium">
                                            {{ $user->initials() }}
                                        </span>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $user->name }}</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $user->email }}</div>
                                    </div>
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    Joined {{ $user->created_at->format('M j, Y') }}
                                </div>
                            </div>
                            @endforeach
                        </div>
                        
                        <div class="mt-4">
                            <a href="{{ route('user.management') }}" wire:navigate
                               class="text-sm text-purple-600 dark:text-purple-400 hover:text-purple-700 dark:hover:text-purple-300">
                                View all users →
                            </a>
                        </div>
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
            @elseif ($activeTab === 'data_management')
                <!-- Data Management -->
                <div class="space-y-6">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Data Management</h2>
                        <div class="text-sm text-gray-500">
                            Manage test data and bulk operations
                        </div>
                    </div>
                    
                    <!-- Data Statistics -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="bg-gray-50 dark:bg-zinc-700 p-4 rounded-lg">
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Records</h3>
                            <div class="mt-2 space-y-1">
                                <div class="flex justify-between">
                                    <span class="text-sm">Participants</span>
                                    <span class="font-semibold">{{ $dataStats['total_participants'] ?? 0 }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm">Households</span>
                                    <span class="font-semibold">{{ $dataStats['total_households'] ?? 0 }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-blue-50 dark:bg-blue-900 p-4 rounded-lg">
                            <h3 class="text-sm font-medium text-blue-700 dark:text-blue-300">Import Data</h3>
                            <div class="mt-2 space-y-1">
                                <div class="flex justify-between">
                                    <span class="text-sm">Imported Participants</span>
                                    <span class="font-semibold">{{ $dataStats['imported_participants'] ?? 0 }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm">Import Logs</span>
                                    <span class="font-semibold">{{ $dataStats['import_logs'] ?? 0 }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-yellow-50 dark:bg-yellow-900 p-4 rounded-lg">
                            <h3 class="text-sm font-medium text-yellow-700 dark:text-yellow-300">Duplicate Management</h3>
                            <div class="mt-2 space-y-1">
                                <div class="flex justify-between">
                                    <span class="text-sm">Pending Duplicates</span>
                                    <span class="font-semibold">{{ $dataStats['pending_duplicates'] ?? 0 }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm">Total Duplicates</span>
                                    <span class="font-semibold">{{ $dataStats['potential_duplicates'] ?? 0 }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- System Data Protection Notice -->
                    <div class="bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-lg p-4 mb-6">
                        <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200 mb-2">
                            🔒 System Data Protection
                        </h3>
                        <p class="text-sm text-blue-700 dark:text-blue-300">
                            These delete operations only affect participant and household data. System configuration data 
                            (Programs, Services, Work Plans, Goals, Reference Data, Poverty Guidelines, Contracts) are protected 
                            and cannot be deleted through these bulk operations.
                        </p>
                    </div>
                    
                    <!-- Bulk Delete Actions -->
                    <div class="bg-red-50 dark:bg-red-900 border border-red-200 dark:border-red-700 rounded-lg p-6">
                        <h3 class="text-lg font-medium text-red-800 dark:text-red-200 mb-4">
                            ⚠️ Bulk Delete Operations - Participant/Household Data Only
                        </h3>
                        <p class="text-sm text-red-700 dark:text-red-300 mb-4">
                            Use these tools to clean up participant and household data. Always backup your database before performing bulk operations.
                        </p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="space-y-2">
                                <button wire:click="showBulkDeleteModal('test_data')" 
                                        wire:loading.attr="disabled"
                                        wire:loading.class="opacity-50 cursor-not-allowed"
                                        class="w-full bg-orange-600 text-white px-4 py-2 rounded-md hover:bg-orange-700 transition duration-150 ease-in-out text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                                    <span wire:loading.remove wire:target="showBulkDeleteModal('test_data')">🧹 Clean Test Data</span>
                                    <span wire:loading wire:target="showBulkDeleteModal('test_data')">Loading...</span>
                                </button>
                                <p class="text-xs text-gray-600 dark:text-gray-400">Remove imported test data only</p>
                            </div>
                            
                            <div class="space-y-2">
                                <button wire:click="showBulkDeleteModal('participants')" 
                                        wire:loading.attr="disabled"
                                        wire:loading.class="opacity-50 cursor-not-allowed"
                                        class="w-full bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 transition duration-150 ease-in-out text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                                    <span wire:loading.remove wire:target="showBulkDeleteModal('participants')">👥 Delete Participants</span>
                                    <span wire:loading wire:target="showBulkDeleteModal('participants')">Loading...</span>
                                </button>
                                <p class="text-xs text-gray-600 dark:text-gray-400">Delete participant records</p>
                            </div>
                            
                            <div class="space-y-2">
                                <button wire:click="showBulkDeleteModal('households')" 
                                        wire:loading.attr="disabled"
                                        wire:loading.class="opacity-50 cursor-not-allowed"
                                        class="w-full bg-red-700 text-white px-4 py-2 rounded-md hover:bg-red-800 transition duration-150 ease-in-out text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                                    <span wire:loading.remove wire:target="showBulkDeleteModal('households')">🏠 Delete Households</span>
                                    <span wire:loading wire:target="showBulkDeleteModal('households')">Loading...</span>
                                </button>
                                <p class="text-xs text-gray-600 dark:text-gray-400">Delete household records and their participants</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Import Activity -->
                    @if(isset($dataStats['recent_imports']) && $dataStats['recent_imports']->count() > 0)
                    <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Recent Import Activity</h3>
                        <div class="space-y-3">
                            @foreach($dataStats['recent_imports'] as $import)
                            <div class="flex items-center justify-between p-3 bg-white dark:bg-zinc-600 rounded border">
                                <div>
                                    <div class="font-medium text-sm">{{ $import->template->name ?? 'Unknown Template' }}</div>
                                    <div class="text-xs text-gray-500">
                                        {{ $import->successful_records ?? 0 }} imported, 
                                        {{ $import->failed_records ?? 0 }} failed
                                    </div>
                                </div>
                                <div class="text-right">
                                    <!-- <div class="text-xs font-medium {{ $import->status === 'success' ? 'text-green-600' : 'text-red-600' }}">
                                        {{ ucfirst($import->status) }}
                                    </div> -->
                                    <div class="text-xs text-gray-500">
                                        {{ $import->created_at->diffForHumans() }}
                                    </div>
                                </div>
                            </div>
                            @endforeach
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
            @elseif ($activeTab === 'contract_settings')
                <!-- Contract Settings -->
                <div class="space-y-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Contract Notification Settings</h2>
                    
                    <form wire:submit.prevent="updateContractSettings" class="space-y-6">
                        <!-- Test Mode Toggle -->
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Test Mode</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">When enabled, notifications will only be sent to the test email address</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" wire:model="contract_reminder_test_mode" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 dark:peer-focus:ring-purple-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-purple-600"></div>
                                </label>
                            </div>
                            @if($contract_reminder_test_mode)
                                <div class="mt-3">
                                    <label for="test_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Test Email Address</label>
                                    <input type="email" wire:model="contract_reminder_test_email" id="test_email" 
                                           class="mt-1 block w-full border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500"
                                           placeholder="test@example.com">
                                    @error('contract_reminder_test_email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                            @endif
                        </div>
                        
                        <!-- Global Reminder Intervals -->
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-3">Global Reminder Intervals</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Select when reminders should be sent before the due date</p>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                @foreach([365 => '1 Year', 180 => '6 Months', 90 => '3 Months', 60 => '2 Months', 30 => '30 Days', 15 => '15 Days', 7 => '7 Days', 3 => '3 Days', 1 => '1 Day', 0 => 'Due Date'] as $days => $label)
                                    <label class="flex items-center space-x-2">
                                        <input type="checkbox" 
                                               wire:model="contract_reminder_intervals" 
                                               value="{{ $days }}"
                                               class="rounded border-gray-300 text-purple-600 shadow-sm focus:border-purple-300 focus:ring focus:ring-purple-200 focus:ring-opacity-50">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('contract_reminder_intervals') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        
                        <!-- Email Subject -->
                        <div>
                            <label for="email_subject" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email Subject Template</label>
                            <input type="text" wire:model="contract_reminder_email_subject" id="email_subject" 
                                   class="mt-1 block w-full border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500"
                                   placeholder="Contract Report Reminder: @{{report_name}}">
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Available variables: @{{report_name}}, @{{contract_title}}, @{{due_date}}, @{{days_until}}</p>
                            @error('contract_reminder_email_subject') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        
                        <!-- Email Template -->
                        <div wire:ignore>
                            <label for="email_template" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email Body Template</label>
                            <textarea wire:model="contract_reminder_email_template" id="email_template" rows="10"
                                      class="mt-1 block w-full border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500 font-mono text-sm"></textarea>
                            <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                <p class="font-medium mb-1">Available variables:</p>
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                                    <span>@{{report_name}}</span>
                                    <span>@{{contract_title}}</span>
                                    <span>@{{program}}</span>
                                    <span>@{{funder}}</span>
                                    <span>@{{due_date}}</span>
                                    <span>@{{days_until}}</span>
                                    <span>@{{description}}</span>
                                </div>
                            </div>
                            @error('contract_reminder_email_template') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="flex justify-between">
                            <a href="{{ route('contract.notification-test') }}" wire:navigate
                               class="inline-flex items-center px-4 py-2 border border-purple-600 text-purple-600 text-sm font-medium rounded-md hover:bg-purple-50 dark:hover:bg-purple-900/20">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                                Test Dashboard
                            </a>
                            <button type="submit" 
                                    class="bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition duration-150 ease-in-out">
                                Update Contract Settings
                            </button>
                        </div>
                    </form>
                </div>
            @elseif ($activeTab === 'roles')
                <!-- Roles & Permissions -->
                <div class="space-y-6">
                    <div class="flex justify-between items-center">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Roles & Permissions Management</h2>
                        <a href="{{ route('role-permission.management') }}" wire:navigate
                           class="inline-flex items-center px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-md hover:bg-purple-700">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Manage Roles & Permissions
                        </a>
                    </div>
                    
                    <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-6">
                        <p class="text-gray-600 dark:text-gray-300 mb-4">
                            Configure user roles and their associated permissions to control access to different parts of the system.
                        </p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h3 class="font-medium text-gray-900 dark:text-white mb-2">Current Roles</h3>
                                @php
                                    $roles = \App\Models\Role::withCount('users')->get();
                                @endphp
                                <ul class="space-y-2">
                                    @foreach($roles as $role)
                                    <li class="flex justify-between items-center p-2 bg-white dark:bg-zinc-800 rounded">
                                        <span class="text-gray-700 dark:text-gray-300">{{ $role->name }}</span>
                                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $role->users_count }} users</span>
                                    </li>
                                    @endforeach
                                </ul>
                            </div>
                            
                            <div>
                                <h3 class="font-medium text-gray-900 dark:text-white mb-2">Permission Categories</h3>
                                @php
                                    $permissions = \App\Models\Permission::all()->groupBy(function($item) {
                                        return explode('.', $item->name)[0];
                                    });
                                @endphp
                                <ul class="space-y-2">
                                    @foreach($permissions as $category => $perms)
                                    <li class="flex justify-between items-center p-2 bg-white dark:bg-zinc-800 rounded">
                                        <span class="text-gray-700 dark:text-gray-300 capitalize">{{ $category }}</span>
                                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ count($perms) }} permissions</span>
                                    </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            @elseif ($activeTab === 'reference')
                <!-- Reference Data -->
                <div class="space-y-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Reference Data Management</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <!-- Reference Data Sets -->
                        <div class="bg-white dark:bg-zinc-700 rounded-lg p-6 shadow">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Reference Data Sets</h3>
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
                                </svg>
                            </div>
                            <p class="text-gray-600 dark:text-gray-300 text-sm mb-4">
                                Manage data sets used throughout the system like education levels, income sources, etc.
                            </p>
                            <a href="{{ route('reference-data-set.management') }}" wire:navigate
                               class="inline-flex items-center text-purple-600 hover:text-purple-700 dark:text-purple-400">
                                Manage Data Sets
                                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </a>
                        </div>
                        
                        <!-- Reference Data Items -->
                        <div class="bg-white dark:bg-zinc-700 rounded-lg p-6 shadow">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Reference Data Items</h3>
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                            </div>
                            <p class="text-gray-600 dark:text-gray-300 text-sm mb-4">
                                Manage individual reference data items within each data set.
                            </p>
                            <a href="{{ route('reference-data.management') }}" wire:navigate
                               class="inline-flex items-center text-purple-600 hover:text-purple-700 dark:text-purple-400">
                                Manage Data Items
                                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </a>
                        </div>
                        
                        <!-- Poverty Guidelines -->
                        <div class="bg-white dark:bg-zinc-700 rounded-lg p-6 shadow">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Poverty Guidelines</h3>
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <p class="text-gray-600 dark:text-gray-300 text-sm mb-4">
                                Manage federal poverty guidelines used for income calculations.
                            </p>
                            <a href="{{ route('poverty-guideline.management') }}" wire:navigate
                               class="inline-flex items-center text-purple-600 hover:text-purple-700 dark:text-purple-400">
                                Manage Guidelines
                                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </a>
                        </div>
                    </div>
                    
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                        <div class="flex">
                            <svg class="w-5 h-5 text-blue-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-blue-800 dark:text-blue-300">Important Note</h3>
                                <p class="mt-1 text-sm text-blue-700 dark:text-blue-400">
                                    Reference data is used throughout the system. Changes to reference data will affect all records using that data.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Bulk Delete Modal -->
    @if($bulkDeleteModalVisible)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                        @if($bulkDeleteType === 'test_data')
                            🧹 Clean Test Data
                        @elseif($bulkDeleteType === 'participants')
                            👥 Bulk Delete Participants
                        @elseif($bulkDeleteType === 'households')
                            🏠 Bulk Delete Households
                        @endif
                    </h3>
                    <button wire:click="closeBulkDeleteModal" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <div class="space-y-4">
                    <!-- Warning -->
                    <div class="bg-red-50 dark:bg-red-900 border border-red-200 dark:border-red-700 rounded-lg p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800 dark:text-red-200">
                                    Warning: This action cannot be undone
                                </h3>
                                <div class="mt-2 text-sm text-red-700 dark:text-red-300">
                                    <p>Make sure you have a database backup before proceeding.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Options -->
                    <div class="space-y-4">
                        @if($bulkDeleteType !== 'test_data')
                        <!-- Test Data Only Option -->
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" wire:model="deleteTestDataOnly" class="rounded border-gray-300 text-red-600 shadow-sm focus:border-red-300 focus:ring focus:ring-red-200 focus:ring-opacity-50">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Only delete test/imported data (recommended)</span>
                            </label>
                        </div>
                        @endif
                        
                        <!-- Date Range -->
                        <div>
                            <label for="dateRange" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Date Range</label>
                            <select wire:model="selectedDateRange" id="dateRange" class="mt-1 block w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                                <option value="7">Last 7 days</option>
                                <option value="30">Last 30 days</option>
                                <option value="90">Last 90 days</option>
                                <option value="365">Last year</option>
                                <option value="all">All time</option>
                            </select>
                        </div>
                        
                        @if($bulkDeleteType === 'participants' || $bulkDeleteType === 'test_data')
                        <!-- Program Filter -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Programs (leave empty for all)</label>
                            <div class="mt-2 space-y-2">
                                @foreach($this->getAvailablePrograms() as $program)
                                <label class="flex items-center">
                                    <input type="checkbox" wire:model="selectedPrograms" value="{{ $program->id }}" class="rounded border-gray-300 text-red-600">
                                    <span class="ml-2 text-sm">{{ $program->name }}</span>
                                </label>
                                @endforeach
                            </div>
                        </div>
                        @endif
                        
                        @if($bulkDeleteType === 'test_data')
                        <!-- Source Filter -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Import Sources</label>
                            <div class="mt-2 space-y-2">
                                @foreach($this->getAvailableSources() as $key => $label)
                                <label class="flex items-center">
                                    <input type="checkbox" wire:model="selectedSources" value="{{ $key }}" class="rounded border-gray-300 text-red-600">
                                    <span class="ml-2 text-sm">{{ $label }}</span>
                                </label>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                    
                    <!-- Preview -->
                    <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-4">
                        <h4 class="font-medium text-gray-900 dark:text-white mb-2">Preview</h4>
                        @if($bulkDeleteType === 'test_data')
                            @php 
                                try {
                                    $preview = $this->getBulkDeletePreview();
                                } catch (\Exception $e) {
                                    $preview = ['participants' => 0, 'households' => 0, 'duplicates' => 0, 'import_logs' => 0];
                                }
                            @endphp
                            <div class="text-sm space-y-1">
                                <div>Participants to delete: <span class="font-semibold">{{ $preview['participants'] ?? 0 }}</span></div>
                                <div>Households to delete: <span class="font-semibold">{{ $preview['households'] ?? 0 }}</span></div>
                                <div>Duplicate records to delete: <span class="font-semibold">{{ $preview['duplicates'] ?? 0 }}</span></div>
                                <div>Import logs to delete: <span class="font-semibold">{{ $preview['import_logs'] ?? 0 }}</span></div>
                            </div>
                        @else
                            <div class="text-sm">
                                Records to delete: <span class="font-semibold">{{ is_numeric($preview = $this->getBulkDeletePreview()) ? $preview : 0 }}</span>
                            </div>
                        @endif
                    </div>
                    
                    <!-- Confirmation -->
                    <div>
                        <label for="confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Type 
                            <span class="font-mono bg-gray-100 dark:bg-zinc-700 px-2 py-1 rounded">
                                @if($bulkDeleteType === 'test_data')
                                    DELETE TEST DATA
                                @else
                                    DELETE {{ strtoupper($bulkDeleteType) }}
                                @endif
                            </span> 
                            to confirm
                        </label>
                        @php
                            $expectedText = $bulkDeleteType === 'test_data' ? 'DELETE TEST DATA' : 'DELETE ' . strtoupper($bulkDeleteType);
                            $isMatching = strtoupper(trim($deleteConfirmation)) === $expectedText;
                        @endphp
                        <input type="text" wire:model.live="deleteConfirmation" id="confirmation" 
                               class="mt-1 block w-full rounded-md {{ $isMatching ? 'border-green-500 focus:border-green-500 focus:ring-green-500' : 'border-gray-300 dark:border-zinc-600' }} dark:bg-zinc-700 dark:text-white"
                               placeholder="Type confirmation text here">
                        @if($deleteConfirmation && !$isMatching)
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">Confirmation text does not match</p>
                        @elseif($isMatching)
                            <p class="mt-1 text-sm text-green-600 dark:text-green-400">✓ Confirmation text matches</p>
                        @endif
                    </div>
                    
                    <!-- Actions -->
                    <div class="flex justify-end space-x-3">
                        <button wire:click="closeBulkDeleteModal" 
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                            Cancel
                        </button>
                        @php
                            $expectedConfirmation = $bulkDeleteType === 'test_data' ? 'DELETE TEST DATA' : 'DELETE ' . strtoupper($bulkDeleteType);
                            $isConfirmationValid = strtoupper(trim($deleteConfirmation)) === $expectedConfirmation;
                        @endphp
                        <button wire:click="executeBulkDelete" 
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-50 cursor-not-allowed"
                                class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed relative"
                                @if(!$isConfirmationValid) disabled @endif>
                            <span wire:loading.remove wire:target="executeBulkDelete">Delete</span>
                            <span wire:loading wire:target="executeBulkDelete">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Processing...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>