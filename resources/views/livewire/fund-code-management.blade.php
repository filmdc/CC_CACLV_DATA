<div class="max-w-7xl mx-auto p-6">
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Fund Codes &amp; Services</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Fund codes for budgeting, and the housing-service catalog that ties them to requirements.
                </p>
            </div>
            @if($this->can('fund_codes.create'))
                @if($activeTab === 'fund_codes')
                    <button wire:click="showCreateForm"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add Fund Code
                    </button>
                @else
                    <button wire:click="showCreateService"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 flex items-center">
                        + New Housing Service
                    </button>
                @endif
            @endif
        </div>

        {{-- Phase 10: tabs --}}
        <div class="border-b border-gray-200 dark:border-zinc-700 mb-4">
            <nav class="flex gap-6" aria-label="Tabs">
                <button type="button" wire:click="setTab('fund_codes')"
                        class="py-3 px-1 -mb-px border-b-2 text-sm font-medium {{ $activeTab === 'fund_codes' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200' }}">
                    Fund Codes
                </button>
                <button type="button" wire:click="setTab('housing_services')"
                        class="py-3 px-1 -mb-px border-b-2 text-sm font-medium {{ $activeTab === 'housing_services' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200' }}">
                    Housing Services
                </button>
            </nav>
        </div>

        <!-- Success/Error Messages -->
        @if (session()->has('success'))
            <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 mb-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="rounded-md bg-red-50 dark:bg-red-900/30 p-4 mb-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-red-800 dark:text-red-200">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if($activeTab === 'fund_codes')

        <!-- Filters -->
        <div class="flex flex-col md:flex-row gap-4 mb-6">
            <div class="flex-1">
                <input type="text" wire:model.live.debounce.300ms="search"
                       placeholder="Search fund codes..."
                       class="w-full px-4 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div class="w-full md:w-48">
                <select wire:model.live="activeFilter"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                    <option value="">All Status</option>
                    <option value="active">Active Only</option>
                    <option value="inactive">Inactive Only</option>
                </select>
            </div>
        </div>

        <!-- Fund Codes Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                <thead class="bg-gray-50 dark:bg-zinc-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Code</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Linked Contract</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Usage</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-700">
                    @forelse($fundCodes as $fundCode)
                        <tr class="{{ !$fundCode->is_active ? 'opacity-60' : '' }}">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="font-mono font-semibold text-gray-900 dark:text-white">{{ $fundCode->code }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-gray-900 dark:text-white">{{ $fundCode->description }}</span>
                            </td>
                            <td class="px-6 py-4">
                                @if($fundCode->contract)
                                    <a href="{{ route('contract.show', $fundCode->contract_id) }}"
                                       class="text-indigo-600 dark:text-indigo-400 hover:underline text-sm">
                                        {{ $fundCode->contract->title }}
                                        @if($fundCode->contract->contract_number)
                                            <span class="text-gray-500 dark:text-gray-400">({{ $fundCode->contract->contract_number }})</span>
                                        @endif
                                    </a>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500 text-sm italic">No linked contract</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full bg-gray-100 dark:bg-zinc-700 text-gray-800 dark:text-gray-200">
                                    {{ $fundCode->communication_logs_count }} log{{ $fundCode->communication_logs_count !== 1 ? 's' : '' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($fundCode->is_active)
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">Active</span>
                                @else
                                    <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800 dark:bg-zinc-600 dark:text-gray-300">Inactive</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm space-x-2">
                                @if($this->can('fund_codes.edit'))
                                <button wire:click="openAtomLinkModal({{ $fundCode->id }})"
                                        class="text-purple-700 dark:text-purple-400 hover:text-purple-900 dark:hover:text-purple-300"
                                        title="Link requirement atoms from the library to this fund code">
                                    Atoms
                                </button>
                                <button wire:click="manageRequirements({{ $fundCode->id }})"
                                        class="text-emerald-700 dark:text-emerald-400 hover:text-emerald-900 dark:hover:text-emerald-300"
                                        title="Legacy fund-code-requirements (P-39b)">
                                    Legacy reqs ({{ $fundCode->requirements_count }})
                                </button>
                                <button wire:click="showEditForm({{ $fundCode->id }})"
                                        class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">
                                    Edit
                                </button>
                                <button wire:click="toggleActive({{ $fundCode->id }})"
                                        class="text-amber-600 dark:text-amber-400 hover:text-amber-900 dark:hover:text-amber-300">
                                    {{ $fundCode->is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                                @endif
                                @if($this->can('fund_codes.delete'))
                                <button wire:click="delete({{ $fundCode->id }})"
                                        wire:confirm="Are you sure you want to delete this fund code?"
                                        class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">
                                    Delete
                                </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <p class="mt-2 text-sm">No fund codes found.</p>
                                @if($this->can('fund_codes.create'))
                                <button wire:click="showCreateForm" class="mt-2 text-indigo-600 dark:text-indigo-400 hover:underline">
                                    Create your first fund code
                                </button>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-4">
            {{ $fundCodes->links() }}
        </div>

        @endif  {{-- /activeTab === fund_codes --}}

        {{-- Phase 10: Housing Services tab content --}}
        @if($activeTab === 'housing_services')
            @if($editingServiceId && $servicesDetail)
                {{-- Service detail: link fund codes + atoms --}}
                <div class="flex items-center justify-between mb-4">
                    <button wire:click="closeService" class="inline-flex items-center text-sm text-indigo-600 hover:underline dark:text-indigo-400">← Back to services</button>
                    <div class="text-right">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{ $servicesDetail->name }}</h2>
                        @if($servicesDetail->description)
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 max-w-md text-right">{{ $servicesDetail->description }}</p>
                        @endif
                    </div>
                </div>

                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                    Picking this service on a Communication Log auto-attaches the linked fund codes AND stamps every linked atom onto intake requirements.
                </p>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div class="bg-gray-50 dark:bg-zinc-900 rounded-md p-4 border border-gray-200 dark:border-zinc-700">
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-2">Linked fund codes</h3>
                        <div class="bg-white dark:bg-zinc-800 rounded border border-gray-200 dark:border-zinc-700 max-h-96 overflow-y-auto">
                            @forelse($servicesAllFundCodes as $fc)
                                <label class="flex items-center gap-2 px-3 py-1.5 border-b border-gray-100 dark:border-zinc-700 last:border-b-0 cursor-pointer hover:bg-gray-50 dark:hover:bg-zinc-700">
                                    <input type="checkbox" wire:click="toggleServiceFundCode({{ $fc->id }})"
                                           @checked(in_array($fc->id, $serviceLinkedFundCodeIds))
                                           class="rounded border-gray-300 dark:border-zinc-600 text-indigo-600 focus:ring-indigo-500" />
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

                    <div class="bg-gray-50 dark:bg-zinc-900 rounded-md p-4 border border-gray-200 dark:border-zinc-700">
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-2">Linked requirement atoms</h3>
                        <div class="bg-white dark:bg-zinc-800 rounded border border-gray-200 dark:border-zinc-700 max-h-96 overflow-y-auto">
                            @forelse($servicesAllAtoms as $a)
                                <label class="flex items-center gap-2 px-3 py-1.5 border-b border-gray-100 dark:border-zinc-700 last:border-b-0 cursor-pointer hover:bg-gray-50 dark:hover:bg-zinc-700">
                                    <input type="checkbox" wire:click="toggleServiceAtom({{ $a->id }})"
                                           @checked(in_array($a->id, $serviceLinkedAtomIds))
                                           class="rounded border-gray-300 dark:border-zinc-600 text-indigo-600 focus:ring-indigo-500" />
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] uppercase font-mono bg-gray-100 dark:bg-zinc-700 text-gray-700 dark:text-gray-200">{{ $a->kind }}</span>
                                    <div class="min-w-0">
                                        <span class="block text-sm text-gray-900 dark:text-white truncate">{{ $a->label }}</span>
                                        <span class="block text-xs font-mono text-gray-500 dark:text-gray-400 truncate">{{ $a->key }}</span>
                                    </div>
                                </label>
                            @empty
                                <div class="px-3 py-4 text-center text-xs text-gray-500 dark:text-gray-400">No atoms defined yet — create some on <a href="{{ route('requirements-library.management') }}" class="text-indigo-600 hover:underline">/requirements-library</a>.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="mt-4 flex justify-end">
                    <button wire:click="saveServiceLinks"
                            class="px-4 py-2 rounded-md bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium">
                        Save service links
                    </button>
                </div>
            @else
                {{-- Service list --}}
                <div class="mb-4">
                    <input type="text" wire:model.live.debounce.300ms="search"
                           placeholder="Search service name…"
                           class="w-full md:w-1/2 px-4 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                        <thead class="bg-gray-50 dark:bg-zinc-900">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Service</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Fund codes</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Atoms</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Active</th>
                                <th class="px-3 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-700">
                            @forelse($housingServices as $svc)
                                <tr>
                                    <td class="px-3 py-2 text-sm text-gray-900 dark:text-white">
                                        <button wire:click="openService({{ $svc->id }})" class="text-indigo-600 hover:underline dark:text-indigo-400">{{ $svc->name }}</button>
                                        @if($svc->description)
                                            <div class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-md">{{ $svc->description }}</div>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-sm text-gray-700 dark:text-gray-200">{{ $svc->fund_codes_count }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-700 dark:text-gray-200">{{ $svc->requirement_definitions_count }}</td>
                                    <td class="px-3 py-2 text-sm">
                                        @if($svc->is_active)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Active</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-600 dark:bg-zinc-700 dark:text-gray-300">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-sm text-right whitespace-nowrap">
                                        @if($this->can('fund_codes.edit'))
                                            <button wire:click="showEditService({{ $svc->id }})" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 mr-3">Rename</button>
                                            <button wire:click="openService({{ $svc->id }})" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 mr-3">Manage</button>
                                            <button wire:click="deleteService({{ $svc->id }})"
                                                    wire:confirm="Move this service to trash? Historical comm-log references are preserved."
                                                    class="text-red-600 hover:text-red-900 dark:text-red-400">Delete</button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-3 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No services yet. Click "+ New Housing Service" to start the catalog.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $housingServices->links() }}</div>
            @endif
        @endif
    </div>

    {{-- Phase 10: per-fund-code atom-link modal --}}
    @if($showAtomLinkModal && $atomLinkFundCode)
        <div class="fixed inset-0 bg-gray-500/75 dark:bg-black/70 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-2xl w-full">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-zinc-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Requirements — <span class="font-mono">{{ $atomLinkFundCode->code }}</span>
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Atoms ticked here auto-stamp on a Communication Log when this fund code is anticipated.
                    </p>
                </div>
                <div class="px-6 py-4 max-h-[60vh] overflow-y-auto">
                    @forelse($allAtoms as $a)
                        <label class="flex items-center gap-2 px-2 py-1.5 border-b border-gray-100 dark:border-zinc-700 last:border-b-0 cursor-pointer hover:bg-gray-50 dark:hover:bg-zinc-700">
                            <input type="checkbox" wire:click="toggleAtomLink({{ $a->id }})"
                                   @checked(in_array($a->id, $atomLinkSelectedIds))
                                   class="rounded border-gray-300 dark:border-zinc-600 text-indigo-600 focus:ring-indigo-500" />
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] uppercase font-mono bg-gray-100 dark:bg-zinc-700 text-gray-700 dark:text-gray-200">{{ $a->kind }}</span>
                            @php
                                $catLabel = match($a->category ?? 'eligibility') {
                                    'eligibility' => ['Eligibility', 'bg-teal-100 text-teal-800'],
                                    'compliance' => ['Compliance', 'bg-amber-100 text-amber-800'],
                                    'service_delivery_compliance' => ['Service delivery', 'bg-purple-100 text-purple-800'],
                                    default => [$a->category, 'bg-gray-100 text-gray-800'],
                                };
                            @endphp
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] {{ $catLabel[1] }} dark:bg-opacity-30">{{ $catLabel[0] }}</span>
                            <div class="min-w-0">
                                <span class="block text-sm text-gray-900 dark:text-white truncate">{{ $a->label }}</span>
                                <span class="block text-xs font-mono text-gray-500 dark:text-gray-400 truncate">{{ $a->key }}</span>
                            </div>
                        </label>
                    @empty
                        <div class="px-2 py-4 text-center text-xs text-gray-500 dark:text-gray-400">No atoms defined yet.</div>
                    @endforelse
                </div>
                <div class="px-6 py-3 border-t border-gray-200 dark:border-zinc-700 flex justify-end gap-2">
                    <button type="button" wire:click="closeAtomLinkModal"
                            class="px-4 py-2 rounded-md border border-gray-300 dark:border-zinc-600 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-zinc-700">Cancel</button>
                    <button type="button" wire:click="saveAtomLinks"
                            class="px-4 py-2 rounded-md bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium">Save</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Phase 10: housing-service create/rename modal --}}
    @if($showServiceForm)
        <div class="fixed inset-0 bg-gray-500/75 dark:bg-black/70 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-lg w-full shadow-xl">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    {{ $serviceFormId ? 'Rename service' : 'New housing service' }}
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

    {{-- Original P-06 Requirements Modal continues below --}}
    @if($showRequirementsModal && $activeFundCode)
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-zinc-900 dark:bg-opacity-75 flex items-center justify-center z-50 p-4">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-3xl w-full max-h-[90vh] flex flex-col">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-zinc-700 flex justify-between items-center">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Requirements — <span class="font-mono">{{ $activeFundCode->code }}</span>
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ $activeFundCode->description }}</p>
                </div>
                <button wire:click="closeRequirementsModal"
                        class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="px-6 py-4 overflow-y-auto flex-1">
                @if($this->can('fund_codes.edit') && ! $showRequirementForm)
                <button wire:click="openRequirementForm"
                        class="mb-4 px-3 py-1.5 bg-indigo-600 text-white rounded-md text-sm hover:bg-indigo-700">
                    + Add Requirement
                </button>
                @endif

                @if($showRequirementForm)
                <div class="mb-4 p-4 bg-gray-50 dark:bg-zinc-900 rounded-md border border-gray-200 dark:border-zinc-700">
                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-3">
                        {{ $editingRequirementId ? 'Edit Requirement' : 'New Requirement' }}
                    </h4>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Label <span class="text-red-500">*</span>
                            </label>
                            <input type="text" wire:model="requirement_label"
                                   placeholder="e.g., Environmental review required"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md text-sm">
                            @error('requirement_label') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Description (optional)
                            </label>
                            <textarea wire:model="requirement_description" rows="2"
                                      placeholder="Detail or remediation hint, including any milestone hint as free text."
                                      class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md text-sm"></textarea>
                            @error('requirement_description') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div class="flex items-center space-x-4">
                            <label class="flex items-center">
                                <input type="checkbox" wire:model="requirement_is_hard"
                                       class="rounded border-gray-300 dark:border-zinc-600 text-red-600 focus:ring-red-500">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                    Hard requirement (must be satisfied — not just informational)
                                </span>
                            </label>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Sort order
                            </label>
                            <input type="number" min="0" wire:model="requirement_sort_order"
                                   class="w-24 px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md text-sm">
                            @error('requirement_sort_order') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div class="flex justify-end space-x-2 pt-1">
                            <button wire:click="$set('showRequirementForm', false)"
                                    class="px-3 py-1.5 bg-gray-200 dark:bg-zinc-600 text-gray-700 dark:text-gray-200 rounded-md text-sm hover:bg-gray-300 dark:hover:bg-zinc-500">
                                Cancel
                            </button>
                            <button wire:click="saveRequirement"
                                    wire:loading.attr="disabled"
                                    class="px-3 py-1.5 bg-indigo-600 text-white rounded-md text-sm hover:bg-indigo-700">
                                {{ $editingRequirementId ? 'Update' : 'Add' }}
                            </button>
                        </div>
                    </div>
                </div>
                @endif

                @if($activeRequirements->isEmpty())
                    <div class="text-center py-8 text-sm text-gray-500 dark:text-gray-400">
                        No requirements yet. Add one to surface it on every project that uses this fund code.
                    </div>
                @else
                <ul class="divide-y divide-gray-200 dark:divide-zinc-700">
                    @foreach($activeRequirements as $req)
                    <li class="py-3 flex items-start gap-3">
                        <div class="flex flex-col items-center pt-0.5 space-y-1">
                            @if($this->can('fund_codes.edit'))
                            <button wire:click="moveRequirementUp({{ $req->id }})"
                                    class="text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400"
                                    aria-label="Move up">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                </svg>
                            </button>
                            <span class="text-xs font-mono text-gray-500 dark:text-gray-400">{{ $req->sort_order }}</span>
                            <button wire:click="moveRequirementDown({{ $req->id }})"
                                    class="text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400"
                                    aria-label="Move down">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            @else
                            <span class="text-xs font-mono text-gray-500 dark:text-gray-400">{{ $req->sort_order }}</span>
                            @endif
                        </div>
                        <div class="flex-1">
                            <div class="flex items-start gap-2">
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $req->label }}</span>
                                @if($req->is_hard_requirement)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                        Required
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-200">
                                        Informational
                                    </span>
                                @endif
                            </div>
                            @if($req->description)
                                <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">{{ $req->description }}</p>
                            @endif
                        </div>
                        @if($this->can('fund_codes.edit'))
                        <div class="flex space-x-2 text-xs">
                            <button wire:click="editRequirement({{ $req->id }})"
                                    class="text-indigo-600 dark:text-indigo-400 hover:underline">Edit</button>
                            <button wire:click="deleteRequirement({{ $req->id }})"
                                    wire:confirm="Delete this requirement?"
                                    class="text-red-600 dark:text-red-400 hover:underline">Delete</button>
                        </div>
                        @endif
                    </li>
                    @endforeach
                </ul>
                @endif
            </div>

            <div class="px-6 py-3 border-t border-gray-200 dark:border-zinc-700 flex justify-end">
                <button wire:click="closeRequirementsModal"
                        class="px-4 py-2 bg-gray-200 dark:bg-zinc-600 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-500">
                    Close
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- Create/Edit Modal -->
    @if($showForm)
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-zinc-900 dark:bg-opacity-75 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl p-6 max-w-lg w-full mx-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                {{ $editingId ? 'Edit Fund Code' : 'Add Fund Code' }}
            </h3>

            <div class="space-y-4">
                <!-- Code -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Fund Code <span class="text-red-500">*</span>
                    </label>
                    <input type="text" wire:model="code"
                           placeholder="e.g., F7000"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md uppercase">
                    @error('code') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Description <span class="text-red-500">*</span>
                    </label>
                    <input type="text" wire:model="description"
                           placeholder="e.g., WHR"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                    @error('description') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <!-- Contract Link -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Link to Contract (Optional)
                    </label>
                    <select wire:model="contract_id"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                        <option value="">None</option>
                        @foreach($contracts as $contract)
                            <option value="{{ $contract->id }}">
                                {{ $contract->title }}
                                @if($contract->contract_number) ({{ $contract->contract_number }}) @endif
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Optionally link this fund code to a contract for easy reference
                    </p>
                </div>

                <!-- Active Status -->
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" wire:model="is_active"
                               class="rounded border-gray-300 dark:border-zinc-600 text-indigo-600 focus:ring-indigo-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Active</span>
                    </label>
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <button wire:click="$set('showForm', false)"
                        class="px-4 py-2 bg-gray-200 dark:bg-zinc-600 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-500">
                    Cancel
                </button>
                <button wire:click="save"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50 cursor-not-allowed"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                    <span wire:loading.remove wire:target="save">{{ $editingId ? 'Update' : 'Create' }}</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </button>
            </div>
        </div>
    </div>
    @endif

    @if($showDeleteFundCodeModal)
        {{-- P-18: Global Fund-Code Delete Confirmation (keep or cascade requirements across all projects) --}}
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50" wire:click.self="closeDeleteFundCodeModal">
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Delete fund code</h3>
                <p class="text-sm text-gray-700 dark:text-gray-300 mb-4">
                    This fund code generated <strong>{{ $deleteFundCodeRequirementCount }}</strong> requirement{{ $deleteFundCodeRequirementCount === 1 ? '' : 's' }} across one or more projects.
                    Keep them as-is for audit (their source_label_snapshot text survives), or cascade-delete them along with the fund code?
                </p>
                <div class="mt-6 flex justify-end space-x-3">
                    <button wire:click="closeDeleteFundCodeModal"
                            class="px-4 py-2 bg-gray-200 dark:bg-zinc-600 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-500">
                        Cancel
                    </button>
                    <button wire:click="confirmDeleteFundCode(false)"
                            class="px-4 py-2 bg-amber-600 text-white rounded-md hover:bg-amber-700">
                        Keep requirements
                    </button>
                    <button wire:click="confirmDeleteFundCode(true)"
                            class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                        Cascade delete
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
