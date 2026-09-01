{{--
    One call/intake as a person-first row (the non-expanded Calls list, and
    the pinned section above it). Row data comes from HousingPortalData::callRow().

    Vars: $row, $badgeFor, $pinned (bool), $tint (optional background override)
--}}
<div class="flex items-center gap-4 px-4 py-3.5"
     style="border-bottom: 1px solid var(--calv-slate-15); background: {{ $tint ?? ($row['overdue'] ? 'var(--calv-red-15)' : '#fff') }};">
    <div class="min-w-0" style="flex: 1 1 30%;">
        <div class="text-[14.5px] font-semibold" style="color: var(--calv-slate);">{{ $row['who'] }}</div>
        <div class="text-xs mt-0.5" style="color: var(--calv-slate-65);">{{ $row['where'] ?: '—' }}</div>
    </div>
    <div class="min-w-0 text-[12.5px] leading-snug" style="flex: 1 1 34%; color: var(--calv-slate-65);">{{ Str::limit($row['note'], 130) }}</div>
    <div class="flex flex-col gap-1 items-start" style="flex: 0 0 150px;">
        <span class="{{ $badgeFor[$row['stage_key']] ?? 'calv-badge' }}">{{ $row['stage_label'] }}</span>
        @if($row['reqs'])<span class="text-[11px]" style="color: var(--calv-slate-65);">{{ $row['reqs'] }}</span>@endif
    </div>
    <div class="min-w-0 text-right" style="flex: 1 1 24%;">
        <div class="text-[12.5px] font-semibold" style="color: {{ $row['overdue'] ? 'var(--calv-red)' : 'var(--calv-slate)' }};">{{ $row['next'] }}</div>
        <div class="text-[11px] mt-0.5" style="color: var(--calv-slate-65);">last touched {{ $row['last'] }}</div>
    </div>
    @include('livewire.homes.partials.pin-button', ['type' => 'call', 'id' => $row['id'], 'pinned' => $pinned])
    <a href="{{ route('homes.call', $row['id']) }}" wire:navigate class="calv-btn calv-btn--quiet calv-btn--sm shrink-0">Open</a>
</div>
