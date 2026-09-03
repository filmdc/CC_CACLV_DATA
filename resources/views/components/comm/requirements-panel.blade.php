@props([
    'items' => [],            // Collection<IntakeRequirement>
    'title' => 'Application requirements',
    'hint' => null,
    'addAction' => true,      // show "+ ad-hoc requirement" affordance
])

@php
    use App\Models\IntakeRequirement;

    $coll = collect($items)->filter(fn ($i) => ! ($i->is_orphaned ?? false));

    // Sort: unmet first, then optional, then met (matches spec §4.7).
    $sorted = $coll->sortBy(function ($i) {
        $met = $i->isMet() ? 1 : 0;
        $optional = ($i->required ?? true) === false ? 1 : 0;
        return $met * 10 + $optional;
    })->values();

    $required = $coll->filter(fn ($i) => $i->required ?? true);
    $requiredCount = $required->count();
    $requiredMet = $required->filter(fn ($i) => $i->isMet())->count();
    $allRequiredMet = $requiredCount > 0 && $requiredMet === $requiredCount;
    $pct = $requiredCount > 0 ? round(($requiredMet / $requiredCount) * 100) : 100;
    $optional = $coll->filter(fn ($i) => ($i->required ?? true) === false);
    $optionalPending = $optional->filter(fn ($i) => ! $i->isMet())->count();

    $kindIcon = [
        IntakeRequirement::KIND_DOC      => 'paperclip',
        IntakeRequirement::KIND_FORM     => 'pencil',
        IntakeRequirement::KIND_DATA     => 'circle-dot',
        IntakeRequirement::KIND_VERIFY   => 'check',
        IntakeRequirement::KIND_EXTERNAL => 'link',
    ];

    $statusTone = [
        IntakeRequirement::STATUS_PENDING   => 'neutral',
        IntakeRequirement::STATUS_REQUESTED => 'amber',
        IntakeRequirement::STATUS_RECEIVED  => 'sage',
        IntakeRequirement::STATUS_WAIVED    => 'sand',
        IntakeRequirement::STATUS_NA        => 'sand',
        IntakeRequirement::STATUS_BLOCKED   => 'red',
    ];
@endphp

<section id="requirements-panel" {{ $attributes->merge([
    'class' => 'bg-white border border-calv-slate-15 rounded-calv-sm p-4',
]) }}>
    {{-- Header --}}
    <header class="flex items-baseline justify-between mb-3">
        <div class="flex items-baseline gap-2">
            <h3 class="calv-subhead text-[11px] text-calv-slate">{{ $title }}</h3>
            @if($hint)
                <span class="text-[11px] text-calv-slate-65">{{ $hint }}</span>
            @endif
        </div>
        @if($addAction)
            <button type="button"
                    wire:click="$dispatch('add-ad-hoc-requirement')"
                    class="text-[11px] text-calv-teal underline underline-offset-2 hover:text-calv-red">
                + ad-hoc requirement
            </button>
        @endif
    </header>

    {{-- Progress strip --}}
    <div class="mb-4">
        <div class="flex items-baseline justify-between mb-1.5">
            <div class="calv-h1 text-[20px] text-calv-slate leading-none">
                {{ $requiredMet }} <span class="text-calv-slate-65">of {{ $requiredCount }}</span> required met
            </div>
            <div class="calv-subhead text-[11px] {{ $allRequiredMet ? 'text-calv-sage' : 'text-[#8A6410]' }}">
                @if($allRequiredMet)
                    Ready to promote
                @else
                    {{ $requiredCount - $requiredMet }} unmet{{ $optionalPending ? " · {$optionalPending} optional" : '' }}
                @endif
            </div>
        </div>
        <div class="h-2 rounded-calv-pill bg-calv-slate-15 overflow-hidden">
            <div class="h-full {{ $allRequiredMet ? 'bg-calv-sage' : 'bg-calv-amber' }} transition-all"
                 style="width: {{ $pct }}%"></div>
        </div>
    </div>

    {{-- Row list --}}
    <div class="border border-calv-slate-15 rounded-calv-sm overflow-hidden">
        @forelse($sorted as $item)
            @php
                $isOptional = ($item->required ?? true) === false;
                $isAdHoc = $item->source_type === IntakeRequirement::SOURCE_AD_HOC;
                $icon = $kindIcon[$item->kind] ?? 'circle';
                $rowBg = $isAdHoc ? 'bg-calv-amber-15' : 'bg-white';
                $primaryActionLabel = match ($item->status) {
                    IntakeRequirement::STATUS_PENDING   => 'Request',
                    IntakeRequirement::STATUS_REQUESTED => 'Mark received',
                    IntakeRequirement::STATUS_BLOCKED   => 'Check status',
                    default => 'Edit',
                };
            @endphp

            <div class="grid grid-cols-[28px_1fr_auto_auto] gap-3 items-center px-3 py-2.5 border-b border-calv-slate-15 last:border-b-0 {{ $rowBg }}">
                <span class="{{ $isOptional ? 'text-calv-slate-65' : 'text-calv-slate' }}">
                    <x-lucide :name="$icon" :size="16" />
                </span>

                <div class="min-w-0">
                    <div class="flex items-baseline gap-2 flex-wrap">
                        <span class="text-[13.5px] font-medium text-calv-slate truncate">{{ $item->label }}</span>
                        @if($isAdHoc)
                            <x-comm.meta-chip tone="amber">ad-hoc</x-comm.meta-chip>
                        @endif
                        @if($isOptional)
                            <span class="calv-subhead text-[9px] text-calv-slate-65">optional</span>
                        @endif
                    </div>

                    {{-- Free-form worker note (partial-receipt annotations etc.).
                         Wraps and preserves line breaks; the append-only audit
                         trail (`notes`) surfaces in the Activity feed instead. --}}
                    @if(filled($item->worker_note))
                        <div class="text-[11.5px] text-calv-slate-65 mt-0.5 whitespace-pre-line">{{ \Illuminate\Support\Str::limit($item->worker_note, 280) }}</div>
                    @endif

                    {{-- Income eligibility carries figures rather than just a
                         receipt, so the determination reads on the row itself. --}}
                    @if($item->key === IntakeRequirement::KEY_INCOME_ELIGIBILITY)
                        @php $assessment = $item->incomeAssessment; @endphp
                        <div class="text-[11.5px] text-calv-slate-65 mt-0.5">
                            @if($assessment && $assessment->annual_income !== null)
                                ${{ number_format((float) $assessment->annual_income, 0) }}/yr ·
                                household of {{ $assessment->household_size }}
                                @if($assessment->poverty_percentage !== null)
                                    · <span class="font-semibold text-calv-slate">{{ $assessment->determination_label }}</span>
                                @else
                                    · no poverty guideline loaded for this household size
                                @endif
                            @else
                                Annual income, household size, and proof — pay stubs, tax return, benefit letters.
                            @endif
                        </div>
                    @endif

                    @if(is_array($item->via) && count($item->via))
                        <div class="flex flex-wrap gap-1 mt-1">
                            @foreach($item->via as $tag)
                                <span class="calv-subhead text-[9px] text-calv-teal bg-calv-teal-15 px-1.5 py-0.5 rounded-calv-xs">{{ $tag }}</span>
                            @endforeach
                        </div>
                    @endif

                    {{-- Phase 12.1: attachments surface inline on the row so the user
                         can View/Download without opening the Edit modal. Edit is
                         reserved for changes (status transitions + new uploads). --}}
                    @php
                        $rowMedia = ($item->type ?? null) === IntakeRequirement::TYPE_DOCUMENT && method_exists($item, 'getMedia')
                            ? $item->getMedia('documents')
                            : collect();
                    @endphp
                    @if($rowMedia->isNotEmpty())
                        <ul class="flex flex-wrap gap-x-3 gap-y-1 mt-2">
                            @foreach($rowMedia as $m)
                                @php
                                    $previewable = in_array($m->mime_type, [
                                        'application/pdf',
                                        'image/jpeg', 'image/jpg', 'image/png', 'image/gif',
                                        'image/webp', 'image/svg+xml',
                                        'text/plain', 'text/csv',
                                    ], true);
                                @endphp
                                <li class="inline-flex items-center gap-1.5 text-[11px] bg-calv-sand-15 px-2 py-1 rounded-calv-xs">
                                    <x-lucide name="paperclip" :size="11" class="text-calv-slate-65 shrink-0" />
                                    <span class="text-calv-slate truncate max-w-[180px]" title="{{ $m->name }} · {{ $m->mime_type }}">{{ $m->name }}</span>
                                    <span class="text-calv-slate-65">·</span>
                                    @if($previewable)
                                        <a href="{{ route('media.preview', $m->id) }}" target="_blank" rel="noopener"
                                           class="text-calv-teal hover:underline">View</a>
                                        <span class="text-calv-slate-35">/</span>
                                    @endif
                                    <a href="{{ route('media.download', $m->id) }}"
                                       class="text-calv-teal hover:underline">Download</a>
                                    @if(auth()->user()?->isAdmin())
                                        <span class="text-calv-slate-35">/</span>
                                        <button type="button"
                                                wire:click="deleteRequirementMedia({{ $item->id }}, {{ $m->id }})"
                                                wire:confirm="Delete this attachment?"
                                                class="text-calv-red hover:underline">Remove</button>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <x-comm.meta-chip :tone="$statusTone[$item->status] ?? 'neutral'">
                    {{ IntakeRequirement::statusDisplay($item->status) }}
                </x-comm.meta-chip>

                <div class="flex items-center gap-1.5">
                    @if($item->key === IntakeRequirement::KEY_INCOME_ELIGIBILITY)
                        <button type="button"
                                wire:click="openIncomeEligibility"
                                class="px-2.5 py-1 rounded-calv-sm border border-calv-teal bg-calv-teal-15 calv-subhead text-[10px] text-calv-teal hover:bg-calv-teal-35">
                            {{ $item->isMet() ? 'Review income' : 'Enter income' }}
                        </button>
                    @endif
                    <button type="button"
                            wire:click="$dispatch('act-on-requirement', { id: {{ $item->id }} })"
                            class="px-2.5 py-1 rounded-calv-sm border border-calv-slate-35 calv-subhead text-[10px] text-calv-slate hover:bg-calv-sand-15">
                        {{ $primaryActionLabel }}
                    </button>
                </div>
            </div>
        @empty
            <div class="px-3 py-8 text-center text-[13px] text-calv-slate-65">
                No requirements yet. Tag a service or add an ad-hoc requirement to start a checklist.
            </div>
        @endforelse
    </div>
</section>
