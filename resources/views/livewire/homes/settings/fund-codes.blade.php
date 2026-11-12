<div class="px-6 lg:px-9 py-7 max-w-5xl mx-auto" style="min-height: 100vh;">

    @if (session('homes_message'))
        <div class="mb-4 rounded-md px-4 py-3 text-sm" style="background: var(--calv-sage-15); color: #2F5A41; border: 1px solid var(--calv-sage-35);">
            {{ session('homes_message') }}
        </div>
    @endif

    @php
        $fundDot = ['soon' => 'var(--calv-red)', 'warning' => 'var(--calv-amber)', 'ok' => 'var(--calv-sage)', 'perpetual' => 'var(--calv-slate-35)', 'expired' => 'var(--calv-slate)'];
    @endphp

    <a href="{{ route('homes.settings') }}" wire:navigate class="calv-link inline-block mb-3" style="font-size: 12.5px;">← Settings</a>

    <div class="flex items-end gap-4 mb-5">
        <div class="flex-1 min-w-0">
            <h1 class="calv-h1" style="font-size: 30px;">Fund codes</h1>
            <div class="text-[13.5px] mt-1.5" style="color: var(--calv-slate-65);">Funding sources, their expiry dates, and the documents each one requires.</div>
        </div>
        @if($canManage)
            <button type="button" wire:click="openCreate" class="calv-btn calv-btn--primary">+ Fund code</button>
        @endif
    </div>

    {{-- Search + pills --}}
    <div class="flex flex-wrap gap-2.5 items-center mb-4">
        <div class="relative flex-1" style="min-width: 240px; max-width: 360px;">
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search a code or description…" class="calv-input" style="border-radius: 8px;">
        </div>
        @foreach([['active', 'Active'], ['inactive', 'Inactive'], ['all', 'Everything']] as [$id, $label])
            @php $active = $pill === $id; @endphp
            <button type="button" wire:click="setPill('{{ $id }}')"
                    style="font-family: var(--font-sub); font-size: 11px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; padding: 8px 14px; border-radius: 999px; cursor: pointer;
                           background: {{ $active ? 'var(--calv-slate)' : '#fff' }}; color: {{ $active ? '#fff' : 'var(--calv-slate)' }};
                           border: 1px solid {{ $active ? 'var(--calv-slate)' : 'var(--calv-slate-35)' }};">{{ $label }}</button>
        @endforeach
    </div>

    {{-- Rows --}}
    <div class="calv-card overflow-hidden">
        @forelse($funds as $fund)
            <div class="flex items-center gap-4 px-4 py-3.5" style="border-bottom: 1px solid var(--calv-slate-15); {{ $fund->is_active ? '' : 'opacity: .55;' }}">
                <span style="width: 8px; height: 8px; border-radius: 999px; background: {{ $fundDot[$fund->expiry_state] ?? 'var(--calv-sage)' }};" title="{{ $fund->expiry_state }}"></span>
                <div class="min-w-0" style="flex: 0 0 110px;">
                    <span class="text-[14px] font-semibold" style="color: var(--calv-slate); font-family: ui-monospace, Menlo, monospace;">{{ $fund->code }}</span>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-[13.5px]" style="color: var(--calv-slate);">{{ $fund->description }}</div>
                    <div class="text-[11px] mt-0.5" style="color: var(--calv-slate-65);">
                        {{ $fund->communication_logs_count }} {{ Str::plural('intake', $fund->communication_logs_count) }} ·
                        {{ $fund->requirement_definitions_count }} required {{ Str::plural('item', $fund->requirement_definitions_count) }}
                        @if($fund->expiry_state === 'soon') · <span style="color: var(--calv-red); font-weight: 600;">expires in {{ $fund->days_to_expiry }}d</span>
                        @elseif($fund->expiry_state === 'warning') · expires in {{ $fund->days_to_expiry }}d
                        @elseif($fund->expiry_state === 'expired') · expired
                        @elseif($fund->expires_at) · expires {{ $fund->expires_at->isoFormat('MMM YYYY') }}
                        @else · no expiry @endif
                    </div>
                </div>
                @if($canManage)
                    <div class="flex items-center gap-1.5 shrink-0">
                        <button type="button" wire:click="openAtomLinks({{ $fund->id }})" class="calv-btn calv-btn--quiet calv-btn--sm">Requirements</button>
                        <button type="button" wire:click="openEdit({{ $fund->id }})" class="calv-btn calv-btn--quiet calv-btn--sm">Edit</button>
                        <button type="button" wire:click="toggleActive({{ $fund->id }})" class="calv-btn calv-btn--ghost calv-btn--sm">{{ $fund->is_active ? 'Deactivate' : 'Activate' }}</button>
                        @if($canDelete)
                            <button type="button" wire:click="delete({{ $fund->id }})" wire:confirm="Delete fund code {{ $fund->code }}? This only works when nothing uses it." class="calv-btn calv-btn--ghost calv-btn--sm" style="color: var(--calv-red);">Delete</button>
                        @endif
                    </div>
                @endif
            </div>
        @empty
            <div class="px-6 py-8 text-center text-sm" style="color: var(--calv-slate-65);">No fund codes match.</div>
        @endforelse
    </div>

    <div class="mt-4">{{ $funds->links() }}</div>

    <div class="mt-3 text-[11.5px]" style="color: var(--calv-slate-65);">Counties and their fund/requirement links live under <a href="{{ route('homes.settings.counties') }}" wire:navigate class="calv-link">Settings → Counties</a> — day-to-day, counties attach right from a call's detail page.</div>

    {{-- Create/edit modal --}}
    @if($showForm)
        <div class="fixed inset-0 z-[100] flex items-start justify-center overflow-y-auto" style="background: rgba(40,42,44,.55); padding: 56px 16px;">
            <div class="w-full" style="max-width: 480px; background: #fff; border-radius: 10px; box-shadow: var(--shadow-lg); overflow: hidden;" @click.stop>
                <header class="px-6 py-4" style="border-bottom: 1px solid var(--calv-slate-15);">
                    <h2 class="calv-h1" style="font-size: 20px;">{{ $editingId ? 'Edit fund code' : 'New fund code' }}</h2>
                </header>
                <div class="px-6 py-5 flex flex-col gap-3.5">
                    <div class="grid gap-3" style="grid-template-columns: 140px 1fr;">
                        <div>
                            <label class="calv-label">Code</label>
                            <input wire:model="code" type="text" class="calv-input" placeholder="F4205" style="font-family: ui-monospace, Menlo, monospace;">
                            @error('code')<p class="mt-1 text-xs" style="color: var(--calv-red);">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="calv-label">Description</label>
                            <input wire:model="description" type="text" class="calv-input" placeholder="What this fund pays for">
                            @error('description')<p class="mt-1 text-xs" style="color: var(--calv-red);">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div class="grid gap-3" style="grid-template-columns: 1fr 1fr;">
                        <div>
                            <label class="calv-label">Starts <span style="font-weight:400; text-transform:none; letter-spacing:0; color: var(--calv-slate-65);">— optional</span></label>
                            <input wire:model="startsAt" type="date" class="calv-input">
                        </div>
                        <div>
                            <label class="calv-label">Expires <span style="font-weight:400; text-transform:none; letter-spacing:0; color: var(--calv-slate-65);">— optional</span></label>
                            <input wire:model="expiresAt" type="date" class="calv-input">
                            @error('expiresAt')<p class="mt-1 text-xs" style="color: var(--calv-red);">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div>
                        <label class="calv-label">Contract <span style="font-weight:400; text-transform:none; letter-spacing:0; color: var(--calv-slate-65);">— optional link</span></label>
                        <select wire:model="contractId" class="calv-input">
                            <option value="">—</option>
                            @foreach($contracts as $contract)
                                <option value="{{ $contract->id }}">{{ $contract->contract_number }} — {{ Str::limit($contract->title, 50) }}</option>
                            @endforeach
                        </select>
                        @error('contractId')<p class="mt-1 text-xs" style="color: var(--calv-red);">{{ $message }}</p>@enderror
                    </div>
                    <label class="flex items-center gap-2.5">
                        <input wire:model="isActive" type="checkbox" style="accent-color: var(--calv-red);">
                        <span class="text-sm" style="color: var(--calv-slate);">Active</span>
                    </label>
                </div>
                <footer class="flex items-center gap-2.5 px-6 py-3.5 justify-end" style="border-top: 1px solid var(--calv-slate-15); background: var(--calv-sand-15);">
                    <button type="button" wire:click="closeForm" class="calv-btn calv-btn--ghost">Cancel</button>
                    <button type="button" wire:click="save" class="calv-btn calv-btn--primary" wire:loading.attr="disabled" wire:target="save">Save</button>
                </footer>
            </div>
        </div>
    @endif

    {{-- Atom-link modal --}}
    @if($linkingFundId)
        <div class="fixed inset-0 z-[100] flex items-start justify-center overflow-y-auto" style="background: rgba(40,42,44,.55); padding: 56px 16px;">
            <div class="w-full" style="max-width: 520px; background: #fff; border-radius: 10px; box-shadow: var(--shadow-lg); overflow: hidden;" @click.stop>
                <header class="px-6 py-4" style="border-bottom: 1px solid var(--calv-slate-15);">
                    <h2 class="calv-h1" style="font-size: 20px;">Required items for this fund</h2>
                    <div class="text-xs mt-1" style="color: var(--calv-slate-65);">Checked items are stamped onto every intake that anticipates this fund.</div>
                </header>
                <div class="px-6 py-5 flex flex-col gap-1.5" style="max-height: 50vh; overflow-y: auto;">
                    @forelse($atoms as $atom)
                        @php $checked = in_array($atom->id, $linkedAtomIds); @endphp
                        <label class="flex items-center gap-2.5 rounded-lg px-3 py-2 cursor-pointer text-[13px]" style="border: 1px solid {{ $checked ? 'var(--calv-teal)' : 'var(--calv-slate-15)' }}; background: {{ $checked ? 'var(--calv-teal-15)' : '#fff' }};">
                            <input type="checkbox" wire:click="toggleAtom({{ $atom->id }})" @checked($checked) style="accent-color: var(--calv-teal);">
                            <span class="flex-1" style="color: var(--calv-slate);">{{ $atom->label }}</span>
                            <span class="calv-badge" style="font-size: 9px;">{{ $atom->category === 'service_delivery_compliance' ? 'project' : $atom->category }}</span>
                        </label>
                    @empty
                        <div class="text-sm" style="color: var(--calv-slate-65);">No requirement items in the library yet — add them under Settings → Requirements library.</div>
                    @endforelse
                </div>
                <footer class="flex items-center gap-2.5 px-6 py-3.5 justify-end" style="border-top: 1px solid var(--calv-slate-15); background: var(--calv-sand-15);">
                    <button type="button" wire:click="$set('linkingFundId', null)" class="calv-btn calv-btn--ghost">Cancel</button>
                    <button type="button" wire:click="saveAtomLinks" class="calv-btn calv-btn--primary" wire:loading.attr="disabled" wire:target="saveAtomLinks">Save links</button>
                </footer>
            </div>
        </div>
    @endif
</div>
