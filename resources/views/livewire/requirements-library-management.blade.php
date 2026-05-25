<div class="max-w-7xl mx-auto p-6">
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl p-6">
        {{-- Page header --}}
        <div class="flex flex-wrap items-end justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Requirements Library</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                    Curate the master list of requirement items (atoms) and group them into named bundles.
                    Bundles linked to a program service auto-instantiate on any Communication Log entry tagged with that service.
                </p>
            </div>

            @if($canManage)
                <div class="flex gap-2">
                    @if($activeTab === 'atoms')
                        <button type="button" wire:click="showCreateAtom"
                                class="inline-flex items-center px-4 py-2 rounded-md bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium">
                            + New atom
                        </button>
                    @elseif($activeTab === 'bundles')
                        <button type="button" wire:click="showCreateBundle"
                                class="inline-flex items-center px-4 py-2 rounded-md bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium">
                            + New bundle
                        </button>
                    @elseif($activeTab === 'services')
                        <button type="button" wire:click="showCreateService"
                                class="inline-flex items-center px-4 py-2 rounded-md bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium">
                            + New service
                        </button>
                    @endif
                </div>
            @endif
        </div>

        {{-- Flash --}}
        @if (session()->has('message'))
            <div class="rounded-md bg-green-50 dark:bg-green-900/40 border border-green-200 dark:border-green-700 p-3 mb-4 text-sm text-green-800 dark:text-green-200">
                {{ session('message') }}
            </div>
        @endif
        @if (session()->has('error'))
            <div class="rounded-md bg-red-50 dark:bg-red-900/40 border border-red-200 dark:border-red-700 p-3 mb-4 text-sm text-red-800 dark:text-red-200">
                {{ session('error') }}
            </div>
        @endif

        {{-- Tab bar --}}
        <div class="border-b border-gray-200 dark:border-zinc-700 mb-4">
            <nav class="flex gap-6" aria-label="Tabs">
                <button type="button" wire:click="setTab('atoms')"
                        class="py-3 px-1 -mb-px border-b-2 text-sm font-medium {{ $activeTab === 'atoms' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200' }}">
                    Atoms
                </button>
                <button type="button" wire:click="setTab('bundles')"
                        class="py-3 px-1 -mb-px border-b-2 text-sm font-medium {{ $activeTab === 'bundles' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200' }}">
                    Bundles
                </button>
                <button type="button" wire:click="setTab('services')"
                        class="py-3 px-1 -mb-px border-b-2 text-sm font-medium {{ $activeTab === 'services' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200' }}">
                    Services
                </button>
            </nav>
        </div>

        {{-- Service detail drill-down ────────────────────────── --}}
        @if($activeTab === 'services' && $editingServiceId && $housingServiceDetail)
            @include('livewire.partials.requirements-library-service-detail', [
                'service' => $housingServiceDetail,
                'allFundCodes' => $serviceAllFundCodes,
                'allBundles' => $serviceAllBundles,
                'allAtoms' => $serviceAllAtoms,
                'canManage' => $canManage,
            ])

        {{-- Bundle detail drill-down ─────────────────────────── --}}
        @elseif($activeTab === 'bundles' && $editingBundleId && $bundleDetail)
            @include('livewire.partials.requirements-library-bundle-detail', [
                'bundle' => $bundleDetail,
                'items' => $bundleItems,
                'picker' => $atomsForPicker,
                'allServices' => $allServices,
                'canManage' => $canManage,
            ])
        @else
            {{-- Search + filter row ───────────────────────────── --}}
            <div class="flex flex-wrap items-center gap-3 mb-4">
                <input type="text" wire:model.live.debounce.300ms="search"
                       placeholder="{{ $activeTab === 'atoms' ? 'Search label or key…' : 'Search bundle name…' }}"
                       class="flex-1 min-w-[200px] rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500" />

                @if($activeTab === 'atoms')
                    <select wire:model.live="kindFilter"
                            class="rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        <option value="">All kinds</option>
                        @foreach($kinds as $k)
                            <option value="{{ $k }}">{{ ucfirst($k) }}</option>
                        @endforeach
                    </select>
                @endif

                <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                    <input type="checkbox" wire:model.live="showTrashed"
                           class="rounded border-gray-300 dark:border-zinc-600 text-indigo-600 focus:ring-indigo-500" />
                    Show deleted
                </label>
            </div>

            {{-- Atoms table ────────────────────────────────────── --}}
            @if($activeTab === 'atoms')
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                        <thead class="bg-gray-50 dark:bg-zinc-900">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Label</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Key</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kind</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Default required</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Hint</th>
                                <th class="px-3 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-700">
                            @forelse($atoms as $atom)
                                <tr class="{{ $atom->trashed() ? 'opacity-60' : '' }}">
                                    <td class="px-3 py-2 text-sm text-gray-900 dark:text-white">
                                        {{ $atom->label }}
                                        @if($atom->trashed())
                                            <span class="ml-2 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">deleted</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-sm font-mono text-gray-600 dark:text-gray-300">{{ $atom->key }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-700 dark:text-gray-200">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-gray-100 dark:bg-zinc-700">{{ $atom->kind }}</span>
                                    </td>
                                    <td class="px-3 py-2 text-sm text-gray-700 dark:text-gray-200">
                                        {{ $atom->default_required ? 'Required' : 'Optional' }}
                                    </td>
                                    <td class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400 max-w-md truncate">{{ $atom->hint }}</td>
                                    <td class="px-3 py-2 text-sm text-right whitespace-nowrap">
                                        @if($canManage)
                                            @if($atom->trashed())
                                                <button wire:click="restoreAtom({{ $atom->id }})"
                                                        class="text-green-600 hover:text-green-900 dark:text-green-400">Restore</button>
                                            @else
                                                <button wire:click="showEditAtom({{ $atom->id }})"
                                                        class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 mr-3">Edit</button>
                                                <button wire:click="deleteAtom({{ $atom->id }})"
                                                        wire:confirm="Move this atom to trash? Historical instances will keep their reference."
                                                        class="text-red-600 hover:text-red-900 dark:text-red-400">Delete</button>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-3 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No atoms found. Click "+ New atom" to start your library.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $atoms->links() }}</div>
            @endif

            {{-- Bundles table ──────────────────────────────────── --}}
            @if($activeTab === 'bundles')
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                        <thead class="bg-gray-50 dark:bg-zinc-900">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Bundle</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Items</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Linked services</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Description</th>
                                <th class="px-3 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-700">
                            @forelse($bundles as $bundle)
                                <tr class="{{ $bundle->trashed() ? 'opacity-60' : '' }}">
                                    <td class="px-3 py-2 text-sm text-gray-900 dark:text-white">
                                        <button wire:click="openBundle({{ $bundle->id }})" class="text-indigo-600 hover:underline dark:text-indigo-400">
                                            {{ $bundle->name }}
                                        </button>
                                        @if($bundle->trashed())
                                            <span class="ml-2 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">deleted</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-sm text-gray-700 dark:text-gray-200">{{ $bundle->items_count }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-700 dark:text-gray-200">{{ $bundle->services_count }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400 max-w-md truncate">{{ $bundle->description }}</td>
                                    <td class="px-3 py-2 text-sm text-right whitespace-nowrap">
                                        @if($canManage)
                                            @if($bundle->trashed())
                                                <button wire:click="restoreBundle({{ $bundle->id }})"
                                                        class="text-green-600 hover:text-green-900 dark:text-green-400">Restore</button>
                                            @else
                                                <button wire:click="showEditBundle({{ $bundle->id }})"
                                                        class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 mr-3">Rename</button>
                                                <button wire:click="openBundle({{ $bundle->id }})"
                                                        class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 mr-3">Manage</button>
                                                <button wire:click="deleteBundle({{ $bundle->id }})"
                                                        wire:confirm="Move this bundle to trash? Items inside it stay in the library."
                                                        class="text-red-600 hover:text-red-900 dark:text-red-400">Delete</button>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-3 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No bundles yet. Click "+ New bundle" to group atoms.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $bundles->links() }}</div>
            @endif

            {{-- Services table ────────────────────────────────── --}}
            @if($activeTab === 'services')
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                        <thead class="bg-gray-50 dark:bg-zinc-900">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Service</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Fund codes</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Bundles</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Direct atoms</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Active</th>
                                <th class="px-3 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-700">
                            @forelse($housingServices as $svc)
                                <tr class="{{ $svc->trashed() ? 'opacity-60' : '' }}">
                                    <td class="px-3 py-2 text-sm text-gray-900 dark:text-white">
                                        <button wire:click="openService({{ $svc->id }})" class="text-indigo-600 hover:underline dark:text-indigo-400">
                                            {{ $svc->name }}
                                        </button>
                                        @if($svc->description)
                                            <div class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-md">{{ $svc->description }}</div>
                                        @endif
                                        @if($svc->trashed())
                                            <span class="ml-2 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">deleted</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-sm text-gray-700 dark:text-gray-200">{{ $svc->fund_codes_count }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-700 dark:text-gray-200">{{ $svc->template_sets_count }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-700 dark:text-gray-200">{{ $svc->requirement_definitions_count }}</td>
                                    <td class="px-3 py-2 text-sm">
                                        @if($svc->is_active)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Active</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-600 dark:bg-zinc-700 dark:text-gray-300">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-sm text-right whitespace-nowrap">
                                        @if($canManage)
                                            @if($svc->trashed())
                                                <button wire:click="restoreService({{ $svc->id }})"
                                                        class="text-green-600 hover:text-green-900 dark:text-green-400">Restore</button>
                                            @else
                                                <button wire:click="showEditService({{ $svc->id }})"
                                                        class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 mr-3">Rename</button>
                                                <button wire:click="openService({{ $svc->id }})"
                                                        class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 mr-3">Manage</button>
                                                <button wire:click="deleteService({{ $svc->id }})"
                                                        wire:confirm="Move this service to trash? Historical comm-log references are preserved."
                                                        class="text-red-600 hover:text-red-900 dark:text-red-400">Delete</button>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-3 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No services yet. Click "+ New service" to start the catalog.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $housingServices->links() }}</div>
            @endif
        @endif
    </div>

    {{-- Service modal ────────────────────────────────────────── --}}
    @if($showServiceForm)
        <div class="fixed inset-0 bg-gray-500/75 dark:bg-black/70 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-lg w-full shadow-xl">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    {{ $serviceFormId ? 'Rename service' : 'New service' }}
                </h2>

                <form wire:submit.prevent="saveService" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Name</label>
                        <input type="text" wire:model="serviceName"
                               placeholder="First-Time Homebuyer Counseling"
                               class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500" />
                        @error('serviceName') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Description <span class="text-xs text-gray-500 font-normal">(optional)</span></label>
                        <textarea wire:model="serviceDescription" rows="3"
                                  class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    </div>

                    <div>
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                            <input type="checkbox" wire:model="serviceActive"
                                   class="rounded border-gray-300 dark:border-zinc-600 text-indigo-600 focus:ring-indigo-500" />
                            Active (selectable on Communication Log entries)
                        </label>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="$set('showServiceForm', false)"
                                class="px-4 py-2 rounded-md border border-gray-300 dark:border-zinc-600 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-zinc-700">Cancel</button>
                        <button type="submit"
                                class="px-4 py-2 rounded-md bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium">
                            {{ $serviceFormId ? 'Save' : 'Create & manage links' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Atom modal ──────────────────────────────────────────── --}}
    @if($showAtomForm)
        <div class="fixed inset-0 bg-gray-500/75 dark:bg-black/70 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-lg w-full shadow-xl">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    {{ $atomFormId ? 'Edit atom' : 'New atom' }}
                </h2>

                <form wire:submit.prevent="saveAtom" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Label</label>
                        <input type="text" wire:model.live.debounce.300ms="atomLabel"
                               placeholder="Photo ID"
                               class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500" />
                        @error('atomLabel') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                            Key
                            <span class="text-xs text-gray-500 dark:text-gray-400 font-normal">(dedupe slug — auto-suggested from label)</span>
                        </label>
                        <input type="text" wire:model="atomKey"
                               placeholder="photo-id"
                               class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white font-mono text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        @error('atomKey') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Kind</label>
                            <select wire:model="atomKind"
                                    class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($kinds as $k)
                                    <option value="{{ $k }}">{{ ucfirst($k) }}</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                doc / form / data / verify / external — drives the icon shown in the panel.
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Default required</label>
                            <label class="inline-flex items-center gap-2 mt-2">
                                <input type="checkbox" wire:model="atomDefaultRequired"
                                       class="rounded border-gray-300 dark:border-zinc-600 text-indigo-600 focus:ring-indigo-500" />
                                <span class="text-sm text-gray-700 dark:text-gray-200">Required by default</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Hint <span class="text-xs text-gray-500 font-normal">(optional)</span></label>
                        <textarea wire:model="atomHint" rows="2"
                                  placeholder="e.g. Driver's license, state ID, or passport."
                                  class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="$set('showAtomForm', false)"
                                class="px-4 py-2 rounded-md border border-gray-300 dark:border-zinc-600 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-zinc-700">Cancel</button>
                        <button type="submit"
                                class="px-4 py-2 rounded-md bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium">
                            {{ $atomFormId ? 'Save' : 'Create' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Bundle modal ────────────────────────────────────────── --}}
    @if($showBundleForm)
        <div class="fixed inset-0 bg-gray-500/75 dark:bg-black/70 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-lg w-full shadow-xl">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    {{ $bundleFormId ? 'Rename bundle' : 'New bundle' }}
                </h2>

                <form wire:submit.prevent="saveBundle" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Name</label>
                        <input type="text" wire:model="bundleName"
                               placeholder="HCP First-Time Homebuyer"
                               class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500" />
                        @error('bundleName') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Description <span class="text-xs text-gray-500 font-normal">(optional)</span></label>
                        <textarea wire:model="bundleDescription" rows="3"
                                  class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="$set('showBundleForm', false)"
                                class="px-4 py-2 rounded-md border border-gray-300 dark:border-zinc-600 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-zinc-700">Cancel</button>
                        <button type="submit"
                                class="px-4 py-2 rounded-md bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium">
                            {{ $bundleFormId ? 'Save' : 'Create & manage items' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
