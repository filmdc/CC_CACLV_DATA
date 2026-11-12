{{--
    Sort control for portal list screens (Calls & Intakes, Projects).
    Include with:
        @include('livewire.homes.partials.sort-control')

    The dropdown covers every screen, including the views with no clickable
    header (the simple Calls list) — screens that do have a header keep both
    in step, since they read the same state off HasSortableColumns.
--}}
@php
    $sortOptions = $this->sortOptions();
    $sortIsExplicit = $this->sort !== '';
@endphp

<div class="inline-flex items-center gap-1 shrink-0">
    <label class="sr-only" for="homes-sort-{{ $this->getId() }}">Sort the list by</label>
    <select
        id="homes-sort-{{ $this->getId() }}"
        wire:model.live="sort"
        class="calv-input"
        style="width: auto; min-width: 150px; border-radius: 8px; color: {{ $sortIsExplicit ? 'var(--calv-slate)' : 'var(--calv-slate-65)' }};">
        <option value="">Sort: {{ $this->defaultSortLabel() }}</option>
        @foreach($sortOptions as $key => $label)
            <option value="{{ $key }}">Sort: {{ $label }}</option>
        @endforeach
    </select>

    <button
        type="button"
        wire:click="toggleSortDir"
        title="{{ $this->sortDirLabel() }}"
        aria-label="{{ $this->sortDirLabel() }}"
        @disabled(! $sortIsExplicit)
        class="inline-flex items-center justify-center shrink-0"
        style="width: 34px; height: 34px; border-radius: 8px; line-height: 1; padding: 0; background: #fff;
               border: 1px solid var(--calv-slate-35);
               color: {{ $sortIsExplicit ? 'var(--calv-slate)' : 'var(--calv-slate-65)' }};
               cursor: {{ $sortIsExplicit ? 'pointer' : 'default' }}; opacity: {{ $sortIsExplicit ? '1' : '.5' }};">
        <span aria-hidden="true" style="font-size: 11px;">{{ $this->sortDir === 'asc' ? '▲' : '▼' }}</span>
    </button>
</div>
