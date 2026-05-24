@props([
    'update',  // CommunicationLogUpdate
])

@php
    // Map type → meta-chip tone + label. 'Status' is system-only.
    $kindMap = [
        'note'           => ['tone' => 'neutral', 'label' => 'Note'],
        'follow_up'      => ['tone' => 'teal',    'label' => 'Follow-up'],
        'action'         => ['tone' => 'amber',   'label' => 'Action'],
        'email'          => ['tone' => 'sage',    'label' => 'Email'],
        'status_change'  => ['tone' => 'red',     'label' => 'Status'],
        'outcome'        => ['tone' => 'amber',   'label' => 'Action'], // legacy → Action
        'other'          => ['tone' => 'neutral', 'label' => 'Note'],   // legacy → Note
    ];
    $kind = $kindMap[$update?->update_type] ?? $kindMap['note'];
@endphp

<article {{ $attributes->merge([
    'class' => 'flex gap-3 px-4 py-3 border-b border-calv-slate-15',
]) }}>
    <div class="flex-shrink-0 pt-1">
        <x-comm.meta-chip :tone="$kind['tone']">{{ $kind['label'] }}</x-comm.meta-chip>
    </div>

    <div class="min-w-0 flex-1">
        <div class="flex items-baseline justify-between gap-3 mb-1">
            <span class="text-[12px] font-semibold text-calv-slate">
                {{ $update?->author?->name ?? $update?->createdBy?->name ?? 'System' }}
            </span>
            <time class="text-[11px] text-calv-slate-65 whitespace-nowrap" datetime="{{ $update?->created_at?->toIso8601String() }}">
                {{ $update?->created_at?->diffForHumans() }}
            </time>
        </div>

        <p class="text-[13.5px] text-calv-slate whitespace-pre-wrap">{{ $update?->update_text ?? $update?->body }}</p>

        @if($update?->update_date)
            <div class="mt-1.5 text-[11px] text-calv-teal calv-subhead">
                Due {{ \Illuminate\Support\Carbon::parse($update->update_date)->format('M j, Y') }}
            </div>
        @endif
    </div>
</article>
