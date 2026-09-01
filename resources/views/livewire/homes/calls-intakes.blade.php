<div class="px-6 lg:px-9 py-7 max-w-6xl mx-auto" style="min-height: 100vh;">

    @php
        $badgeFor = [
            'call' => 'calv-badge',
            'collecting' => 'calv-badge calv-badge--amber',
            'ready' => 'calv-badge calv-badge--sage',
            'converted' => 'calv-badge calv-badge--teal',
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
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search a name, address, or zip…" class="calv-input" style="border-radius: 8px;">
        </div>

        <select wire:model.live="city" class="calv-input" style="width: auto; min-width: 130px; border-radius: 8px; color: {{ $city !== '' ? 'var(--calv-slate)' : 'var(--calv-slate-65)' }};">
            <option value="">Any city</option>
            @foreach($cities as $c)
                <option value="{{ $c }}">{{ $c }}</option>
            @endforeach
        </select>

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

    @if(! $this->showsPinnedSection() && $this->pinnedIds['call'] !== [])
        <div class="mb-3 text-[11.5px]" style="color: var(--calv-slate-65);">Pinned calls are hidden while you're searching, so the results below are complete.</div>
    @endif

    {{-- List — pinned rows ride at the top, ahead of the active filter --}}
    @if($logs->count() === 0 && ! $hasPinned)
        <div class="calv-card px-6 py-8 text-center text-sm" style="color: var(--calv-slate-65);">
            No calls match — try "Everything" or clear the search.
        </div>
    @elseif($expanded)
        {{-- Expanded power table (columns follow the active view) --}}
        @php $visible = collect($this->columnOptions())->only($columns); @endphp
        <div class="calv-card overflow-auto">
            <table class="w-full border-collapse" style="min-width: {{ 160 + $visible->count() * 100 }}px;">
                <thead>
                    <tr style="background: var(--calv-slate); color: #fff;">
                        <th style="width: 1%;"><span class="sr-only">Pin</span></th>
                        @foreach($visible as $h)
                            <th class="text-left px-3 py-2.5 whitespace-nowrap" style="font-family: var(--font-sub); font-size: 10px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase;">{{ $h }}</th>
                        @endforeach
                        <th></th>
                    </tr>
                </thead>
                @if($hasPinned)
                    <tbody>
                        <tr>
                            <td colspan="{{ $visible->count() + 2 }}" style="padding: 0;">
                                @include('livewire.homes.partials.pinned-band', ['count' => $pinnedRows->count()])
                            </td>
                        </tr>
                        @foreach($pinnedRows as $row)
                            @include('livewire.homes.partials.call-table-row', [
                                'row' => $row,
                                'visible' => $visible,
                                'badgeFor' => $badgeFor,
                                'pinned' => true,
                                'tint' => $row['overdue'] ? 'var(--calv-red-15)' : 'var(--calv-sand-15)',
                            ])
                        @endforeach
                    </tbody>
                @endif
                <tbody>
                    @foreach($logs as $row)
                        @include('livewire.homes.partials.call-table-row', [
                            'row' => $row,
                            'visible' => $visible,
                            'badgeFor' => $badgeFor,
                            'pinned' => $this->isCallPinned($row['id']),
                            'tint' => null,
                        ])
                    @endforeach
                    @if($logs->count() === 0)
                        <tr><td colspan="{{ $visible->count() + 2 }}" class="px-6 py-8 text-center text-sm" style="color: var(--calv-slate-65);">Nothing else matches — try "Everything" or clear the search.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    @else
        {{-- Simple person-first rows --}}
        <div class="calv-card overflow-hidden">
            @if($hasPinned)
                @include('livewire.homes.partials.pinned-band', ['count' => $pinnedRows->count()])
                @foreach($pinnedRows as $row)
                    @include('livewire.homes.partials.call-row', [
                        'row' => $row,
                        'badgeFor' => $badgeFor,
                        'pinned' => true,
                        'tint' => $row['overdue'] ? 'var(--calv-red-15)' : 'var(--calv-sand-15)',
                    ])
                @endforeach
            @endif
            @foreach($logs as $row)
                @include('livewire.homes.partials.call-row', [
                    'row' => $row,
                    'badgeFor' => $badgeFor,
                    'pinned' => $this->isCallPinned($row['id']),
                    'tint' => null,
                ])
            @endforeach
            @if($logs->count() === 0)
                <div class="px-6 py-8 text-center text-sm" style="color: var(--calv-slate-65);">Nothing else matches — try "Everything" or clear the search.</div>
            @endif
        </div>
    @endif

    <div class="mt-4">
        {{ $logs->links() }}
    </div>
</div>
