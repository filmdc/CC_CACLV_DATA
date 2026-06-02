<div class="max-w-7xl mx-auto p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Activity Audit Log</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            Who changed what, when — with before/after values — across projects, milestones, requirements, budgets, expenses, and communication logs.
        </p>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-4 mb-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-3">
            <div class="lg:col-span-2">
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Search</label>
                <input type="text" wire:model.live.debounce.300ms="search"
                       placeholder="Description or changed values…"
                       class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">User</label>
                <select wire:model.live="userFilter"
                        class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                    <option value="">All users</option>
                    @foreach($causers as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Entity</label>
                <select wire:model.live="entityFilter"
                        class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                    <option value="">All entities</option>
                    @foreach($entityOptions as $label)
                        <option value="{{ $label }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Action</label>
                <select wire:model.live="eventFilter"
                        class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                    <option value="">All actions</option>
                    <option value="created">Created</option>
                    <option value="updated">Updated</option>
                    <option value="deleted">Deleted</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">&nbsp;</label>
                <button wire:click="clearFilters"
                        class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-50 dark:hover:bg-zinc-700">
                    Clear
                </button>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">From</label>
                <input type="date" wire:model.live="dateFrom"
                       class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">To</label>
                <input type="date" wire:model.live="dateTo"
                       class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                <thead class="bg-gray-50 dark:bg-zinc-900">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">When</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">User</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Entity</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Action</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Changes</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-700">
                    @forelse($activities as $activity)
                        @php
                            $entityLabel = $classToLabel[$activity->subject_type] ?? class_basename($activity->subject_type ?? '');
                            $diff = \App\Livewire\ActivityAuditLog::diffRows($activity);
                            $isExpanded = in_array($activity->id, $expanded, true);
                            $eventColors = [
                                'created' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                'updated' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                                'deleted' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                            ];
                            $eventClass = $eventColors[$activity->event] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300';
                        @endphp
                        <tr class="align-top">
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white whitespace-nowrap">
                                {{ $activity->created_at?->format('M d, Y') }}
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $activity->created_at?->format('g:i A') }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white whitespace-nowrap">
                                {{ $activity->causer?->name ?? 'System' }}
                            </td>
                            <td class="px-4 py-3 text-sm whitespace-nowrap">
                                <span class="text-gray-900 dark:text-white font-medium">{{ $entityLabel }}</span>
                                <div class="text-xs text-gray-500 dark:text-gray-400">#{{ $activity->subject_id }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm whitespace-nowrap">
                                <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium {{ $eventClass }}">
                                    {{ ucfirst($activity->event ?? '—') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                @if(empty($diff))
                                    <span class="text-gray-400 dark:text-gray-500 italic">No field changes recorded</span>
                                @else
                                    @if($isExpanded)
                                        <div class="space-y-1">
                                            @foreach($diff as $row)
                                                <div class="flex flex-wrap items-baseline gap-1.5">
                                                    <span class="font-medium text-gray-900 dark:text-white">{{ $row['field'] }}:</span>
                                                    @if($activity->event === 'created')
                                                        <span class="text-green-700 dark:text-green-400">{{ $row['new'] }}</span>
                                                    @elseif($activity->event === 'deleted')
                                                        <span class="text-red-700 dark:text-red-400 line-through">{{ $row['old'] }}</span>
                                                    @else
                                                        <span class="text-gray-500 dark:text-gray-400 line-through">{{ $row['old'] }}</span>
                                                        <span class="text-gray-400">→</span>
                                                        <span class="text-gray-900 dark:text-white">{{ $row['new'] }}</span>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                        <button wire:click="toggleExpanded({{ $activity->id }})"
                                                class="mt-1 text-xs text-indigo-600 dark:text-indigo-400 hover:underline">Hide</button>
                                    @else
                                        <span class="text-gray-600 dark:text-gray-400">
                                            {{ count($diff) }} field{{ count($diff) === 1 ? '' : 's' }} changed
                                        </span>
                                        <button wire:click="toggleExpanded({{ $activity->id }})"
                                                class="ml-2 text-xs text-indigo-600 dark:text-indigo-400 hover:underline"
                                                data-test="activity-expand">View before / after</button>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                No activity matches the current filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($activities->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 dark:border-zinc-700">
                {{ $activities->links() }}
            </div>
        @endif
    </div>
</div>
