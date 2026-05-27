{{--
    Bundle detail panel — items + service auto-apply links for a single bundle.
    Receives: $bundle, $items, $picker, $allServices, $canManage
--}}
<div>
    {{-- Back link + bundle title --}}
    <div class="flex items-center justify-between mb-4">
        <button wire:click="closeBundle" class="inline-flex items-center text-sm text-indigo-600 hover:underline dark:text-indigo-400">
            ← Back to bundles
        </button>
        <div class="text-right">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{ $bundle->name }}</h2>
            @if($bundle->description)
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 max-w-md text-right">{{ $bundle->description }}</p>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- LEFT: items in the bundle (2/3 width) --}}
        <div class="lg:col-span-2 bg-gray-50 dark:bg-zinc-900 rounded-md p-4 border border-gray-200 dark:border-zinc-700">
            <div class="flex items-baseline justify-between mb-3">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">Items in this bundle ({{ $items->count() }})</h3>
            </div>

            <div class="bg-white dark:bg-zinc-800 rounded border border-gray-200 dark:border-zinc-700 overflow-hidden">
                @forelse($items as $atom)
                    <div class="flex items-center gap-3 px-3 py-2 border-b border-gray-100 dark:border-zinc-700 last:border-b-0 {{ $atom->trashed() ? 'opacity-60' : '' }}">
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] uppercase font-mono bg-gray-100 dark:bg-zinc-700 text-gray-700 dark:text-gray-200">{{ $atom->kind }}</span>

                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $atom->label }}</div>
                            <div class="text-xs font-mono text-gray-500 dark:text-gray-400">{{ $atom->key }}</div>
                        </div>

                        @if($canManage)
                            {{-- required override --}}
                            <select wire:change="toggleItemRequired({{ $atom->id }}, $event.target.value === '' ? null : ($event.target.value === '1'))"
                                    class="text-xs rounded border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                                @php
                                    $current = $atom->pivot->required_override;
                                @endphp
                                <option value="" {{ $current === null ? 'selected' : '' }}>Default ({{ $atom->default_required ? 'required' : 'optional' }})</option>
                                <option value="1" {{ $current === 1 || $current === true ? 'selected' : '' }}>Required</option>
                                <option value="0" {{ $current === 0 || $current === false ? 'selected' : '' }}>Optional</option>
                            </select>

                            <div class="flex items-center gap-1">
                                <button wire:click="moveItem({{ $atom->id }}, 'up')" title="Move up"
                                        class="px-1.5 py-0.5 text-gray-500 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400 text-xs">↑</button>
                                <button wire:click="moveItem({{ $atom->id }}, 'down')" title="Move down"
                                        class="px-1.5 py-0.5 text-gray-500 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400 text-xs">↓</button>
                            </div>

                            <button wire:click="removeItem({{ $atom->id }})"
                                    wire:confirm="Remove this requirement item from the bundle? It stays in the library."
                                    class="text-red-600 hover:text-red-900 dark:text-red-400 text-xs">Remove</button>
                        @endif
                    </div>
                @empty
                    <div class="px-3 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                        No requirement items in this bundle yet. Add one from the picker on the right.
                    </div>
                @endforelse
            </div>

            {{-- Atom picker --}}
            @if($canManage)
                <div class="mt-4 bg-white dark:bg-zinc-800 rounded border border-gray-200 dark:border-zinc-700 p-3">
                    <h4 class="text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-2">Add a requirement item from the library</h4>
                    <div class="flex gap-2">
                        <input type="text" wire:model.live.debounce.250ms="itemPickerSearch"
                               placeholder="Search library…"
                               class="flex-1 rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                    </div>
                    @if($picker->isNotEmpty())
                        <div class="mt-2 max-h-48 overflow-y-auto border border-gray-100 dark:border-zinc-700 rounded">
                            @foreach($picker as $candidate)
                                <button wire:click="$set('newItemAtomId', {{ $candidate->id }})"
                                        type="button"
                                        class="flex items-center gap-2 w-full px-2 py-1.5 text-left text-sm hover:bg-indigo-50 dark:hover:bg-zinc-700 {{ $newItemAtomId == $candidate->id ? 'bg-indigo-50 dark:bg-zinc-700' : '' }}">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] uppercase font-mono bg-gray-100 dark:bg-zinc-700 text-gray-700 dark:text-gray-200">{{ $candidate->kind }}</span>
                                    <span class="text-gray-900 dark:text-white">{{ $candidate->label }}</span>
                                    <span class="text-gray-500 dark:text-gray-400 text-xs font-mono ml-auto">{{ $candidate->key }}</span>
                                </button>
                            @endforeach
                        </div>
                    @elseif($itemPickerSearch !== '')
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">No matches. Create the atom on the Atoms tab.</p>
                    @endif
                    @if($newItemAtomId)
                        <div class="mt-2 flex justify-end">
                            <button wire:click="addItem"
                                    class="px-3 py-1.5 rounded bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium">
                                Add to bundle
                            </button>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        {{-- RIGHT: service auto-apply links (1/3 width) --}}
        <div class="bg-gray-50 dark:bg-zinc-900 rounded-md p-4 border border-gray-200 dark:border-zinc-700">
            <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">Auto-apply when service is tagged</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                Tick the program services that should auto-stamp this bundle's atoms onto a Communication Log entry.
            </p>

            <div class="bg-white dark:bg-zinc-800 rounded border border-gray-200 dark:border-zinc-700 max-h-96 overflow-y-auto">
                @forelse($allServices as $svc)
                    <label class="flex items-center gap-2 px-3 py-2 border-b border-gray-100 dark:border-zinc-700 last:border-b-0 cursor-pointer hover:bg-gray-50 dark:hover:bg-zinc-700">
                        <input type="checkbox" value="{{ $svc->id }}"
                               wire:click="toggleServiceLink({{ $svc->id }})"
                               @checked(in_array($svc->id, $linkedServiceIds))
                               class="rounded border-gray-300 dark:border-zinc-600 text-indigo-600 focus:ring-indigo-500"
                               {{ $canManage ? '' : 'disabled' }} />
                        <div class="min-w-0">
                            <div class="text-sm text-gray-900 dark:text-white truncate">{{ $svc->name }}</div>
                            @if($svc->program)
                                <div class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $svc->program->name }}</div>
                            @endif
                        </div>
                    </label>
                @empty
                    <div class="px-3 py-6 text-center text-sm text-gray-500 dark:text-gray-400">No services in the system yet.</div>
                @endforelse
            </div>

            @if($canManage)
                <div class="mt-3 flex justify-end">
                    <button wire:click="saveServiceLinks"
                            class="px-3 py-1.5 rounded bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium">
                        Save service links
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>
