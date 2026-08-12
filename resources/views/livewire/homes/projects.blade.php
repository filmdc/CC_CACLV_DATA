<div class="px-6 lg:px-9 py-7 max-w-6xl mx-auto" style="min-height: 100vh;">

    @php
        $statusBadge = [
            'attention' => ['label' => 'Needs attention', 'class' => 'calv-badge calv-badge--red'],
            'active' => ['label' => 'Moving along', 'class' => 'calv-badge calv-badge--teal'],
            'finished' => ['label' => 'Finished', 'class' => 'calv-badge calv-badge--sage'],
        ];
    @endphp

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
    </div>

    {{-- Rows --}}
    <div class="calv-card overflow-hidden">
        @forelse($rows as $r)
            <a href="{{ route('homes.project', $r['id']) }}" wire:navigate
               class="flex items-center gap-4 px-4 py-3.5"
               style="border-bottom: 1px solid var(--calv-slate-15); background: {{ $r['status'] === 'attention' ? 'var(--calv-red-15)' : '#fff' }}; text-decoration: none;">
                <div class="min-w-0" style="flex: 1 1 26%;">
                    <div class="text-[14.5px] font-semibold" style="color: var(--calv-slate);">{{ $r['who'] }}</div>
                    <div class="text-xs mt-0.5" style="color: var(--calv-slate-65);">{{ $r['where'] }}</div>
                </div>
                <div class="flex flex-col gap-1 items-start" style="flex: 0 0 160px;">
                    <span class="{{ $statusBadge[$r['status']]['class'] }}">{{ $statusBadge[$r['status']]['label'] }}</span>
                    <span class="text-[11px]" style="color: var(--calv-slate-65);">{{ $r['program'] ? $r['program'].' · ' : '' }}{{ Str::limit($r['title'], 34) }}</span>
                </div>
                <div style="flex: 0 0 180px;">
                    <div class="flex justify-between text-[11px] mb-1" style="color: var(--calv-slate-65);">
                        <span>{{ $r['ms_done'] }} of {{ $r['ms_total'] }} milestones</span>
                        <span>${{ number_format($r['spent']) }} / ${{ number_format($r['budget']) }}</span>
                    </div>
                    @php $pct = $r['budget'] > 0 ? min(100, (int) round($r['spent'] / $r['budget'] * 100)) : 0; @endphp
                    <div style="background: var(--calv-slate-15); border-radius: 999px; height: 6px; overflow: hidden;">
                        <div style="width: {{ $pct }}%; height: 100%; background: {{ $pct >= 100 ? 'var(--calv-sage)' : 'var(--calv-teal)' }}; border-radius: 999px;"></div>
                    </div>
                </div>
                <div class="min-w-0 text-right" style="flex: 1 1 26%;">
                    <div class="text-[12.5px] font-semibold leading-snug" style="color: {{ $r['status'] === 'attention' ? 'var(--calv-red)' : 'var(--calv-slate)' }};">{{ Str::limit($r['next'], 70) }}</div>
                    <div class="text-[11px] mt-0.5" style="color: var(--calv-slate-65);">{{ $r['next_due'] }}</div>
                </div>
                <span class="calv-btn calv-btn--quiet calv-btn--sm shrink-0">Open</span>
            </a>
        @empty
            <div class="px-6 py-8 text-center text-sm" style="color: var(--calv-slate-65);">
                No projects match — try "Everything" or clear the search.
            </div>
        @endforelse
    </div>

    <div class="mt-3 text-center text-xs" style="color: var(--calv-slate-65);">Finished projects live under "Everything" — nothing is ever deleted.</div>
</div>
