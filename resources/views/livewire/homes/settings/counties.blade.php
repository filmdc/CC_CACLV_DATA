<div class="px-6 lg:px-9 py-7 max-w-5xl mx-auto" style="min-height: 100vh;">

    @if (session('homes_message'))
        <div class="mb-4 rounded-md px-4 py-3 text-sm" style="background: var(--calv-sage-15); color: #2F5A41; border: 1px solid var(--calv-sage-35);">
            {{ session('homes_message') }}
        </div>
    @endif

    <a href="{{ route('homes.settings') }}" wire:navigate class="calv-link inline-block mb-3" style="font-size: 12.5px;">← Settings</a>

    <div class="flex items-end gap-4 mb-5">
        <div class="flex-1 min-w-0">
            <h1 class="calv-h1" style="font-size: 30px;">Counties</h1>
            <div class="text-[13.5px] mt-1.5" style="color: var(--calv-slate-65);">
                Picking a county on a call attaches its funds and stamps its required documents onto the intake. Click a county to edit those links.
            </div>
        </div>
        @if($canManage)
            <button type="button" wire:click="openCreate" class="calv-btn calv-btn--primary">+ County</button>
        @endif
    </div>

    {{-- Search + trashed toggle --}}
    <div class="flex flex-wrap gap-2.5 items-center mb-4">
        <div class="relative flex-1" style="min-width: 240px; max-width: 360px;">
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search a county…" class="calv-input" style="border-radius: 8px;">
        </div>
        <label class="flex items-center gap-2 text-[12px]" style="color: var(--calv-slate-65);">
            <input wire:model.live="showTrashed" type="checkbox" style="accent-color: var(--calv-slate);"> show retired
        </label>
    </div>

    {{-- Rows --}}
    <div class="calv-card overflow-hidden">
        @forelse($counties as $county)
            @php $open = $openId === $county->id && ! $county->trashed(); @endphp
            <div style="border-bottom: 1px solid var(--calv-slate-15); {{ (!$county->is_active || $county->trashed()) ? 'opacity: .55;' : '' }}">
                <div class="flex items-center gap-4 px-4 py-3.5 {{ $county->trashed() ? '' : 'cursor-pointer' }}"
                     @unless($county->trashed()) wire:click="openLinks({{ $county->id }})" @endunless
                     style="background: {{ $open ? 'var(--calv-sand-15)' : '#fff' }};">
                    <div class="flex-1 min-w-0">
                        <div class="text-[14px] font-semibold" style="color: var(--calv-slate);">
                            {{ $county->name }}
                            @unless($county->is_active)<span class="calv-badge" style="margin-left: 6px;">Inactive</span>@endunless
                            @if($county->trashed())<span class="calv-badge" style="margin-left: 6px;">Retired</span>@endif
                        </div>
                        <div class="text-[11.5px] mt-0.5" style="color: var(--calv-slate-65);">
                            @if($county->description){{ Str::limit($county->description, 70) }} · @endif
                            {{ $county->fund_codes_count }} {{ Str::plural('fund', $county->fund_codes_count) }} ·
                            {{ $county->requirement_definitions_count }} required {{ Str::plural('item', $county->requirement_definitions_count) }} ·
                            {{ $county->communication_logs_count }} {{ Str::plural('intake', $county->communication_logs_count) }}
                        </div>
                    </div>
                    @if($canManage)
                        <div class="flex items-center gap-1.5 shrink-0" wire:click.stop>
                            @if($county->trashed())
                                <button type="button" wire:click="restore({{ $county->id }})" class="calv-btn calv-btn--quiet calv-btn--sm">Restore</button>
                            @else
                                <button type="button" wire:click="openEdit({{ $county->id }})" class="calv-btn calv-btn--quiet calv-btn--sm">Edit</button>
                                <button type="button" wire:click="delete({{ $county->id }})" wire:confirm="Retire {{ $county->name }}? Existing intakes keep their history; it just leaves the pick list." class="calv-btn calv-btn--ghost calv-btn--sm" style="color: var(--calv-red);">Retire</button>
                            @endif
                        </div>
                    @endif
                    @unless($county->trashed())
                        <span style="color: var(--calv-slate-65); transform: {{ $open ? 'rotate(90deg)' : 'none' }}; transition: transform .15s;">▸</span>
                    @endunless
                </div>

                {{-- Inline link editor --}}
                @if($open)
                    <div class="px-4 pb-4 flex flex-col gap-3.5" style="background: var(--calv-sand-15);">
                        <div>
                            <div class="calv-subhead mb-1.5" style="font-size: 11px;">Funds this county draws from</div>
                            <div class="flex flex-wrap gap-1.5">
                                @forelse($allFunds as $fund)
                                    @php $on = in_array($fund->id, $linkedFundCodeIds, true); @endphp
                                    <button type="button" @if($canManage) wire:click="toggleFund({{ $fund->id }})" @endif
                                            title="{{ $fund->description }}"
                                            style="font-family: ui-monospace, Menlo, monospace; font-size: 12px; font-weight: 600; padding: 6px 12px; border-radius: 999px; cursor: {{ $canManage ? 'pointer' : 'default' }};
                                                   background: {{ $on ? 'var(--calv-teal)' : '#fff' }}; color: {{ $on ? '#fff' : 'var(--calv-slate)' }};
                                                   border: 1px solid {{ $on ? 'var(--calv-teal)' : 'var(--calv-slate-35)' }};">
                                        {{ $on ? '✓ ' : '' }}{{ $fund->code }}
                                    </button>
                                @empty
                                    <span class="text-[12px]" style="color: var(--calv-slate-65);">No active fund codes yet — add them under Settings → Fund codes.</span>
                                @endforelse
                            </div>
                        </div>
                        <div>
                            <div class="calv-subhead mb-1.5" style="font-size: 11px;">Documents &amp; tasks stamped on new intakes</div>
                            <div class="flex flex-wrap gap-1.5">
                                @forelse($allAtoms as $atom)
                                    @php $on = in_array($atom->id, $linkedAtomIds, true); @endphp
                                    <button type="button" @if($canManage) wire:click="toggleAtom({{ $atom->id }})" @endif
                                            style="font-family: var(--font-body); font-size: 12.5px; padding: 6px 12px; border-radius: 999px; cursor: {{ $canManage ? 'pointer' : 'default' }};
                                                   background: {{ $on ? 'var(--calv-teal)' : '#fff' }}; color: {{ $on ? '#fff' : 'var(--calv-slate)' }};
                                                   border: 1px solid {{ $on ? 'var(--calv-teal)' : 'var(--calv-slate-35)' }};">
                                        {{ $on ? '✓ ' : '' }}{{ $atom->label }}
                                    </button>
                                @empty
                                    <span class="text-[12px]" style="color: var(--calv-slate-65);">The requirements library is empty — add items under Settings → Requirements library.</span>
                                @endforelse
                            </div>
                        </div>
                        @if($canManage)
                            <div class="flex items-center gap-3">
                                <button type="button" wire:click="saveLinks" class="calv-btn calv-btn--primary calv-btn--sm" wire:loading.attr="disabled" wire:target="saveLinks">Save links</button>
                                <span class="text-[11.5px]" style="color: var(--calv-slate-65);">Changes apply to intakes that pick this county from now on — existing intakes are untouched.</span>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        @empty
            <div class="px-6 py-8 text-center text-sm" style="color: var(--calv-slate-65);">{{ $showTrashed ? 'Nothing retired.' : 'No counties yet.' }}</div>
        @endforelse
    </div>

    {{-- Create/edit modal --}}
    @if($showForm)
        <div class="fixed inset-0 z-[100] flex items-start justify-center overflow-y-auto" style="background: rgba(40,42,44,.55); padding: 56px 16px;"
             x-data x-on:keydown.escape.window="$wire.closeForm()" wire:click.self="closeForm">
            <div class="w-full" style="max-width: 440px; background: #fff; border-radius: 10px; box-shadow: var(--shadow-lg); overflow: hidden;" wire:click.stop>
                <header class="px-6 py-4" style="border-bottom: 1px solid var(--calv-slate-15);">
                    <h2 class="calv-h1" style="font-size: 20px;">{{ $editingId ? 'Edit county' : 'New county' }}</h2>
                </header>
                <div class="px-6 py-5 flex flex-col gap-3.5">
                    <div>
                        <label class="calv-label">Name</label>
                        <input wire:model="serviceName" type="text" class="calv-input" placeholder="e.g. Northampton County">
                        @error('serviceName')<p class="mt-1 text-xs" style="color: var(--calv-red);">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="calv-label">Description <span style="font-weight:400; text-transform:none; letter-spacing:0; color: var(--calv-slate-65);">— optional</span></label>
                        <textarea wire:model="serviceDescription" rows="2" class="calv-input" style="resize: vertical;"></textarea>
                        @error('serviceDescription')<p class="mt-1 text-xs" style="color: var(--calv-red);">{{ $message }}</p>@enderror
                    </div>
                    <label class="flex items-center gap-2.5">
                        <input wire:model="serviceActive" type="checkbox" style="accent-color: var(--calv-red);">
                        <span class="text-sm" style="color: var(--calv-slate);">Active — appears in the pick list on calls</span>
                    </label>
                </div>
                <footer class="flex items-center gap-2.5 px-6 py-3.5 justify-end" style="border-top: 1px solid var(--calv-slate-15); background: var(--calv-sand-15);">
                    <button type="button" wire:click="closeForm" class="calv-btn calv-btn--ghost">Cancel</button>
                    <button type="button" wire:click="save" class="calv-btn calv-btn--primary" wire:loading.attr="disabled" wire:target="save">Save</button>
                </footer>
            </div>
        </div>
    @endif
</div>
