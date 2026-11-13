{{--
    One project as a list row (the Projects list, and the pinned section above
    it). Row data comes from HousingPortalData::projectRow(). The pin button
    lives outside the row link — a button nested in an anchor is invalid, and
    pinning must not navigate.

    Vars: $r, $statusBadge, $pinned (bool), $tint (optional background override)
--}}
<div class="flex items-stretch"
     style="border-bottom: 1px solid var(--calv-slate-15); background: {{ $tint ?? ($r['status'] === 'attention' ? 'var(--calv-red-15)' : '#fff') }};">
    <a href="{{ route('homes.project', $r['id']) }}" wire:navigate
       class="flex items-center gap-4 px-4 py-3.5 flex-1 min-w-0"
       style="text-decoration: none;">
        @if($this->showsColumn('number'))
            <div class="text-[12px] whitespace-nowrap" style="flex: 0 0 auto; min-width: 96px; color: var(--calv-slate-65); font-family: ui-monospace, Menlo, monospace;">{{ $r['number'] }}</div>
        @endif
        <div class="min-w-0" style="flex: 1 1 26%;">
            <div class="text-[14.5px] font-semibold" style="color: var(--calv-slate);">{{ $r['who'] }}</div>
            <div class="text-xs mt-0.5" style="color: var(--calv-slate-65);">{{ $r['where'] }}</div>
        </div>
        @if($this->showsColumn('status'))
            <div class="flex flex-col gap-1 items-start" style="flex: 0 0 160px;">
                <span class="{{ $statusBadge[$r['status']]['class'] }}">{{ $statusBadge[$r['status']]['label'] }}</span>
                <span class="text-[11px]" style="color: var(--calv-slate-65);">{{ $r['program'] ? $r['program'].' · ' : '' }}{{ Str::limit($r['title'], 34) }}</span>
            </div>
        @endif
        @if($this->showsColumn('progress'))
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
        @endif
        @if($this->showsColumn('next'))
            <div class="min-w-0 text-right" style="flex: 1 1 26%;">
                <div class="text-[12.5px] font-semibold leading-snug" style="color: {{ $r['status'] === 'attention' ? 'var(--calv-red)' : 'var(--calv-slate)' }};">{{ Str::limit($r['next'], 70) }}</div>
                <div class="text-[11px] mt-0.5" style="color: var(--calv-slate-65);">{{ $r['next_due'] }}</div>
            </div>
        @endif
        <span class="calv-btn calv-btn--quiet calv-btn--sm shrink-0">Open</span>
    </a>
    <div class="flex items-center pr-3 pl-1">
        @include('livewire.homes.partials.pin-button', ['type' => 'project', 'id' => $r['id'], 'pinned' => $pinned])
    </div>
</div>
