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
