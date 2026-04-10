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
                <button wire:click="setActiveTab('sync_filters')"
                        class="px-6 py-4 text-sm font-medium border-b-2 {{ $activeTab === 'sync_filters' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                    Sync Filters
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

                    @if($this->can('intacct.configure') || $this->isAdmin())
                    <!-- Authentication Method Selection (Admin Only) -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Authentication Method</h3>
                        <div class="bg-gray-50 dark:bg-zinc-700 p-4 rounded-lg">
                            <div class="space-y-3">
                                <label class="flex items-start space-x-3 cursor-pointer p-3 rounded-md hover:bg-gray-100 dark:hover:bg-zinc-600">
                                    <input type="radio"
                                           wire:model.live="auth_method"
                                           value="xml"
                                           class="mt-1 h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300">
                                    <div class="flex-1">
                                        <div class="font-medium text-gray-900 dark:text-white">XML Web Services (Recommended)</div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">Server-to-server authentication with Sender ID. Works behind firewalls. No user authorization needed.</div>
                                    </div>
                                </label>

                                <label class="flex items-start space-x-3 cursor-pointer p-3 rounded-md hover:bg-gray-100 dark:hover:bg-zinc-600">
                                    <input type="radio"
                                           wire:model.live="auth_method"
                                           value="oauth"
                                           class="mt-1 h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300">
                                    <div class="flex-1">
                                        <div class="font-medium text-gray-900 dark:text-white">OAuth 2.0 / REST API</div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">User-based authentication. Requires publicly accessible callback URL. Each user authorizes individually.</div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- XML Web Services Credentials Section -->
                    @if($auth_method === 'xml')
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">XML Web Services Credentials</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Configure your Sage Intacct Web Services credentials.</p>
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
                                <!-- Sender ID -->
                                <div>
                                    <label for="sender_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Sender ID <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text"
                                           wire:model="sender_id"
                                           id="sender_id"
                                           placeholder="Enter Web Services Sender ID"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Web Services License Sender ID from Sage</p>
                                    @error('sender_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <!-- Sender Password -->
                                <div>
                                    <label for="sender_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Sender Password <span class="text-red-500">*</span>
                                    </label>
                                    <input type="password"
                                           wire:model="sender_password"
                                           id="sender_password"
                                           placeholder="Enter Sender Password"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Web Services License Password (encrypted)</p>
                                    @error('sender_password') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Web Services User ID -->
                                <div>
                                    <label for="web_services_user_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Web Services User ID <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text"
                                           wire:model="web_services_user_id"
                                           id="web_services_user_id"
                                           placeholder="Enter Web Services User ID"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">User ID created in Sage Intacct</p>
                                    @error('web_services_user_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <!-- Web Services Password -->
                                <div>
                                    <label for="web_services_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Web Services Password <span class="text-red-500">*</span>
                                    </label>
                                    <input type="password"
                                           wire:model="web_services_password"
                                           id="web_services_password"
                                           placeholder="Enter Web Services Password"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">User password (encrypted)</p>
                                    @error('web_services_password') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- OAuth REST API Credentials Section -->
                    @if($auth_method === 'oauth')
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">OAuth REST API Credentials</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Configure your Sage Intacct REST API OAuth 2.0 credentials.</p>
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-md p-3 mb-4">
                            <p class="text-sm text-yellow-800 dark:text-yellow-300">
                                ⚠️ <strong>Note:</strong> OAuth requires a publicly accessible callback URL. This won't work if your server is behind a firewall.
                            </p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-700 p-4 rounded-lg space-y-4">
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
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">OAuth 2.0 Client ID from Sage Developer Portal</p>
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

                            <!-- Redirect URI -->
                            <div>
                                <label for="redirect_uri" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    OAuth Redirect URI <span class="text-red-500">*</span>
                                </label>
                                <input type="url"
                                       wire:model="redirect_uri"
                                       id="redirect_uri"
                                       readonly
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-600 dark:text-gray-300 rounded-md bg-gray-100">
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Configure this URL in your Sage Intacct OAuth app registration</p>
                                @error('redirect_uri') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Optional Dimensional Accounting (Both Methods) -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Dimensional Accounting (Optional)</h3>
                        <div class="bg-gray-50 dark:bg-zinc-700 p-4 rounded-lg">
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
                        </div>
                    </div>
                    @else
                    <!-- Read-Only View for Non-Admins -->
                    <div class="bg-gray-50 dark:bg-zinc-700 p-6 rounded-lg border-2 border-gray-300 dark:border-zinc-600">
                        <div class="flex items-start">
                            <svg class="h-6 w-6 text-gray-400 mr-3 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                            <div>
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white">API Configuration (Admin Only)</h4>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    API credentials and authentication settings are restricted to administrators for security reasons.
                                </p>
                                <div class="mt-3 text-sm text-gray-700 dark:text-gray-300">
                                    <p><strong>Current Setup:</strong></p>
                                    <ul class="mt-2 space-y-1 list-disc list-inside">
                                        <li>Authentication: <span class="font-mono text-xs bg-gray-200 dark:bg-zinc-600 px-2 py-1 rounded">{{ strtoupper($auth_method) }}</span></li>
                                        <li>Company ID: <span class="font-mono text-xs bg-gray-200 dark:bg-zinc-600 px-2 py-1 rounded">{{ $company_id }}</span></li>
                                        <li>Custom Object: <span class="font-mono text-xs bg-gray-200 dark:bg-zinc-600 px-2 py-1 rounded">{{ $custom_object_name }}</span></li>
                                        <li>Status: <span class="font-semibold {{ $is_active ? 'text-green-600 dark:text-green-400' : 'text-gray-500' }}">{{ $is_active ? 'Active' : 'Inactive' }}</span></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Sync Settings -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Sync Settings</h3>
                        <div class="bg-gray-50 dark:bg-zinc-700 p-4 rounded-lg space-y-6">

                            <!-- Sync Destination Mode -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                    Sync Destination
                                </label>
                                <div class="space-y-3">
                                    <label class="flex items-start space-x-3 cursor-pointer p-3 rounded-md hover:bg-gray-100 dark:hover:bg-zinc-600 border {{ $use_other_receipts ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : 'border-gray-200 dark:border-zinc-600' }}">
                                        <input type="radio"
                                               wire:model.live="use_other_receipts"
                                               value="1"
                                               class="mt-1 h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300">
                                        <div class="flex-1">
                                            <div class="font-medium text-gray-900 dark:text-white">Other Receipts (Recommended)</div>
                                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                                Creates standard Accounts Receivable entries. Best for most organizations.
                                                Check images are attached as Supporting Documents (SUPDOC).
                                            </div>
                                        </div>
                                    </label>

                                    <label class="flex items-start space-x-3 cursor-pointer p-3 rounded-md hover:bg-gray-100 dark:hover:bg-zinc-600 border {{ !$use_other_receipts ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : 'border-gray-200 dark:border-zinc-600' }}">
                                        <input type="radio"
                                               wire:model.live="use_other_receipts"
                                               value="0"
                                               class="mt-1 h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300">
                                        <div class="flex-1">
                                            <div class="font-medium text-gray-900 dark:text-white">Custom Object</div>
                                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                                Stores data in a custom Platform Services object. Requires manual setup in Sage Intacct.
                                                Check images use Document File attributes.
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Bank/Deposit Settings (Other Receipts mode only) -->
                            @if($use_other_receipts)
                            <div class="border-t border-gray-200 dark:border-zinc-600 pt-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                    Deposit Settings
                                </label>

                                <div class="space-y-4">
                                    <label class="flex items-center space-x-3 cursor-pointer">
                                        <input type="checkbox"
                                               wire:model.live="use_undeposited_funds"
                                               class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                        <div>
                                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Use Undeposited Funds Account</span>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">Post to an undeposited funds GL account instead of a bank account</p>
                                        </div>
                                    </label>

                                    @if($use_undeposited_funds)
                                    <div>
                                        <label for="undeposited_gl_account" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Undeposited Funds GL Account
                                        </label>
                                        <input type="text"
                                               wire:model="undeposited_gl_account"
                                               id="undeposited_gl_account"
                                               placeholder="10300"
                                               class="w-full max-w-xs px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">GL account number for undeposited funds (default: 10300)</p>
                                    </div>
                                    @else
                                    <div>
                                        <label for="bank_account_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Bank Account ID
                                        </label>
                                        <input type="text"
                                               wire:model="bank_account_id"
                                               id="bank_account_id"
                                               placeholder="Enter bank account ID"
                                               class="w-full max-w-xs px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Sage Intacct bank account ID for direct deposits</p>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @endif

                            <!-- Custom Object Name (Custom Object mode only) -->
                            @if(!$use_other_receipts)
                            <div class="border-t border-gray-200 dark:border-zinc-600 pt-4">
                                <label for="custom_object_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Custom Object Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text"
                                       wire:model="custom_object_name"
                                       id="custom_object_name"
                                       placeholder="Enter the custom object name in Sage Intacct"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    The name of the custom object in Sage Intacct where gift data will be stored.
                                    <strong>This object must already exist in your Sage Intacct account.</strong>
                                </p>
                                @error('custom_object_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            @endif

                            <!-- Check Image Attachment Settings -->
                            <div class="border-t border-gray-200 dark:border-zinc-600 pt-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                    Check Image Attachments
                                </label>

                                <div class="space-y-4">
                                    <label class="flex items-center space-x-3 cursor-pointer">
                                        <input type="checkbox"
                                               wire:model.live="include_check_images"
                                               class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                        <div>
                                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Include Check Images</span>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">Attach scanned check images from DonorPerfect to Sage Intacct records</p>
                                        </div>
                                    </label>

                                    @if($include_check_images)
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pl-7">
                                        <div>
                                            <label for="attachment_folder_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                Attachment Folder Name
                                            </label>
                                            <input type="text"
                                                   wire:model="attachment_folder_name"
                                                   id="attachment_folder_name"
                                                   placeholder="DP_Check_Images"
                                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Folder name in Sage Intacct for attachments</p>
                                        </div>

                                        <div>
                                            <label for="attachment_max_size_mb" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                Max Attachment Size (MB)
                                            </label>
                                            <input type="number"
                                                   wire:model="attachment_max_size_mb"
                                                   id="attachment_max_size_mb"
                                                   min="1"
                                                   max="50"
                                                   placeholder="10"
                                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Skip images larger than this size</p>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Custom Field Names -->
                            <div class="border-t border-gray-200 dark:border-zinc-600 pt-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                    Custom Field Names
                                </label>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="gift_id_field_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Gift ID Field Name
                                        </label>
                                        <input type="text"
                                               wire:model="gift_id_field_name"
                                               id="gift_id_field_name"
                                               placeholder="DP_GIFT_ID"
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Integration Name of the DP Gift ID custom field on Other Receipt in Sage Intacct</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Default Dimension Settings -->
                            <div class="border-t border-gray-200 dark:border-zinc-600 pt-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                    Default Dimensions
                                </label>

                                <div>
                                    <label for="default_service_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Default Service ID (Class)
                                    </label>
                                    <input type="text"
                                           wire:model="default_service_id"
                                           id="default_service_id"
                                           placeholder="S14999"
                                           class="w-full max-w-xs px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        Used when no sub-solicitation code mapping exists (departmentid dimension)
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- My OAuth Connection Status (OAuth Mode Only) -->
                    @if($auth_method === 'oauth')
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">My Connection</h3>
                        @php
                            $myToken = auth()->user()->sageIntacctToken;
                            $hasConnection = $myToken && $myToken->hasValidToken();
                        @endphp

                        @if($hasConnection)
                            <!-- Connected status -->
                            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-md p-4">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-green-800 dark:text-green-300">
                                            ✓ Connected to Sage Intacct
                                        </p>
                                        <p class="text-xs text-green-600 dark:text-green-400 mt-1">
                                            Company: {{ $myToken->company_id }}
                                        </p>
                                        <p class="text-xs text-green-600 dark:text-green-400">
                                            Token expires: {{ $myToken->expires_at->diffForHumans() }}
                                        </p>
                                    </div>
                                    <button type="button"
                                            wire:click="disconnectMyAccount"
                                            class="text-xs px-3 py-1 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-md border border-red-300 dark:border-red-700">
                                        Disconnect
                                    </button>
                                </div>
                            </div>
                        @else
                            <!-- Not connected -->
                            <div class="bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-md p-4">
                                <p class="text-sm text-orange-800 dark:text-orange-300 mb-2">
                                    You are not connected to Sage Intacct
                                </p>
                                <a href="{{ route('intacct.oauth.authorize') }}"
                                   class="inline-flex items-center px-4 py-2 bg-orange-600 text-white text-sm rounded-md hover:bg-orange-700">
                                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                                    </svg>
                                    Connect to Sage Intacct
                                </a>
                                <p class="text-xs text-orange-600 dark:text-orange-400 mt-2">
                                    You'll be redirected to Sage Intacct to authorize access
                                </p>
                            </div>
                        @endif
                    </div>
                    @endif

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

                    <!-- Action Buttons (Admin Only) -->
                    @if($this->can('intacct.configure') || $this->isAdmin())
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
                    @endif
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
                                    Gift Date From
                                </label>
                                <input type="date"
                                       wire:model.live="filterStartDate"
                                       id="filterStartDate"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>

                            <div>
                                <label for="filterEndDate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Gift Date To
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
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Gift Dates</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Synced At</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Records</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Success</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Failed</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-700">
                                    @foreach($syncHistory as $sync)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-zinc-700">
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                @if($sync->sync_start_date && $sync->sync_end_date)
                                                    @if($sync->sync_start_date == $sync->sync_end_date)
                                                        {{ \Carbon\Carbon::parse($sync->sync_start_date)->format('M d, Y') }}
                                                    @else
                                                        {{ \Carbon\Carbon::parse($sync->sync_start_date)->format('M d') }} - {{ \Carbon\Carbon::parse($sync->sync_end_date)->format('M d, Y') }}
                                                    @endif
                                                @else
                                                    <span class="text-gray-400 dark:text-gray-500">-</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                {{ $sync->started_at->format('M d, Y H:i') }}
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <div class="flex items-center space-x-2">
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

                                                    @if($sync->reset_at)
                                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-200"
                                                              title="Reset {{ $sync->reset_count > 1 ? $sync->reset_count . ' times, last' : '' }} on {{ $sync->reset_at->format('M d, Y g:i A') }}{{ $sync->resetBy ? ' by ' . $sync->resetBy->name : '' }}">
                                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                            </svg>
                                                            Reset{{ $sync->reset_count > 1 ? ' ×' . $sync->reset_count : '' }}
                                                        </span>
                                                    @endif
                                                </div>
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
                                            <td class="px-4 py-3 whitespace-nowrap text-sm">
                                                <a href="{{ route('intacct.sync-history.details', $sync->id) }}"
                                                   class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/20">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                    </svg>
                                                    View Details
                                                </a>
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
                    <div class="flex items-center space-x-2">
                        <button wire:click="showImportMappings"
                                class="px-3 py-1 text-sm bg-emerald-600 text-white rounded-md hover:bg-emerald-700"
                                wire:loading.attr="disabled">
                            Import CSV
                        </button>
                        <button wire:click="bulkArchiveUnmapped"
                                class="px-3 py-1 text-sm bg-gray-600 text-white rounded-md hover:bg-gray-700"
                                wire:loading.attr="disabled"
                                wire:confirm="Are you sure you want to archive all unmapped codes?">
                            Archive All Unmapped
                        </button>
                    </div>
                </div>

                <!-- Import CSV Modal -->
                @if($showImportModal)
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50" wire:click.self="cancelImport">
                    <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-lg w-full mx-4 shadow-xl">
                        <div class="mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                Import Code Mappings
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                Upload a CSV file to bulk-import mappings for
                                <span class="font-semibold">{{ str_replace('_', ' ', ucwords($selectedFieldType, '_')) }}</span>.
                            </p>
                        </div>

                        @php $isConditionalType = in_array($selectedFieldType, ['solicit_code', 'sub_solicit_code']); @endphp

                        @if($importResults)
                            <!-- Results Display -->
                            <div class="space-y-3 mb-4">
                                <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                                    <h4 class="text-sm font-semibold text-green-800 dark:text-green-300 mb-2">Import Complete</h4>
                                    <div class="grid grid-cols-3 gap-3 text-sm">
                                        <div class="text-center">
                                            <div class="text-2xl font-bold text-green-700 dark:text-green-400">{{ $importResults['created'] }}</div>
                                            <div class="text-green-600 dark:text-green-500">Created</div>
                                        </div>
                                        <div class="text-center">
                                            <div class="text-2xl font-bold text-blue-700 dark:text-blue-400">{{ $importResults['updated'] }}</div>
                                            <div class="text-blue-600 dark:text-blue-500">Updated</div>
                                        </div>
                                        <div class="text-center">
                                            <div class="text-2xl font-bold text-gray-500 dark:text-gray-400">{{ $importResults['skipped'] }}</div>
                                            <div class="text-gray-500 dark:text-gray-400">Skipped</div>
                                        </div>
                                    </div>
                                    @if(($importResults['conditional_created'] ?? 0) > 0 || ($importResults['conditional_updated'] ?? 0) > 0)
                                        <div class="mt-3 pt-3 border-t border-green-200 dark:border-green-800">
                                            <p class="text-xs font-medium text-green-700 dark:text-green-400 mb-1">Location-Specific Conditional Mappings:</p>
                                            <div class="grid grid-cols-2 gap-3 text-sm">
                                                <div class="text-center">
                                                    <div class="text-xl font-bold text-green-700 dark:text-green-400">{{ $importResults['conditional_created'] }}</div>
                                                    <div class="text-green-600 dark:text-green-500 text-xs">Created</div>
                                                </div>
                                                <div class="text-center">
                                                    <div class="text-xl font-bold text-blue-700 dark:text-blue-400">{{ $importResults['conditional_updated'] }}</div>
                                                    <div class="text-blue-600 dark:text-blue-500 text-xs">Updated</div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                @if(count($importResults['errors']) > 0)
                                    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                                        <h4 class="text-sm font-semibold text-red-800 dark:text-red-300 mb-1">Errors ({{ count($importResults['errors']) }})</h4>
                                        <ul class="text-xs text-red-700 dark:text-red-400 space-y-1 max-h-32 overflow-y-auto">
                                            @foreach($importResults['errors'] as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>
                            <div class="flex justify-end">
                                <button wire:click="cancelImport"
                                        class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700">
                                    Done
                                </button>
                            </div>
                        @else
                            <!-- Upload Form -->
                            <form wire:submit.prevent="importCodeMappings" class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">CSV File</label>
                                    <input type="file"
                                           wire:model="importFile"
                                           accept=".csv,.txt"
                                           class="block w-full text-sm text-gray-500 dark:text-gray-400
                                                  file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0
                                                  file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700
                                                  dark:file:bg-indigo-900/30 dark:file:text-indigo-400
                                                  hover:file:bg-indigo-100 dark:hover:file:bg-indigo-900/50">
                                    @error('importFile') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    <div wire:loading wire:target="importFile" class="text-sm text-indigo-600 dark:text-indigo-400 mt-1">Uploading...</div>
                                </div>

                                <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-3">
                                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">CSV Format</h4>
                                    @if($isConditionalType)
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                                            Required: <code class="bg-gray-200 dark:bg-zinc-600 px-1 rounded">intacct_location_code</code>, <code class="bg-gray-200 dark:bg-zinc-600 px-1 rounded">dp_code</code>, <code class="bg-gray-200 dark:bg-zinc-600 px-1 rounded">intacct_code</code>.
                                            Optional: <code class="bg-gray-200 dark:bg-zinc-600 px-1 rounded">priority</code>, <code class="bg-gray-200 dark:bg-zinc-600 px-1 rounded">notes</code>
                                        </p>
                                        <pre class="text-xs text-gray-600 dark:text-gray-400 bg-white dark:bg-zinc-800 p-2 rounded border dark:border-zinc-600 overflow-x-auto">intacct_location_code,dp_code,intacct_code,priority,notes
P1000,ANNLFUND,F8000,0,Annual fund
P1000,CAPITAL,F8100,0,Capital campaign
P1400,ANNLFUND,F9000,0,Different location</pre>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Each row maps a DP code to a Sage Intacct code for a specific location. Rows without a location code will be imported as standard code mappings instead.</p>
                                    @else
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Required columns: <code class="bg-gray-200 dark:bg-zinc-600 px-1 rounded">dp_code</code>, <code class="bg-gray-200 dark:bg-zinc-600 px-1 rounded">intacct_code</code>. Optional: <code class="bg-gray-200 dark:bg-zinc-600 px-1 rounded">notes</code></p>
                                        <pre class="text-xs text-gray-600 dark:text-gray-400 bg-white dark:bg-zinc-800 p-2 rounded border dark:border-zinc-600 overflow-x-auto">dp_code,intacct_code,notes
ANNLFUND,40100,Annual fund revenue
CAPITAL,40200,Capital campaign</pre>
                                    @endif
                                    <button type="button" wire:click="downloadImportTemplate"
                                            class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline mt-2">
                                        Download template CSV
                                    </button>
                                </div>

                                <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-zinc-700">
                                    <button type="button"
                                            wire:click="cancelImport"
                                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-zinc-700 border border-gray-300 dark:border-zinc-600 rounded-md hover:bg-gray-50 dark:hover:bg-zinc-600">
                                        Cancel
                                    </button>
                                    <button type="submit"
                                            class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-md hover:bg-emerald-700 disabled:opacity-50"
                                            wire:loading.attr="disabled"
                                            wire:target="importCodeMappings">
                                        <span wire:loading.remove wire:target="importCodeMappings">Import Mappings</span>
                                        <span wire:loading wire:target="importCodeMappings">Importing...</span>
                                    </button>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
                @endif

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

                <!-- Conditional Code Mappings Section (for solicit_code and sub_solicit_code only) -->
                @if(in_array($selectedFieldType, ['solicit_code', 'sub_solicit_code']))
                <div class="mt-8 border-t border-gray-200 dark:border-zinc-700 pt-6">
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Location-Specific Conditional Mappings</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                These conditional mappings enforce Sage Intacct location-specific dimensional compatibility rules.
                                If a location has conditional rules defined, only the codes listed below are allowed for that location.
                            </p>
                        </div>
                        <button wire:click="showCreateConditionalMappingForm"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 text-sm font-medium">
                            + Add New Rule
                        </button>
                    </div>

                    @php
                        $conditionalMappings = \App\Models\ConditionalCodeMapping::where('field_type', $selectedFieldType)
                            ->orderBy('intacct_location_code')
                            ->orderBy('priority', 'desc')
                            ->get()
                            ->groupBy('intacct_location_code');
                    @endphp

                    @if($conditionalMappings->count() > 0)
                        <div class="space-y-6">
                            @foreach($conditionalMappings as $locationCode => $mappings)
                            <div class="border border-gray-200 dark:border-zinc-700 rounded-lg p-4 bg-gray-50 dark:bg-zinc-900/50">
                                <div class="mb-3">
                                    <h4 class="text-md font-semibold text-gray-900 dark:text-white">
                                        Intacct Location: <span class="font-mono text-indigo-600 dark:text-indigo-400">{{ $locationCode }}</span>
                                    </h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        {{ $mappings->count() }} {{ $selectedFieldType === 'solicit_code' ? 'solicitation' : 'sub-solicitation' }} code(s) allowed for this location
                                    </p>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                                        <thead class="bg-white dark:bg-zinc-800">
                                            <tr>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">DP Code</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">→ Intacct Code</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Priority</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Notes</th>
                                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-700">
                                            @foreach($mappings as $mapping)
                                            <tr class="{{ $mapping->is_active ? '' : 'opacity-50' }}">
                                                <td class="px-4 py-2 text-sm text-gray-900 dark:text-white font-mono">
                                                    {{ $mapping->dp_code }}
                                                </td>
                                                <td class="px-4 py-2 text-sm text-gray-900 dark:text-white font-mono">
                                                    <span class="text-green-600 dark:text-green-400">{{ $mapping->intacct_code }}</span>
                                                </td>
                                                <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">
                                                    {{ $mapping->priority }}
                                                </td>
                                                <td class="px-4 py-2">
                                                    @if($mapping->is_active)
                                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                                            Active
                                                        </span>
                                                    @else
                                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                                            Inactive
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">
                                                    {{ $mapping->notes ? Str::limit($mapping->notes, 40) : '—' }}
                                                </td>
                                                <td class="px-4 py-2 text-right space-x-2">
                                                    <button wire:click="showEditConditionalMappingForm({{ $mapping->id }})"
                                                            class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 text-sm">
                                                        Edit
                                                    </button>
                                                    <button wire:click="toggleConditionalMappingActive({{ $mapping->id }})"
                                                            class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300 text-sm">
                                                        {{ $mapping->is_active ? 'Deactivate' : 'Activate' }}
                                                    </button>
                                                    <button wire:click="deleteConditionalMapping({{ $mapping->id }})"
                                                            wire:confirm="Are you sure you want to delete this conditional mapping?"
                                                            class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 text-sm">
                                                        Delete
                                                    </button>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8 bg-gray-50 dark:bg-zinc-900/50 rounded-lg border border-gray-200 dark:border-zinc-700">
                            <p class="text-gray-500 dark:text-gray-400">
                                No conditional mappings configured for {{ $selectedFieldType === 'solicit_code' ? 'Solicitation Code' : 'Sub-Solicitation Code' }}.
                            </p>
                            <p class="text-sm text-gray-400 dark:text-gray-500 mt-2">
                                Click "Add New Rule" to create your first conditional mapping.
                            </p>
                        </div>
                    @endif
                </div>

                <!-- Conditional Mapping Form Modal -->
                @if($showConditionalMappingForm)
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50" wire:click.self="cancelConditionalMappingForm">
                    <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-2xl w-full mx-4 shadow-xl">
                        <div class="mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ $editingConditionalMappingId ? 'Edit' : 'Create' }} Conditional Mapping
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                Define a location-specific code mapping rule to enforce Sage Intacct dimensional compatibility for {{ $selectedFieldType === 'solicit_code' ? 'solicitation codes' : 'sub-solicitation codes' }}.
                            </p>
                        </div>

                        <form wire:submit.prevent="saveConditionalMapping" class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <!-- Intacct Location Code -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Intacct Location Code <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text"
                                           wire:model="conditional_intacct_location_code"
                                           class="w-full px-3 py-2 border rounded-md dark:bg-zinc-700 dark:border-zinc-600 dark:text-white focus:ring-indigo-500 focus:border-indigo-500"
                                           placeholder="e.g., P1000, P1400, P1600"
                                           {{ $editingConditionalMappingId ? 'disabled' : '' }}>
                                    @error('conditional_intacct_location_code') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Sage Intacct location/program code</p>
                                </div>

                                <!-- Field Type -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Field Type <span class="text-red-500">*</span>
                                    </label>
                                    <select wire:model="conditional_field_type"
                                            class="w-full px-3 py-2 border rounded-md dark:bg-zinc-700 dark:border-zinc-600 dark:text-white focus:ring-indigo-500 focus:border-indigo-500"
                                            {{ $editingConditionalMappingId ? 'disabled' : '' }}>
                                        <option value="solicit_code">Solicitation Code</option>
                                        <option value="sub_solicit_code">Sub-Solicitation Code</option>
                                    </select>
                                    @error('conditional_field_type') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <!-- DP Code -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        DonorPerfect Code <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text"
                                           wire:model="conditional_dp_code"
                                           class="w-full px-3 py-2 border rounded-md dark:bg-zinc-700 dark:border-zinc-600 dark:text-white focus:ring-indigo-500 focus:border-indigo-500"
                                           placeholder="e.g., ANNLFUND, FBHPAPPL"
                                           {{ $editingConditionalMappingId ? 'disabled' : '' }}>
                                    @error('conditional_dp_code') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Code value from DonorPerfect</p>
                                </div>

                                <!-- Intacct Code -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Sage Intacct Code <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text"
                                           wire:model="conditional_intacct_code"
                                           class="w-full px-3 py-2 border rounded-md dark:bg-zinc-700 dark:border-zinc-600 dark:text-white focus:ring-indigo-500 focus:border-indigo-500"
                                           placeholder="e.g., F8000, S14999">
                                    @error('conditional_intacct_code') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Mapped Sage Intacct code</p>
                                </div>

                                <!-- Priority -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Priority
                                    </label>
                                    <input type="number"
                                           wire:model="conditional_priority"
                                           min="0"
                                           class="w-full px-3 py-2 border rounded-md dark:bg-zinc-700 dark:border-zinc-600 dark:text-white focus:ring-indigo-500 focus:border-indigo-500"
                                           placeholder="0">
                                    @error('conditional_priority') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Higher priority = checked first</p>
                                </div>

                                <!-- Active Status -->
                                <div class="flex items-center pt-6">
                                    <label class="flex items-center space-x-2 cursor-pointer">
                                        <input type="checkbox"
                                               wire:model="conditional_is_active"
                                               class="rounded border-gray-300 dark:border-zinc-600 text-indigo-600 focus:ring-indigo-500">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Active</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Notes -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Notes
                                </label>
                                <textarea wire:model="conditional_notes"
                                          rows="2"
                                          class="w-full px-3 py-2 border rounded-md dark:bg-zinc-700 dark:border-zinc-600 dark:text-white focus:ring-indigo-500 focus:border-indigo-500"
                                          placeholder="Business logic explanation for this rule..."></textarea>
                                @error('conditional_notes') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <!-- Form Actions -->
                            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-zinc-700">
                                <button type="button"
                                        wire:click="cancelConditionalMappingForm"
                                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-zinc-700 border border-gray-300 dark:border-zinc-600 rounded-md hover:bg-gray-50 dark:hover:bg-zinc-600">
                                    Cancel
                                </button>
                                <button type="submit"
                                        class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700">
                                    {{ $editingConditionalMappingId ? 'Update' : 'Create' }} Mapping
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                @endif
                @endif
            </div>
            @endif

            <!-- Sync Filters Tab -->
            @if($activeTab === 'sync_filters')
            <div class="space-y-6">
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Sync Filters</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                Define rules to exclude certain gifts from syncing to Sage Intacct (e.g., soft credits, in-kind donations, declined transactions).
                                Filtered gifts are logged but not sent to Intacct.
                            </p>
                        </div>
                        <button wire:click="showCreateSyncFilterForm"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 text-sm font-medium">
                            + Add Filter
                        </button>
                    </div>
                </div>

                @php
                    $syncFilters = \App\Models\SageIntacctSyncFilter::orderBy('priority', 'desc')->orderBy('name')->get();
                @endphp

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                        <thead class="bg-gray-50 dark:bg-zinc-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Rule</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Action</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Priority</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-700">
                            @forelse($syncFilters as $filter)
                            <tr class="{{ $filter->is_active ? '' : 'opacity-50' }}">
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                    {{ $filter->name }}
                                    @if($filter->description)
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $filter->description }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm font-mono text-gray-900 dark:text-white">
                                    {{ $filter->field_name }} {{ $filter->operator }} {{ $filter->value }}
                                </td>
                                <td class="px-6 py-4">
                                    @if($filter->action === 'exclude')
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300">
                                            Exclude
                                        </span>
                                    @else
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                            Include
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $filter->priority }}
                                </td>
                                <td class="px-6 py-4">
                                    @if($filter->is_active)
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                            Inactive
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <button wire:click="showEditSyncFilterForm({{ $filter->id }})"
                                            class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 text-sm">
                                        Edit
                                    </button>
                                    <button wire:click="toggleSyncFilterActive({{ $filter->id }})"
                                            class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300 text-sm">
                                        {{ $filter->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                    <button wire:click="deleteSyncFilter({{ $filter->id }})"
                                            wire:confirm="Are you sure you want to delete this sync filter?"
                                            class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 text-sm">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                    No sync filters configured. Click "Add Filter" to create your first filter rule.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Sync Filter Form Modal -->
                @if($showSyncFilterForm)
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50" wire:click.self="cancelSyncFilterForm">
                    <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-2xl w-full mx-4 shadow-xl">
                        <div class="mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ $editingSyncFilterId ? 'Edit' : 'Create' }} Sync Filter
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                Define a rule to exclude or include gifts based on field values.
                            </p>
                        </div>

                        <form wire:submit.prevent="saveSyncFilter" class="space-y-4">
                            <!-- Filter Name -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Filter Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text"
                                       wire:model="sync_filter_name"
                                       class="w-full px-3 py-2 border rounded-md dark:bg-zinc-700 dark:border-zinc-600 dark:text-white focus:ring-indigo-500 focus:border-indigo-500"
                                       placeholder="e.g., Exclude Soft Credits">
                                @error('sync_filter_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div class="grid grid-cols-3 gap-4">
                                <!-- Field Name -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Field Name <span class="text-red-500">*</span>
                                    </label>
                                    <select wire:model="sync_filter_field_name"
                                            class="w-full px-3 py-2 border rounded-md dark:bg-zinc-700 dark:border-zinc-600 dark:text-white focus:ring-indigo-500 focus:border-indigo-500">
                                        <option value="">Select field...</option>
                                        <option value="gift_type">gift_type</option>
                                        <option value="campaign">campaign</option>
                                        <option value="solicit_code">solicit_code</option>
                                        <option value="sub_solicit_code">sub_solicit_code</option>
                                        <option value="amount">amount</option>
                                        <option value="gl_code">gl_code</option>
                                        <option value="gift_date">gift_date</option>
                                        <option value="payment_type">payment_type</option>
                                    </select>
                                    @error('sync_filter_field_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <!-- Operator -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Operator <span class="text-red-500">*</span>
                                    </label>
                                    <select wire:model="sync_filter_operator"
                                            class="w-full px-3 py-2 border rounded-md dark:bg-zinc-700 dark:border-zinc-600 dark:text-white focus:ring-indigo-500 focus:border-indigo-500">
                                        <option value="equals">Equals</option>
                                        <option value="not_equals">Not Equals</option>
                                        <option value="contains">Contains</option>
                                        <option value="not_contains">Not Contains</option>
                                        <option value="greater_than">Greater Than</option>
                                        <option value="less_than">Less Than</option>
                                        <option value="greater_or_equal">Greater or Equal</option>
                                        <option value="less_or_equal">Less or Equal</option>
                                        <option value="is_empty">Is Empty</option>
                                        <option value="is_not_empty">Is Not Empty</option>
                                    </select>
                                    @error('sync_filter_operator') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <!-- Value -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Value
                                    </label>
                                    <input type="text"
                                           wire:model="sync_filter_value"
                                           class="w-full px-3 py-2 border rounded-md dark:bg-zinc-700 dark:border-zinc-600 dark:text-white focus:ring-indigo-500 focus:border-indigo-500"
                                           placeholder="e.g., SC, 100"
                                           :disabled="['is_empty', 'is_not_empty'].includes($wire.sync_filter_operator)">
                                    @error('sync_filter_value') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Not required for 'is empty' operators</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <!-- Action -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Action <span class="text-red-500">*</span>
                                    </label>
                                    <select wire:model="sync_filter_action"
                                            class="w-full px-3 py-2 border rounded-md dark:bg-zinc-700 dark:border-zinc-600 dark:text-white focus:ring-indigo-500 focus:border-indigo-500">
                                        <option value="exclude">Exclude (skip matching gifts)</option>
                                        <option value="include">Include (only sync matching gifts)</option>
                                    </select>
                                    @error('sync_filter_action') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <!-- Priority -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Priority
                                    </label>
                                    <input type="number"
                                           wire:model="sync_filter_priority"
                                           min="0"
                                           class="w-full px-3 py-2 border rounded-md dark:bg-zinc-700 dark:border-zinc-600 dark:text-white focus:ring-indigo-500 focus:border-indigo-500"
                                           placeholder="0">
                                    @error('sync_filter_priority') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Higher priority filters evaluated first</p>
                                </div>
                            </div>

                            <!-- Description -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Description
                                </label>
                                <textarea wire:model="sync_filter_description"
                                          rows="2"
                                          class="w-full px-3 py-2 border rounded-md dark:bg-zinc-700 dark:border-zinc-600 dark:text-white focus:ring-indigo-500 focus:border-indigo-500"
                                          placeholder="Explain why this filter exists..."></textarea>
                                @error('sync_filter_description') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <!-- Active Status -->
                            <div class="flex items-center">
                                <label class="flex items-center space-x-2 cursor-pointer">
                                    <input type="checkbox"
                                           wire:model="sync_filter_is_active"
                                           class="rounded border-gray-300 dark:border-zinc-600 text-indigo-600 focus:ring-indigo-500">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Active</span>
                                </label>
                            </div>

                            <!-- Form Actions -->
                            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-zinc-700">
                                <button type="button"
                                        wire:click="cancelSyncFilterForm"
                                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-zinc-700 border border-gray-300 dark:border-zinc-600 rounded-md hover:bg-gray-50 dark:hover:bg-zinc-600">
                                    Cancel
                                </button>
                                <button type="submit"
                                        class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700">
                                    {{ $editingSyncFilterId ? 'Update' : 'Create' }} Filter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                @endif
            </div>
            @endif
        </div>
    </div>
</div>
