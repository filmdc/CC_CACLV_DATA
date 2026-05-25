{{--
    Housing service detail panel — fund codes + bundles + direct atoms.
    Receives: $service, $allFundCodes, $allBundles, $allAtoms, $canManage
--}}
<div>
    <div class="flex items-center justify-between mb-4">
        <button wire:click="closeService" class="inline-flex items-center text-sm text-indigo-600 hover:underline dark:text-indigo-400">
            ← Back to services
        </button>
        <div class="text-right">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{ $service->name }}</h2>
            @if($service->description)
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 max-w-md text-right">{{ $service->description }}</p>
            @endif
        </div>
    </div>

    <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
        Picking this service on a Communication Log auto-attaches the linked fund codes (for budgeting + eligibility templates),
        and stamps every linked atom + bundle onto the log's intake requirements.
    </p>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        {{-- Fund codes --}}
        <div class="bg-gray-50 dark:bg-zinc-900 rounded-md p-4 border border-gray-200 dark:border-zinc-700">
            <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">Fund codes</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Auto-attached to the log's anticipated fund codes when this service is picked.</p>
            <div class="bg-white dark:bg-zinc-800 rounded border border-gray-200 dark:border-zinc-700 max-h-96 overflow-y-auto">
                @forelse($allFundCodes as $fc)
                    <label class="flex items-center gap-2 px-3 py-1.5 border-b border-gray-100 dark:border-zinc-700 last:border-b-0 cursor-pointer hover:bg-gray-50 dark:hover:bg-zinc-700">
                        <input type="checkbox" value="{{ $fc->id }}"
                               wire:click="toggleServiceFundCode({{ $fc->id }})"
                               @checked(in_array($fc->id, $serviceLinkedFundCodeIds))
                               class="rounded border-gray-300 dark:border-zinc-600 text-indigo-600 focus:ring-indigo-500"
                               {{ $canManage ? '' : 'disabled' }} />
                        <div class="min-w-0">
                            <span class="block font-mono text-xs text-gray-900 dark:text-white">{{ $fc->code }}</span>
                            <span class="block text-xs text-gray-500 dark:text-gray-400 truncate">{{ $fc->description ?? '' }}</span>
                        </div>
                    </label>
                @empty
                    <div class="px-3 py-4 text-center text-xs text-gray-500 dark:text-gray-400">No active fund codes.</div>
                @endforelse
            </div>
        </div>

        {{-- Bundles --}}
        <div class="bg-gray-50 dark:bg-zinc-900 rounded-md p-4 border border-gray-200 dark:border-zinc-700">
            <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">Requirement bundles</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Every atom in these bundles auto-stamps onto the log.</p>
            <div class="bg-white dark:bg-zinc-800 rounded border border-gray-200 dark:border-zinc-700 max-h-96 overflow-y-auto">
                @forelse($allBundles as $b)
                    <label class="flex items-center gap-2 px-3 py-1.5 border-b border-gray-100 dark:border-zinc-700 last:border-b-0 cursor-pointer hover:bg-gray-50 dark:hover:bg-zinc-700">
                        <input type="checkbox" value="{{ $b->id }}"
                               wire:click="toggleServiceBundle({{ $b->id }})"
                               @checked(in_array($b->id, $serviceLinkedBundleIds))
                               class="rounded border-gray-300 dark:border-zinc-600 text-indigo-600 focus:ring-indigo-500"
                               {{ $canManage ? '' : 'disabled' }} />
                        <span class="text-sm text-gray-900 dark:text-white truncate">{{ $b->name }}</span>
                    </label>
                @empty
                    <div class="px-3 py-4 text-center text-xs text-gray-500 dark:text-gray-400">No bundles defined yet.</div>
                @endforelse
            </div>
        </div>

        {{-- Direct atoms --}}
        <div class="bg-gray-50 dark:bg-zinc-900 rounded-md p-4 border border-gray-200 dark:border-zinc-700">
            <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">Direct atoms</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">One-off atoms attached without going through a bundle.</p>
            <div class="bg-white dark:bg-zinc-800 rounded border border-gray-200 dark:border-zinc-700 max-h-96 overflow-y-auto">
                @forelse($allAtoms as $a)
                    <label class="flex items-center gap-2 px-3 py-1.5 border-b border-gray-100 dark:border-zinc-700 last:border-b-0 cursor-pointer hover:bg-gray-50 dark:hover:bg-zinc-700">
                        <input type="checkbox" value="{{ $a->id }}"
                               wire:click="toggleServiceAtom({{ $a->id }})"
                               @checked(in_array($a->id, $serviceLinkedAtomIds))
                               class="rounded border-gray-300 dark:border-zinc-600 text-indigo-600 focus:ring-indigo-500"
                               {{ $canManage ? '' : 'disabled' }} />
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] uppercase font-mono bg-gray-100 dark:bg-zinc-700 text-gray-700 dark:text-gray-200">{{ $a->kind }}</span>
                        <div class="min-w-0">
                            <span class="block text-sm text-gray-900 dark:text-white truncate">{{ $a->label }}</span>
                            <span class="block text-xs font-mono text-gray-500 dark:text-gray-400 truncate">{{ $a->key }}</span>
                        </div>
                    </label>
                @empty
                    <div class="px-3 py-4 text-center text-xs text-gray-500 dark:text-gray-400">No atoms defined yet.</div>
                @endforelse
            </div>
        </div>
    </div>

    @if($canManage)
        <div class="mt-4 flex justify-end">
            <button wire:click="saveServiceLinks2"
                    class="px-4 py-2 rounded-md bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium">
                Save service links
            </button>
        </div>
    @endif
</div>
