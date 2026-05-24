@props([
    'service',
    'checked' => false,
    'name' => 'selectedServices[]',
])

@php
    // Program-tinted: CAH = teal, HCP = sage, SSS = amber, default = slate.
    // Resolve from service.program.code (or a `program_code` attribute).
    $programCode = strtoupper($service?->program?->code ?? $service?->program_code ?? '');
    $tone = match (true) {
        str_starts_with($programCode, 'CAH') => 'border-calv-teal text-calv-teal bg-calv-teal-15',
        str_starts_with($programCode, 'HCP') => 'border-calv-sage text-[#2F5A41] bg-calv-sage-15',
        str_starts_with($programCode, 'SSS') => 'border-calv-amber text-[#8A6410] bg-calv-amber-15',
        default                              => 'border-calv-slate-35 text-calv-slate bg-white',
    };
@endphp

<label {{ $attributes->merge([
    'class' => "inline-flex items-center gap-2 px-3 py-1.5 rounded-calv-pill border text-[12px] cursor-pointer transition hover:shadow-calv-sm {$tone}",
]) }}>
    <input
        type="checkbox"
        name="{{ $name }}"
        value="{{ $service?->id }}"
        @checked($checked)
        class="sr-only peer"
    />
    <span class="w-2 h-2 rounded-calv-pill bg-current opacity-0 peer-checked:opacity-100" aria-hidden="true"></span>
    <span class="font-medium">{{ $service?->name ?? '—' }}</span>
</label>
