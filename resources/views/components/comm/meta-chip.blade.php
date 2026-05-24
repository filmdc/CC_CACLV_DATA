@props([
    'tone' => 'neutral', // neutral | slate | teal | red | sage | amber | sand
])

@php
    $toneStyles = [
        'neutral' => 'bg-calv-slate-15 text-calv-slate',
        'slate'   => 'bg-calv-slate text-white',
        'teal'    => 'bg-calv-teal-15 text-calv-teal',
        'red'     => 'bg-calv-red-15 text-calv-red',
        'sage'    => 'bg-calv-sage-15 text-[#2F5A41]',
        'amber'   => 'bg-calv-amber-15 text-[#8A6410]',
        'sand'    => 'bg-calv-sand-15 text-calv-slate',
    ];
    $classes = $toneStyles[$tone] ?? $toneStyles['neutral'];
@endphp

<span {{ $attributes->merge([
    'class' => "inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-calv-pill text-[11px] font-semibold uppercase tracking-wider {$classes}",
]) }}>
    {{ $slot }}
</span>
