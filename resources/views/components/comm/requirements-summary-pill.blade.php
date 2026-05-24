@props([
    'items' => [],         // Collection<IntakeRequirement> (only non-orphaned)
    'href' => '#requirements-panel',  // anchor or route to jump to the full panel
])

@php
    use App\Models\IntakeRequirement;

    // Coerce to Collection for chainability.
    $coll = collect($items);
    $required = $coll->filter(fn ($i) => $i->required ?? true);
    $requiredCount = $required->count();
    $requiredMet = $required->filter(fn ($i) => $i->isMet())->count();
    $optional = $coll->filter(fn ($i) => ($i->required ?? true) === false);
    $optionalPending = $optional->filter(fn ($i) => ! $i->isMet())->count();
    $allRequiredMet = $requiredCount > 0 && $requiredMet === $requiredCount;
    $bg = $allRequiredMet ? 'bg-calv-sage-15' : 'bg-calv-amber-15';
    $border = $allRequiredMet ? 'border-calv-sage' : 'border-calv-amber';
@endphp

<a href="{{ $href }}" {{ $attributes->merge([
    'class' => "flex items-center gap-3 w-full px-3 py-2.5 border rounded-calv-sm hover:shadow-calv-sm transition {$bg} {$border}",
]) }}>
    <span class="calv-h1 text-[22px] text-calv-slate leading-none">
        {{ $requiredMet }}<span class="text-calv-slate-65">/{{ $requiredCount }}</span>
    </span>

    <span class="flex-1 min-w-0">
        <span class="calv-subhead text-[10.5px] text-calv-slate block">Requirements met</span>
        <span class="block text-[11.5px] text-calv-slate-65 mt-0.5">
            @if($allRequiredMet)
                Ready to promote to project →
            @else
                {{ $requiredCount - $requiredMet }} unmet{{ $optionalPending ? " · {$optionalPending} optional pending" : '' }}
            @endif
        </span>
    </span>
</a>
