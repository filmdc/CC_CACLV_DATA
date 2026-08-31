<div class="px-6 lg:px-9 py-7 max-w-5xl mx-auto" style="min-height: 100vh;">

    @if (session('homes_message'))
        <div class="mb-4 rounded-md px-4 py-3 text-sm" style="background: var(--calv-sage-15); color: #2F5A41; border: 1px solid var(--calv-sage-35);">
            {{ session('homes_message') }}
        </div>
    @endif

    <a href="{{ route('homes.settings') }}" wire:navigate class="calv-link inline-block mb-3" style="font-size: 12.5px;">← Settings</a>

    <div class="flex items-end gap-4 mb-5">
        <div class="flex-1 min-w-0">
            <h1 class="calv-h1" style="font-size: 30px;">PDF form templates</h1>
            <div class="text-[13.5px] mt-1.5" style="color: var(--calv-slate-65);">Fillable forms generated from participant data. Upload here; field mapping happens in the main app.</div>
        </div>
        @if($canManage)
            <button type="button" wire:click="openCreate" class="calv-btn calv-btn--primary" @if(!$pdftkAvailable) disabled title="pdftk unavailable on this server" @endif>+ Template</button>
        @endif
    </div>

    @unless($pdftkAvailable)
        <div class="mb-4 rounded-lg px-4 py-3 text-[12.5px]" style="background: var(--calv-amber-15); border-left: 3px solid var(--calv-amber); color: var(--calv-slate);">
            PDF field scanning (pdftk) isn't available on this server — existing templates still work, but new uploads are disabled.
        </div>
    @endunless

    <div class="flex flex-wrap gap-2.5 items-center mb-4">
        <div class="relative flex-1" style="min-width: 240px; max-width: 360px;">
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search a template…" class="calv-input" style="border-radius: 8px;">
        </div>
    </div>

    {{-- Rows --}}
    <div class="calv-card overflow-hidden">
        @forelse($templates as $t)
            <div class="flex items-center gap-4 px-4 py-3.5" style="border-bottom: 1px solid var(--calv-slate-15); {{ $t->is_active ? '' : 'opacity: .55;' }}">
                <div class="flex-1 min-w-0">
                    <div class="text-[14px] font-semibold" style="color: var(--calv-slate);">{{ $t->name }}
                        @if($t->category)<span class="calv-badge" style="margin-left: 6px;">{{ ucfirst($t->category) }}</span>@endif
                        @unless($t->is_active)<span class="calv-badge" style="margin-left: 6px;">Inactive</span>@endunless
                    </div>
                    <div class="text-[11.5px] mt-0.5" style="color: var(--calv-slate-65);">
                        {{ $t->original_filename }} · {{ $t->field_count }} fields ({{ $t->mapping_completion }}% mapped) · used {{ $t->usage_count }}×
                    </div>
                </div>
                <div class="flex items-center gap-1.5 shrink-0">
                    @if($canManage)
                        <a href="{{ $mainApp }}/admin/pdf-templates/{{ $t->id }}/map-fields" target="_blank" rel="noopener" class="calv-btn calv-btn--secondary calv-btn--sm">Map fields ↗</a>
                        <button type="button" wire:click="openEdit({{ $t->id }})" class="calv-btn calv-btn--quiet calv-btn--sm">Edit</button>
                        <button type="button" wire:click="toggleActive({{ $t->id }})" class="calv-btn calv-btn--ghost calv-btn--sm">{{ $t->is_active ? 'Deactivate' : 'Activate' }}</button>
                    @endif
                    @if($canDelete)
                        <button type="button" wire:click="delete({{ $t->id }})" wire:confirm="Delete '{{ $t->name }}'? The file and its field mappings are removed. Only possible when it hasn't been used." class="calv-btn calv-btn--ghost calv-btn--sm" style="color: var(--calv-red);">Delete</button>
                    @endif
                </div>
            </div>
        @empty
            <div class="px-6 py-8 text-center text-sm" style="color: var(--calv-slate-65);">No templates yet.</div>
        @endforelse
    </div>

    <div class="mt-4">{{ $templates->links() }}</div>

    {{-- Upload/edit modal --}}
    @if($showForm)
        <div class="fixed inset-0 z-[100] flex items-start justify-center overflow-y-auto" style="background: rgba(40,42,44,.55); padding: 56px 16px;">
            <div class="w-full" style="max-width: 480px; background: #fff; border-radius: 10px; box-shadow: var(--shadow-lg); overflow: hidden;" wire:click.stop>
                <header class="px-6 py-4" style="border-bottom: 1px solid var(--calv-slate-15);">
                    <h2 class="calv-h1" style="font-size: 20px;">{{ $editingId ? 'Edit template' : 'Upload template' }}</h2>
                    @unless($editingId)
                        <div class="text-xs mt-1" style="color: var(--calv-slate-65);">Form fields are discovered automatically on upload.</div>
                    @endunless
                </header>
                <div class="px-6 py-5 flex flex-col gap-3.5">
                    <div>
                        <label class="calv-label">Name</label>
                        <input wire:model="name" type="text" class="calv-input" placeholder="e.g. WHR Application">
                        @error('name')<p class="mt-1 text-xs" style="color: var(--calv-red);">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="calv-label">Description <span style="font-weight:400; text-transform:none; letter-spacing:0; color: var(--calv-slate-65);">— optional</span></label>
                        <textarea wire:model="description" rows="2" class="calv-input" style="resize: vertical;"></textarea>
                    </div>
                    <div>
                        <label class="calv-label">Category</label>
                        <select wire:model="category" class="calv-input">
                            <option value="">—</option>
                            <option value="housing">Housing</option>
                            <option value="participant">Participant</option>
                            <option value="contract">Contract</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    @unless($editingId)
                        <div>
                            <label class="calv-label">PDF file</label>
                            <input type="file" wire:model="templateFile" accept="application/pdf" class="calv-input">
                            <div wire:loading wire:target="templateFile" class="text-xs mt-1" style="color: var(--calv-slate-65);">Uploading…</div>
                            @error('templateFile')<p class="mt-1 text-xs" style="color: var(--calv-red);">{{ $message }}</p>@enderror
                        </div>
                    @endunless
                </div>
                <footer class="flex items-center gap-2.5 px-6 py-3.5 justify-end" style="border-top: 1px solid var(--calv-slate-15); background: var(--calv-sand-15);">
                    <button type="button" wire:click="closeForm" class="calv-btn calv-btn--ghost">Cancel</button>
                    <button type="button" wire:click="save" class="calv-btn calv-btn--primary" wire:loading.attr="disabled" wire:target="save,templateFile">
                        <span wire:loading.remove wire:target="save">{{ $editingId ? 'Save' : 'Upload' }}</span>
                        <span wire:loading wire:target="save">Working…</span>
                    </button>
                </footer>
            </div>
        </div>
    @endif
</div>
