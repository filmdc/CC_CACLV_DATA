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
