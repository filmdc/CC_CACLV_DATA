{{--
    One call/intake as an Expanded-view table row. Columns follow the active
    saved view ($visible), so pinned rows line up with the list beneath them.

    Vars: $row, $visible, $badgeFor, $pinned (bool), $tint (optional)
--}}
<tr style="background: {{ $tint ?? ($row['overdue'] ? 'var(--calv-red-15)' : '#fff') }}; border-top: 1px solid var(--calv-slate-15);">
    <td class="pl-3 py-2.5" style="width: 1%;">
        @include('livewire.homes.partials.pin-button', ['type' => 'call', 'id' => $row['id'], 'pinned' => $pinned])
    </td>
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
