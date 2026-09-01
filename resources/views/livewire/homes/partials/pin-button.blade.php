{{--
    Pin toggle for one portal record.

    @include('livewire.homes.partials.pin-button', [
        'type'   => 'call',            // 'call' | 'project'
        'id'     => $row['id'],
        'pinned' => $this->isCallPinned($row['id']),
    ])

    Relies on the HasPins trait on the host component. .stop/.prevent keep the
    click off any surrounding row link.
--}}
<button
    type="button"
    wire:click.stop.prevent="togglePin('{{ $type }}', {{ $id }})"
    title="{{ $pinned ? 'Unpin — take this off your dashboard' : 'Pin to your dashboard' }}"
    aria-label="{{ $pinned ? 'Unpin' : 'Pin' }}"
    aria-pressed="{{ $pinned ? 'true' : 'false' }}"
    class="shrink-0 inline-flex items-center justify-center"
    style="width: 28px; height: 28px; border-radius: 8px; cursor: pointer; line-height: 1; padding: 0;
           background: {{ $pinned ? 'var(--calv-amber-15)' : 'transparent' }};
           border: 1px solid {{ $pinned ? 'var(--calv-amber-35)' : 'transparent' }};
           opacity: {{ $pinned ? '1' : '.4' }};">
    <span aria-hidden="true" style="font-size: 13px;">📌</span>
</button>
