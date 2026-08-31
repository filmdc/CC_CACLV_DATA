<div class="px-6 lg:px-9 py-7 max-w-5xl mx-auto" style="min-height: 100vh;">

    @if (session('homes_message'))
        <div class="mb-4 rounded-md px-4 py-3 text-sm" style="background: var(--calv-sage-15); color: #2F5A41; border: 1px solid var(--calv-sage-35);">
            {{ session('homes_message') }}
        </div>
    @endif

    <a href="{{ route('homes.settings') }}" wire:navigate class="calv-link inline-block mb-3" style="font-size: 12.5px;">← Settings</a>

    <div class="flex items-end gap-4 mb-5">
        <div class="flex-1 min-w-0">
            <h1 class="calv-h1" style="font-size: 30px;">Contractors</h1>
            <div class="text-[13.5px] mt-1.5" style="color: var(--calv-slate-65);">Licensing, insurance, and contact details. Expired paperwork floats to the top of "Expiring".</div>
        </div>
        @if($canManage)
            <button type="button" wire:click="openCreate" class="calv-btn calv-btn--primary">+ Contractor</button>
        @endif
    </div>

    {{-- Search + pills --}}
    <div class="flex flex-wrap gap-2.5 items-center mb-4">
        <div class="relative flex-1" style="min-width: 240px; max-width: 360px;">
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search a company, name, or license…" class="calv-input" style="border-radius: 8px;">
        </div>
        @foreach([['active', 'Active'], ['expiring', 'Expiring paperwork'], ['all', 'Everything']] as [$id, $label])
            @php $active = $pill === $id; @endphp
            <button type="button" wire:click="setPill('{{ $id }}')"
                    style="font-family: var(--font-sub); font-size: 11px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; padding: 8px 14px; border-radius: 999px; cursor: pointer;
                           background: {{ $active ? 'var(--calv-slate)' : '#fff' }}; color: {{ $active ? '#fff' : 'var(--calv-slate)' }};
                           border: 1px solid {{ $active ? 'var(--calv-slate)' : 'var(--calv-slate-35)' }};">{{ $label }}</button>
        @endforeach
    </div>

    {{-- Rows --}}
    <div class="calv-card overflow-hidden">
        @forelse($contractors as $c)
            @php
                $licenseExpired = $c->license_expiration && $c->license_expiration->isPast();
                $insuranceExpired = $c->insurance_expiration && $c->insurance_expiration->isPast();
            @endphp
            <div class="flex items-center gap-4 px-4 py-3.5" style="border-bottom: 1px solid var(--calv-slate-15); background: {{ ($licenseExpired || $insuranceExpired) && $c->status === 'active' ? 'var(--calv-red-15)' : '#fff' }}; {{ $c->status !== 'active' ? 'opacity: .55;' : '' }}">
                <div class="flex-1 min-w-0">
                    <div class="text-[14px] font-semibold" style="color: var(--calv-slate);">{{ $c->company_name }}
                        @if($c->status === 'suspended')<span class="calv-badge calv-badge--red" style="margin-left: 6px;">Suspended</span>
                        @elseif($c->status === 'inactive')<span class="calv-badge" style="margin-left: 6px;">Inactive</span>@endif
                    </div>
                    <div class="text-[11.5px] mt-0.5" style="color: var(--calv-slate-65);">
                        {{ trim(($c->contact_first_name ?? '').' '.($c->contact_last_name ?? '')) ?: '—' }} · {{ $c->phone }}@if($c->email) · {{ $c->email }}@endif
                    </div>
                    <div class="text-[11px] mt-0.5" style="color: var(--calv-slate-65);">
                        @if($c->license_expiration)
                            <span style="color: {{ $licenseExpired ? 'var(--calv-red)' : 'var(--calv-slate-65)' }};">License {{ $licenseExpired ? 'expired' : 'to' }} {{ $c->license_expiration->isoFormat('MMM D, YYYY') }}</span>
                        @else License on file: {{ $c->license_number ?: 'no' }} @endif
                        ·
                        @if($c->insurance_expiration)
                            <span style="color: {{ $insuranceExpired ? 'var(--calv-red)' : 'var(--calv-slate-65)' }};">Insurance {{ $insuranceExpired ? 'expired' : 'to' }} {{ $c->insurance_expiration->isoFormat('MMM D, YYYY') }}</span>
                        @else Insurance: not on file @endif
                    </div>
                </div>
                @if($canManage)
                    <div class="flex items-center gap-1.5 shrink-0">
                        <button type="button" wire:click="openEdit({{ $c->id }})" class="calv-btn calv-btn--quiet calv-btn--sm">Edit</button>
                        <button type="button" wire:click="toggleStatus({{ $c->id }})" class="calv-btn calv-btn--ghost calv-btn--sm">{{ $c->status === 'active' ? 'Deactivate' : 'Activate' }}</button>
                        @if($canDelete)
                            <button type="button" wire:click="delete({{ $c->id }})" wire:confirm="Delete {{ $c->company_name }}? Only possible when they have no projects." class="calv-btn calv-btn--ghost calv-btn--sm" style="color: var(--calv-red);">Delete</button>
                        @endif
                    </div>
                @endif
            </div>
        @empty
            <div class="px-6 py-8 text-center text-sm" style="color: var(--calv-slate-65);">No contractors match.</div>
        @endforelse
    </div>

    <div class="mt-4">{{ $contractors->links() }}</div>

    <div class="mt-3 text-[11.5px]" style="color: var(--calv-slate-65);">Contractor document review (W-9s, agreements) stays on the main app's contractor page.</div>

    {{-- Create/edit modal --}}
    @if($showForm)
        <div class="fixed inset-0 z-[100] flex items-start justify-center overflow-y-auto" style="background: rgba(40,42,44,.55); padding: 48px 16px;">
            <div class="w-full" style="max-width: 640px; background: #fff; border-radius: 10px; box-shadow: var(--shadow-lg); overflow: hidden;" wire:click.stop>
                <header class="px-6 py-4" style="border-bottom: 1px solid var(--calv-slate-15);">
                    <h2 class="calv-h1" style="font-size: 20px;">{{ $editingId ? 'Edit contractor' : 'New contractor' }}</h2>
                </header>
                <div class="px-6 py-5 grid gap-3.5" style="grid-template-columns: 1fr 1fr;">
                    <div style="grid-column: 1 / -1;">
                        <label class="calv-label">Company</label>
                        <input wire:model="form.company_name" type="text" class="calv-input">
                        @error('form.company_name')<p class="mt-1 text-xs" style="color: var(--calv-red);">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="calv-label">Contact first name</label>
                        <input wire:model="form.contact_first_name" type="text" class="calv-input">
                    </div>
                    <div>
                        <label class="calv-label">Contact last name</label>
                        <input wire:model="form.contact_last_name" type="text" class="calv-input">
                    </div>
                    <div>
                        <label class="calv-label">Phone</label>
                        <input wire:model="form.phone" type="text" class="calv-input">
                        @error('form.phone')<p class="mt-1 text-xs" style="color: var(--calv-red);">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="calv-label">Email</label>
                        <input wire:model="form.email" type="email" class="calv-input">
                        @error('form.email')<p class="mt-1 text-xs" style="color: var(--calv-red);">{{ $message }}</p>@enderror
                    </div>
                    <div style="grid-column: 1 / -1;">
                        <label class="calv-label">Street address</label>
                        <input wire:model="form.address_line_1" type="text" class="calv-input">
                        @error('form.address_line_1')<p class="mt-1 text-xs" style="color: var(--calv-red);">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="calv-label">City</label>
                        <input wire:model="form.city" type="text" class="calv-input">
                        @error('form.city')<p class="mt-1 text-xs" style="color: var(--calv-red);">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid gap-3" style="grid-template-columns: 1fr 1fr;">
                        <div>
                            <label class="calv-label">State</label>
                            <input wire:model="form.state" type="text" maxlength="2" class="calv-input">
                            @error('form.state')<p class="mt-1 text-xs" style="color: var(--calv-red);">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="calv-label">Zip</label>
                            <input wire:model="form.zip_code" type="text" class="calv-input">
                            @error('form.zip_code')<p class="mt-1 text-xs" style="color: var(--calv-red);">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div>
                        <label class="calv-label">License #</label>
                        <input wire:model="form.license_number" type="text" class="calv-input">
                    </div>
                    <div>
                        <label class="calv-label">License expires</label>
                        <input wire:model="form.license_expiration" type="date" class="calv-input">
                    </div>
                    <div>
                        <label class="calv-label">Insurance provider</label>
                        <input wire:model="form.insurance_provider" type="text" class="calv-input">
                    </div>
                    <div>
                        <label class="calv-label">Policy #</label>
                        <input wire:model="form.insurance_policy_number" type="text" class="calv-input">
                    </div>
                    <div>
                        <label class="calv-label">Insurance expires</label>
                        <input wire:model="form.insurance_expiration" type="date" class="calv-input">
                    </div>
                    <div>
                        <label class="calv-label">Liability coverage $</label>
                        <input wire:model="form.liability_coverage_amount" type="number" step="0.01" min="0" class="calv-input">
                    </div>
                    <div>
                        <label class="calv-label">EIN</label>
                        <input wire:model="form.ein" type="text" class="calv-input">
                    </div>
                    <div>
                        <label class="calv-label">Status</label>
                        <select wire:model="form.status" class="calv-input">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>
                    @if($serviceOptions->count() > 0)
                        <div style="grid-column: 1 / -1;">
                            <label class="calv-label">Services offered</label>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($serviceOptions as $svc)
                                    @php $on = in_array((int) $svc->id, $selectedServices, true); @endphp
                                    <button type="button" wire:click="toggleService({{ $svc->id }})"
                                            style="font-family: var(--font-body); font-size: 12.5px; padding: 6px 12px; border-radius: 999px; cursor: pointer;
                                                   background: {{ $on ? 'var(--calv-teal)' : '#fff' }}; color: {{ $on ? '#fff' : 'var(--calv-slate)' }};
                                                   border: 1px solid {{ $on ? 'var(--calv-teal)' : 'var(--calv-slate-35)' }};">{{ $svc->label }}</button>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    <div style="grid-column: 1 / -1;">
                        <label class="calv-label">Notes</label>
                        <textarea wire:model="form.notes" rows="2" class="calv-input" style="resize: vertical;"></textarea>
                    </div>
                </div>
                <footer class="flex items-center gap-2.5 px-6 py-3.5 justify-end" style="border-top: 1px solid var(--calv-slate-15); background: var(--calv-sand-15);">
                    <button type="button" wire:click="$set('showForm', false)" class="calv-btn calv-btn--ghost">Cancel</button>
                    <button type="button" wire:click="save" class="calv-btn calv-btn--primary" wire:loading.attr="disabled" wire:target="save">Save</button>
                </footer>
            </div>
        </div>
    @endif
</div>
