@props([
    'title' => '',
    'breadcrumb' => null,
    'id' => null,
])

{{-- Comm Log modal title bar: breadcrumb · title · ID · close. --}}
<div {{ $attributes->merge(['class' => 'flex items-center justify-between gap-4 px-6 py-4 border-b border-calv-slate-15 bg-white']) }}>
    <div class="min-w-0 flex-1">
        @if($breadcrumb)
            <div class="calv-subhead text-[10px] text-calv-slate-65 mb-1">{{ $breadcrumb }}</div>
        @endif
        <h2 class="calv-h1 text-[22px] text-calv-slate truncate">{{ $title }}</h2>
    </div>

    @if($id)
        <span class="font-mono text-[11px] text-calv-slate-65 whitespace-nowrap">#{{ $id }}</span>
    @endif

    <button type="button"
            x-on:click="$dispatch('comm-modal-close')"
            class="text-calv-slate-65 hover:text-calv-slate calv-focus-ring p-1 rounded-calv-sm"
            aria-label="Close">
        <x-lucide name="x" :size="20" />
    </button>
</div>
