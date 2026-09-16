{{--
    Sortable header strip for the Projects list. Projects are rendered as
    flex rows rather than a table (see project-row.blade.php), so the header
    mirrors the same flex bases segment by segment to stay in line with the
    rows beneath it — including the invisible "Open"/pin spacers on the right.

    A row segment carries more than one fact, so a segment's header offers a
    button per fact. Sorts with nothing to point at in the row ("Date added")
    live only in the toolbar dropdown.

    Relies on the HasSortableColumns + HasSavedViews traits on the host.
--}}
@php
    $segments = [
        'who' => ['keys' => ['who', 'where'], 'basis' => 'flex: 1 1 26%;', 'justify' => 'flex-start'],
        'status' => ['keys' => ['status', 'program'], 'basis' => 'flex: 0 0 160px;', 'justify' => 'flex-start'],
        'progress' => ['keys' => ['progress', 'budget'], 'basis' => 'flex: 0 0 180px;', 'justify' => 'flex-start'],
        'next' => ['keys' => ['next'], 'basis' => 'flex: 1 1 26%;', 'justify' => 'flex-end'],
    ];
    $sortOptions = $this->sortOptions();
@endphp

<div class="flex items-stretch" style="background: var(--calv-slate); color: #fff;">
    <div class="flex items-center gap-4 px-4 py-2 flex-1 min-w-0">
        @foreach($segments as $column => $segment)
            @continue($column !== 'who' && ! $this->showsColumn($column))
            <div class="min-w-0 flex items-center flex-wrap gap-x-2 gap-y-0.5" style="{{ $segment['basis'] }} justify-content: {{ $segment['justify'] }};">
                @foreach($segment['keys'] as $key)
                    @php $sorted = $this->isSortedBy($key); @endphp
                    <button
                        type="button"
                        wire:click="sortBy('{{ $key }}')"
                        title="Sort by {{ $sortOptions[$key] }}"
                        aria-label="Sort by {{ $sortOptions[$key] }}"
                        class="inline-flex items-center gap-1 whitespace-nowrap"
                        style="font-family: var(--font-sub); font-size: 10px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase;
                               color: inherit; padding: 3px 6px; border-radius: 5px; border: 0; cursor: pointer;
                               background: {{ $sorted ? 'rgba(255,255,255,.16)' : 'transparent' }};">
                        {{ $sortOptions[$key] }}
                        <span aria-hidden="true" style="font-size: 8px; opacity: {{ $sorted ? '1' : '.4' }};">{{ $this->sortArrow($key) ?? '⇅' }}</span>
                    </button>
                @endforeach
            </div>
        @endforeach
        {{-- Spacer matching the row's "Open" button so the segments line up. --}}
        <span class="calv-btn calv-btn--quiet calv-btn--sm shrink-0" aria-hidden="true" style="visibility: hidden;">Open</span>
    </div>
    {{-- Spacer matching the row's pin button. --}}
    <div class="flex items-center pr-3 pl-1" aria-hidden="true"><span class="block" style="width: 28px;"></span></div>
</div>
