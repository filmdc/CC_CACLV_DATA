@props([
    'items' => [],                       // Collection<IntakeRequirement>
    'overrideModel' => 'requirementOverride',         // wire bool model
    'overrideReasonModel' => 'requirementOverrideReason', // wire string model
])

@php
    use App\Models\IntakeRequirement;

    $coll = collect($items)->filter(fn ($i) => ! ($i->is_orphaned ?? false));
    $required = $coll->filter(fn ($i) => $i->required ?? true);
    $unmet = $required->filter(fn ($i) => ! $i->isMet())->values();
    $requiredCount = $required->count();
    $requiredMet = $required->count() - $unmet->count();
    $ok = $unmet->isEmpty() && $requiredCount > 0;
    $pct = $requiredCount > 0 ? round(($requiredMet / $requiredCount) * 100) : 100;

    $kindIcon = [
        IntakeRequirement::KIND_DOC      => 'paperclip',
        IntakeRequirement::KIND_FORM     => 'pencil',
        IntakeRequirement::KIND_DATA     => 'circle-dot',
        IntakeRequirement::KIND_VERIFY   => 'check',
        IntakeRequirement::KIND_EXTERNAL => 'link',
    ];
@endphp

<div {{ $attributes->merge([
    'class' => 'border-2 rounded-calv-sm p-4 ' . ($ok ? 'border-calv-sage bg-calv-sage-15' : 'border-calv-red bg-calv-red-15'),
]) }}>
    {{-- Header --}}
    <div class="flex items-center gap-3 mb-2">
        <span class="{{ $ok ? 'text-calv-sage' : 'text-calv-red' }}">
            @if($ok)
                <x-lucide name="check" :size="20" />
            @else
                <x-lucide name="alert-triangle" :size="20" />
            @endif
        </span>
        <span class="calv-h1 text-[16px] text-calv-slate flex-1">
            @if($ok)
                All required requirements met
            @else
                {{ $unmet->count() }} required requirement{{ $unmet->count() === 1 ? '' : 's' }} unmet
            @endif
        </span>
        <span class="calv-subhead text-[11px] {{ $ok ? 'text-calv-sage' : 'text-calv-red' }}">
            {{ $requiredMet }}/{{ $requiredCount }}
        </span>
    </div>

    {{-- Progress bar --}}
    <div class="mb-3">
        <div class="h-2 rounded-calv-pill bg-calv-slate-15 overflow-hidden">
            <div class="h-full {{ $ok ? 'bg-calv-sage' : 'bg-calv-amber' }}" style="width: {{ $pct }}%"></div>
        </div>
    </div>

    {{-- Unmet list + override block --}}
    @if(! $ok)
        <div class="calv-subhead text-[10px] text-calv-slate-65 mt-3 mb-1.5">Unmet</div>
        <div class="bg-white border border-calv-red-35 rounded-calv-sm overflow-hidden">
            @foreach($unmet as $item)
                @php $icon = $kindIcon[$item->kind] ?? 'circle'; @endphp
                <div class="grid grid-cols-[20px_1fr_auto] gap-2 items-center px-3 py-1.5 border-b border-calv-slate-15 last:border-b-0">
                    <span class="text-calv-slate"><x-lucide :name="$icon" :size="14" /></span>
                    <span class="text-[12.5px] text-calv-slate truncate">{{ $item->label }}</span>
                    <x-comm.meta-chip tone="neutral">{{ IntakeRequirement::statusDisplay($item->status) }}</x-comm.meta-chip>
                </div>
            @endforeach
        </div>

        {{-- Override path --}}
        <div class="mt-3 p-3 bg-white rounded-calv-sm border border-calv-slate-15">
            <label class="flex items-start gap-2.5 cursor-pointer">
                <input type="checkbox" wire:model.live="{{ $overrideModel }}"
                       class="mt-1 rounded-calv-xs border-calv-slate-35 text-calv-red focus:ring-calv-red" />
                <span class="flex-1 text-[12.5px] text-calv-slate leading-relaxed">
                    <strong>Override and create anyway.</strong>
                    Any conversion with unmet required requirements is audit-logged with the reason below.
                </span>
            </label>

            <div wire:show="{{ $overrideModel }}" class="mt-2">
                <textarea
                    wire:model.defer="{{ $overrideReasonModel }}"
                    rows="2"
                    placeholder="Reason (required for override)…"
                    class="w-full px-2.5 py-2 text-[12.5px] text-calv-slate border border-calv-slate-35 rounded-calv-sm focus:border-calv-red focus:outline-none calv-focus-ring"
                ></textarea>
            </div>
        </div>
    @endif
</div>
