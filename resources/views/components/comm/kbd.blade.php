@props([
    'keys' => null, // e.g. ['cmd', '⏎'] or "⌘P"
])

@php
    $items = is_array($keys) ? $keys : ($keys !== null ? [$keys] : [$slot->toHtml()]);
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-0.5 align-middle']) }}>
    @foreach($items as $i => $k)
        @if($i > 0)<span class="text-[10px] text-calv-slate-65">+</span>@endif
        <kbd class="inline-flex items-center px-1.5 py-0.5 rounded-calv-xs border border-calv-slate-35 bg-calv-sand-15 font-mono text-[10.5px] leading-none text-calv-slate min-w-[18px] justify-center">
            {!! $k !!}
        </kbd>
    @endforeach
</span>
