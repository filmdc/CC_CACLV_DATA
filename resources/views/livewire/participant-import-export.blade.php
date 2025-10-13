<div class="max-w-7xl mx-auto p-6">
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl">
        <div class="p-6">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    Participant Import/Export
                </h1>
            </div>

            <!-- Flash Messages -->
            @if (session()->has('success'))
                <div class="rounded-md bg-green-50 dark:bg-green-900 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800 dark:text-green-200">
                                {{ session('success') }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            @if (session()->has('error'))
                <div class="rounded-md bg-red-50 dark:bg-red-900 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-red-800 dark:text-red-200">
                                {{ session('error') }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Tab Navigation -->
            <div class="border-b border-gray-200 dark:border-zinc-700 mb-6">
                <nav class="-mb-px flex space-x-8">
                    @if($this->canExport)
                        <button wire:click="setActiveTab('export')" 
                                class="py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'export' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                            Export Data
                        </button>
                    @endif
                    @if($this->canImport)
                        <button wire:click="setActiveTab('import')" 
                                class="py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'import' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                            System Import
                        </button>
                        <button wire:click="setActiveTab('custom-import')" 
                                class="py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'custom-import' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                            Custom Import
                        </button>
                        <button wire:click="setActiveTab('import-history')" 
                                class="py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'import-history' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                            Import History
                        </button>
                    @endif
                </nav>
            </div>

            <!-- Export Tab -->
            @if($activeTab === 'export' && $this->canExport)
                <div class="space-y-6">
                    <!-- Statistics -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-gray-50 dark:bg-zinc-700 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($this->exportStatistics['total_participants']) }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-300">Total Participants</div>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-700 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($this->exportStatistics['total_households']) }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-300">Total Households</div>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-700 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($this->exportStatistics['participants_this_month']) }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-300">Participants This Month</div>
                        </div>
                    </div>

                    <!-- Export Form -->
                    <form wire:submit.prevent="export">
                        <div class="space-y-4">
                            <!-- Export Type -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Export Type</label>
                                <select wire:model.live="exportType" class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="all">All Participants & Households</option>
                                    <option value="household">Specific Household</option>
                                    <option value="date_range">Date Range</option>
                                    <option value="household_codes">Household Codes</option>
                                    <option value="program">By Program</option>
                                    <option value="program_date_range">By Program & Date Range</option>
                                </select>
                                @error('exportType') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- Conditional Fields -->
                            @if($exportType === 'household')
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Household ID</label>
                                    <input type="number" wire:model="householdId" 
                                           class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                                           placeholder="Enter household ID">
                                    @error('householdId') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                            @endif

                            @if($exportType === 'date_range')
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Start Date</label>
                                        <input type="date" wire:model="startDate" 
                                               class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                                        @error('startDate') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">End Date</label>
                                        <input type="date" wire:model="endDate" 
                                               class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                                        @error('endDate') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date Field</label>
                                        <select wire:model="dateField" class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="created_at">Created Date</option>
                                            <option value="updated_at">Updated Date</option>
                                        </select>
                                    </div>
                                </div>
                            @endif

                            @if($exportType === 'household_codes')
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Household Codes (comma-separated)</label>
                                    <textarea wire:model="householdCodes" rows="3"
                                              class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                                              placeholder="ABC123H, DEF456G, XYZ789J"></textarea>
                                    @error('householdCodes') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                            @endif

                            @if($exportType === 'program' || $exportType === 'program_date_range')
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Program</label>
                                    <select wire:model="programId" class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">Select a program...</option>
                                        @foreach($this->availablePrograms as $program)
                                            <option value="{{ $program->id }}">{{ $program->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('programId') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                            @endif

                            @if($exportType === 'program_date_range')
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Start Date</label>
                                        <input type="date" wire:model="startDate"
                                               class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                                        @error('startDate') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">End Date</label>
                                        <input type="date" wire:model="endDate"
                                               class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                                        @error('endDate') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date Field</label>
                                        <select wire:model="dateField" class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="enrollment_date">Enrollment Date</option>
                                            <option value="exit_date">Exit Date</option>
                                        </select>
                                    </div>
                                </div>
                            @endif

                            <!-- Export Buttons -->
                            <div class="flex space-x-4">
                                <button type="submit" 
                                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                                        wire:loading.attr="disabled" 
                                        wire:target="export">
                                    <svg wire:loading wire:target="export" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span wire:loading.remove wire:target="export">Export Data</span>
                                    <span wire:loading wire:target="export">Exporting...</span>
                                </button>

                                <button type="button" wire:click="resetExportForm"
                                        class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-zinc-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-zinc-700 hover:bg-gray-50 dark:hover:bg-zinc-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Reset
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Export Result -->
                    @if($exportResult)
                        <div class="bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-700 rounded-md p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-medium text-green-800 dark:text-green-200">Export Ready</h3>
                                    <p class="text-sm text-green-600 dark:text-green-300">
                                        File: {{ $exportResult['filename'] }} 
                                        ({{ number_format($exportResult['record_count']) }} records, {{ number_format($exportResult['file_size'] / 1024, 1) }} KB)
                                    </p>
                                </div>
                                <button wire:click="downloadExport"
                                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                    </svg>
                                    Download
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Import Tab -->
            @if($activeTab === 'import' && $this->canImport)
                <div class="space-y-6">
                    <!-- Template Download -->
                    <div class="bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-md p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-medium text-blue-800 dark:text-blue-200">Import Template</h3>
                                <p class="text-sm text-blue-600 dark:text-blue-300">Download a CSV template with example data to ensure proper formatting.</p>
                            </div>
                            <button wire:click="downloadTemplate"
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                </svg>
                                Download Template
                            </button>
                        </div>
                    </div>

                    <!-- File Upload -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Select CSV File</label>
                        <input type="file" wire:model="importFile" accept=".csv,.txt" 
                               class="block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                        @error('importFile') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- Reference Data Set Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Reference Data Set for Validation</label>
                        <select wire:model="selectedReferenceDataSet" 
                                class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Use Import Default</option>
                            @foreach($this->availableReferenceDataSets as $dataSet)
                                <option value="{{ $dataSet->id }}">
                                    {{ $dataSet->name }} ({{ $dataSet->version }})
                                    @if($dataSet->is_active) - Active @endif
                                    @if($dataSet->is_import_default) - Import Default @endif
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Select a reference data set for validating import values. Use this for historical data imports.
                        </p>
                    </div>

                    <!-- Import Options -->
                    <div class="space-y-4">
                        <div class="flex items-center">
                            <input type="checkbox" wire:model="updateExisting" id="updateExisting"
                                   class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                            <label for="updateExisting" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                Update existing records if found
                            </label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" wire:model="skipErrors" id="skipErrors"
                                   class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                            <label for="skipErrors" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                Skip records with errors and continue
                            </label>
                        </div>
                    </div>

                    <!-- Validate Button -->
                    <div>
                        <button wire:click="validateImportFile" 
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                                wire:loading.attr="disabled" 
                                wire:target="validateImportFile"
                                @if(!$importFile) disabled @endif>
                            <svg wire:loading wire:target="validateImportFile" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span wire:loading.remove wire:target="validateImportFile">Validate File</span>
                            <span wire:loading wire:target="validateImportFile">Validating...</span>
                        </button>
                    </div>

                    <!-- Validation Results -->
                    @if($validationResult)
                        <div class="space-y-4">
                            @if($validationResult['valid'])
                                <div class="bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-700 rounded-md p-4">
                                    <h3 class="text-lg font-medium text-green-800 dark:text-green-200">Validation Successful</h3>
                                    <p class="text-sm text-green-600 dark:text-green-300">
                                        Found {{ $validationResult['record_count'] }} records across {{ $validationResult['household_count'] }} households.
                                    </p>
                                </div>

                                <!-- Preview and Import Buttons -->
                                <div class="flex space-x-4">
                                    <button wire:click="previewImport"
                                            class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-zinc-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-zinc-700 hover:bg-gray-50 dark:hover:bg-zinc-600">
                                        Preview Data
                                    </button>
                                    <button wire:click="previewTransformations"
                                            class="inline-flex items-center px-4 py-2 border border-purple-300 dark:border-purple-600 text-sm font-medium rounded-md text-purple-700 dark:text-purple-300 bg-purple-50 dark:bg-purple-900 hover:bg-purple-100 dark:hover:bg-purple-800">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path>
                                        </svg>
                                        Preview Transformations
                                    </button>
                                    <button wire:click="processImport"
                                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 disabled:opacity-50"
                                            wire:loading.attr="disabled" 
                                            wire:target="processImport">
                                        <svg wire:loading wire:target="processImport" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span wire:loading.remove wire:target="processImport">Import Data</span>
                                        <span wire:loading wire:target="processImport">Importing...</span>
                                    </button>
                                </div>
                            @else
                                <div class="bg-red-50 dark:bg-red-900 border border-red-200 dark:border-red-700 rounded-md p-4">
                                    <h3 class="text-lg font-medium text-red-800 dark:text-red-200">Validation Failed</h3>
                                    <div class="mt-2">
                                        @if(!empty($validationResult['errors']))
                                            <p class="text-sm text-red-600 dark:text-red-300 font-medium">Errors:</p>
                                            <ul class="mt-1 text-sm text-red-600 dark:text-red-300 list-disc list-inside">
                                                @foreach($validationResult['errors'] as $error)
                                                    <li>{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        @endif
                                        @if(!empty($validationResult['warnings']))
                                            <p class="text-sm text-yellow-600 dark:text-yellow-300 font-medium mt-2">Warnings:</p>
                                            <ul class="mt-1 text-sm text-yellow-600 dark:text-yellow-300 list-disc list-inside">
                                                @foreach($validationResult['warnings'] as $warning)
                                                    <li>{{ $warning }}</li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- Preview Data -->
                    @if($showPreview && $previewData)
                        <div class="bg-gray-50 dark:bg-zinc-700 rounded-md p-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Data Preview (First 10 Records)</h3>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-600">
                                    <thead class="bg-gray-100 dark:bg-zinc-600">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Row</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Household Code</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Participant Type</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Name</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-zinc-600">
                                        @foreach($previewData['preview_data'] as $row)
                                            <tr class="bg-white dark:bg-zinc-700">
                                                <td class="px-3 py-2 text-sm text-gray-900 dark:text-white">{{ $row['row_number'] }}</td>
                                                <td class="px-3 py-2 text-sm text-gray-900 dark:text-white">{{ $row['data']['household_code'] ?? '' }}</td>
                                                <td class="px-3 py-2 text-sm text-gray-900 dark:text-white">{{ $row['data']['participant_type'] ?? '' }}</td>
                                                <td class="px-3 py-2 text-sm text-gray-900 dark:text-white">{{ ($row['data']['first_name'] ?? '') . ' ' . ($row['data']['last_name'] ?? '') }}</td>
                                                <td class="px-3 py-2 text-sm">
                                                    @if($row['validation']['valid'])
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Valid</span>
                                                    @else
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Invalid</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    <!-- Import Results -->
                    @if($importResult)
                        <div class="bg-{{ $importResult['success'] ? 'green' : 'red' }}-50 dark:bg-{{ $importResult['success'] ? 'green' : 'red' }}-900 border border-{{ $importResult['success'] ? 'green' : 'red' }}-200 dark:border-{{ $importResult['success'] ? 'green' : 'red' }}-700 rounded-md p-4">
                            <h3 class="text-lg font-medium text-{{ $importResult['success'] ? 'green' : 'red' }}-800 dark:text-{{ $importResult['success'] ? 'green' : 'red' }}-200">
                                Import {{ $importResult['success'] ? 'Completed' : 'Failed' }}
                            </h3>
                            @if($importResult['success'])
                                <div class="mt-2 text-sm text-green-600 dark:text-green-300">
                                    <p>Processed: {{ $importResult['processed_households'] }} households, {{ $importResult['processed_participants'] }} participants</p>
                                    @if($importResult['updated_households'] > 0 || $importResult['updated_participants'] > 0)
                                        <p>Updated: {{ $importResult['updated_households'] }} households, {{ $importResult['updated_participants'] }} participants</p>
                                    @endif
                                    @if($importResult['skipped_records'] > 0)
                                        <p>Skipped: {{ $importResult['skipped_records'] }} records with errors</p>
                                    @endif
                                </div>
                            @endif
                            @if(!empty($importResult['errors']))
                                <div class="mt-2">
                                    <p class="text-sm text-red-600 dark:text-red-300 font-medium">Errors:</p>
                                    <ul class="mt-1 text-sm text-red-600 dark:text-red-300 list-disc list-inside max-h-40 overflow-y-auto">
                                        @foreach(array_slice($importResult['errors'], 0, 10) as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                        @if(count($importResult['errors']) > 10)
                                            <li>... and {{ count($importResult['errors']) - 10 }} more errors</li>
                                        @endif
                                    </ul>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            @endif

            <!-- Custom Import Tab -->
            @if($activeTab === 'custom-import' && $this->canImport)
                <div class="space-y-6">
                    <!-- Template Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Import Template</label>
                        <select wire:model.live="selectedCustomTemplate" 
                                class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Select an import template...</option>
                            @foreach($this->customImportTemplates as $template)
                                <option value="{{ $template->id }}">{{ $template->name }}</option>
                            @endforeach
                        </select>
                        @error('selectedCustomTemplate') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        
                        @if($selectedCustomTemplateData)
                            <div class="mt-2 p-3 bg-gray-50 dark:bg-zinc-700 rounded-md">
                                <p class="text-sm text-gray-600 dark:text-gray-300">
                                    <strong>{{ $selectedCustomTemplateData['name'] }}</strong>
                                </p>
                                @if($selectedCustomTemplateData['description'])
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $selectedCustomTemplateData['description'] }}</p>
                                @endif
                            </div>
                        @endif
                    </div>

                    @if($selectedCustomTemplate)
                        <!-- Download Template Button -->
                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-md p-4">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="ml-3 flex-1">
                                    <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">Need a template?</h3>
                                    <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                                        <p>Download a template file with the correct column headers, field descriptions, and example data to help you format your import file properly.</p>
                                    </div>
                                    <div class="mt-3">
                                        <button wire:click="downloadCustomTemplate"
                                                type="button"
                                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900/40 dark:text-blue-200 dark:hover:bg-blue-900/60 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                                            <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                            </svg>
                                            Download Template
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- File Upload -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Select File</label>
                            <input type="file" wire:model="customImportFile" accept=".csv,.txt" 
                                   class="block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                            @error('customImportFile') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Import Options -->
                        <div class="space-y-4">
                            <div class="flex items-center">
                                <input type="checkbox" wire:model="customUpdateExisting" id="customUpdateExisting"
                                       class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                <label for="customUpdateExisting" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                    Update existing records if found
                                </label>
                            </div>
                            <div class="flex items-center">
                                <input type="checkbox" wire:model="customSkipErrors" id="customSkipErrors"
                                       class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                <label for="customSkipErrors" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                    Skip records with errors and continue
                                </label>
                            </div>
                        </div>

                        <!-- Validate Button -->
                        <div>
                            <button wire:click="validateCustomImportFile" 
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                                    wire:loading.attr="disabled" 
                                    wire:target="validateCustomImportFile"
                                    @if(!$customImportFile) disabled @endif>
                                <svg wire:loading wire:target="validateCustomImportFile" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span wire:loading.remove wire:target="validateCustomImportFile">Validate File</span>
                                <span wire:loading wire:target="validateCustomImportFile">Validating...</span>
                            </button>
                        </div>

                        <!-- Custom Validation Results -->
                        @if($customValidationResult)
                            <div class="space-y-4">
                                @if($customValidationResult['valid'])
                                    <div class="bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-700 rounded-md p-4">
                                        <h3 class="text-lg font-medium text-green-800 dark:text-green-200">Validation Successful</h3>
                                        <p class="text-sm text-green-600 dark:text-green-300">
                                            Found {{ $customValidationResult['record_count'] }} records 
                                            @if(isset($customValidationResult['household_count']))
                                                across {{ $customValidationResult['household_count'] }} households
                                            @endif
                                        </p>
                                    </div>

                                    <!-- Import Button -->
                                    <div>
                                        <button wire:click="processCustomImport"
                                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 disabled:opacity-50"
                                                wire:loading.attr="disabled" 
                                                wire:target="processCustomImport">
                                            <svg wire:loading wire:target="processCustomImport" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            <span wire:loading.remove wire:target="processCustomImport">Import Data</span>
                                            <span wire:loading wire:target="processCustomImport">Importing...</span>
                                        </button>
                                    </div>
                                @else
                                    <div class="bg-red-50 dark:bg-red-900 border border-red-200 dark:border-red-700 rounded-md p-4">
                                        <h3 class="text-lg font-medium text-red-800 dark:text-red-200">Validation Failed</h3>
                                        <div class="mt-2">
                                            @if(!empty($customValidationResult['errors']))
                                                <p class="text-sm text-red-600 dark:text-red-300 font-medium">Errors:</p>
                                                <ul class="mt-1 text-sm text-red-600 dark:text-red-300 list-disc list-inside">
                                                    @foreach($customValidationResult['errors'] as $error)
                                                        <li>{{ $error }}</li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                            @if(!empty($customValidationResult['warnings']))
                                                <p class="text-sm text-yellow-600 dark:text-yellow-300 font-medium mt-2">Warnings:</p>
                                                <ul class="mt-1 text-sm text-yellow-600 dark:text-yellow-300 list-disc list-inside">
                                                    @foreach($customValidationResult['warnings'] as $warning)
                                                        <li>{{ $warning }}</li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif

                        <!-- Custom Import Results -->
                        @if($customImportResult)
                            <div class="bg-{{ $customImportResult['success'] ? 'green' : 'red' }}-50 dark:bg-{{ $customImportResult['success'] ? 'green' : 'red' }}-900 border border-{{ $customImportResult['success'] ? 'green' : 'red' }}-200 dark:border-{{ $customImportResult['success'] ? 'green' : 'red' }}-700 rounded-md p-4">
                                <h3 class="text-lg font-medium text-{{ $customImportResult['success'] ? 'green' : 'red' }}-800 dark:text-{{ $customImportResult['success'] ? 'green' : 'red' }}-200">
                                    Import {{ $customImportResult['success'] ? 'Completed' : 'Failed' }}
                                </h3>
                                @if($customImportResult['success'])
                                    <div class="mt-2 text-sm text-green-600 dark:text-green-300">
                                        <p>Processed: {{ $customImportResult['processed_households'] ?? 0 }} households, {{ $customImportResult['processed_participants'] }} participants</p>
                                        @if(isset($customImportResult['processed_enrollments']) && $customImportResult['processed_enrollments'] > 0)
                                            <p>Enrollments: {{ $customImportResult['processed_enrollments'] }}</p>
                                        @endif
                                        @if(($customImportResult['updated_households'] ?? 0) > 0 || $customImportResult['updated_participants'] > 0)
                                            <p>Updated: {{ $customImportResult['updated_households'] ?? 0 }} households, {{ $customImportResult['updated_participants'] }} participants</p>
                                        @endif
                                        @if($customImportResult['skipped_records'] > 0)
                                            <p>Skipped: {{ $customImportResult['skipped_records'] }} records with errors</p>
                                        @endif
                                    </div>
                                @endif
                                @if(!empty($customImportResult['errors']))
                                    <div class="mt-2">
                                        <p class="text-sm text-red-600 dark:text-red-300 font-medium">Errors:</p>
                                        <ul class="mt-1 text-sm text-red-600 dark:text-red-300 list-disc list-inside max-h-40 overflow-y-auto">
                                            @foreach(array_slice($customImportResult['errors'], 0, 10) as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                            @if(count($customImportResult['errors']) > 10)
                                                <li>... and {{ count($customImportResult['errors']) - 10 }} more errors</li>
                                            @endif
                                        </ul>
                                    </div>
                                @endif
                                @if(!empty($customImportResult['warnings']))
                                    <div class="mt-2">
                                        <p class="text-sm text-yellow-600 dark:text-yellow-300 font-medium">Warnings:</p>
                                        <ul class="mt-1 text-sm text-yellow-600 dark:text-yellow-300 list-disc list-inside max-h-40 overflow-y-auto">
                                            @foreach(array_slice($customImportResult['warnings'], 0, 10) as $warning)
                                                <li>{{ $warning }}</li>
                                            @endforeach
                                            @if(count($customImportResult['warnings']) > 10)
                                                <li>... and {{ count($customImportResult['warnings']) - 10 }} more warnings</li>
                                            @endif
                                        </ul>
                                    </div>
                                @endif
                            </div>
                        @endif
                    @else
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Select Import Template</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Choose a pre-configured template or create a custom mapping to import third-party data.</p>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Import History Tab -->
            @if($activeTab === 'import-history' && $this->canImport)
                <div class="space-y-6">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-white">Import History</h2>
                        <button wire:click="refreshImportHistory" 
                                class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-zinc-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-zinc-700 hover:bg-gray-50 dark:hover:bg-zinc-600">
                            <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Refresh
                        </button>
                    </div>

                    @if($this->importHistory && $this->importHistory->count() > 0)
                        <div class="bg-white dark:bg-zinc-800 shadow overflow-hidden sm:rounded-md">
                            <ul class="divide-y divide-gray-200 dark:divide-zinc-700">
                                @foreach($this->importHistory as $log)
                                    <li class="px-4 py-4 sm:px-6">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0">
                                                    @if($log->status === 'completed')
                                                        <div class="h-8 w-8 rounded-full bg-green-100 dark:bg-green-900 flex items-center justify-center">
                                                            <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                            </svg>
                                                        </div>
                                                    @elseif($log->status === 'processing')
                                                        <div class="h-8 w-8 rounded-full bg-yellow-100 dark:bg-yellow-900 flex items-center justify-center">
                                                            <svg class="h-5 w-5 text-yellow-600 dark:text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                                            </svg>
                                                        </div>
                                                    @else
                                                        <div class="h-8 w-8 rounded-full bg-red-100 dark:bg-red-900 flex items-center justify-center">
                                                            <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                                            </svg>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="ml-4">
                                                    <div class="flex items-center">
                                                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                            {{ ucfirst(str_replace('_', ' ', $log->import_type)) }} Import
                                                        </p>
                                                        <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                            @if($log->status === 'completed') bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200
                                                            @elseif($log->status === 'processing') bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200
                                                            @else bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 @endif">
                                                            {{ ucfirst($log->status) }}
                                                        </span>
                                                    </div>
                                                    <div class="mt-1 flex items-center text-sm text-gray-500 dark:text-gray-400">
                                                        <span>{{ $log->file_name }}</span>
                                                        <span class="mx-2">•</span>
                                                        <span>{{ $log->created_at->format('M j, Y g:i A') }}</span>
                                                        <span class="mx-2">•</span>
                                                        <span>{{ $log->user->name ?? 'Unknown User' }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex items-center space-x-4">
                                                <div class="text-right">
                                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                        {{ $log->records_created }}/{{ $log->records_processed }} records
                                                    </p>
                                                    @if($log->records_skipped > 0)
                                                        <p class="text-sm text-red-600 dark:text-red-400">
                                                            {{ $log->records_skipped }} skipped
                                                        </p>
                                                    @endif
                                                </div>
                                                @if($log->error_summary)
                                                    <button wire:click="toggleErrorDetails({{ $log->id }})" 
                                                            class="inline-flex items-center px-3 py-1.5 border border-red-300 dark:border-red-600 text-xs font-medium rounded text-red-700 dark:text-red-300 bg-white dark:bg-zinc-700 hover:bg-red-50 dark:hover:bg-red-900">
                                                        @if(in_array($log->id, $this->expandedErrors ?? []))
                                                            Hide Details
                                                        @else
                                                            View Details
                                                        @endif
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                        
                                        @if($log->error_summary && in_array($log->id, $this->expandedErrors ?? []))
                                            <div class="mt-4 border-t border-gray-200 dark:border-zinc-700 pt-4">
                                                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Error Details:</h4>
                                                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-md p-3 max-h-60 overflow-y-auto">
                                                    <p class="text-sm text-red-800 dark:text-red-200 whitespace-pre-wrap">{{ $log->error_summary }}</p>
                                                </div>
                                            </div>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        
                        <!-- Pagination if needed -->
                        @if($this->importHistory->hasPages())
                            <div class="mt-4">
                                {{ $this->importHistory->links() }}
                            </div>
                        @endif
                    @else
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No Import History</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">No import operations have been performed yet.</p>
                        </div>
                    @endif
                </div>
            @endif

            <!-- No Permission Message -->
            @if(!$this->canExport && !$this->canImport)
                <div class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Access Denied</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">You do not have permission to access import/export functionality.</p>
                </div>
            @endif
        </div>
    </div>
    
    <!-- Transformation Preview Modal -->
    @if($showTransformationPreview ?? false)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[80vh] overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-zinc-700">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Data Transformation Preview</h3>
                        <button wire:click="$set('showTransformationPreview', false)" 
                                class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div class="px-6 py-4 overflow-y-auto max-h-[calc(80vh-8rem)]">
                    @if($transformationPreview ?? false)
                        <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                            <p>Showing how data will be transformed during import. These transformations help ensure data consistency and CSBG compliance.</p>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                                <thead class="bg-gray-50 dark:bg-zinc-700">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Field</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Original Value</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Transformed Value</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-700">
                                    @foreach($transformationPreview['transformations'] ?? [] as $field => $transformation)
                                        <tr>
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ ucwords(str_replace('_', ' ', $field)) }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $transformation['original'] ?? 'N/A' }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $transformation['transformed'] ?? 'N/A' }}</td>
                                            <td class="px-4 py-3 text-sm">
                                                @if($transformation['changed'] ?? false)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">
                                                        Transformed
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300">
                                                        No Change
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        @if($transformationPreview['summary'] ?? false)
                            <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-md">
                                <h4 class="text-sm font-medium text-blue-800 dark:text-blue-200 mb-2">Transformation Summary</h4>
                                <ul class="text-sm text-blue-700 dark:text-blue-300 space-y-1">
                                    <li>Total fields: {{ $transformationPreview['summary']['total_fields'] ?? 0 }}</li>
                                    <li>Fields transformed: {{ $transformationPreview['summary']['transformed_count'] ?? 0 }}</li>
                                    <li>Fields unchanged: {{ $transformationPreview['summary']['unchanged_count'] ?? 0 }}</li>
                                </ul>
                            </div>
                        @endif
                    @else
                        <div class="text-center py-8">
                            <p class="text-gray-500 dark:text-gray-400">Loading transformation preview...</p>
                        </div>
                    @endif
                </div>
                
                <div class="px-6 py-4 border-t border-gray-200 dark:border-zinc-700 flex justify-end">
                    <button wire:click="$set('showTransformationPreview', false)" 
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-zinc-700 hover:bg-gray-200 dark:hover:bg-zinc-600 rounded-md">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
document.addEventListener('livewire:init', () => {
    Livewire.on('triggerDownload', (event) => {
        // Extract data from event (Livewire 3 format)
        const data = event[0] || event;
        
        // Create a temporary anchor element and trigger download
        const link = document.createElement('a');
        link.href = data.url;
        link.download = data.filename || '';
        link.style.display = 'none';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
});
</script>