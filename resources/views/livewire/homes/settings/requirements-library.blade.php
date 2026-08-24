<div class="px-6 lg:px-9 py-7 max-w-5xl mx-auto" style="min-height: 100vh;">

    @if (session('homes_message'))
        <div class="mb-4 rounded-md px-4 py-3 text-sm" style="background: var(--calv-sage-15); color: #2F5A41; border: 1px solid var(--calv-sage-35);">
            {{ session('homes_message') }}
        </div>
    @endif

    @php
        $categoryLabel = [
            'eligibility' => ['Eligibility', 'calv-badge calv-badge--teal'],
            'compliance' => ['Compliance', 'calv-badge calv-badge--amber'],
            'service_delivery_compliance' => ['Project phase', 'calv-badge calv-badge--sage'],
        ];
        $kindLabel = ['doc' => 'Document', 'form' => 'Form', 'data' => 'Data', 'verify' => 'Verification', 'external' => 'External check'];
    @endphp

    <a href="{{ route('homes.settings') }}" wire:navigate class="calv-link inline-block mb-3" style="font-size: 12.5px;">← Settings</a>

    <div class="flex items-end gap-4 mb-5">
        <div class="flex-1 min-w-0">
            <h1 class="calv-h1" style="font-size: 30px;">Requirements library</h1>
            <div class="text-[13.5px] mt-1.5" style="color: var(--calv-slate-65);">The reusable checklist items behind intakes and projects. Retiring one never touches existing cases.</div>
        </div>
        @if($canManage)
            <button type="button" wire:click="openCreate" class="calv-btn calv-btn--primary">+ Item</button>
        @endif
    </div>

    {{-- Search + filters --}}
    <div class="flex flex-wrap gap-2.5 items-center mb-4">
        <div class="relative flex-1" style="min-width: 240px; max-width: 360px;">
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search an item…" class="calv-input" style="border-radius: 8px;">
        </div>
        <select wire:model.live="categoryFilter" class="calv-input" style="width: auto;">
            <option value="">All categories</option>
            @foreach($categories as $cat)
                <option value="{{ $cat }}">{{ $categoryLabel[$cat][0] ?? $cat }}</option>
            @endforeach
        </select>
        <label class="flex items-center gap-2 text-[12px]" style="color: var(--calv-slate-65);">
            <input wire:model.live="showTrashed" type="checkbox" style="accent-color: var(--calv-slate);"> show retired
        </label>
    </div>

    {{-- Rows --}}
    <div class="calv-card overflow-hidden">
        @forelse($atoms as $atom)
            <div class="flex items-center gap-4 px-4 py-3" style="border-bottom: 1px solid var(--calv-slate-15); {{ $atom->trashed() ? 'opacity: .55;' : '' }}">
                <div class="flex-1 min-w-0">
                    <div class="text-[13.5px] font-semibold" style="color: var(--calv-slate);">{{ $atom->label }}
                        @if($atom->is_default_milestone)<span class="calv-badge calv-badge--teal" style="font-size: 9px; margin-left: 6px;">Default milestone</span>@endif
                        @if(!$atom->default_required)<span class="calv-badge" style="font-size: 9px; margin-left: 6px;">Optional</span>@endif
                    </div>
                    <div class="text-[11px] mt-0.5" style="color: var(--calv-slate-65);">
                        {{ $kindLabel[$atom->kind] ?? $atom->kind }} · <span style="font-family: ui-monospace, Menlo, monospace;">{{ $atom->key }}</span>
                        @if($atom->hint) · {{ Str::limit($atom->hint, 60) }}@endif
                    </div>
                </div>
                <span class="{{ $categoryLabel[$atom->category][1] ?? 'calv-badge' }}">{{ $categoryLabel[$atom->category][0] ?? $atom->category }}</span>
                @if($canManage)
                    <div class="flex items-center gap-1.5 shrink-0">
                        @if($atom->trashed())
                            <button type="button" wire:click="restore({{ $atom->id }})" class="calv-btn calv-btn--quiet calv-btn--sm">Restore</button>
                        @else
                            <button type="button" wire:click="openEdit({{ $atom->id }})" class="calv-btn calv-btn--quiet calv-btn--sm">Edit</button>
                            <button type="button" wire:click="delete({{ $atom->id }})" wire:confirm="Retire '{{ $atom->label }}'? Existing cases keep their copies." class="calv-btn calv-btn--ghost calv-btn--sm" style="color: var(--calv-red);">Retire</button>
                        @endif
                    </div>
                @endif
            </div>
        @empty
            <div class="px-6 py-8 text-center text-sm" style="color: var(--calv-slate-65);">{{ $showTrashed ? 'Nothing retired.' : 'No items match.' }}</div>
        @endforelse
    </div>

    <div class="mt-4">{{ $atoms->links() }}</div>

    {{-- Create/edit modal --}}
    @if($showForm)
        <div class="fixed inset-0 z-[100] flex items-start justify-center overflow-y-auto" style="background: rgba(40,42,44,.55); padding: 56px 16px;">
            <div class="w-full" style="max-width: 500px; background: #fff; border-radius: 10px; box-shadow: var(--shadow-lg); overflow: hidden;" wire:click.stop>
                <header class="px-6 py-4" style="border-bottom: 1px solid var(--calv-slate-15);">
                    <h2 class="calv-h1" style="font-size: 20px;">{{ $editingId ? 'Edit item' : 'New library item' }}</h2>
                </header>
                <div class="px-6 py-5 flex flex-col gap-3.5">
                    <div>
                        <label class="calv-label">Label</label>
                        <input wire:model.live.debounce.400ms="atomLabel" type="text" class="calv-input" placeholder="e.g. Photo ID">
                        @error('atomLabel')<p class="mt-1 text-xs" style="color: var(--calv-red);">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="calv-label">Key <span style="font-weight:400; text-transform:none; letter-spacing:0; color: var(--calv-slate-65);">— lowercase, dashes only</span></label>
                        <input wire:model="atomKey" type="text" class="calv-input" style="font-family: ui-monospace, Menlo, monospace;">
                        @error('atomKey')<p class="mt-1 text-xs" style="color: var(--calv-red);">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid gap-3" style="grid-template-columns: 1fr 1fr;">
                        <div>
                            <label class="calv-label">Kind</label>
                            <select wire:model="atomKind" class="calv-input">
                                @foreach($kinds as $kind)
                                    <option value="{{ $kind }}">{{ $kindLabel[$kind] ?? $kind }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="calv-label">Category</label>
                            <select wire:model="atomCategory" class="calv-input">
                                @foreach($categories as $cat)
                                    <option value="{{ $cat }}">{{ $categoryLabel[$cat][0] ?? $cat }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="calv-label">Hint <span style="font-weight:400; text-transform:none; letter-spacing:0; color: var(--calv-slate-65);">— shown to the worker, optional</span></label>
                        <textarea wire:model="atomHint" rows="2" class="calv-input" style="resize: vertical;"></textarea>
                    </div>
                    <label class="flex items-center gap-2.5">
                        <input wire:model="atomDefaultRequired" type="checkbox" style="accent-color: var(--calv-red);">
                        <span class="text-sm" style="color: var(--calv-slate);">Required by default</span>
                    </label>
                    <label class="flex items-center gap-2.5">
                        <input wire:model="atomIsDefaultMilestone" type="checkbox" style="accent-color: var(--calv-red);">
                        <span class="text-sm" style="color: var(--calv-slate);">Seed as a milestone on every new project</span>
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
