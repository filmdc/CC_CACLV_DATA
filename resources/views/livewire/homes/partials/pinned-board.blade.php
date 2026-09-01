{{--
    The Today dashboard's pinned board: everything the project manager has
    pinned, calls and projects together, in the order she pinned them.

    Vars: $pinnedCalls, $pinnedProjects (both from HousingPortalData)
    Relies on the HasPins trait on the host component.
--}}
@php
    $items = collect()
        ->concat($pinnedCalls->map(fn ($row) => [
            'type' => 'call',
            'chip' => 'Call',
            'id' => $row['id'],
            'who' => $row['who'],
            'where' => $row['where'],
            'badge' => $row['stage_label'],
            'badge_class' => match ($row['stage_key']) {
                'collecting' => 'calv-badge calv-badge--amber',
                'ready' => 'calv-badge calv-badge--sage',
                'converted' => 'calv-badge calv-badge--teal',
                default => 'calv-badge',
            },
            'line' => $row['next'],
            'alert' => $row['overdue'],
            'meta' => $row['reqs'] ?: 'last touched '.$row['last'],
            'url' => route('homes.call', $row['id']),
        ]))
        ->concat($pinnedProjects->map(fn ($row) => [
            'type' => 'project',
            'chip' => 'Project',
            'id' => $row['id'],
            'who' => $row['who'],
            'where' => $row['where'],
            'badge' => match ($row['status']) {
                'attention' => 'Needs attention',
                'finished' => 'Finished',
                default => 'Moving along',
            },
            'badge_class' => match ($row['status']) {
                'attention' => 'calv-badge calv-badge--red',
                'finished' => 'calv-badge calv-badge--sage',
                default => 'calv-badge calv-badge--teal',
            },
            'line' => $row['next'],
            'alert' => $row['status'] === 'attention',
            'meta' => Str::limit($row['title'], 38).' · '.$row['ms_done'].' of '.$row['ms_total'].' milestones · $'.number_format($row['spent']).' of $'.number_format($row['budget']),
            'url' => route('homes.project', $row['id']),
        ]));
@endphp

@if($items->isNotEmpty())
    <section class="calv-card overflow-hidden mb-4">
        <header class="flex items-center gap-2.5 px-4 py-3" style="background: var(--calv-amber-15); border-bottom: 1px solid var(--calv-amber-35);">
            <span aria-hidden="true" style="font-size: 13px;">📌</span>
            <h3 class="calv-subhead flex-1" style="font-size: 12px; color: #8A6410;">Pinned</h3>
            <span class="text-[11.5px]" style="color: #8A6410;">{{ $items->count() }} {{ Str::plural('item', $items->count()) }} you asked to keep in front of you</span>
        </header>

        <div class="grid" style="grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));">
            @foreach($items as $item)
                <div class="flex items-start gap-3 px-4 py-3.5" style="border-bottom: 1px solid var(--calv-slate-15); border-right: 1px solid var(--calv-slate-15);">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="calv-subhead" style="font-size: 9.5px; color: var(--calv-slate-65); background: var(--calv-slate-15); border-radius: 4px; padding: 2px 6px;">{{ $item['chip'] }}</span>
                            <span class="text-sm font-semibold truncate" style="color: var(--calv-slate);">{{ $item['who'] }}</span>
                        </div>
                        @if($item['where'])
                            <div class="text-[12px] mt-1 truncate" style="color: var(--calv-slate-65);">{{ $item['where'] }}</div>
                        @endif
                        <div class="flex items-center gap-2 mt-2 flex-wrap">
                            <span class="{{ $item['badge_class'] }}">{{ $item['badge'] }}</span>
                            <span class="text-[12px]" style="color: {{ $item['alert'] ? 'var(--calv-red)' : 'var(--calv-slate)' }};">{{ Str::limit($item['line'], 48) }}</span>
                        </div>
                        <div class="text-[11px] mt-1" style="color: var(--calv-slate-65);">{{ $item['meta'] }}</div>
                    </div>
                    <div class="flex flex-col items-end gap-1.5 shrink-0">
                        <a href="{{ $item['url'] }}" wire:navigate class="calv-btn calv-btn--quiet calv-btn--sm">Open</a>
                        @include('livewire.homes.partials.pin-button', ['type' => $item['type'], 'id' => $item['id'], 'pinned' => true])
                    </div>
                </div>
            @endforeach
        </div>
    </section>
@endif
