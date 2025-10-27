<div class="w-full px-0 py-6">
    <div class="bg-white dark:bg-zinc-800 shadow-lg rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-blue-600 text-white p-6">
            <h1 class="text-2xl font-bold">Reporting Dashboard</h1>
            <p class="text-blue-100 mt-2">Generate comprehensive participant and program reports</p>
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

        <!-- Report Parameters -->
        <div class="p-6 bg-gray-50 dark:bg-zinc-700 border-b border-gray-200 dark:border-zinc-600">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Report Parameters</h3>
            
            <form wire:submit="generateReport" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- Date Range -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start Date *</label>
                        <input type="date" wire:model="start_date" required 
                               class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @error('start_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">End Date *</label>
                        <input type="date" wire:model="end_date" required 
                               class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @error('end_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- Reference Data Set -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Reference Data Set</label>
                        <select wire:model="reference_data_set_id"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Data Sets</option>
                            @foreach($referenceDataSets->where('is_active', true) as $dataSet)
                                <option value="{{ $dataSet->id }}">
                                    {{ $dataSet->name }} ({{ $dataSet->version }})
                                    @if($dataSet->is_import_default) - Import Default @endif
                                </option>
                            @endforeach
                        </select>
                        @error('reference_data_set_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- Program Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Program Filter</label>
                        <select wire:model="program_id" 
                                class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Programs</option>
                            @foreach($programs as $program)
                                <option value="{{ $program->id }}">{{ $program->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Heads of Household Only -->
                    <div class="flex items-center">
                        <input type="checkbox" wire:model="heads_of_household_only" 
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-zinc-600 rounded">
                        <label class="ml-2 block text-sm text-gray-900 dark:text-white">Heads of Household Only</label>
                    </div>

                    <!-- Advanced Filters Toggle -->
                    <div class="flex items-center">
                        <button type="button" wire:click="toggleAdvancedFilters" 
                                class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-medium">
                            @if($showAdvancedFilters)
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                </svg>
                                Hide Advanced Filters
                            @else
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                                Show Advanced Filters
                            @endif
                        </button>
                    </div>
                </div>

                <!-- Advanced Filters Section -->
                @if($showAdvancedFilters)
                    <div class="mt-6 p-4 bg-gray-100 dark:bg-zinc-600 rounded-lg border border-gray-200 dark:border-zinc-500">
                        <div class="flex justify-between items-center mb-4">
                            <h4 class="text-md font-medium text-gray-900 dark:text-white">Advanced Filters</h4>
                            <button type="button" wire:click="clearAdvancedFilters" 
                                    class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
                                Clear All Filters
                            </button>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <!-- Gender Filter -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Gender</label>
                                <div class="space-y-2">
                                    <label class="flex items-center">
                                        <input type="checkbox" wire:model="filter_gender" value="Male" 
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-zinc-600 rounded">
                                        <span class="ml-2 text-sm text-gray-900 dark:text-white">Male</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" wire:model="filter_gender" value="Female" 
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-zinc-600 rounded">
                                        <span class="ml-2 text-sm text-gray-900 dark:text-white">Female</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" wire:model="filter_gender" value="Other" 
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-zinc-600 rounded">
                                        <span class="ml-2 text-sm text-gray-900 dark:text-white">Other</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Ethnicity Filter -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ethnicity</label>
                                <div class="space-y-2">
                                    <label class="flex items-center">
                                        <input type="checkbox" wire:model="filter_ethnicity" value="Hispanic or Latino" 
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-zinc-600 rounded">
                                        <span class="ml-2 text-sm text-gray-900 dark:text-white">Hispanic or Latino</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" wire:model="filter_ethnicity" value="Not Hispanic or Latino" 
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-zinc-600 rounded">
                                        <span class="ml-2 text-sm text-gray-900 dark:text-white">Not Hispanic or Latino</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Age Range Filter -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Age Range</label>
                                <div class="flex space-x-2">
                                    <input type="number" wire:model="filter_age_min" placeholder="Min" min="0" max="120"
                                           class="w-20 px-2 py-1 text-sm border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white">
                                    <span class="text-gray-500 dark:text-gray-400 text-sm self-center">to</span>
                                    <input type="number" wire:model="filter_age_max" placeholder="Max" min="0" max="120"
                                           class="w-20 px-2 py-1 text-sm border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white">
                                </div>
                            </div>

                            <!-- Education Level Filter -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Education Level</label>
                                <div class="space-y-2 max-h-24 overflow-y-auto">
                                    <label class="flex items-center">
                                        <input type="checkbox" wire:model="filter_education_level" value="Less than high school" 
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-zinc-600 rounded">
                                        <span class="ml-2 text-sm text-gray-900 dark:text-white">Less than high school</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" wire:model="filter_education_level" value="High school graduate/GED" 
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-zinc-600 rounded">
                                        <span class="ml-2 text-sm text-gray-900 dark:text-white">High school graduate/GED</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" wire:model="filter_education_level" value="Some college" 
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-zinc-600 rounded">
                                        <span class="ml-2 text-sm text-gray-900 dark:text-white">Some college</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" wire:model="filter_education_level" value="College graduate" 
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-zinc-600 rounded">
                                        <span class="ml-2 text-sm text-gray-900 dark:text-white">College graduate</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Work Status Filter -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Work Status</label>
                                <div class="space-y-2">
                                    <label class="flex items-center">
                                        <input type="checkbox" wire:model="filter_work_status" value="Employed" 
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-zinc-600 rounded">
                                        <span class="ml-2 text-sm text-gray-900 dark:text-white">Employed</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" wire:model="filter_work_status" value="Unemployed" 
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-zinc-600 rounded">
                                        <span class="ml-2 text-sm text-gray-900 dark:text-white">Unemployed</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" wire:model="filter_work_status" value="Not in labor force" 
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-zinc-600 rounded">
                                        <span class="ml-2 text-sm text-gray-900 dark:text-white">Not in labor force</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Household Type Filter -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Household Type</label>
                                <div class="space-y-2">
                                    <label class="flex items-center">
                                        <input type="checkbox" wire:model="filter_household_type" value="Single adult" 
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-zinc-600 rounded">
                                        <span class="ml-2 text-sm text-gray-900 dark:text-white">Single adult</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" wire:model="filter_household_type" value="Two-parent family" 
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-zinc-600 rounded">
                                        <span class="ml-2 text-sm text-gray-900 dark:text-white">Two-parent family</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" wire:model="filter_household_type" value="Single-parent family" 
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-zinc-600 rounded">
                                        <span class="ml-2 text-sm text-gray-900 dark:text-white">Single-parent family</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="flex gap-3 pt-4">
                    <button type="submit" 
                            :disabled="$wire.isGenerating"
                            class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span wire:loading.remove wire:target="generateReport">Generate Report</span>
                        <span wire:loading wire:target="generateReport">Generating...</span>
                    </button>

                    @if($reportData)
                        <div class="flex flex-wrap gap-3">
                            <button type="button" wire:click="exportToCsv" 
                                    class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 text-sm">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Export CSV
                            </button>
                            
                            <button type="button" wire:click="exportToPdf" 
                                    class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 text-sm">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                </svg>
                                Export PDF
                            </button>
                            
                            <button type="button" wire:click="exportToExcel" 
                                    class="px-4 py-2 bg-emerald-600 text-white rounded-md hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 text-sm">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Export Excel
                            </button>
                            
                            <button type="button" wire:click="clearReport" 
                                    class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 text-sm">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                                Clear Report
                            </button>
                        </div>
                    @endif
                </div>
            </form>
        </div>

        <!-- Report Results -->
        @if($showReport && $reportData)
            <div class="p-6">
                <!-- Report Summary -->
                <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <h4 class="text-lg font-medium text-blue-900 dark:text-blue-100 mb-2">Report Summary</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
                        <div>
                            <span class="font-medium text-blue-700 dark:text-blue-300">Total Participants:</span>
                            <span class="text-blue-900 dark:text-blue-100">{{ number_format($reportData['participant_count']) }}</span>
                        </div>
                        <div>
                            <span class="font-medium text-blue-700 dark:text-blue-300">Period:</span>
                            <span class="text-blue-900 dark:text-blue-100">{{ Carbon\Carbon::parse($reportData['report_metadata']['period_start'])->format('M j, Y') }} - {{ Carbon\Carbon::parse($reportData['report_metadata']['period_end'])->format('M j, Y') }}</span>
                        </div>
                        <div>
                            <span class="font-medium text-blue-700 dark:text-blue-300">Reference Data:</span>
                            <span class="text-blue-900 dark:text-blue-100">{{ $reportData['report_metadata']['reference_data_set']['name'] ?? 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="font-medium text-blue-700 dark:text-blue-300">Generated:</span>
                            <span class="text-blue-900 dark:text-blue-100">{{ Carbon\Carbon::parse($reportData['report_metadata']['generated_at'])->format('M j, Y g:i A') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Report Tabs -->
                <div class="border-b border-gray-200 dark:border-zinc-600 mb-6">
                    <nav class="-mb-px flex space-x-8">
                        <button wire:click="setActiveTab('demographics')" 
                                class="py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'demographics' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            Demographics
                        </button>
                        <button wire:click="setActiveTab('characteristics')" 
                                class="py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'characteristics' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            Characteristics
                        </button>
                        <button wire:click="setActiveTab('employment')" 
                                class="py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'employment' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            Employment & Income
                        </button>
                        <button wire:click="setActiveTab('health')" 
                                class="py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'health' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            Health & Housing
                        </button>
                        <button wire:click="setActiveTab('visualizations')" 
                                class="py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'visualizations' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            Visualizations
                        </button>
                    </nav>
                </div>

                <!-- Tab Content -->
                <div class="space-y-6">
                    @if($activeTab === 'demographics')
                        @foreach($reportData['demographics'] as $category => $data)
                            <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-4">
                                <h5 class="font-medium text-gray-900 dark:text-white mb-3">{{ ucfirst(str_replace('_', ' ', $category)) }}</h5>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 text-sm">
                                    @foreach($data as $value => $count)
                                        <div class="flex justify-between py-1 px-2 bg-white dark:bg-zinc-800 rounded">
                                            <span class="text-gray-700 dark:text-gray-300">{{ $value }}</span>
                                            <button wire:click="drillDown('{{ $category }}', '{{ addslashes($value) }}')" 
                                                    class="font-medium text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 hover:underline">
                                                {{ number_format($count) }}
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    @endif

                    @if($activeTab === 'characteristics')
                        @foreach($reportData['characteristics'] as $category => $data)
                            <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-4">
                                <h5 class="font-medium text-gray-900 dark:text-white mb-3">{{ ucfirst(str_replace('_', ' ', $category)) }}</h5>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 text-sm">
                                    @foreach($data as $value => $count)
                                        <div class="flex justify-between py-1 px-2 bg-white dark:bg-zinc-800 rounded">
                                            <span class="text-gray-700 dark:text-gray-300">{{ $value }}</span>
                                            <button wire:click="drillDown('{{ $category }}', '{{ addslashes($value) }}')" 
                                                    class="font-medium text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 hover:underline">
                                                {{ number_format($count) }}
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    @endif

                    @if($activeTab === 'employment')
                        @foreach($reportData['employment_income'] as $category => $data)
                            <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-4">
                                <h5 class="font-medium text-gray-900 dark:text-white mb-3">{{ ucfirst(str_replace('_', ' ', $category)) }}</h5>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 text-sm">
                                    @foreach($data as $value => $count)
                                        <div class="flex justify-between py-1 px-2 bg-white dark:bg-zinc-800 rounded">
                                            <span class="text-gray-700 dark:text-gray-300">{{ $value }}</span>
                                            <button wire:click="drillDown('{{ $category }}', '{{ addslashes($value) }}')" 
                                                    class="font-medium text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 hover:underline">
                                                {{ number_format($count) }}
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    @endif

                    @if($activeTab === 'health')
                        @foreach($reportData['health_housing'] as $category => $data)
                            <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-4">
                                <h5 class="font-medium text-gray-900 dark:text-white mb-3">{{ ucfirst(str_replace('_', ' ', $category)) }}</h5>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 text-sm">
                                    @foreach($data as $value => $count)
                                        <div class="flex justify-between py-1 px-2 bg-white dark:bg-zinc-800 rounded">
                                            <span class="text-gray-700 dark:text-gray-300">{{ $value }}</span>
                                            <button wire:click="drillDown('{{ $category }}', '{{ addslashes($value) }}')" 
                                                    class="font-medium text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 hover:underline">
                                                {{ number_format($count) }}
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    @endif

                    @if($activeTab === 'visualizations')
                        <!-- Visualizations Content -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6" id="chartContainer" wire:ignore
                             x-data="{ 
                                 mounted: false,
                                 initCharts() {
                                     if (typeof initializeCharts === 'function' && window.reportingCharts?.reportData) {
                                         console.log('Alpine: Initializing charts');
                                         initializeCharts(window.reportingCharts.reportData);
                                     }
                                 }
                             }"
                             x-init="
                                 mounted = true;
                                 // Initialize on next tick
                                 $nextTick(() => initCharts());
                                 // Also listen for tab changes
                                 $wire.on('initializeCharts', () => {
                                     $nextTick(() => initCharts());
                                 });
                             ">
                            <!-- Gender Distribution Chart -->
                            <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-4">
                                <h5 class="font-medium text-gray-900 dark:text-white mb-3">Gender Distribution</h5>
                                <div style="position: relative; height: 300px;">
                                    <canvas id="genderChart"></canvas>
                                </div>
                            </div>

                            <!-- Age Groups Chart -->
                            <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-4">
                                <h5 class="font-medium text-gray-900 dark:text-white mb-3">Age Groups</h5>
                                <div style="position: relative; height: 300px;">
                                    <canvas id="ageChart"></canvas>
                                </div>
                            </div>

                            <!-- Education Level Chart -->
                            <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-4">
                                <h5 class="font-medium text-gray-900 dark:text-white mb-3">Education Level</h5>
                                <div style="position: relative; height: 300px;">
                                    <canvas id="educationChart"></canvas>
                                </div>
                            </div>

                            <!-- Poverty Levels Chart -->
                            <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-4">
                                <h5 class="font-medium text-gray-900 dark:text-white mb-3">Poverty Levels</h5>
                                <div style="position: relative; height: 300px;">
                                    <canvas id="povertyChart"></canvas>
                                </div>
                            </div>

                            <!-- Employment Status Chart -->
                            <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-4">
                                <h5 class="font-medium text-gray-900 dark:text-white mb-3">Work Status</h5>
                                <div style="position: relative; height: 300px;">
                                    <canvas id="workStatusChart"></canvas>
                                </div>
                            </div>

                            <!-- Housing Status Chart -->
                            <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-4">
                                <h5 class="font-medium text-gray-900 dark:text-white mb-3">Housing Status</h5>
                                <div style="position: relative; height: 300px;">
                                    <canvas id="housingChart"></canvas>
                                </div>
                            </div>
                        </div>

                    @endif
                </div>
            </div>
        @endif
    </div>

    <!-- Drill-Down Modal -->
    @if($showDrillDown)
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-6xl w-full mx-4 max-h-screen overflow-hidden">
            <!-- Modal Header -->
            <div class="bg-blue-600 text-white px-6 py-4 flex justify-between items-center">
                <div>
                    <h3 class="text-lg font-bold">{{ $drillDownTitle }}</h3>
                    <p class="text-blue-100 text-sm">{{ count($drillDownData) }} participants found</p>
                </div>
                <button wire:click="closeDrillDown" class="text-white hover:text-gray-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Search and Controls -->
            <div class="p-4 border-b border-gray-200 dark:border-zinc-600">
                <div class="flex flex-col sm:flex-row gap-4 items-center">
                    <div class="flex-1">
                        <input type="text" wire:model.live="drillDownSearch" placeholder="Search participants..."
                               class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        Showing {{ count($drillDownData) }} of {{ count($drillDownData) }} participants
                    </div>
                </div>
            </div>

            <!-- Participant List -->
            <div class="overflow-auto" style="max-height: 70vh;">
                @if(count($drillDownData) > 0)
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-600">
                        <thead class="bg-gray-50 dark:bg-zinc-700 sticky top-0">
                            <tr>
                                <th wire:click="sortDrillDown('last_name')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-zinc-600">
                                    Name
                                    @if($drillDownSortBy === 'last_name')
                                        <span class="ml-1">{{ $drillDownSortDirection === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </th>
                                <th wire:click="sortDrillDown('date_of_birth')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-zinc-600">
                                    Age
                                    @if($drillDownSortBy === 'date_of_birth')
                                        <span class="ml-1">{{ $drillDownSortDirection === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Household Code
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Programs
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-600">
                            @foreach($drillDownData->sortBy($drillDownSortBy, SORT_REGULAR, $drillDownSortDirection === 'desc')->filter(function($participant) {
                                if (empty($this->drillDownSearch)) return true;
                                $search = strtolower($this->drillDownSearch);
                                return str_contains(strtolower($participant->first_name . ' ' . $participant->last_name), $search) ||
                                       str_contains(strtolower($participant->household->household_code ?? ''), $search);
                            }) as $participant)
                                <tr class="hover:bg-gray-50 dark:hover:bg-zinc-700">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $participant->first_name }} {{ $participant->last_name }}
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ $participant->is_head_of_household ? 'Head of Household' : 'Household Member' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        @if($participant->date_of_birth)
                                            {{ \Carbon\Carbon::parse($participant->date_of_birth)->age }} years
                                        @else
                                            <span class="text-gray-400">Unknown</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ $participant->household->household_code ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($participant->programEnrollments as $enrollment)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                    {{ $enrollment->program->name }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="{{ route('participant.show', ['id' => $participant->id]) }}" 
                                           class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                           wire:navigate>
                                            View Profile
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                        No participants found for this criteria.
                    </div>
                @endif
            </div>

            <!-- Modal Footer -->
            <div class="bg-gray-50 dark:bg-zinc-700 px-6 py-4 flex justify-end">
                <button wire:click="closeDrillDown" 
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-zinc-600 border border-gray-300 dark:border-zinc-500 rounded-md hover:bg-gray-50 dark:hover:bg-zinc-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    Close
                </button>
            </div>
        </div>
    </div>
@endif

<!-- Chart Management Script -->
<script>
// Initialize chart management
window.reportingCharts = {
    initialized: false,
    reportData: @js($reportData),
    activeTab: @js($activeTab)
};

// Wait for DOM and dependencies
if (window.DOMReady) {
    window.DOMReady.ready(() => {
        initializeReportingCharts();
    });
} else {
    // Fallback for immediate execution
    document.addEventListener('DOMContentLoaded', () => {
        setTimeout(initializeReportingCharts, 100);
    });
}

function initializeReportingCharts() {
    console.log('Initializing reporting charts');
    
    // Check if we're on visualizations tab with data
    if (window.reportingCharts.activeTab === 'visualizations' && window.reportingCharts.reportData) {
        // Initialize charts immediately
        initializeCharts(window.reportingCharts.reportData);
    }
    
    // Set up Livewire event listeners
    if (window.LivewireEventManager) {
        window.LivewireEventManager.on('initializeCharts', (data) => {
            console.log('Received initializeCharts event');
            const reportData = Array.isArray(data) ? data[0] : data;
            window.reportingCharts.reportData = reportData;
            
            // Use requestAnimationFrame for smooth rendering
            requestAnimationFrame(() => {
                if (document.getElementById('chartContainer')) {
                    initializeCharts(reportData);
                }
            });
        }, 'reporting-management');
    } else {
        // Fallback to direct Livewire events
        if (window.Livewire) {
            Livewire.on('initializeCharts', (data) => {
                const reportData = Array.isArray(data) ? data[0] : data;
                setTimeout(() => initializeCharts(reportData), 100);
            });
        }
    }
}

function initializeCharts(reportData) {
    console.log('initializeCharts called');
    
    // Validate Chart.js is loaded
    if (!window.ChartManager || !window.ChartManager.isChartJsLoaded()) {
        console.error('Chart.js not loaded');
        // Retry after a delay
        setTimeout(() => {
            if (window.Chart) {
                initializeCharts(reportData);
            }
        }, 500);
        return;
    }
    
    // Use provided data or fallback to window data
    if (!reportData) {
        reportData = window.reportingCharts.reportData;
        if (!reportData) {
            console.warn('No report data available');
            return;
        }
    }
    
    // Prevent duplicate initialization
    if (window.reportingCharts.initialized) {
        console.log('Charts already initialized, refreshing...');
    }
    
    console.log('Creating charts...');
    
    // Destroy all existing charts
    if (window.ChartManager) {
        window.ChartManager.destroyAll();
    }
    
    const colors = {
        primary: ['#3B82F6', '#EF4444', '#10B981', '#F59E0B', '#8B5CF6', '#F97316', '#06B6D4', '#84CC16'],
        poverty: ['#DC2626', '#F59E0B', '#10B981', '#3B82F6', '#8B5CF6', '#F97316']
    };

    try {
        // Gender Chart
        if (reportData.demographics?.gender) {
            const chart = createPieChart('genderChart', reportData.demographics.gender, colors.primary);
            if (chart && window.ChartManager) {
                window.ChartManager.register('gender', chart);
            }
        }

        // Age Groups Chart  
        if (reportData.characteristics?.age_groups) {
            const chart = createBarChart('ageChart', reportData.characteristics.age_groups, colors.primary[0]);
            if (chart && window.ChartManager) {
                window.ChartManager.register('age', chart);
            }
        }

        // Education Level Chart
        if (reportData.demographics?.education_level) {
            const chart = createDoughnutChart('educationChart', reportData.demographics.education_level, colors.primary);
            if (chart && window.ChartManager) {
                window.ChartManager.register('education', chart);
            }
        }

        // Poverty Levels Chart
        if (reportData.employment_income?.poverty_levels) {
            const chart = createBarChart('povertyChart', reportData.employment_income.poverty_levels, colors.poverty[0]);
            if (chart && window.ChartManager) {
                window.ChartManager.register('poverty', chart);
            }
        }

        // Work Status Chart
        if (reportData.employment_income?.work_status) {
            const chart = createPieChart('workStatusChart', reportData.employment_income.work_status, colors.primary);
            if (chart && window.ChartManager) {
                window.ChartManager.register('workStatus', chart);
            }
        }

        // Housing Status Chart
        if (reportData.health_housing?.housing_status) {
            const chart = createDoughnutChart('housingChart', reportData.health_housing.housing_status, colors.primary);
            if (chart && window.ChartManager) {
                window.ChartManager.register('housing', chart);
            }
        }
        
        window.reportingCharts.initialized = true;
        console.log('All charts created successfully');
    } catch (error) {
        console.error('Error creating charts:', error);
        window.reportingCharts.initialized = false;
    }
}

function createPieChart(canvasId, data, colors) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) {
        console.warn('Canvas not found:', canvasId);
        return null;
    }
    
    // Get 2D context
    const ctx = canvas.getContext('2d');
    if (!ctx) {
        console.error('Could not get 2D context for:', canvasId);
        return null;
    }

    return new Chart(ctx, {
        type: 'pie',
        data: {
            labels: Object.keys(data),
            datasets: [{
                data: Object.values(data),
                backgroundColor: colors,
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 10,
                        usePointStyle: true,
                        font: { size: 11 }
                    }
                }
            }
        }
    });
}

function createDoughnutChart(canvasId, data, colors) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) {
        console.warn('Canvas not found:', canvasId);
        return null;
    }
    
    // Get 2D context
    const ctx = canvas.getContext('2d');
    if (!ctx) {
        console.error('Could not get 2D context for:', canvasId);
        return null;
    }

    return new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: Object.keys(data),
            datasets: [{
                data: Object.values(data),
                backgroundColor: colors,
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 10,
                        usePointStyle: true,
                        font: { size: 11 }
                    }
                }
            }
        }
    });
}

function createBarChart(canvasId, data, color) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) {
        console.warn('Canvas not found:', canvasId);
        return null;
    }
    
    // Get 2D context
    const ctx = canvas.getContext('2d');
    if (!ctx) {
        console.error('Could not get 2D context for:', canvasId);
        return null;
    }

    return new Chart(ctx, {
        type: 'bar',
        data: {
            labels: Object.keys(data),
            datasets: [{
                label: 'Count',
                data: Object.values(data),
                backgroundColor: color + '80',
                borderColor: color,
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 }
                }
            }
        }
    });
}

// Clean up function
window.cleanupReportingCharts = function() {
    if (window.ChartManager) {
        window.ChartManager.destroyAll();
    }
    if (window.LivewireEventManager) {
        window.LivewireEventManager.off('initializeCharts', 'reporting-management');
    }
    window.reportingCharts.initialized = false;
};

// Manual initialization for testing
window.testChartInit = function() {
    console.log('Manual chart initialization triggered');
    initializeCharts(window.reportingCharts.reportData);
};
</script>
</div>