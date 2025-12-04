<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <div class="bg-white dark:bg-zinc-800 shadow-lg rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-indigo-600 text-white p-6">
            <h1 class="text-2xl font-bold">Role & Permission Export/Import</h1>
            <p class="text-indigo-100 mt-2">Export roles to CSV or import from another instance</p>
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

        @if (!empty($importErrors))
            <div class="p-4 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-400">
                <h4 class="text-sm font-medium text-red-800 dark:text-red-200 mb-2">Import Errors:</h4>
                <ul class="list-disc list-inside text-sm text-red-700 dark:text-red-300">
                    @foreach ($importErrors as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (!empty($importSuccess))
            <div class="p-4 bg-green-50 dark:bg-green-900/20 border-l-4 border-green-400">
                <h4 class="text-sm font-medium text-green-800 dark:text-green-200 mb-2">Import Results:</h4>
                <ul class="list-disc list-inside text-sm text-green-700 dark:text-green-300">
                    @foreach ($importSuccess as $msg)
                        <li>{{ $msg }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Export Section -->
                <div class="border dark:border-zinc-600 rounded-lg p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                        <svg class="w-6 h-6 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                        Export Roles
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Select roles to export to a CSV file. The export will include role details and all assigned permissions.
                    </p>

                    <div class="mb-4 flex gap-2">
                        <button wire:click="selectAllRoles"
                                class="px-3 py-1 text-sm bg-gray-200 dark:bg-zinc-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-300 dark:hover:bg-zinc-600">
                            Select All
                        </button>
                        <button wire:click="deselectAllRoles"
                                class="px-3 py-1 text-sm bg-gray-200 dark:bg-zinc-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-300 dark:hover:bg-zinc-600">
                            Deselect All
                        </button>
                    </div>

                    <div class="max-h-80 overflow-y-auto border dark:border-zinc-600 rounded-lg mb-4">
                        @foreach ($allRoles as $role)
                            <label class="flex items-center p-3 hover:bg-gray-50 dark:hover:bg-zinc-700 border-b dark:border-zinc-600 last:border-b-0 cursor-pointer">
                                <input type="checkbox"
                                       wire:model="selectedRoles"
                                       value="{{ $role['id'] }}"
                                       class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                <div class="ml-3 flex-1">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $role['display_name'] ?? $role['name'] }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $role['name'] }} &bull; {{ count($role['permissions'] ?? []) }} permissions
                                    </div>
                                </div>
                            </label>
                        @endforeach
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">
                            {{ count($selectedRoles) }} role(s) selected
                        </span>
                        <button wire:click="exportRoles"
                                @if(empty($selectedRoles)) disabled @endif
                                class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:bg-gray-400 disabled:cursor-not-allowed">
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Export to CSV
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Import Section -->
                <div class="border dark:border-zinc-600 rounded-lg p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                        <svg class="w-6 h-6 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Import Roles
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Upload a CSV file exported from another instance. You can choose how to handle existing roles.
                    </p>

                    @if (!$showImportPreview)
                        <div class="border-2 border-dashed border-gray-300 dark:border-zinc-600 rounded-lg p-6 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                            </svg>
                            <div class="mt-4">
                                <label for="importFile" class="cursor-pointer">
                                    <span class="mt-2 block text-sm font-medium text-indigo-600 hover:text-indigo-500">
                                        Click to upload a CSV file
                                    </span>
                                    <input id="importFile"
                                           type="file"
                                           wire:model="importFile"
                                           accept=".csv,.txt"
                                           class="sr-only">
                                </label>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">CSV file up to 2MB</p>
                            </div>
                            @error('importFile')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <div wire:loading wire:target="importFile" class="mt-4">
                                <svg class="animate-spin h-6 w-6 mx-auto text-indigo-600" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">Processing file...</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Import Preview -->
            @if ($showImportPreview)
                <div class="mt-8 border dark:border-zinc-600 rounded-lg overflow-hidden">
                    <div class="bg-gray-50 dark:bg-zinc-700 px-6 py-4 border-b dark:border-zinc-600">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                Import Preview
                            </h3>
                            <button wire:click="cancelImport" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            Review the roles below and choose an action for each one.
                        </p>
                    </div>

                    <div class="divide-y divide-gray-200 dark:divide-zinc-600">
                        @foreach ($importPreview as $roleName => $preview)
                            <div class="p-4 {{ $preview['exists'] ? 'bg-yellow-50 dark:bg-yellow-900/10' : 'bg-green-50 dark:bg-green-900/10' }}">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center">
                                            <h4 class="text-base font-medium text-gray-900 dark:text-white">
                                                {{ $preview['data']['display_name'] ?: $roleName }}
                                            </h4>
                                            @if ($preview['exists'])
                                                <span class="ml-2 px-2 py-0.5 text-xs font-medium rounded-full bg-yellow-200 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-200">
                                                    Exists
                                                </span>
                                            @else
                                                <span class="ml-2 px-2 py-0.5 text-xs font-medium rounded-full bg-green-200 text-green-800 dark:bg-green-800 dark:text-green-200">
                                                    New
                                                </span>
                                            @endif
                                        </div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                            {{ $roleName }}
                                            @if ($preview['data']['description'])
                                                &bull; {{ $preview['data']['description'] }}
                                            @endif
                                        </p>

                                        <div class="mt-2 flex flex-wrap gap-2 text-xs">
                                            <span class="text-gray-600 dark:text-gray-400">
                                                {{ $preview['total_permissions'] }} permission(s) in file
                                            </span>
                                            @if ($preview['exists'])
                                                <span class="text-blue-600 dark:text-blue-400">
                                                    {{ count($preview['existing_permissions']) }} already assigned
                                                </span>
                                                <span class="text-green-600 dark:text-green-400">
                                                    {{ count($preview['new_permissions']) }} new
                                                </span>
                                            @endif
                                            @if (!empty($preview['missing_permissions']))
                                                <span class="text-red-600 dark:text-red-400">
                                                    {{ count($preview['missing_permissions']) }} not found in system
                                                </span>
                                            @endif
                                        </div>

                                        @if (!empty($preview['missing_permissions']))
                                            <div class="mt-2 text-xs text-red-600 dark:text-red-400">
                                                Missing: {{ implode(', ', array_slice($preview['missing_permissions'], 0, 5)) }}
                                                @if (count($preview['missing_permissions']) > 5)
                                                    ... and {{ count($preview['missing_permissions']) - 5 }} more
                                                @endif
                                            </div>
                                        @endif
                                    </div>

                                    <div class="ml-4">
                                        <select wire:model="importActions.{{ $roleName }}"
                                                class="text-sm border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                                            @if ($preview['exists'])
                                                <option value="skip">Skip (no changes)</option>
                                                <option value="merge">Merge permissions</option>
                                                <option value="replace">Replace permissions</option>
                                            @else
                                                <option value="create">Create role</option>
                                                <option value="skip">Skip</option>
                                            @endif
                                        </select>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="bg-gray-50 dark:bg-zinc-700 px-6 py-4 border-t dark:border-zinc-600 flex justify-end gap-3">
                        <button wire:click="cancelImport"
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-zinc-600 border border-gray-300 dark:border-zinc-500 rounded-md hover:bg-gray-50 dark:hover:bg-zinc-500">
                            Cancel
                        </button>
                        <button wire:click="executeImport"
                                class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700">
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Execute Import
                            </span>
                        </button>
                    </div>
                </div>
            @endif

            <!-- CSV Format Help -->
            <div class="mt-8 bg-gray-50 dark:bg-zinc-700 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">CSV Format Reference</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    The CSV file should have the following columns:
                </p>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b dark:border-zinc-600">
                                <th class="text-left py-2 px-3 font-medium text-gray-700 dark:text-gray-300">Column</th>
                                <th class="text-left py-2 px-3 font-medium text-gray-700 dark:text-gray-300">Required</th>
                                <th class="text-left py-2 px-3 font-medium text-gray-700 dark:text-gray-300">Description</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-zinc-600">
                            <tr>
                                <td class="py-2 px-3 font-mono text-indigo-600 dark:text-indigo-400">role_name</td>
                                <td class="py-2 px-3 text-green-600">Yes</td>
                                <td class="py-2 px-3 text-gray-600 dark:text-gray-400">Unique identifier for the role</td>
                            </tr>
                            <tr>
                                <td class="py-2 px-3 font-mono text-indigo-600 dark:text-indigo-400">role_display_name</td>
                                <td class="py-2 px-3 text-gray-500">No</td>
                                <td class="py-2 px-3 text-gray-600 dark:text-gray-400">Human-readable name</td>
                            </tr>
                            <tr>
                                <td class="py-2 px-3 font-mono text-indigo-600 dark:text-indigo-400">role_description</td>
                                <td class="py-2 px-3 text-gray-500">No</td>
                                <td class="py-2 px-3 text-gray-600 dark:text-gray-400">Description of the role</td>
                            </tr>
                            <tr>
                                <td class="py-2 px-3 font-mono text-indigo-600 dark:text-indigo-400">permission_name</td>
                                <td class="py-2 px-3 text-gray-500">No</td>
                                <td class="py-2 px-3 text-gray-600 dark:text-gray-400">Permission to assign (must exist in target system)</td>
                            </tr>
                            <tr>
                                <td class="py-2 px-3 font-mono text-indigo-600 dark:text-indigo-400">permission_display_name</td>
                                <td class="py-2 px-3 text-gray-500">No</td>
                                <td class="py-2 px-3 text-gray-600 dark:text-gray-400">For reference only</td>
                            </tr>
                            <tr>
                                <td class="py-2 px-3 font-mono text-indigo-600 dark:text-indigo-400">permission_description</td>
                                <td class="py-2 px-3 text-gray-500">No</td>
                                <td class="py-2 px-3 text-gray-600 dark:text-gray-400">For reference only</td>
                            </tr>
                            <tr>
                                <td class="py-2 px-3 font-mono text-indigo-600 dark:text-indigo-400">permission_category</td>
                                <td class="py-2 px-3 text-gray-500">No</td>
                                <td class="py-2 px-3 text-gray-600 dark:text-gray-400">For reference only</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-4">
                    Note: Each row represents one role-permission assignment. A role can have multiple rows (one per permission).
                </p>
            </div>
        </div>
    </div>
</div>
