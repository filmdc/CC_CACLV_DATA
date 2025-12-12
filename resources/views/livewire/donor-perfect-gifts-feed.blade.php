<div class="w-full px-0 py-6">
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl">
        <!-- Header -->
        <div class="bg-indigo-600 text-white p-6 rounded-t-lg">
            <h1 class="text-2xl font-bold">DonorPerfect Gifts Feed</h1>
            <p class="text-indigo-100 mt-2">View and export gift data from DonorPerfect</p>
        </div>

        <!-- API Not Configured Notice -->
        @if(!$isConfigured)
        <div class="p-6 border-b border-gray-200 dark:border-zinc-700">
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700 dark:text-yellow-300">
                            DonorPerfect API is not configured.
                            <a href="{{ route('donor-gifts.configuration') }}" wire:navigate class="font-medium underline hover:text-yellow-600 dark:hover:text-yellow-200">
                                Configure API settings
                            </a> to view gift data.
                        </p>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Success/Error Messages -->
        @if (session()->has('success'))
            <div class="p-4 bg-green-50 dark:bg-green-900/20 border-l-4 border-green-400">
                <div class="flex">
                    <div class="ml-3">
                        <p class="text-sm text-green-700 dark:text-green-300">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="p-4 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-400">
                <div class="flex">
                    <div class="ml-3">
                        <p class="text-sm text-red-700 dark:text-red-300">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Authentication Error - Show prominently -->
        @if(isset($isAuthError) && $isAuthError && isset($errorMessage))
        <div class="p-6 border-b border-gray-200 dark:border-zinc-700">
            <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-600 p-6 rounded-r-lg">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                    <div class="ml-4 flex-1">
                        <h3 class="text-lg font-semibold text-red-800 dark:text-red-200 mb-2">
                            Authentication Failed
                        </h3>
                        <p class="text-sm text-red-700 dark:text-red-300 mb-4">
                            {{ $errorMessage }}
                        </p>
                        <div class="bg-white dark:bg-zinc-800 rounded-md p-4 border border-red-200 dark:border-red-800">
                            <p class="text-sm font-medium text-gray-900 dark:text-white mb-2">What to check:</p>
                            <ul class="list-disc list-inside text-sm text-gray-700 dark:text-gray-300 space-y-1">
                                <li>Verify your API Key is correct</li>
                                <li>Verify your API Login username is correct</li>
                                <li>Verify your API Password is correct</li>
                                <li>Check that your DonorPerfect account is active</li>
                                <li>Ensure your API credentials have the necessary permissions</li>
                            </ul>
                        </div>
                        <div class="mt-4">
                            <a href="{{ route('donor-gifts.configuration') }}"
                               wire:navigate
                               class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-red-500">
                                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                Update Configuration
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @elseif(isset($errorMessage) && $errorMessage && $isConfigured)
        <!-- General Error (non-authentication) -->
        <div class="p-6 border-b border-gray-200 dark:border-zinc-700">
            <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-400 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800 dark:text-red-200">Error Loading Gifts</h3>
                        <p class="text-sm text-red-700 dark:text-red-300 mt-1">{{ $errorMessage }}</p>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Tabs -->
        <div class="border-b border-gray-200 dark:border-zinc-700">
            <nav class="flex -mb-px">
                <button wire:click="setActiveTab('gifts')"
                        class="px-6 py-4 text-sm font-medium border-b-2 {{ $activeTab === 'gifts' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                    Gifts Feed
                </button>
                @if($codeMappingEnabled ?? false)
                <button wire:click="setActiveTab('field-mappings')"
                        class="px-6 py-4 text-sm font-medium border-b-2 {{ $activeTab === 'field-mappings' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                    Field Mappings
                </button>
                <button wire:click="setActiveTab('code_mappings')"
                        class="px-6 py-4 text-sm font-medium border-b-2 {{ $activeTab === 'code_mappings' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                    Code Mappings
                </button>
                <button wire:click="setActiveTab('sync-history')"
                        class="px-6 py-4 text-sm font-medium border-b-2 {{ $activeTab === 'sync-history' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                    Sync History
                </button>
                <button wire:click="setActiveTab('sync_filters')"
                        class="px-6 py-4 text-sm font-medium border-b-2 {{ $activeTab === 'sync_filters' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                    Sync Filters
                </button>
                @endif
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-6">
            <!-- Gifts Feed Tab -->
            @if($activeTab === 'gifts')

        <!-- Code Mapping Mode Indicator -->
        @if($isConfigured && $codeMappingEnabled)
        <div class="p-4 border-b border-gray-200 dark:border-zinc-700">
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-md p-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2 text-sm text-blue-800 dark:text-blue-300">
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                        <span>
                            <strong>Code Mapping Enabled:</strong> Showing Sage Intacct code equivalents for reference.
                            @if($this->isAdmin())
                                <a href="{{ route('sage-intacct.configuration') }}" class="underline hover:text-blue-900 dark:hover:text-blue-100" wire:navigate>Configure mappings →</a>
                            @endif
                        </span>
                    </div>
                    <label class="flex items-center space-x-2 text-sm text-blue-800 dark:text-blue-300 cursor-pointer">
                        <input type="checkbox" wire:model.live="showMappedCodes" class="rounded border-blue-300 dark:border-blue-600 text-blue-600 focus:ring-blue-500">
                        <span class="font-medium">Show Mapped Values</span>
                    </label>
                </div>
            </div>
        </div>
        @endif

        <!-- Filters and Actions -->
        @if($isConfigured)
        <div class="p-4 bg-gray-50 dark:bg-zinc-700 border-b border-gray-200 dark:border-zinc-600">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <!-- Start Date -->
                <div>
                    <label for="startDate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Start Date
                    </label>
                    <input type="date"
                           wire:model.live="startDate"
                           id="startDate"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <!-- End Date -->
                <div>
                    <label for="endDate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        End Date
                    </label>
                    <input type="date"
                           wire:model.live="endDate"
                           id="endDate"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <!-- Search -->
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Search
                    </label>
                    <input type="text"
                           wire:model.live="search"
                           id="search"
                           placeholder="Search donors, amounts..."
                           class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <!-- Sync Status Filter -->
                @if($intacctConfigured)
                <div>
                    <label for="syncStatusFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Sync Status
                    </label>
                    <select wire:model.live="syncStatusFilter"
                            id="syncStatusFilter"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="all">All Gifts</option>
                        <option value="not_synced">Not Synced</option>
                        <option value="success">Successfully Synced</option>
                        <option value="failed">Failed to Sync</option>
                        <option value="pending">Pending</option>
                    </select>
                </div>
                @endif
            </div>

            <!-- Additional Filters Row -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mt-4">
                <!-- Gift Type Filter -->
                <div>
                    <label for="giftTypeFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Gift Type
                    </label>
                    <select wire:model.live="giftTypeFilter"
                            id="giftTypeFilter"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                        <option value="">All Types</option>
                        @foreach($uniqueGiftTypes as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Payment Type Filter -->
                <div>
                    <label for="paymentTypeFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Payment Type
                    </label>
                    <select wire:model.live="paymentTypeFilter"
                            id="paymentTypeFilter"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                        <option value="">All Payments</option>
                        @foreach($uniquePaymentTypes as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- GL Code Filter -->
                <div>
                    <label for="glCodeFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        GL Code
                    </label>
                    <select wire:model.live="glCodeFilter"
                            id="glCodeFilter"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                        <option value="">All GL Codes</option>
                        @foreach($uniqueGlCodes as $code)
                            <option value="{{ $code }}">{{ $code }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Campaign Filter -->
                <div>
                    <label for="campaignFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Campaign
                    </label>
                    <select wire:model.live="campaignFilter"
                            id="campaignFilter"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                        <option value="">All Campaigns</option>
                        @foreach($uniqueCampaigns as $campaign)
                            <option value="{{ $campaign }}">{{ $campaign }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Solicit Code Filter -->
                <div>
                    <label for="solicitCodeFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Solicit Code
                    </label>
                    <select wire:model.live="solicitCodeFilter"
                            id="solicitCodeFilter"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                        <option value="">All Solicit Codes</option>
                        @foreach($uniqueSolicitCodes as $code)
                            <option value="{{ $code }}">{{ $code }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Sub-Solicit Code Filter -->
                <div>
                    <label for="subSolicitCodeFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Sub-Solicit Code
                    </label>
                    <select wire:model.live="subSolicitCodeFilter"
                            id="subSolicitCodeFilter"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                        <option value="">All Sub-Solicit Codes</option>
                        @foreach($uniqueSubSolicitCodes as $code)
                            <option value="{{ $code }}">{{ $code }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Check Scan Batch Filter -->
                <div>
                    <label for="checkScanBatchFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Check Scan Batch
                    </label>
                    <input type="text"
                           wire:model.live.debounce.300ms="checkScanBatchFilter"
                           id="checkScanBatchFilter"
                           list="batchSuggestions"
                           placeholder="Search batch..."
                           class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                    <datalist id="batchSuggestions">
                        @foreach($uniqueCheckScanBatches ?? [] as $batch)
                            <option value="{{ $batch }}">
                        @endforeach
                    </datalist>
                </div>
            </div>

            <!-- Clear Filters Button -->
            <div class="mt-4 flex justify-end">
                <button wire:click="clearAllFilters"
                        class="px-3 py-1.5 text-sm bg-gray-500 text-white rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-400">
                    Clear All Filters
                </button>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-between mt-4">
                <div class="flex items-center space-x-2">
                    @if($this->canAny(['donorperfect.sync.refresh', 'donor_gifts.view', 'donor_gifts.configure']))
                    <button wire:click="refreshData"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="refreshData">Refresh Data</span>
                        <span wire:loading wire:target="refreshData" class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Refreshing...
                        </span>
                    </button>
                    @endif

                    <!-- Batch Sync Link -->
                    @if($intacctConfigured ?? false)
                    <a href="{{ route('donor-gifts.batch-sync') }}"
                       class="px-4 py-2 bg-emerald-600 text-white rounded-md hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 flex items-center">
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                        Batch Sync
                    </a>
                    @endif
                </div>

                <div class="flex items-center space-x-2">
                    <!-- Sage Intacct Sync Button -->
                    @if($intacctConfigured && $this->canAny(['sage.sync.perform', 'intacct.sync']))
                        @php
                            $intacctSetting = \App\Models\SageIntacctSetting::getActive();
                            $isXmlMode = $intacctSetting && $intacctSetting->isXmlApi();
                            $userToken = auth()->user()->sageIntacctToken;
                            $hasValidToken = $userToken && $userToken->hasValidToken();
                        @endphp

                        @if($isXmlMode)
                            <!-- XML mode - always show sync button (no user connection needed) -->
                            <button wire:click="toggleSyncModal"
                                    class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 flex items-center">
                                <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                </svg>
                                Sync to Intacct
                                @if($selectedCount > 0)
                                    <span class="ml-2 px-2 py-0.5 bg-purple-800 rounded-full text-xs">{{ $selectedCount }}</span>
                                @endif
                            </button>

                            <!-- Select All Pages Button -->
                            <button wire:click="selectAllPages"
                                    class="px-3 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 flex items-center"
                                    title="Select all gifts across all pages">
                                <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                </svg>
                                Select All Pages
                            </button>

                            @if($selectedCount > 0)
                            <!-- Deselect All Button -->
                            <button wire:click="deselectAllForSync"
                                    class="px-3 py-2 bg-gray-600 text-white text-sm rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500"
                                    title="Clear all selections">
                                Clear Selection
                            </button>
                            @endif

                            <!-- Auth method badge -->
                            <span class="text-xs px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded-md">
                                XML Web Services
                            </span>
                        @else
                            <!-- OAuth mode - check user connection -->
                            @if($hasValidToken)
                                <!-- User is connected - show sync button -->
                                <button wire:click="toggleSyncModal"
                                        class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 flex items-center">
                                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                    </svg>
                                    Sync to Intacct
                                    @if($selectedCount > 0)
                                        <span class="ml-2 px-2 py-0.5 bg-purple-800 rounded-full text-xs">{{ $selectedCount }}</span>
                                    @endif
                                </button>

                                <!-- Select All Pages Button -->
                                <button wire:click="selectAllPages"
                                        class="px-3 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 flex items-center"
                                        title="Select all gifts across all pages">
                                    <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                    </svg>
                                    Select All Pages
                                </button>

                                @if($selectedCount > 0)
                                <!-- Deselect All Button -->
                                <button wire:click="deselectAllForSync"
                                        class="px-3 py-2 bg-gray-600 text-white text-sm rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500"
                                        title="Clear all selections">
                                    Clear Selection
                                </button>
                                @endif

                                <!-- Connection status badge -->
                                <span class="text-xs px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 rounded-md">
                                    ✓ OAuth Connected ({{ $userToken->company_id }})
                                </span>
                            @else
                                <!-- User not connected - show connect button -->
                                <a href="{{ route('intacct.oauth.authorize') }}"
                                   class="px-4 py-2 bg-orange-600 text-white rounded-md hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 flex items-center">
                                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                                    </svg>
                                    Connect to Sage Intacct
                                </a>
                            @endif
                        @endif
                    @endif

                    <!-- Export Button -->
                    <button wire:click="toggleExportModal"
                            class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 flex items-center">
                        <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        Export
                    </button>
                </div>
            </div>
        </div>

        <!-- Data Table -->
        <div class="p-6">
            <!-- Selection Summary Banner -->
            @if($selectedCount > 0)
            <div class="mb-4 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-md p-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2 text-sm text-purple-800 dark:text-purple-300">
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        <span>
                            <strong>{{ $selectedCount }}</strong> gift(s) selected for sync
                        </span>
                    </div>
                    <button wire:click="deselectAllForSync"
                            class="text-sm text-purple-600 hover:text-purple-800 dark:text-purple-400 dark:hover:text-purple-300 underline">
                        Clear Selection
                    </button>
                </div>
            </div>
            @endif

            <div class="overflow-x-auto">
                <!-- Loading State -->
                <div wire:loading wire:target="refreshData,startDate,endDate,search,giftTypeFilter,paymentTypeFilter,glCodeFilter,campaignFilter,solicitCodeFilter,subSolicitCodeFilter,checkScanBatchFilter,syncStatusFilter"
                     class="flex items-center justify-center py-12">
                    <div class="flex items-center space-x-3">
                        <svg class="animate-spin h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-gray-600 dark:text-gray-300 font-medium">Loading gifts data...</span>
                    </div>
                </div>

                <!-- Table -->
                <div wire:loading.remove wire:target="refreshData,startDate,endDate,search,giftTypeFilter,paymentTypeFilter,glCodeFilter,campaignFilter,solicitCodeFilter,subSolicitCodeFilter,checkScanBatchFilter,syncStatusFilter">
                    @if($gifts && count($gifts) > 0)
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                        <thead class="bg-gray-50 dark:bg-zinc-700">
                            <tr>
                                @if($intacctConfigured && $this->canAny(['sage.sync.perform', 'intacct.sync']))
                                <th scope="col" class="px-4 py-3 text-center">
                                    @php
                                        $visibleGiftIds = $gifts->pluck('gift_id')->filter()->toArray();
                                        $allVisibleSelected = !empty($visibleGiftIds) && count(array_intersect($visibleGiftIds, $selectedGiftsForSync)) === count($visibleGiftIds);
                                    @endphp
                                    <input type="checkbox"
                                           wire:click="selectAllForSync"
                                           @if($allVisibleSelected) checked @endif
                                           class="rounded border-gray-300 dark:border-zinc-600 text-purple-600 focus:ring-purple-500"
                                           title="{{ $allVisibleSelected ? 'Deselect all visible gifts' : 'Select all visible gifts' }}">
                                </th>
                                @endif
                                @if($intacctConfigured)
                                <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Sync Status</th>
                                @endif
                                <!-- Sortable Headers -->
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-zinc-700" wire:click="sortBy('gift_date')">
                                    <div class="flex items-center">
                                        Gift Date
                                        @if($sortField === 'gift_date')
                                            <svg class="ml-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                @if($sortDirection === 'asc')
                                                    <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                                                @else
                                                    <path d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"/>
                                                @endif
                                            </svg>
                                        @endif
                                    </div>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-zinc-700" wire:click="sortBy('amount')">
                                    <div class="flex items-center">
                                        Amount
                                        @if($sortField === 'amount')
                                            <svg class="ml-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                @if($sortDirection === 'asc')
                                                    <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                                                @else
                                                    <path d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"/>
                                                @endif
                                            </svg>
                                        @endif
                                    </div>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-zinc-700" wire:click="sortBy('gl_code')">
                                    <div class="flex items-center">
                                        GL Code
                                        @if($sortField === 'gl_code')
                                            <svg class="ml-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                @if($sortDirection === 'asc')
                                                    <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                                                @else
                                                    <path d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"/>
                                                @endif
                                            </svg>
                                        @endif
                                    </div>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-zinc-700" wire:click="sortBy('campaign')">
                                    <div class="flex items-center">
                                        Campaign
                                        @if($sortField === 'campaign')
                                            <svg class="ml-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                @if($sortDirection === 'asc')
                                                    <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                                                @else
                                                    <path d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"/>
                                                @endif
                                            </svg>
                                        @endif
                                    </div>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Solicitation Code</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Sub-Solicit Code</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Payment Type</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-zinc-700" wire:click="sortBy('gift_type')">
                                    Gift Type
                                    @if($sortField === 'gift_type')
                                        <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Check Scan Batch</th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-700">
                            @foreach($gifts as $gift)
                                @php
                                    $syncStatus = $this->getSyncStatus($gift);
                                    $syncItem = \App\Models\SageIntacctSyncItem::where('gift_id', $gift['gift_id'] ?? null)->orderBy('created_at', 'desc')->first();
                                    $isAlreadySynced = ($syncStatus === 'synced' || $syncStatus === 'success');
                                    $isSelected = in_array($gift['gift_id'] ?? null, $selectedGiftsForSync);
                                @endphp
                                <tr class="
                                    {{ $isSelected ? 'bg-purple-50 dark:bg-purple-900/20 border-l-4 border-purple-500' : '' }}
                                    {{ $isAlreadySynced ? 'opacity-60' : '' }}
                                    {{ !$isSelected ? 'hover:bg-gray-50 dark:hover:bg-zinc-700' : 'hover:bg-purple-100 dark:hover:bg-purple-900/30' }}
                                ">
                                    @if($intacctConfigured && $this->canAny(['sage.sync.perform', 'intacct.sync']))
                                    <td class="px-4 py-4 text-center" onclick="event.stopPropagation()">
                                        <input type="checkbox"
                                               wire:model.live="selectedGiftsForSync"
                                               value="{{ $gift['gift_id'] ?? '' }}"
                                               @if($isSelected) checked @endif
                                               class="rounded border-gray-300 dark:border-zinc-600 text-purple-600 focus:ring-purple-500 {{ $isAlreadySynced ? 'cursor-not-allowed' : '' }}"
                                               {{ $isAlreadySynced ? 'disabled' : '' }}
                                               title="{{ $isAlreadySynced ? 'Already synced successfully' : ($isSelected ? 'Selected for sync - click to deselect' : 'Select for sync') }}">
                                    </td>
                                    @endif
                                    @if($intacctConfigured)
                                    <td class="px-4 py-4 text-center">
                                        @if($syncStatus === 'synced' || $syncStatus === 'success')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300"
                                                  title="Synced on {{ $syncItem && $syncItem->synced_at ? $syncItem->synced_at->format('M d, Y g:i A') : '' }}">
                                                <svg class="h-3 w-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                </svg>
                                                Synced
                                            </span>
                                        @elseif($syncStatus === 'failed')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300"
                                                  title="{{ $syncItem && $syncItem->error_message ? $syncItem->error_message : 'Sync failed' }}">
                                                <svg class="h-3 w-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                                </svg>
                                                Failed
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                                <svg class="h-3 w-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                                </svg>
                                                Pending
                                            </span>
                                        @endif
                                    </td>
                                    @endif
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white cursor-pointer" wire:click="showGiftDetails({{ json_encode($gift) }})">
                                        {{ isset($gift['gift_date']) && $gift['gift_date'] ? $gift['gift_date'] : '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-white cursor-pointer" wire:click="showGiftDetails({{ json_encode($gift) }})">
                                        ${{ number_format(floatval(str_replace(',', '', $gift['amount'] ?? 0)), 2) }}
                                    </td>
                                    <td class="px-6 py-4 text-sm cursor-pointer" wire:click="showGiftDetails({{ json_encode($gift) }})">
                                        @if($codeMappingEnabled && $showMappedCodes)
                                            @php
                                                $dpCode = $gift['gl_code'] ?? '';
                                                $mapping = $this->getDisplayCodeMapping($gift, 'gl_code');
                                                $intacctCode = $mapping['code'];
                                                $mappingSource = $mapping['source'];
                                            @endphp
                                            <div class="space-y-0.5">
                                                <div class="font-mono text-gray-600 dark:text-gray-400 text-xs">
                                                    DP: {{ $dpCode ?: '—' }}
                                                </div>
                                                @if($intacctCode)
                                                    <div class="font-mono text-indigo-600 dark:text-indigo-400 font-semibold text-sm">
                                                        ✓ {{ $intacctCode }}
                                                    </div>
                                                @else
                                                    <div class="font-mono text-yellow-600 dark:text-yellow-400 text-xs italic">
                                                        ⚠ Not mapped
                                                    </div>
                                                @endif
                                            </div>
                                        @else
                                            <span class="font-mono text-gray-900 dark:text-white">{{ $gift['gl_code'] ?? '—' }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm cursor-pointer" wire:click="showGiftDetails({{ json_encode($gift) }})">
                                        @if($codeMappingEnabled && $showMappedCodes)
                                            @php
                                                $dpCode = $gift['campaign'] ?? '';
                                                $mapping = $this->getDisplayCodeMapping($gift, 'campaign');
                                                $intacctCode = $mapping['code'];
                                                $mappingSource = $mapping['source'];
                                            @endphp
                                            <div class="space-y-0.5">
                                                <div class="font-mono text-gray-600 dark:text-gray-400 text-xs">
                                                    DP: {{ $dpCode ?: '—' }}
                                                </div>
                                                @if($intacctCode)
                                                    <div class="font-mono text-indigo-600 dark:text-indigo-400 font-semibold text-sm">
                                                        ✓ {{ $intacctCode }}
                                                    </div>
                                                @else
                                                    <div class="font-mono text-yellow-600 dark:text-yellow-400 text-xs italic">
                                                        ⚠ Not mapped
                                                    </div>
                                                @endif
                                            </div>
                                        @else
                                            <span class="font-mono text-gray-900 dark:text-white">{{ $gift['campaign'] ?? '—' }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm cursor-pointer" wire:click="showGiftDetails({{ json_encode($gift) }})">
                                        @if($codeMappingEnabled && $showMappedCodes)
                                            @php
                                                $dpCode = $gift['solicit_code'] ?? '';
                                                $mapping = $this->getDisplayCodeMapping($gift, 'solicit_code');
                                                $intacctCode = $mapping['code'];
                                                $mappingSource = $mapping['source'];
                                                $isBlocked = $mapping['blocked'] ?? false;
                                                $blockReason = $mapping['reason'] ?? '';
                                            @endphp
                                            <div class="space-y-0.5">
                                                <div class="font-mono text-gray-600 dark:text-gray-400 text-xs">
                                                    DP: {{ $dpCode ?: '—' }}
                                                </div>
                                                @if($intacctCode)
                                                    <div class="flex items-center space-x-1">
                                                        @if($isBlocked)
                                                            <div class="font-mono text-red-600 dark:text-red-400 font-semibold text-sm" title="{{ $blockReason }}">
                                                                ✗ {{ $intacctCode }}
                                                            </div>
                                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300" title="{{ $blockReason }}">
                                                                BLOCKED
                                                            </span>
                                                        @else
                                                            <div class="font-mono text-indigo-600 dark:text-indigo-400 font-semibold text-sm">
                                                                ✓ {{ $intacctCode }}
                                                            </div>
                                                            @if($mappingSource === 'conditional')
                                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300" title="Uses location-specific conditional mapping">
                                                                    C
                                                                </span>
                                                            @endif
                                                        @endif
                                                    </div>
                                                @else
                                                    <div class="font-mono text-yellow-600 dark:text-yellow-400 text-xs italic">
                                                        ⚠ Not mapped
                                                    </div>
                                                @endif
                                            </div>
                                        @else
                                            <span class="font-mono text-gray-900 dark:text-white">{{ $gift['solicit_code'] ?? '—' }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm cursor-pointer" wire:click="showGiftDetails({{ json_encode($gift) }})">
                                        @if($codeMappingEnabled && $showMappedCodes)
                                            @php
                                                $dpCode = $gift['sub_solicit_code'] ?? '';
                                                $mapping = $this->getDisplayCodeMapping($gift, 'sub_solicit_code');
                                                $intacctCode = $mapping['code'];
                                                $mappingSource = $mapping['source'];
                                                $isBlocked = $mapping['blocked'] ?? false;
                                                $blockReason = $mapping['reason'] ?? '';
                                            @endphp
                                            <div class="space-y-0.5">
                                                <div class="font-mono text-gray-600 dark:text-gray-400 text-xs">
                                                    DP: {{ $dpCode ?: '—' }}
                                                </div>
                                                @if($intacctCode)
                                                    <div class="flex items-center space-x-1">
                                                        @if($isBlocked)
                                                            <div class="font-mono text-red-600 dark:text-red-400 font-semibold text-sm" title="{{ $blockReason }}">
                                                                ✗ {{ $intacctCode }}
                                                            </div>
                                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300" title="{{ $blockReason }}">
                                                                BLOCKED
                                                            </span>
                                                        @else
                                                            <div class="font-mono text-indigo-600 dark:text-indigo-400 font-semibold text-sm">
                                                                ✓ {{ $intacctCode }}
                                                            </div>
                                                            @if($mappingSource === 'conditional')
                                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300" title="Uses location-specific conditional mapping">
                                                                    C
                                                                </span>
                                                            @endif
                                                        @endif
                                                    </div>
                                                @else
                                                    <div class="font-mono text-yellow-600 dark:text-yellow-400 text-xs italic">
                                                        ⚠ Not mapped
                                                    </div>
                                                @endif
                                            </div>
                                        @else
                                            <span class="font-mono text-gray-900 dark:text-white">{{ $gift['sub_solicit_code'] ?? '—' }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white cursor-pointer" wire:click="showGiftDetails({{ json_encode($gift) }})">
                                        {{ $gift['payment_type'] ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm cursor-pointer" wire:click="showGiftDetails({{ json_encode($gift) }})">
                                        @php
                                            $giftType = $gift['gift_type'] ?? '';
                                            $filterResult = \App\Models\SageIntacctSyncFilter::shouldFilterGift($gift);
                                            $willFilter = $filterResult['should_filter'];
                                        @endphp
                                        <div class="flex items-center space-x-2">
                                            <span class="font-mono {{ $willFilter ? 'text-red-600 dark:text-red-400 font-semibold' : 'text-gray-900 dark:text-white' }}">
                                                {{ $giftType ?: '—' }}
                                            </span>
                                            @if($willFilter)
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300"
                                                      title="{{ $filterResult['reason'] ?? 'Will be filtered out of sync' }}">
                                                    ⊗
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white cursor-pointer" wire:click="showGiftDetails({{ json_encode($gift) }})">
                                        {{ $gift['DP_CheckScan_Batch'] ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                        <div class="flex items-center justify-center space-x-2">
                                            @if(!empty($gift['IMAGE_CHECK']))
                                                @if(!empty($gift['IMAGE_CHECK_URL']))
                                                    {{-- Use cached image URL if available --}}
                                                    <a href="{{ $gift['IMAGE_CHECK_URL'] }}"
                                                       target="_blank"
                                                       rel="noopener noreferrer"
                                                       class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300"
                                                       title="View Check Image (Cached)"
                                                       onclick="event.stopPropagation()">
                                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                        </svg>
                                                    </a>
                                                @else
                                                    {{-- Fallback: copy UNC path to clipboard --}}
                                                    <button type="button"
                                                            onclick="event.stopPropagation();
                                                                const path = '{{ addslashes($gift['IMAGE_CHECK']) }}';
                                                                if (navigator.clipboard && navigator.clipboard.writeText) {
                                                                    navigator.clipboard.writeText(path).then(() => {
                                                                        alert('Check image path copied to clipboard!\\n\\n' + path + '\\n\\nPaste this into Windows Explorer to open the file.');
                                                                    }).catch(() => {
                                                                        prompt('Copy this path to clipboard:', path);
                                                                    });
                                                                } else {
                                                                    prompt('Copy this path to clipboard:', path);
                                                                }"
                                                            class="text-orange-600 hover:text-orange-800 dark:text-orange-400 dark:hover:text-orange-300"
                                                            title="Copy network path: {{ $gift['IMAGE_CHECK'] }}">
                                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                                        </svg>
                                                    </button>
                                                @endif
                                            @endif
                                            <a href="{{ $this->getDonorPerfectGiftUrl($gift['gift_id'] ?? '') }}"
                                               target="_blank"
                                               rel="noopener noreferrer"
                                               class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                                               title="View in DonorPerfect"
                                               onclick="event.stopPropagation()">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                                </svg>
                                            </a>
                                            <button type="button"
                                                    wire:click="showGiftDetails({{ json_encode($gift) }})"
                                                    class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                                    title="View Details">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <!-- Summary -->
                    @if(isset($totalGifts) && isset($totalAmount))
                    <div class="mt-4 p-4 bg-gray-50 dark:bg-zinc-700 rounded-md">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Total Gifts</p>
                                <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ number_format($totalGifts) }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Total Amount</p>
                                <p class="text-lg font-semibold text-gray-900 dark:text-white">${{ number_format($totalAmount, 2) }}</p>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Pagination -->
                    <div class="mt-6">
                        {{ $gifts->links() }}
                    </div>
                    @else
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <p class="mt-4 text-gray-500 dark:text-gray-400">No gifts found for the selected date range.</p>
                        <p class="mt-2 text-sm text-gray-400 dark:text-gray-500">Try adjusting your filters or date range.</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Sync to Intacct Modal -->
    @if($showSyncModal)
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50" wire:click="toggleSyncModal">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-md w-full mx-4" wire:click.stop>
            <!-- Modal Header -->
            <div class="bg-purple-600 text-white p-6 rounded-t-lg flex justify-between items-center">
                <div>
                    <h3 class="text-xl font-bold">Sync to Sage Intacct</h3>
                    <p class="text-purple-100 text-sm mt-1">Confirm synchronization</p>
                </div>
                <button wire:click="toggleSyncModal" class="text-white hover:text-gray-200">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-6">
                @if($selectedCount > 0)
                    <p class="text-gray-700 dark:text-gray-300 mb-4">
                        You are about to sync <strong class="text-purple-600 dark:text-purple-400">{{ $selectedCount }}</strong> selected gift(s) to Sage Intacct.
                    </p>
                @else
                    <p class="text-gray-700 dark:text-gray-300 mb-4">
                        You are about to sync all visible gifts on this page to Sage Intacct.
                    </p>
                @endif

                <!-- Warning about re-syncing -->
                @php
                    $hasAlreadySynced = false;
                    if ($selectedCount > 0) {
                        foreach ($gifts as $gift) {
                            if (in_array($gift['gift_id'] ?? '', $selectedGiftsForSync)) {
                                if ($this->getSyncStatus($gift) === 'synced') {
                                    $hasAlreadySynced = true;
                                    break;
                                }
                            }
                        }
                    } else {
                        foreach ($gifts as $gift) {
                            if ($this->getSyncStatus($gift) === 'synced') {
                                $hasAlreadySynced = true;
                                break;
                            }
                        }
                    }
                @endphp

                @if($hasAlreadySynced)
                <div class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400 p-4 mb-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700 dark:text-yellow-300">
                                <strong>Warning:</strong> Some of these gifts have already been synced. Re-syncing may create duplicates in Intacct.
                            </p>
                        </div>
                    </div>
                </div>
                @endif

                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                    This action will send the gift data to Sage Intacct for processing. Do you want to continue?
                </p>

                <!-- Action Buttons -->
                <div class="flex justify-end space-x-3">
                    <button wire:click="toggleSyncModal"
                            class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500"
                            wire:loading.attr="disabled">
                        Cancel
                    </button>
                    <button wire:click="syncToIntacct"
                            class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            wire:loading.attr="disabled"
                            wire:target="syncToIntacct">
                        <span wire:loading.remove wire:target="syncToIntacct">Confirm Sync</span>
                        <span wire:loading wire:target="syncToIntacct" class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Syncing...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Gift Details Modal -->
    @if($showDetailsModal && $selectedGift)
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50" wire:click="closeDetailsModal">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-3xl w-full mx-4 max-h-[90vh] overflow-y-auto" wire:click.stop>
            <!-- Modal Header -->
            <div class="bg-indigo-600 text-white p-6 rounded-t-lg flex justify-between items-center">
                <div>
                    <h3 class="text-xl font-bold">Gift Details</h3>
                    <p class="text-indigo-100 text-sm mt-1">Gift ID: {{ $selectedGift['gift_id'] ?? 'N/A' }}</p>
                </div>
                <button wire:click="closeDetailsModal" class="text-white hover:text-gray-200">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-6">
                <!-- All Fields Displayed Dynamically -->
                <div class="mb-6">
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-zinc-600 pb-2">
                        Complete Gift & Donor Details
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($selectedGift as $fieldName => $fieldValue)
                            @if($fieldName !== 'donor_name')
                            <div>
                                <label class="block text-sm font-medium text-gray-600 dark:text-gray-400">
                                    {{ ucwords(str_replace('_', ' ', $fieldName)) }}
                                </label>
                                <p class="text-sm text-gray-900 dark:text-white mt-1 {{ in_array($fieldName, ['amount']) ? 'font-semibold' : '' }}">
                                    @if($fieldName === 'amount')
                                        ${{ number_format(floatval($fieldValue ?? 0), 2) }}
                                    @elseif(empty($fieldValue))
                                        <span class="text-gray-400 dark:text-gray-500 italic">-</span>
                                    @else
                                        {{ $fieldValue }}
                                    @endif
                                </p>
                            </div>
                            @endif
                        @endforeach
                    </div>
                </div>

                <!-- Close Button -->
                <div class="flex justify-end pt-4 border-t border-gray-200 dark:border-zinc-600">
                    <button wire:click="closeDetailsModal"
                            class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Export Modal -->
    @if($showExportModal)
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50" wire:click="toggleExportModal">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-md w-full mx-4" wire:click.stop>
            <!-- Modal Header -->
            <div class="bg-green-600 text-white p-6 rounded-t-lg flex justify-between items-center">
                <div>
                    <h3 class="text-xl font-bold">Export Gifts Data</h3>
                    <p class="text-green-100 text-sm mt-1">Choose export format and options</p>
                </div>
                <button wire:click="toggleExportModal" class="text-white hover:text-gray-200">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-6 max-h-[70vh] overflow-y-auto">
                <!-- Field Selection -->
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-3">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Select Fields to Export
                        </label>
                        <div class="flex items-center space-x-2">
                            <button type="button" wire:click="selectAllFields" class="text-xs text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
                                Select All
                            </button>
                            <span class="text-gray-400">|</span>
                            <button type="button" wire:click="deselectAllFields" class="text-xs text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
                                Deselect All
                            </button>
                            <span class="text-gray-400">|</span>
                            <button type="button" wire:click="resetToDefaultFields" class="text-xs text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
                                Reset to Default
                            </button>
                        </div>
                    </div>

                    @if(!empty($availableFields))
                    <div class="bg-gray-50 dark:bg-zinc-700 border border-gray-300 dark:border-zinc-600 rounded-md p-4 max-h-48 overflow-y-auto">
                        <div class="grid grid-cols-2 gap-3">
                            @foreach($availableFields as $field)
                                @if($field !== 'donor_name' && $field !== 'IMAGE_CHECK_URL')
                                <label class="flex items-center space-x-2 cursor-pointer hover:bg-gray-100 dark:hover:bg-zinc-600 p-2 rounded">
                                    <input type="checkbox" wire:model.live="selectedFields" value="{{ $field }}" class="rounded border-gray-300 dark:border-zinc-600 text-green-600 focus:ring-green-500">
                                    <span class="text-sm text-gray-900 dark:text-white">{{ ucwords(str_replace('_', ' ', $field)) }}</span>
                                </label>
                                @endif
                            @endforeach
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Selected: {{ count($selectedFields) }} field(s)
                    </p>
                    @else
                    <div class="bg-gray-50 dark:bg-zinc-700 border border-gray-300 dark:border-zinc-600 rounded-md p-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400 text-center">
                            Loading available fields...
                        </p>
                    </div>
                    @endif
                </div>

                <!-- Check Images Option -->
                <div class="mb-6">
                    <label class="flex items-start space-x-3 cursor-pointer p-3 border rounded-md {{ $includeCheckImages ? 'border-green-600 bg-green-50 dark:bg-green-900/20' : 'border-gray-300 dark:border-zinc-600' }}">
                        <input type="checkbox" wire:model.live="includeCheckImages" class="mt-1 rounded border-gray-300 dark:border-zinc-600 text-green-600 focus:ring-green-500">
                        <div class="flex-1">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">Include Check Images</div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">
                                Check images will be exported separately as a password-protected ZIP file, organized by donor name, donor ID, and gift ID
                            </div>
                        </div>
                    </label>

                    @if($includeCheckImages)
                    <div class="mt-3 ml-8">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            ZIP File Password (Optional)
                        </label>
                        <input type="password"
                               wire:model="zipPassword"
                               placeholder="Enter password to protect ZIP file"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            If provided, the check images ZIP file will be password-protected using AES-256 encryption
                        </p>
                    </div>
                    @endif
                </div>

                @if($codeMappingEnabled)
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        Code Format
                    </label>
                    <div class="space-y-2">
                        <label class="flex items-start space-x-3 cursor-pointer p-3 border rounded-md {{ $useIntacctCodes == 0 ? 'border-indigo-600 bg-indigo-50 dark:bg-indigo-900/20' : 'border-gray-300 dark:border-zinc-600' }}">
                            <input type="radio" wire:model.live="useIntacctCodes" value="0" class="mt-1">
                            <div>
                                <div class="text-sm font-medium text-gray-900 dark:text-white">DonorPerfect Original Codes</div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">Export with codes as received from DonorPerfect</div>
                            </div>
                        </label>
                        <label class="flex items-start space-x-3 cursor-pointer p-3 border rounded-md {{ $useIntacctCodes == 1 ? 'border-indigo-600 bg-indigo-50 dark:bg-indigo-900/20' : 'border-gray-300 dark:border-zinc-600' }}">
                            <input type="radio" wire:model.live="useIntacctCodes" value="1" class="mt-1">
                            <div>
                                <div class="text-sm font-medium text-gray-900 dark:text-white">Sage Intacct Mapped Codes</div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">Export with codes mapped for Sage Intacct import</div>
                            </div>
                        </label>
                    </div>
                </div>
                @endif

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        Export Format
                    </label>
                    <div class="space-y-2">
                        <button wire:click="exportCsv"
                                class="w-full flex items-center justify-between px-4 py-3 border border-gray-300 dark:border-zinc-600 rounded-md hover:bg-gray-50 dark:hover:bg-zinc-700 text-left">
                            <div class="flex items-center space-x-3">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">CSV</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Comma-separated values</div>
                                </div>
                            </div>
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>

                        <button wire:click="exportExcel"
                                class="w-full flex items-center justify-between px-4 py-3 border border-gray-300 dark:border-zinc-600 rounded-md hover:bg-gray-50 dark:hover:bg-zinc-700 text-left">
                            <div class="flex items-center space-x-3">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                                <div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Excel</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Microsoft Excel format (.xlsx)</div>
                                </div>
                            </div>
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>

                        <button wire:click="exportPdf"
                                class="w-full flex items-center justify-between px-4 py-3 border border-gray-300 dark:border-zinc-600 rounded-md hover:bg-gray-50 dark:hover:bg-zinc-700 text-left">
                            <div class="flex items-center space-x-3">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                </svg>
                                <div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">PDF</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Portable document format</div>
                                </div>
                            </div>
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Cancel Button -->
                <div class="flex justify-end mt-6 pt-4 border-t border-gray-200 dark:border-zinc-600">
                    <button wire:click="toggleExportModal"
                            class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Download Modal -->
    @if($showDownloadModal)
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50" wire:click.self="closeDownloadModal">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-lg w-full mx-4">
            <!-- Modal Header -->
            <div class="bg-green-600 text-white p-6 rounded-t-lg flex justify-between items-center">
                <div>
                    <h3 class="text-xl font-bold">Export Ready</h3>
                    <p class="text-green-100 text-sm mt-1">Your files are ready to download</p>
                </div>
                <button wire:click="closeDownloadModal" class="text-white hover:text-gray-200">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-6">
                <div class="space-y-4">
                    <!-- Data Export Download Button -->
                    <a href="{{ $dataFileUrl }}"
                       class="flex items-center justify-between w-full px-6 py-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg shadow-md transition-colors">
                        <div class="flex items-center space-x-3">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <div class="text-left">
                                <div class="font-semibold">Download Data Export</div>
                                <div class="text-sm text-indigo-100">
                                    @php
                                        $exportData = session()->get('export_downloads');
                                        $giftCount = $exportData['gift_count'] ?? 0;
                                    @endphp
                                    {{ $giftCount }} gift record(s)
                                </div>
                            </div>
                        </div>
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </a>

                    <!-- Check Images Download Button (if available) -->
                    @if(!empty($checkImagesUrl))
                    <a href="{{ $checkImagesUrl }}"
                       class="flex items-center justify-between w-full px-6 py-4 bg-purple-600 hover:bg-purple-700 text-white rounded-lg shadow-md transition-colors">
                        <div class="flex items-center space-x-3">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <div class="text-left">
                                <div class="font-semibold">Download Check Images ZIP</div>
                                <div class="text-sm text-purple-100">
                                    @php
                                        $imageCount = $exportData['image_count'] ?? 0;
                                    @endphp
                                    {{ $imageCount }} check image(s)
                                    @if(!empty($zipPassword))
                                        • Password protected
                                    @endif
                                </div>
                            </div>
                        </div>
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </a>
                    @elseif($includeCheckImages && isset($exportData['check_images_error']))
                    <div class="px-6 py-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-300 dark:border-yellow-700 rounded-lg">
                        <div class="flex items-start space-x-3">
                            <svg class="h-5 w-5 text-yellow-600 dark:text-yellow-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                            <div class="text-sm text-yellow-800 dark:text-yellow-200">
                                <div class="font-medium">Check Images Not Available</div>
                                <div class="mt-1">{{ $exportData['check_images_error'] }}</div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Close Button -->
                <div class="mt-6 pt-4 border-t border-gray-200 dark:border-zinc-600">
                    <button wire:click="closeDownloadModal"
                            class="w-full px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

            @endif
            <!-- End Gifts Tab -->

            <!-- Include the tab content files from Sage Intacct Configuration -->
            @include('livewire.partials.field-mappings-tab')
            @include('livewire.partials.code-mappings-tab')
            @include('livewire.partials.sync-history-tab')
            @include('livewire.partials.sync-filters-tab')

        </div>
        <!-- End Tab Content -->

    </div>
</div>
