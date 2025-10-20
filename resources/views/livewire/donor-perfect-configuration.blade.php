<div class="max-w-7xl mx-auto p-6">
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl">
        <!-- Header -->
        <div class="bg-indigo-600 text-white p-6 rounded-t-lg">
            <h1 class="text-2xl font-bold">DonorPerfect API Configuration</h1>
            <p class="text-indigo-100 mt-2">Configure your DonorPerfect API connection and field mappings</p>
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

        <!-- Configuration Form -->
        <div class="p-6">
            <form wire:submit="save" class="space-y-6">
                <!-- API Credentials Section -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">API Credentials</h3>
                    <div class="bg-gray-50 dark:bg-zinc-700 p-4 rounded-lg space-y-4">
                        <!-- API URL -->
                        <div>
                            <label for="api_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                API URL <span class="text-red-500">*</span>
                            </label>
                            <input type="url"
                                   wire:model="api_url"
                                   id="api_url"
                                   placeholder="https://www.donorperfect.net/prod"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            @error('api_url') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- API Key -->
                        <div>
                            <label for="api_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                API Key <span class="text-red-500">*</span>
                            </label>
                            <input type="password"
                                   wire:model="api_key"
                                   id="api_key"
                                   placeholder="Enter your API key"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            @error('api_key') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- API Login -->
                            <div>
                                <label for="api_login" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    API Login <span class="text-red-500">*</span>
                                </label>
                                <input type="text"
                                       wire:model="api_login"
                                       id="api_login"
                                       placeholder="Enter API login"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                @error('api_login') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- API Password -->
                            <div>
                                <label for="api_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    API Password <span class="text-red-500">*</span>
                                </label>
                                <input type="password"
                                       wire:model="api_password"
                                       id="api_password"
                                       placeholder="Enter API password"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                @error('api_password') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Cache Duration -->
                        <div>
                            <label for="cache_duration" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Cache Duration (seconds)
                            </label>
                            <input type="number"
                                   wire:model="cache_duration"
                                   id="cache_duration"
                                   min="60"
                                   max="86400"
                                   step="60"
                                   placeholder="3600"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">How long to cache API responses (60-86400 seconds / 1-1440 minutes). Default: 3600 (60 minutes).</p>
                            @error('cache_duration') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
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
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Field Selection Section -->
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Field Selection</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Select which fields to retrieve from DonorPerfect API.</p>
                        </div>
                        <button type="button"
                                wire:click="toggleFieldBrowser"
                                class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 text-sm font-medium">
                            {{ $showFieldBrowser ? 'Hide' : 'Browse' }} Available Fields
                        </button>
                    </div>

                    <!-- Field Browser (loads from API) -->
                    @if($showFieldBrowser)
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-6 mb-4">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h4 class="font-semibold text-blue-900 dark:text-blue-200">Live Field Browser</h4>
                                <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">
                                    Browse all available fields from your DonorPerfect database
                                </p>
                            </div>
                            @if(!$loadingFields && (count($dpGiftFields) > 0 || count($dpDonorFields) > 0 || count($dpGiftUdfFields) > 0))
                            <button type="button"
                                    wire:click="loadFieldsFromApi"
                                    class="px-3 py-1 bg-blue-600 text-white rounded text-xs hover:bg-blue-700">
                                Refresh Fields
                            </button>
                            @endif
                        </div>

                        @if($loadingFields)
                        <div class="flex items-center justify-center py-8">
                            <svg class="animate-spin h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="ml-3 text-blue-700 dark:text-blue-300">Loading fields from DonorPerfect...</span>
                        </div>
                        @elseif(count($dpGiftFields) > 0 || count($dpDonorFields) > 0 || count($dpGiftUdfFields) > 0)
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Gift Table Fields -->
                            @if(count($dpGiftFields) > 0)
                            <div>
                                <h5 class="font-medium text-gray-900 dark:text-white mb-3 flex items-center">
                                    <span class="bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200 px-2 py-1 rounded text-xs mr-2">DPGIFT</span>
                                    Gift Table Fields ({{ count($dpGiftFields) }})
                                </h5>
                                <div class="max-h-96 overflow-y-auto bg-white dark:bg-zinc-800 rounded p-3 space-y-1">
                                    @foreach($dpGiftFields as $field)
                                    <div class="flex items-center hover:bg-gray-50 dark:hover:bg-zinc-700 p-1 rounded">
                                        <input type="checkbox"
                                               wire:click="toggleFieldFromBrowser('{{ $field }}')"
                                               @if(in_array($field, $selected_fields)) checked @endif
                                               id="browser_gift_{{ $field }}"
                                               class="h-3 w-3 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-zinc-600 rounded">
                                        <label for="browser_gift_{{ $field }}" class="ml-2 block text-xs text-gray-700 dark:text-gray-300 font-mono">
                                            {{ $field }}
                                        </label>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif

                            <!-- Donor Table Fields -->
                            @if(count($dpDonorFields) > 0)
                            <div>
                                <h5 class="font-medium text-gray-900 dark:text-white mb-3 flex items-center">
                                    <span class="bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 px-2 py-1 rounded text-xs mr-2">DP</span>
                                    Donor Table Fields ({{ count($dpDonorFields) }})
                                </h5>
                                <div class="max-h-96 overflow-y-auto bg-white dark:bg-zinc-800 rounded p-3 space-y-1">
                                    @foreach($dpDonorFields as $field)
                                    <div class="flex items-center hover:bg-gray-50 dark:hover:bg-zinc-700 p-1 rounded">
                                        <input type="checkbox"
                                               wire:click="toggleFieldFromBrowser('{{ $field }}')"
                                               @if(in_array($field, $selected_fields)) checked @endif
                                               id="browser_donor_{{ $field }}"
                                               class="h-3 w-3 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-zinc-600 rounded">
                                        <label for="browser_donor_{{ $field }}" class="ml-2 block text-xs text-gray-700 dark:text-gray-300 font-mono">
                                            {{ $field }}
                                        </label>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif

                            <!-- Gift UDF Table Fields -->
                            @if(count($dpGiftUdfFields) > 0)
                            <div class="md:col-span-3">
                                <h5 class="font-medium text-gray-900 dark:text-white mb-3 flex items-center">
                                    <span class="bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-200 px-2 py-1 rounded text-xs mr-2">DPGIFTUDF</span>
                                    Gift User-Defined Fields ({{ count($dpGiftUdfFields) }})
                                </h5>
                                <div class="max-h-96 overflow-y-auto bg-white dark:bg-zinc-800 rounded p-3">
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-1">
                                        @foreach($dpGiftUdfFields as $field)
                                        <div class="flex items-center hover:bg-gray-50 dark:hover:bg-zinc-700 p-1 rounded">
                                            <input type="checkbox"
                                                   wire:click="toggleFieldFromBrowser('{{ $field }}')"
                                                   @if(in_array($field, $selected_fields)) checked @endif
                                                   id="browser_udf_{{ $field }}"
                                                   class="h-3 w-3 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-zinc-600 rounded">
                                            <label for="browser_udf_{{ $field }}" class="ml-2 block text-xs text-gray-700 dark:text-gray-300 font-mono">
                                                {{ $field }}
                                            </label>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                        @else
                        <div class="text-center py-6">
                            <p class="text-blue-700 dark:text-blue-300 mb-3">Click the button below to load available fields from your DonorPerfect account.</p>
                            <button type="button"
                                    wire:click="loadFieldsFromApi"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                Load Available Fields
                            </button>
                        </div>
                        @endif
                    </div>
                    @endif

                    <!-- Selected Fields Summary -->
                    <div class="bg-gray-50 dark:bg-zinc-700 p-4 rounded-lg">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="font-medium text-gray-900 dark:text-white">
                                Selected Fields ({{ count($selected_fields) }})
                            </h4>
                            <div class="space-x-2">
                                <button type="button"
                                        wire:click="selectAllFields"
                                        class="text-sm text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">
                                    Select All Defaults
                                </button>
                                <button type="button"
                                        wire:click="deselectAllFields"
                                        class="text-sm text-red-600 hover:text-red-800 dark:text-red-400">
                                    Clear All
                                </button>
                            </div>
                        </div>

                        @if(count($selected_fields) > 0)
                        <div class="flex flex-wrap gap-2">
                            @foreach($selected_fields as $field)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200">
                                {{ $field }}
                                <button type="button"
                                        wire:click="toggleFieldSelection('{{ $field }}')"
                                        class="ml-2 text-indigo-600 dark:text-indigo-300 hover:text-indigo-800 dark:hover:text-indigo-100">
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </span>
                            @endforeach
                        </div>
                        @else
                        <p class="text-sm text-gray-500 dark:text-gray-400 italic">No fields selected. Use the field browser above or select from defaults.</p>
                        @endif

                        @error('selected_fields') <span class="text-red-500 text-sm mt-2 block">{{ $message }}</span> @enderror
                    </div>
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
                                Enable DonorPerfect Integration
                            </label>
                        </div>
                        <p class="ml-6 text-sm text-gray-500 dark:text-gray-400">When enabled, the system will fetch and display gift data from DonorPerfect.</p>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-zinc-600">
                    <a href="{{ route('donor-gifts.feed') }}"
                       wire:navigate
                       class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-zinc-600 border border-gray-300 dark:border-zinc-500 rounded-md hover:bg-gray-50 dark:hover:bg-zinc-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        Cancel
                    </a>
                    <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="save">Save Configuration</span>
                        <span wire:loading wire:target="save" class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Saving...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
