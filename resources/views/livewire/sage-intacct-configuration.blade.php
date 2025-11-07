<div class="w-full px-0 py-6">
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl">
        <!-- Header -->
        <div class="bg-indigo-600 text-white p-6 rounded-t-lg">
            <h1 class="text-2xl font-bold">Sage Intacct Configuration</h1>
            <p class="text-indigo-100 mt-2">Configure your Sage Intacct API connection, field mappings, and sync settings</p>
        </div>

        <!-- Success/Error Messages -->
        @if (session()->has('success'))
            <div class="p-4 bg-green-50 dark:bg-green-900/20 border-l-4 border-green-400">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-green-700 dark:text-green-300">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="p-4 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-400">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700 dark:text-red-300">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Tabs -->
        <div class="border-b border-gray-200 dark:border-zinc-700">
            <nav class="flex -mb-px">
                <button wire:click="setActiveTab('credentials')"
                        class="px-6 py-4 text-sm font-medium border-b-2 {{ $activeTab === 'credentials' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                    API Credentials
                </button>
                <button wire:click="setActiveTab('field-mappings')"
                        class="px-6 py-4 text-sm font-medium border-b-2 {{ $activeTab === 'field-mappings' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                    Field Mappings
                </button>
                <button wire:click="setActiveTab('sync-history')"
                        class="px-6 py-4 text-sm font-medium border-b-2 {{ $activeTab === 'sync-history' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                    Sync History
                </button>
                @if($code_mapping_enabled ?? false)
                <button wire:click="setActiveTab('code_mappings')"
                        class="px-6 py-4 text-sm font-medium border-b-2 {{ $activeTab === 'code_mappings' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                    Code Mappings
                </button>
                @endif
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-6">
            <!-- Credentials Tab -->
            @if($activeTab === 'credentials')
                <form wire:submit="saveCredentials" class="space-y-6">
                    <!-- Sandbox Warning -->
                    @if(strtolower($company_id ?? '') !== 'caclv-sandbox')
                        <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-yellow-700 dark:text-yellow-300 font-medium">Warning: Not using sandbox environment</p>
                                    <p class="text-sm text-yellow-600 dark:text-yellow-400 mt-1">For testing, use Company ID: <code class="bg-yellow-100 dark:bg-yellow-800 px-2 py-1 rounded">CACLV-sandbox</code></p>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-400">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-blue-700 dark:text-blue-300 font-medium">Sandbox Environment Active</p>
                                    <p class="text-sm text-blue-600 dark:text-blue-400 mt-1">You're safely testing with the sandbox company.</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- REST API Credentials Section -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">REST API Credentials</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Configure your Sage Intacct REST API OAuth 2.0 credentials.</p>
                        <div class="bg-gray-50 dark:bg-zinc-700 p-4 rounded-lg space-y-4">
                            <!-- Company ID -->
                            <div>
                                <label for="company_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Company ID <span class="text-red-500">*</span>
                                </label>
                                <input type="text"
                                       wire:model.live="company_id"
                                       id="company_id"
                                       placeholder="CACLV-sandbox"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Your Sage Intacct company identifier (use CACLV-sandbox for testing)</p>
                                @error('company_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Client ID -->
                                <div>
                                    <label for="client_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Client ID <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text"
                                           wire:model="client_id"
                                           id="client_id"
                                           placeholder="Enter your REST API Client ID"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">OAuth 2.0 Client ID from Sage Intacct</p>
                                    @error('client_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <!-- Client Secret -->
                                <div>
                                    <label for="client_secret" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Client Secret <span class="text-red-500">*</span>
                                    </label>
                                    <input type="password"
                                           wire:model="client_secret"
                                           id="client_secret"
                                           placeholder="Enter your REST API Client Secret"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">OAuth 2.0 Client Secret (encrypted)</p>
                                    @error('client_secret') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <!-- Location ID -->
                                <div>
                                    <label for="location_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Location ID (Optional)
                                    </label>
                                    <input type="text"
                                           wire:model="location_id"
                                           id="location_id"
                                           placeholder="Enter Location ID"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    @error('location_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <!-- Department ID -->
                                <div>
                                    <label for="department_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Department ID (Optional)
                                    </label>
                                    <input type="text"
                                           wire:model="department_id"
                                           id="department_id"
                                           placeholder="Enter Department ID"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    @error('department_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <!-- Class ID -->
                                <div>
                                    <label for="class_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Class ID (Optional)
                                    </label>
                                    <input type="text"
                                           wire:model="class_id"
                                           id="class_id"
                                           placeholder="Enter Class ID"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    @error('class_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <!-- Test Connection Button -->
                            <div class="pt-2">
                                <button type="button"
                                        wire:click="testConnection"
                                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                        wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="testConnection">Test Connection</span>
                                    <span wire:loading wire:target="testConnection" class="flex items-center">
                                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Testing...
                                    </span>
                                </button>

                                <!-- Test Result -->
                                @if($showTestResult && $testConnectionResult)
                                    <div class="mt-3 p-3 rounded-md {{ $testConnectionResult['success'] ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800' }}">
                                        <p class="text-sm {{ $testConnectionResult['success'] ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300' }}">
                                            {{ $testConnectionResult['message'] }}
                                        </p>
                                        @if(isset($testConnectionResult['is_sandbox']) && $testConnectionResult['is_sandbox'])
                                            <div class="mt-2 text-xs text-blue-600 dark:text-blue-400">
                                                ✓ Sandbox environment
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Code Mapping Toggle -->
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-md p-4">
                        <label class="flex items-start space-x-3 cursor-pointer">
                            <input type="checkbox"
                                   wire:model.live="code_mapping_enabled"
                                   class="mt-1 rounded border-gray-300 dark:border-zinc-600 text-indigo-600 focus:ring-indigo-500">
                            <div>
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    Enable Code Display Mapping
                                </div>
                                <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                    Show Sage Intacct code equivalents in the gifts feed and provide export options.
                                    <strong>Note:</strong> This only affects display and exports. API sync always uses original DonorPerfect codes.
                                </div>
                            </div>
                        </label>
                    </div>

                    <!-- Active Toggle -->
                    <div>
                        <div class="bg-gray-50 dark:bg-zinc-700 p-4 rounded-lg">
                            <div class="flex items-center">
                                <input type="checkbox"
                                       wire:model="is_active"
                                       id="is_active"
                                       class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-zinc-600 rounded">
                                <label for="is_active" class="ml-2 block text-sm font-medium text-gray-900 dark:text-white">
                                    Enable Sage Intacct Integration
                                </label>
                            </div>
                            <p class="ml-6 text-sm text-gray-500 dark:text-gray-400">When enabled, the system will sync data to Sage Intacct.</p>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-zinc-600">
                        <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="saveCredentials">Save Configuration</span>
                            <span wire:loading wire:target="saveCredentials" class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Saving...
                            </span>
                        </button>
                    </div>
                </form>
            @endif

            <!-- Field Mappings Tab -->
            @if($activeTab === 'field-mappings')
                <div class="space-y-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Field Mappings</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Map DonorPerfect fields to Sage Intacct fields</p>
                        </div>
                        <button wire:click="showAddMapping"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            Add Mapping
                        </button>
                    </div>

                    @if(count($availableDpFields) === 0)
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                            <p class="text-sm text-yellow-700 dark:text-yellow-300">
                                No DonorPerfect fields available. Please configure DonorPerfect settings first.
                            </p>
                        </div>
                    @endif

                    <!-- Add/Edit Mapping Form -->
                    @if($showAddMappingForm)
                        <div class="bg-gray-50 dark:bg-zinc-700 p-4 rounded-lg border-2 border-indigo-200 dark:border-indigo-800">
                            <h4 class="font-medium text-gray-900 dark:text-white mb-4">
                                {{ $editingMappingId ? 'Edit Field Mapping' : 'Add Field Mapping' }}
                            </h4>
                            <form wire:submit="saveFieldMapping" class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- DonorPerfect Field -->
                                    <div>
                                        <label for="mapping_dp_field" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            DonorPerfect Field <span class="text-red-500">*</span>
                                        </label>
                                        <select wire:model="mapping_dp_field"
                                                id="mapping_dp_field"
                                                class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                            <option value="">Select a field</option>
                                            @foreach($availableDpFields as $field)
                                                <option value="{{ $field }}">{{ $field }}</option>
                                            @endforeach
                                        </select>
                                        @error('mapping_dp_field') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    <!-- Intacct Field -->
                                    <div>
                                        <label for="mapping_intacct_field" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Intacct Field <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text"
                                               wire:model="mapping_intacct_field"
                                               id="mapping_intacct_field"
                                               placeholder="e.g., AMOUNT, CUSTOMERID"
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                        @error('mapping_intacct_field') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <!-- Default Value -->
                                <div>
                                    <label for="mapping_transformation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Default Value (Optional)
                                    </label>
                                    <input type="text"
                                           wire:model="mapping_transformation"
                                           id="mapping_transformation"
                                           placeholder="e.g., Default Department"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        Value to use if the DonorPerfect field is empty
                                    </p>
                                </div>

                                <!-- Is Required -->
                                <div class="flex items-center">
                                    <input type="checkbox"
                                           wire:model="mapping_is_required"
                                           id="mapping_is_required"
                                           class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-zinc-600 rounded">
                                    <label for="mapping_is_required" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                        Mark as required field
                                    </label>
                                </div>

                                <!-- Form Actions -->
                                <div class="flex justify-end space-x-3">
                                    <button type="button"
                                            wire:click="cancelFieldMapping"
                                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-zinc-600 border border-gray-300 dark:border-zinc-500 rounded-md hover:bg-gray-50 dark:hover:bg-zinc-500">
                                        Cancel
                                    </button>
                                    <button type="submit"
                                            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700">
                                        {{ $editingMappingId ? 'Update Mapping' : 'Add Mapping' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endif

                    <!-- Field Mappings Table -->
                    @if($fieldMappings->count() > 0)
                        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-700 overflow-hidden">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                                <thead class="bg-gray-50 dark:bg-zinc-700">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Order</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">DonorPerfect Field</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Intacct Field</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Required</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Default Value</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-700">
                                    @foreach($fieldMappings as $mapping)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-zinc-700">
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <div class="flex items-center space-x-1">
                                                    <button wire:click="moveFieldMapping({{ $mapping->id }}, 'up')"
                                                            class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 disabled:opacity-50"
                                                            {{ $loop->first ? 'disabled' : '' }}>
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                                        </svg>
                                                    </button>
                                                    <button wire:click="moveFieldMapping({{ $mapping->id }}, 'down')"
                                                            class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 disabled:opacity-50"
                                                            {{ $loop->last ? 'disabled' : '' }}>
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                        </svg>
                                                    </button>
                                                    <span class="text-sm text-gray-600 dark:text-gray-400 ml-2">{{ $mapping->order }}</span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <span class="text-sm font-mono text-gray-900 dark:text-white">{{ $mapping->donor_perfect_field }}</span>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <span class="text-sm font-mono text-gray-900 dark:text-white">{{ $mapping->intacct_field }}</span>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                @if($mapping->is_required)
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200">
                                                        Required
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-200">
                                                        Optional
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <span class="text-sm text-gray-600 dark:text-gray-400">{{ $mapping->default_value ?: '-' }}</span>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium">
                                                <button wire:click="editFieldMapping({{ $mapping->id }})"
                                                        class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 mr-3">
                                                    Edit
                                                </button>
                                                <button wire:click="deleteFieldMapping({{ $mapping->id }})"
                                                        onclick="return confirm('Are you sure you want to delete this mapping?')"
                                                        class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                                    Delete
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-12 bg-gray-50 dark:bg-zinc-700 rounded-lg">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No field mappings</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by adding a field mapping.</p>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Sync History Tab -->
            @if($activeTab === 'sync-history')
                <div class="space-y-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Sync History</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">View past synchronization logs</p>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="bg-gray-50 dark:bg-zinc-700 p-4 rounded-lg">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label for="filterStatus" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Status
                                </label>
                                <select wire:model.live="filterStatus"
                                        id="filterStatus"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <option value="">All Statuses</option>
                                    <option value="success">Success</option>
                                    <option value="failed">Failed</option>
                                    <option value="partial">Partial</option>
                                    <option value="running">Running</option>
                                </select>
                            </div>

                            <div>
                                <label for="filterStartDate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Start Date
                                </label>
                                <input type="date"
                                       wire:model.live="filterStartDate"
                                       id="filterStartDate"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>

                            <div>
                                <label for="filterEndDate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    End Date
                                </label>
                                <input type="date"
                                       wire:model.live="filterEndDate"
                                       id="filterEndDate"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>

                            <div class="flex items-end">
                                <button wire:click="clearFilters"
                                        class="w-full px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-zinc-600 border border-gray-300 dark:border-zinc-500 rounded-md hover:bg-gray-50 dark:hover:bg-zinc-500">
                                    Clear Filters
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Sync History Table -->
                    @if($syncHistory->count() > 0)
                        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-700 overflow-hidden">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                                <thead class="bg-gray-50 dark:bg-zinc-700">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Started At</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Completed At</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Records</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Success</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Failed</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-700">
                                    @foreach($syncHistory as $sync)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-zinc-700 cursor-pointer">
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                {{ $sync->started_at->format('M d, Y H:i') }}
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                {{ $sync->completed_at ? $sync->completed_at->format('M d, Y H:i') : '-' }}
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                @if($sync->status === 'success')
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">
                                                        Success
                                                    </span>
                                                @elseif($sync->status === 'failed')
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200">
                                                        Failed
                                                    </span>
                                                @elseif($sync->status === 'partial')
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200">
                                                        Partial
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">
                                                        Running
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                {{ $sync->total_records }}
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-green-600 dark:text-green-400">
                                                {{ $sync->successful_count }}
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-red-600 dark:text-red-400">
                                                {{ $sync->failed_count }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="mt-4">
                            {{ $syncHistory->links() }}
                        </div>
                    @else
                        <div class="text-center py-12 bg-gray-50 dark:bg-zinc-700 rounded-lg">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No sync history</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Sync history will appear here once synchronization runs.</p>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Code Mappings Tab -->
            @if($activeTab === 'code_mappings')
            <div class="space-y-6">
                <!-- Statistics Header -->
                <div class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Code Mapping Mode</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                @php
                                    $stats = \App\Models\DonorPerfectCodeMapping::forFieldType($selectedFieldType)->get();
                                    $total = $stats->count();
                                    $mapped = $stats->where('is_mapped', true)->count();
                                    $unmapped = $stats->where('is_mapped', false)->where('is_archived', false)->count();
                                    $archived = $stats->where('is_archived', true)->count();
                                    $percent = $total > 0 ? round(($mapped / $total) * 100) : 0;
                                @endphp
                                Total: {{ $total }} | Mapped: {{ $mapped }} ({{ $percent }}%) | Unmapped: {{ $unmapped }} | Archived: {{ $archived }}
                            </p>
                        </div>
                        <button wire:click="discoverNewCodes"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50"
                                wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="discoverNewCodes">Discover New Codes</span>
                            <span wire:loading wire:target="discoverNewCodes">Discovering...</span>
                        </button>
                    </div>
                </div>

                <!-- Field Type Sub-tabs -->
                <div class="border-b border-gray-200 dark:border-zinc-700">
                    <nav class="-mb-px flex space-x-4">
                        <button wire:click="selectFieldType('gl_code')"
                                class="px-4 py-2 text-sm font-medium {{ $selectedFieldType === 'gl_code' ? 'border-b-2 border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700' }}">
                            GL Code
                        </button>
                        <button wire:click="selectFieldType('campaign')"
                                class="px-4 py-2 text-sm font-medium {{ $selectedFieldType === 'campaign' ? 'border-b-2 border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700' }}">
                            Campaign
                        </button>
                        <button wire:click="selectFieldType('solicit_code')"
                                class="px-4 py-2 text-sm font-medium {{ $selectedFieldType === 'solicit_code' ? 'border-b-2 border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700' }}">
                            Solicit Code
                        </button>
                        <button wire:click="selectFieldType('sub_solicit_code')"
                                class="px-4 py-2 text-sm font-medium {{ $selectedFieldType === 'sub_solicit_code' ? 'border-b-2 border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700' }}">
                            Sub-Solicit Code
                        </button>
                    </nav>
                </div>

                <!-- Filters and Actions -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <label class="flex items-center space-x-2 text-sm text-gray-700 dark:text-gray-300">
                            <input type="checkbox" wire:model.live="showOnlyUnmapped" class="rounded">
                            <span>Show only unmapped</span>
                        </label>
                        <label class="flex items-center space-x-2 text-sm text-gray-700 dark:text-gray-300">
                            <input type="checkbox" wire:model.live="showArchived" class="rounded">
                            <span>Show archived</span>
                        </label>
                    </div>
                    <button wire:click="bulkArchiveUnmapped"
                            class="px-3 py-1 text-sm bg-gray-600 text-white rounded-md hover:bg-gray-700"
                            wire:loading.attr="disabled"
                            wire:confirm="Are you sure you want to archive all unmapped codes?">
                        Archive All Unmapped
                    </button>
                </div>

                <!-- Code Mappings Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                        <thead class="bg-gray-50 dark:bg-zinc-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">DP Code</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Sage Intacct Code</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Last Seen</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-700">
                            @forelse($mappings as $mapping)
                            <tr class="{{ $mapping->is_archived ? 'opacity-50' : '' }}">
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white font-mono">
                                    {{ $mapping->dp_code }}
                                </td>
                                <td class="px-6 py-4">
                                    @if($editingMappingId === $mapping->id)
                                        <input type="text"
                                               wire:model="editingIntacctCode"
                                               class="w-full px-2 py-1 text-sm border rounded dark:bg-zinc-700 dark:text-white"
                                               placeholder="Enter Intacct code">
                                    @else
                                        <span class="text-sm text-gray-900 dark:text-white font-mono">
                                            {{ $mapping->intacct_code ?: '—' }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @if($mapping->is_archived)
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                            Archived
                                        </span>
                                    @elseif($mapping->is_mapped)
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                            Mapped
                                        </span>
                                    @else
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">
                                            Unmapped
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $mapping->last_seen_at ? $mapping->last_seen_at->diffForHumans() : 'Never' }}
                                </td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    @if($editingMappingId === $mapping->id)
                                        <button wire:click="saveMapping({{ $mapping->id }})" class="text-green-600 hover:text-green-900 text-sm">
                                            Save
                                        </button>
                                        <button wire:click="cancelEdit" class="text-gray-600 hover:text-gray-900 text-sm">
                                            Cancel
                                        </button>
                                    @else
                                        <button wire:click="editMapping({{ $mapping->id }})" class="text-indigo-600 hover:text-indigo-900 text-sm">
                                            Edit
                                        </button>
                                        <button wire:click="toggleArchive({{ $mapping->id }})" class="text-gray-600 hover:text-gray-900 text-sm">
                                            {{ $mapping->is_archived ? 'Unarchive' : 'Archive' }}
                                        </button>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                    No codes found. Click "Discover New Codes" to scan for DonorPerfect codes.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
