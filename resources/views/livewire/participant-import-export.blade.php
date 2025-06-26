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
                            Import Data
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
                                :disabled="!$importFile">
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