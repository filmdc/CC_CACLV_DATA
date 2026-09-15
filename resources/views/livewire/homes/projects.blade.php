<div class="px-6 lg:px-9 py-7 max-w-6xl mx-auto" style="min-height: 100vh;">

    @php
        $statusBadge = [
            'attention' => ['label' => 'Needs attention', 'class' => 'calv-badge calv-badge--red'],
            'active' => ['label' => 'Moving along', 'class' => 'calv-badge calv-badge--teal'],
            'finished' => ['label' => 'Finished', 'class' => 'calv-badge calv-badge--sage'],
        ];
        $hasPinned = $pinnedRows->isNotEmpty();
    @endphp

    @if (session('homes_message'))
        <div class="mb-4 rounded-md px-4 py-3 text-sm" style="background: var(--calv-sage-15); color: #2F5A41; border: 1px solid var(--calv-sage-35);">
            {{ session('homes_message') }}
        </div>
    @endif

    {{-- Header --}}
    <div class="flex items-end gap-4 mb-5">
        <div class="flex-1 min-w-0">
            <h1 class="calv-h1" style="font-size: 30px;">Projects</h1>
            <div class="text-[13.5px] mt-1.5" style="color: var(--calv-slate-65);">Active rehab jobs. New projects start from a ready intake in Calls &amp; Intakes.</div>
        </div>
    </div>

    {{-- Search + pills --}}
    <div class="flex flex-wrap gap-2.5 items-center mb-4">
        <div class="relative flex-1" style="min-width: 260px; max-width: 380px;">
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search a homeowner or address…" class="calv-input" style="border-radius: 8px;">
        </div>
        @php
            $pills = [
                ['open', 'Open', $counts['open']],
                ['attention', 'Needs attention', $counts['attention']],
                ['all', 'Everything', null],
            ];
        @endphp
        @foreach($pills as [$id, $label, $n])
            @php $active = $pill === $id; @endphp
            <button
                type="button"
                wire:click="setPill('{{ $id }}')"
                class="inline-flex items-center gap-1.5"
                style="font-family: var(--font-sub); font-size: 11px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; padding: 8px 14px; border-radius: 999px; cursor: pointer;
                       background: {{ $active ? 'var(--calv-slate)' : '#fff' }}; color: {{ $active ? '#fff' : 'var(--calv-slate)' }};
                       border: 1px solid {{ $active ? 'var(--calv-slate)' : 'var(--calv-slate-35)' }};"
            >
                {{ $label }}
                @if($n !== null)
                    <span style="border-radius: 999px; padding: 1px 7px; font-size: 10px; background: {{ $active ? 'rgba(255,255,255,.25)' : 'var(--calv-slate-15)' }};">{{ $n }}</span>
                @endif
            </button>
        @endforeach

        <span class="flex-1"></span>

        @include('livewire.homes.partials.sort-control')

        @include('livewire.homes.partials.saved-views', ['supportsExpanded' => false])
    </div>

    @if(! $this->showsPinnedSection() && $this->pinnedIds['project'] !== [])
        <div class="mb-3 text-[11.5px]" style="color: var(--calv-slate-65);">Pinned projects are hidden while you're searching, so the results below are complete.</div>
    @endif

    {{-- Rows — pinned projects ride at the top, ahead of the active filter --}}
    <div class="calv-card overflow-hidden">
        @include('livewire.homes.partials.project-sort-header')

        @if($hasPinned)
            @include('livewire.homes.partials.pinned-band', ['count' => $pinnedRows->count()])
            @foreach($pinnedRows as $r)
                @include('livewire.homes.partials.project-row', [
                    'r' => $r,
                    'statusBadge' => $statusBadge,
                    'pinned' => true,
                    'tint' => $r['status'] === 'attention' ? 'var(--calv-red-15)' : 'var(--calv-sand-15)',
                ])
            @endforeach
        @endif

        @forelse($rows as $r)
            @include('livewire.homes.partials.project-row', [
                'r' => $r,
                'statusBadge' => $statusBadge,
                'pinned' => $this->isProjectPinned($r['id']),
                'tint' => null,
            ])
        @empty
            <div class="px-6 py-8 text-center text-sm" style="color: var(--calv-slate-65);">
                {{ $hasPinned ? 'Nothing else matches — try "Everything" or clear the search.' : 'No projects match — try "Everything" or clear the search.' }}
            </div>
        @endforelse
    </div>

    <div class="mt-3 text-center text-xs" style="color: var(--calv-slate-65);">Finished projects live under "Everything" — nothing is ever deleted.</div>
</div>
