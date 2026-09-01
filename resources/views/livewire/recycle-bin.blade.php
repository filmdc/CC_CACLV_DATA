<div class="max-w-7xl mx-auto p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Recycle Bin</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            Call logs and projects deleted from the housing portal. Restoring one brings it back exactly as it was —
            timeline, requirements, milestones, budget and documents included.
        </p>
    </div>

    @if (session()->has('message'))
        <div class="rounded-md bg-green-50 dark:bg-green-900 p-4 mb-4">
            <p class="text-sm text-green-800 dark:text-green-200">{{ session('message') }}</p>
        </div>
    @endif

    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl p-6">

        {{-- Tabs --}}
        <div class="flex items-center gap-2 mb-4 border-b border-gray-200 dark:border-zinc-700">
            @foreach([['calls', 'Call logs', $callCount], ['projects', 'Projects', $projectCount]] as [$key, $label, $n])
                <button
                    type="button"
                    wire:click="setTab('{{ $key }}')"
                    class="px-4 py-2 text-sm font-medium border-b-2 -mb-px
                           {{ $tab === $key
                                ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
                                : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200' }}">
                    {{ $label }}
                    <span class="ml-1.5 rounded-full px-2 py-0.5 text-xs bg-gray-100 dark:bg-zinc-700 text-gray-700 dark:text-gray-300">{{ $n }}</span>
                </button>
            @endforeach
        </div>

        {{-- Search --}}
        <div class="mb-4">
            <input type="search"
                   wire:model.live.debounce.300ms="search"
                   placeholder="{{ $tab === 'projects' ? 'Search a job number or title…' : 'Search a name or subject…' }}"
                   class="w-full max-w-sm rounded-md border-gray-300 dark:border-zinc-600
                          dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
        </div>

        {{-- Rows --}}
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                <thead>
                    <tr>
                        @foreach(['Record', 'Who', 'Where', 'Deleted', 'Deleted by', ''] as $h)
                            <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ $h }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                    @forelse($rows as $row)
                        <tr>
                            <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">
                                {{ $row['title'] }}
                                @if($row['note'])
                                    <div class="text-xs font-normal mt-1 text-amber-700 dark:text-amber-400">{{ $row['note'] }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $row['who'] }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $row['where'] }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $row['deleted_at'] }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $row['deleted_by'] }}</td>
                            <td class="px-4 py-3 text-right">
                                <button
                                    type="button"
                                    wire:click="{{ $tab === 'projects' ? 'restoreProject' : 'restoreCall' }}({{ $row['id'] }})"
                                    wire:confirm="Restore this record and put it back in the housing portal?"
                                    class="rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-500">
                                    Restore
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                Nothing deleted here{{ $search !== '' ? ' matches that search' : '' }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $rows->links() }}
        </div>
    </div>
</div>
