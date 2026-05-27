@props([
    'fund',                  // FundCode model (or stdClass w/ code, name, expiry_state, days_to_expiry)
    'checked' => false,
    'wireModel' => null,     // when set: drives the <input> via wire:model on the parent's array
])

@php
    // expiry_state: soon (<=30d) | warning (<=90d) | ok | perpetual | expired
    $state = $fund?->expiry_state ?? 'ok';
    $days = $fund?->days_to_expiry;

    $config = [
        'soon'      => ['dot' => 'bg-calv-red',     'label' => $days !== null ? "Expires in {$days}d" : 'Expires soon',         'labelTone' => 'text-calv-red',     'disabled' => false],
        'warning'   => ['dot' => 'bg-calv-amber',   'label' => $days !== null ? "Expires in {$days}d" : 'Warning',              'labelTone' => 'text-[#8A6410]',     'disabled' => false],
        'ok'        => ['dot' => 'bg-calv-sage',    'label' => 'Expires '.($fund?->expires_at?->format('M j, Y') ?? '—'),      'labelTone' => 'text-calv-slate-65', 'disabled' => false],
        'perpetual' => ['dot' => 'bg-calv-slate-35','label' => 'No expiry',                                                       'labelTone' => 'text-calv-slate-65', 'disabled' => false],
        'expired'   => ['dot' => 'bg-calv-slate',   'label' => $days !== null ? "Expired {$days}d ago" : 'Expired',              'labelTone' => 'text-calv-slate-65', 'disabled' => true],
    ];
    $c = $config[$state] ?? $config['ok'];

    $codeClass = $state === 'expired' ? 'line-through text-calv-slate-65' : 'text-calv-slate';
@endphp

<label {{ $attributes->merge([
    'class' => 'flex items-center gap-3 px-3 py-2 rounded-calv-sm border border-calv-slate-15 hover:bg-calv-sand-15 cursor-pointer transition'
        . ($c['disabled'] && !$checked ? ' opacity-60 cursor-not-allowed' : ''),
]) }}>
    <input
        type="checkbox"
        @if($wireModel) wire:model.live="{{ $wireModel }}" @endif
        value="{{ $fund?->id }}"
        @checked($checked)
        @disabled($c['disabled'] && !$checked)
        class="rounded-calv-xs border-calv-slate-35 text-calv-red calv-focus-ring"
    />

    <span class="w-2 h-2 rounded-calv-pill {{ $c['dot'] }}" aria-hidden="true"></span>

    <span class="flex-1 min-w-0">
        <span class="block font-mono text-[12px] {{ $codeClass }}">{{ $fund?->code ?? '—' }}</span>
        <span class="block text-[11px] {{ $c['labelTone'] }}">{{ $c['label'] }}</span>
    </span>
</label>
