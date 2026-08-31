<div class="px-6 lg:px-9 py-7 max-w-5xl mx-auto" style="min-height: 100vh;">

    @if (session('homes_message'))
        <div class="mb-4 rounded-md px-4 py-3 text-sm" style="background: var(--calv-sage-15); color: #2F5A41; border: 1px solid var(--calv-sage-35);">
            {{ session('homes_message') }}
        </div>
    @endif

    <a href="{{ route('homes.settings') }}" wire:navigate class="calv-link inline-block mb-3" style="font-size: 12.5px;">← Settings</a>

    <div class="flex items-end gap-4 mb-5">
        <div class="flex-1 min-w-0">
            <h1 class="calv-h1" style="font-size: 30px;">Properties</h1>
            <div class="text-[13.5px] mt-1.5" style="color: var(--calv-slate-65);">Addresses, property types, and notes. Every property belongs to a household.</div>
        </div>
        @if($canManage)
            <button type="button" wire:click="openCreate" class="calv-btn calv-btn--primary">+ Property</button>
        @endif
    </div>

    {{-- Search + pills --}}
    <div class="flex flex-wrap gap-2.5 items-center mb-4">
        <div class="relative flex-1" style="min-width: 240px; max-width: 360px;">
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search an address or homeowner…" class="calv-input" style="border-radius: 8px;">
        </div>
        @foreach([['active', 'Active'], ['all', 'Everything']] as [$id, $label])
            @php $active = $pill === $id; @endphp
            <button type="button" wire:click="setPill('{{ $id }}')"
                    style="font-family: var(--font-sub); font-size: 11px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; padding: 8px 14px; border-radius: 999px; cursor: pointer;
                           background: {{ $active ? 'var(--calv-slate)' : '#fff' }}; color: {{ $active ? '#fff' : 'var(--calv-slate)' }};
                           border: 1px solid {{ $active ? 'var(--calv-slate)' : 'var(--calv-slate-35)' }};">{{ $label }}</button>
        @endforeach
    </div>

    {{-- Rows --}}
    <div class="calv-card overflow-hidden">
        @forelse($properties as $p)
            <div style="border-bottom: 1px solid var(--calv-slate-15); {{ $p->status !== 'active' ? 'opacity: .55;' : '' }}">
                <div class="flex items-center gap-4 px-4 py-3.5">
                    <div class="flex-1 min-w-0">
                        <div class="text-[14px] font-semibold" style="color: var(--calv-slate);">
                            {{ $p->address_line_1 }}, {{ $p->city }}
                            @if($p->status !== 'active')<span class="calv-badge" style="margin-left: 6px;">{{ ucfirst($p->status) }}</span>@endif
                        </div>
                        <div class="text-[11.5px] mt-0.5" style="color: var(--calv-slate-65);">
                            {{ $p->participant?->full_name ?? 'No homeowner linked' }}
                            @if($p->propertyType) · {{ $p->propertyType->label }}@endif
                            · {{ $p->jobs_count }} {{ Str::plural('project', $p->jobs_count) }}
                            · {{ $p->notes_count }} {{ Str::plural('note', $p->notes_count) }}
                        </div>
                    </div>
                    @if($canManage || $canNote)
                        <div class="flex items-center gap-1.5 shrink-0">
                            @if($canNote)
                                <button type="button" wire:click="openNote({{ $p->id }})" class="calv-btn calv-btn--quiet calv-btn--sm">+ Note</button>
                            @endif
                            @if($canManage)
                                <button type="button" wire:click="openEdit({{ $p->id }})" class="calv-btn calv-btn--quiet calv-btn--sm">Edit</button>
                                <button type="button" wire:click="toggleStatus({{ $p->id }})" class="calv-btn calv-btn--ghost calv-btn--sm">{{ $p->status === 'active' ? 'Deactivate' : 'Activate' }}</button>
                            @endif
                            @if($canDelete)
                                <button type="button" wire:click="delete({{ $p->id }})" wire:confirm="Delete this property? Only possible when it has no projects." class="calv-btn calv-btn--ghost calv-btn--sm" style="color: var(--calv-red);">Delete</button>
                            @endif
                        </div>
                    @endif
                </div>
                @if($notingPropertyId === $p->id)
                    <div class="flex items-start gap-2 px-4 pb-3.5">
                        <textarea wire:model="noteText" rows="2" class="calv-input flex-1" style="resize: vertical;" placeholder="What's worth remembering about this property?"></textarea>
                        <div class="flex flex-col gap-1.5">
                            <button type="button" wire:click="saveNote" class="calv-btn calv-btn--primary calv-btn--sm">Save</button>
                            <button type="button" wire:click="$set('notingPropertyId', null)" class="calv-btn calv-btn--ghost calv-btn--sm">Cancel</button>
                        </div>
                    </div>
                    @error('noteText')<p class="px-4 pb-2 text-xs" style="color: var(--calv-red);">{{ $message }}</p>@enderror
                @endif
            </div>
        @empty
            <div class="px-6 py-8 text-center text-sm" style="color: var(--calv-slate-65);">No properties match.</div>
        @endforelse
    </div>

    <div class="mt-4">{{ $properties->links() }}</div>

    {{-- Create/edit modal --}}
    @if($showForm)
        <div class="fixed inset-0 z-[100] flex items-start justify-center overflow-y-auto" style="background: rgba(40,42,44,.55); padding: 48px 16px;">
            <div class="w-full" style="max-width: 620px; background: #fff; border-radius: 10px; box-shadow: var(--shadow-lg); overflow: hidden;" wire:click.stop>
                <header class="px-6 py-4" style="border-bottom: 1px solid var(--calv-slate-15);">
                    <h2 class="calv-h1" style="font-size: 20px;">{{ $editingId ? 'Edit property' : 'New property' }}</h2>
                </header>
                <div class="px-6 py-5 grid gap-3.5" style="grid-template-columns: 1fr 1fr;">
                    <div style="grid-column: 1 / -1;">
                        <label class="calv-label">Household</label>
                        @if($form['household_id'])
                            @php $selectedHousehold = \App\Models\Household::find($form['household_id']); @endphp
                            <div class="flex items-center gap-2">
                                <span class="calv-badge calv-badge--teal">{{ $selectedHousehold?->household_code ?? 'HH #'.$form['household_id'] }}</span>
                                <span class="text-[12.5px]" style="color: var(--calv-slate-65);">{{ $selectedHousehold?->participants()->where('is_head_of_household', true)->first()?->full_name }}</span>
                                <button type="button" wire:click="$set('form.household_id', null)" class="calv-link" style="background:none;border:none;cursor:pointer;font-size:11.5px;">change</button>
                            </div>
                        @else
                            <input wire:model.live.debounce.400ms="householdSearch" type="search" class="calv-input" placeholder="Search by household code or member name…">
                            @if($households->count() > 0)
                                <div class="flex flex-col gap-1 mt-1.5">
                                    @foreach($households as $hh)
                                        <button type="button" wire:click="$set('form.household_id', {{ $hh->id }})"
                                                class="text-left rounded px-3 py-2 text-[12.5px]" style="border: 1px solid var(--calv-slate-15); background: #fff; cursor: pointer; color: var(--calv-slate);">
                                            <strong>{{ $hh->household_code }}</strong> — {{ $hh->participants()->where('is_head_of_household', true)->first()?->full_name ?? 'no head listed' }}
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        @endif
                        @error('form.household_id')<p class="mt-1 text-xs" style="color: var(--calv-red);">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="calv-label">Property type</label>
                        <select wire:model="form.property_type_id" class="calv-input">
                            <option value="">—</option>
                            @foreach($propertyTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->label }}</option>
                            @endforeach
                        </select>
                        @error('form.property_type_id')<p class="mt-1 text-xs" style="color: var(--calv-red);">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="calv-label">Status</label>
                        <select wire:model="form.status" class="calv-input">
                            @foreach(['active' => 'Active', 'inactive' => 'Inactive', 'sold' => 'Sold', 'demolished' => 'Demolished'] as $v => $l)
                                <option value="{{ $v }}">{{ $l }}</option>
                            @endforeach
                        </select>
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
                    <div class="grid gap-3" style="grid-template-columns: 80px 1fr;">
                        <div>
                            <label class="calv-label">State</label>
                            <input wire:model="form.state" type="text" maxlength="2" class="calv-input">
                        </div>
                        <div>
                            <label class="calv-label">Zip</label>
                            <input wire:model="form.zip_code" type="text" class="calv-input">
                            @error('form.zip_code')<p class="mt-1 text-xs" style="color: var(--calv-red);">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div>
                        <label class="calv-label">County</label>
                        <input wire:model="form.county" type="text" class="calv-input">
                        @error('form.county')<p class="mt-1 text-xs" style="color: var(--calv-red);">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid gap-3" style="grid-template-columns: 1fr 1fr;">
                        <div>
                            <label class="calv-label">Year built</label>
                            <input wire:model="form.year_built" type="number" class="calv-input">
                            @error('form.year_built')<p class="mt-1 text-xs" style="color: var(--calv-red);">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="calv-label">Sq ft</label>
                            <input wire:model="form.square_footage" type="number" class="calv-input">
                        </div>
                    </div>
                    <div class="grid gap-3" style="grid-template-columns: 1fr 1fr;">
                        <div>
                            <label class="calv-label">Bedrooms</label>
                            <input wire:model="form.bedrooms" type="number" class="calv-input">
                        </div>
                        <div>
                            <label class="calv-label">Bathrooms</label>
                            <input wire:model="form.bathrooms" type="number" step="0.5" class="calv-input">
                        </div>
                    </div>
                    <div style="grid-column: 1 / -1;">
                        <label class="calv-label">Description <span style="font-weight:400; text-transform:none; letter-spacing:0; color: var(--calv-slate-65);">— optional</span></label>
                        <textarea wire:model="form.property_description" rows="2" class="calv-input" style="resize: vertical;"></textarea>
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
