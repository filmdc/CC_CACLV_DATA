<div class="px-6 lg:px-9 py-7 max-w-6xl mx-auto" style="min-height: 100vh;">

    @php
        $badgeFor = [
            'call' => 'calv-badge',
            'collecting' => 'calv-badge calv-badge--amber',
            'ready' => 'calv-badge calv-badge--sage',
            'converted' => 'calv-badge calv-badge--teal',
        ];
    @endphp

    {{-- Header --}}
    <div class="flex items-end gap-4 mb-5">
        <div class="flex-1 min-w-0">
            <h1 class="calv-h1" style="font-size: 30px;">Calls &amp; Intakes</h1>
            <div class="text-[13.5px] mt-1.5" style="color: var(--calv-slate-65);">Every call you've logged. The ones needing something float to the top.</div>
        </div>
        @can('communications_log.create')
            <button type="button" x-on:click="Livewire.dispatch('homes-open-quick-log')" class="calv-btn calv-btn--primary">+ Log a call</button>
        @endcan
    </div>

    {{-- Search + pills + expanded toggle --}}
    <div class="flex flex-wrap gap-2.5 items-center mb-4">
        <div class="relative flex-1" style="min-width: 260px; max-width: 380px;">
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search a name or address…" class="calv-input" style="border-radius: 8px;">
        </div>

        @php
            $pills = [
                ['needs', 'Needs something', $needsCount],
                ['collecting', 'Collecting docs', $collectingCount],
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

        <button
            type="button"
            wire:click="toggleExpanded"
            style="font-family: var(--font-sub); font-size: 11px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; padding: 8px 14px; border-radius: 8px; cursor: pointer;
                   background: {{ $expanded ? 'var(--calv-teal)' : 'transparent' }}; color: {{ $expanded ? '#fff' : 'var(--calv-slate-65)' }};
                   border: 1px solid {{ $expanded ? 'var(--calv-teal)' : 'var(--calv-slate-35)' }};"
        >
            ⇥ Expanded view {{ $expanded ? 'on' : '' }}
        </button>

        @include('livewire.homes.partials.saved-views', ['supportsExpanded' => true])
    </div>

    {{-- List --}}
    @if($logs->count() === 0)
        <div class="calv-card px-6 py-8 text-center text-sm" style="color: var(--calv-slate-65);">
            No calls match — try "Everything" or clear the search.
        </div>
    @elseif($expanded)
        {{-- Expanded power table (columns follow the active view) --}}
        @php $visible = collect($this->columnOptions())->only($columns); @endphp
        <div class="calv-card overflow-auto">
            <table class="w-full border-collapse" style="min-width: {{ 120 + $visible->count() * 100 }}px;">
                <thead>
                    <tr style="background: var(--calv-slate); color: #fff;">
                        @foreach($visible as $h)
                            <th class="text-left px-3 py-2.5 whitespace-nowrap" style="font-family: var(--font-sub); font-size: 10px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase;">{{ $h }}</th>
                        @endforeach
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $row)
                        <tr style="background: {{ $row['overdue'] ? 'var(--calv-red-15)' : '#fff' }}; border-top: 1px solid var(--calv-slate-15);">
                            @foreach($visible as $key => $h)
                                @switch($key)
                                    @case('who')
                                        <td class="px-3 py-2.5 text-[12.5px] font-semibold" style="color: var(--calv-slate);">{{ $row['who'] }}</td>
                                        @break
                                    @case('where')
                                        <td class="px-3 py-2.5 text-[12.5px]" style="color: var(--calv-slate);">{{ $row['where'] ?: '—' }}</td>
                                        @break
                                    @case('note')
                                        <td class="px-3 py-2.5 text-[12.5px]" style="color: var(--calv-slate-65); min-width: 200px;">{{ Str::limit($row['note'], 90) ?: '—' }}</td>
                                        @break
                                    @case('stage')
                                        <td class="px-3 py-2.5"><span class="{{ $badgeFor[$row['stage_key']] ?? 'calv-badge' }}">{{ $row['stage_label'] }}</span></td>
                                        @break
                                    @case('docs')
                                        <td class="px-3 py-2.5 text-[12.5px]" style="color: var(--calv-slate);">{{ $row['reqs'] ?: '—' }}</td>
                                        @break
                                    @case('last')
                                        <td class="px-3 py-2.5 text-[12.5px]" style="color: var(--calv-slate);">{{ $row['last'] }}</td>
                                        @break
                                    @case('next')
                                        <td class="px-3 py-2.5 text-[12.5px]" style="color: {{ $row['overdue'] ? 'var(--calv-red)' : 'var(--calv-slate)' }};">{{ $row['next'] }}</td>
                                        @break
                                    @case('type')
                                        <td class="px-3 py-2.5 text-[12.5px]" style="color: var(--calv-slate);">{{ $row['type'] }}</td>
                                        @break
                                    @case('direction')
                                        <td class="px-3 py-2.5 text-[12.5px]" style="color: var(--calv-slate);">{{ $row['direction'] }}</td>
                                        @break
                                @endswitch
                            @endforeach
                            <td class="px-3 py-2.5"><a href="{{ route('homes.call', $row['id']) }}" wire:navigate class="calv-btn calv-btn--quiet calv-btn--sm">Open</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        {{-- Simple person-first rows --}}
        <div class="calv-card overflow-hidden">
            @foreach($logs as $row)
                <div class="flex items-center gap-4 px-4 py-3.5" style="border-bottom: 1px solid var(--calv-slate-15); background: {{ $row['overdue'] ? 'var(--calv-red-15)' : '#fff' }};">
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
                    <a href="{{ route('homes.call', $row['id']) }}" wire:navigate class="calv-btn calv-btn--quiet calv-btn--sm shrink-0">Open</a>
                </div>
            @endforeach
        </div>
    @endif

    <div class="mt-4">
        {{ $logs->links() }}
    </div>
</div>
