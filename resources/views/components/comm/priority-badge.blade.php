@props([
    'level' => 'normal', // urgent | high | normal | low
])

@php
    $config = [
        'urgent' => ['dot' => 'bg-calv-red',   'text' => 'text-calv-red',   'label' => 'Urgent'],
        'high'   => ['dot' => 'bg-calv-amber', 'text' => 'text-[#8A6410]', 'label' => 'High'],
        'normal' => ['dot' => 'bg-calv-teal',  'text' => 'text-calv-teal', 'label' => 'Normal'],
        'low'    => ['dot' => 'bg-calv-slate-35','text' => 'text-calv-slate-65', 'label' => 'Low'],
    ];
    $c = $config[$level] ?? $config['normal'];
@endphp

<span {{ $attributes->merge([
    'class' => "inline-flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wider {$c['text']}",
]) }}>
    <span class="w-2 h-2 rounded-calv-pill {{ $c['dot'] }}"></span>
    {{ $c['label'] }}
</span>
