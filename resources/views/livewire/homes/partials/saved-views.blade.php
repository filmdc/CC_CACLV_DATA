{{--
    Saved-views control for portal list screens (Calls & Intakes, Projects).
    Include with: @include('livewire.homes.partials.saved-views', ['supportsExpanded' => bool])
    Relies on the HasSavedViews trait on the host component.
--}}
@php
    $activeView = $this->activeViewId ? $this->savedViews->firstWhere('id', $this->activeViewId) : null;
    $columnOptions = $this->columnOptions();
    $lockedColumns = $this->lockedColumns();
    $pillOptions = $this->pillOptions();
@endphp

<div x-data="{ open: false }" class="relative shrink-0">
    <button
        type="button"
        x-on:click="open = !open"
        class="inline-flex items-center gap-1.5"
        style="font-family: var(--font-sub); font-size: 11px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; padding: 8px 14px; border-radius: 8px; cursor: pointer;
               background: {{ $activeView ? 'var(--calv-slate)' : '#fff' }}; color: {{ $activeView ? '#fff' : 'var(--calv-slate)' }};
               border: 1px solid var(--calv-slate-35);"
    >
        ☰ View: {{ $activeView?->name ?? 'Standard' }}
        <span aria-hidden="true" style="font-size: 9px;">▾</span>
    </button>

    <div
        x-show="open"
        x-on:click.outside="open = false"
        x-transition.opacity.duration.100ms
        x-cloak
        class="absolute right-0 mt-1.5 z-30"
        style="min-width: 260px; background: #fff; border: 1px solid var(--calv-slate-15); border-radius: 10px; box-shadow: 0 12px 28px rgba(30,41,59,.16); padding: 6px;"
    >
        <button
            type="button"
            wire:click="applyStandardView"
            x-on:click="open = false"
            class="w-full flex items-center justify-between gap-2 text-left px-3 py-2 rounded-md text-[12.5px]"
            style="color: var(--calv-slate); background: {{ $activeView === null ? 'var(--calv-slate-15)' : 'transparent' }}; cursor: pointer; border: 0;"
        >
            <span class="font-semibold">Standard view</span>
            @if($activeView === null)<span style="color: var(--calv-teal);">✓</span>@endif
        </button>

        @if($this->savedViews->isNotEmpty())
            <div style="border-top: 1px solid var(--calv-slate-15); margin: 5px 0;"></div>
            @foreach($this->savedViews as $view)
                <div class="flex items-center gap-1 rounded-md" style="background: {{ $this->activeViewId === $view->id ? 'var(--calv-slate-15)' : 'transparent' }};">
                    <button
                        type="button"
                        wire:click="applyView({{ $view->id }})"
                        x-on:click="open = false"
                        class="flex-1 min-w-0 flex items-center gap-1.5 text-left px-3 py-2 text-[12.5px]"
                        style="color: var(--calv-slate); cursor: pointer; border: 0; background: transparent;"
                    >
                        <span class="font-semibold truncate">{{ $view->name }}</span>
                        @if($view->is_default)
                            <span class="calv-badge calv-badge--teal" style="font-size: 9px;">Default</span>
                        @endif
                        @if($this->activeViewId === $view->id)<span style="color: var(--calv-teal);">✓</span>@endif
                    </button>
                    @if(! $view->is_default)
                        <button type="button" wire:click="makeDefaultView({{ $view->id }})" title="Make this my default view"
                                class="px-1.5 py-1 text-[13px]" style="color: var(--calv-slate-65); cursor: pointer; border: 0; background: transparent;">☆</button>
                    @else
                        <button type="button" wire:click="clearDefaultView" title="Stop using as default"
                                class="px-1.5 py-1 text-[13px]" style="color: var(--calv-teal); cursor: pointer; border: 0; background: transparent;">★</button>
                    @endif
                    <button type="button" wire:click="openEditView({{ $view->id }})" x-on:click="open = false" title="Edit view"
                            class="px-1.5 py-1 text-[12px]" style="color: var(--calv-slate-65); cursor: pointer; border: 0; background: transparent;">✎</button>
                    <button type="button" wire:click="deleteView({{ $view->id }})" wire:confirm="Delete the view “{{ $view->name }}”?" title="Delete view"
                            class="px-1.5 py-1 mr-1 text-[12px]" style="color: var(--calv-red); cursor: pointer; border: 0; background: transparent;">✕</button>
                </div>
            @endforeach
        @endif

        <div style="border-top: 1px solid var(--calv-slate-15); margin: 5px 0;"></div>
        <button
            type="button"
            wire:click="openCreateView"
            x-on:click="open = false"
            class="w-full text-left px-3 py-2 rounded-md text-[12.5px] font-semibold"
            style="color: var(--calv-teal); cursor: pointer; border: 0; background: transparent;"
        >
            + New custom view…
        </button>
    </div>
</div>

{{-- View editor modal --}}
@if($showViewModal)
    <div class="fixed inset-0 z-40 flex items-center justify-center p-4" style="background: rgba(30,41,59,.45);" wire:click.self="closeViewModal">
        <div class="calv-card w-full overflow-auto" style="max-width: 460px; max-height: 90vh; padding: 22px;">
            <h2 class="calv-h1" style="font-size: 19px;">{{ $editingViewId ? 'Edit view' : 'New custom view' }}</h2>
            <div class="text-xs mt-1 mb-4" style="color: var(--calv-slate-65);">Pick the columns you want, the filter it starts on, and give it a name.</div>

            {{-- Name --}}
            <label class="block text-[11px] font-bold uppercase mb-1" style="font-family: var(--font-sub); letter-spacing: .05em; color: var(--calv-slate-65);">View name</label>
            <input type="text" wire:model="viewName" wire:keydown.enter="saveView" maxlength="60" placeholder="e.g. My intake queue" class="calv-input" style="border-radius: 8px;">
            @error('viewName')<div class="text-xs mt-1" style="color: var(--calv-red);">{{ $message }}</div>@enderror

            {{-- Columns --}}
            <div class="mt-4">
                <label class="block text-[11px] font-bold uppercase mb-1.5" style="font-family: var(--font-sub); letter-spacing: .05em; color: var(--calv-slate-65);">Columns to show</label>
                <div class="grid grid-cols-2 gap-x-3 gap-y-1.5">
                    @foreach($columnOptions as $key => $label)
                        @php $locked = in_array($key, $lockedColumns, true); @endphp
                        <label class="flex items-center gap-2 text-[12.5px]" style="color: var(--calv-slate); {{ $locked ? 'opacity: .55;' : 'cursor: pointer;' }}">
                            <input
                                type="checkbox"
                                @if($locked) checked disabled @else wire:click="toggleViewColumn('{{ $key }}')" @checked(in_array($key, $viewColumns, true)) @endif
                                class="rounded"
                                style="accent-color: var(--calv-teal);"
                            >
                            {{ $label }}@if($locked)<span class="text-[10px]" style="color: var(--calv-slate-65);">(always)</span>@endif
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- Default filter --}}
            <div class="mt-4">
                <label class="block text-[11px] font-bold uppercase mb-1.5" style="font-family: var(--font-sub); letter-spacing: .05em; color: var(--calv-slate-65);">Starts filtered to</label>
                <div class="flex flex-wrap gap-1.5">
                    @foreach($pillOptions as $key => $label)
                        @php $activePill = ($viewFilters['pill'] ?? '') === $key; @endphp
                        <button
                            type="button"
                            wire:click="$set('viewFilters.pill', '{{ $key }}')"
                            style="font-family: var(--font-sub); font-size: 11px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; padding: 6px 12px; border-radius: 999px; cursor: pointer;
                                   background: {{ $activePill ? 'var(--calv-slate)' : '#fff' }}; color: {{ $activePill ? '#fff' : 'var(--calv-slate)' }};
                                   border: 1px solid {{ $activePill ? 'var(--calv-slate)' : 'var(--calv-slate-35)' }};"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Expanded toggle (Calls only) --}}
            @if($supportsExpanded ?? false)
                <label class="flex items-center gap-2 mt-4 text-[12.5px]" style="color: var(--calv-slate); cursor: pointer;">
                    <input type="checkbox" wire:model="viewFilters.expanded" class="rounded" style="accent-color: var(--calv-teal);">
                    Start in Expanded view (full table)
                </label>
            @endif

            {{-- Default --}}
            <label class="flex items-center gap-2 mt-3 text-[12.5px]" style="color: var(--calv-slate); cursor: pointer;">
                <input type="checkbox" wire:model="viewMakeDefault" class="rounded" style="accent-color: var(--calv-teal);">
                Make this my default view for this page
            </label>

            {{-- Actions --}}
            <div class="flex justify-end gap-2 mt-5">
                <button type="button" wire:click="closeViewModal" class="calv-btn calv-btn--quiet">Cancel</button>
                <button type="button" wire:click="saveView" class="calv-btn calv-btn--primary">{{ $editingViewId ? 'Save changes' : 'Create view' }}</button>
            </div>
        </div>
    </div>
@endif
