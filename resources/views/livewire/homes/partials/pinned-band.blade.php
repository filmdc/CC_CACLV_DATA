{{--
    The "Pinned" divider that sits above the pinned rows on a list screen.
    @include('livewire.homes.partials.pinned-band', ['count' => $pinnedRows->count()])
--}}
<div class="flex items-center gap-2 px-4 py-2"
     style="background: var(--calv-amber-15); border-bottom: 1px solid var(--calv-amber-35);">
    <span aria-hidden="true" style="font-size: 11px;">📌</span>
    <span class="calv-subhead" style="font-size: 10px; color: #8A6410;">Pinned</span>
    <span class="text-[10px]" style="color: #8A6410; opacity: .75;">— always here, whatever the filter says</span>
    <span class="flex-1"></span>
    <span class="text-[10px] font-semibold" style="color: #8A6410;">{{ $count }}</span>
</div>
