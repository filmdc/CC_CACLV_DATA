@props([
    'kindModel' => 'composerKind',
    'bodyModel' => 'composerBody',
    'dateModel' => 'composerDate',
    'submitMethod' => 'submitUpdate',
])

{{--
    Typed compose box: pick kind, write body, optionally set follow-up date,
    submit with ⌘⏎. Wire methods/properties live on the parent Livewire
    component (defaults match the planned phase-2 names).
--}}
<div {{ $attributes->merge([
    'class' => 'rounded-calv-md border border-calv-slate-15 bg-white shadow-calv-sm',
]) }}>
    <div class="flex flex-wrap items-center gap-2 px-3 py-2 border-b border-calv-slate-15 bg-calv-sand-15">
        @foreach (['note' => 'Note', 'follow_up' => 'Follow-up', 'action' => 'Action', 'email' => 'Email'] as $value => $label)
            <label class="cursor-pointer">
                <input type="radio"
                       wire:model.live="{{ $kindModel }}"
                       value="{{ $value }}"
                       class="sr-only peer">
                <span class="inline-flex items-center px-2.5 py-1 rounded-calv-pill border border-calv-slate-15 text-[11px] uppercase tracking-wider text-calv-slate-65 peer-checked:bg-calv-teal peer-checked:text-white peer-checked:border-calv-teal transition">
                    {{ $label }}
                </span>
            </label>
        @endforeach
    </div>

    <textarea
        wire:model.defer="{{ $bodyModel }}"
        rows="3"
        placeholder="Add a note, action, or follow-up… (⌘⏎ to submit)"
        x-on:keydown.cmd.enter.prevent="$wire.call('{{ $submitMethod }}')"
        x-on:keydown.ctrl.enter.prevent="$wire.call('{{ $submitMethod }}')"
        class="w-full px-3 py-2.5 text-[13.5px] text-calv-slate border-0 focus:outline-none resize-none calv-focus-ring rounded-none"
    ></textarea>

    <div class="flex items-center justify-between gap-3 px-3 py-2 border-t border-calv-slate-15 bg-white rounded-b-calv-md">
        <div class="flex items-center gap-2 text-[11px] text-calv-slate-65">
            <span x-show="$wire.{{ $kindModel }} === 'follow_up'" x-cloak>
                <x-lucide name="calendar" :size="14" class="inline -mt-0.5" />
                <input type="date"
                       wire:model.defer="{{ $dateModel }}"
                       class="bg-transparent border-0 p-0 text-[12px] text-calv-slate focus:outline-none calv-focus-ring" />
            </span>
        </div>

        <button type="button"
                wire:click="{{ $submitMethod }}"
                class="px-4 py-1.5 rounded-calv-sm bg-calv-red text-white font-semibold uppercase tracking-wider text-[11px] hover:bg-calv-accent-press calv-focus-ring transition">
            Submit
        </button>
    </div>
</div>
