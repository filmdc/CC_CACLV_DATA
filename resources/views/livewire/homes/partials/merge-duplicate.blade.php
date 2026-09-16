{{--
    Merge a duplicate entry into this one.

    Step 1 picks the record to fold in (suggested matches first, free search
    behind it). Step 2 spells out exactly what is about to move before she
    commits — a merge is not something she should have to undo to understand.

    Explicit Cancel is the only close path (portal modal convention).
--}}
<div class="fixed inset-0 z-[100] flex items-start justify-center overflow-y-auto" style="background: rgba(40,42,44,.55); padding: 48px 16px;">
    <div class="w-full" style="max-width: 640px; background: #fff; border-radius: 10px; box-shadow: var(--shadow-lg); overflow: hidden;" wire:click.stop>

        <header class="px-6 py-4" style="border-bottom: 1px solid var(--calv-slate-15);">
            <h2 class="calv-h1" style="font-size: 20px;">
                {{ $mergeStep === 1 ? 'Merge a duplicate into this entry' : 'Here is what will move' }}
            </h2>
            <div class="text-xs mt-1" style="color: var(--calv-slate-65);">
                Keeping {{ $who }} · COMM-{{ $log->id }}
            </div>
        </header>

        @error('merge')
            <div class="mx-6 mt-4 rounded-md px-3.5 py-2.5 text-[13px]" style="background: var(--calv-red-15); color: var(--calv-red); border: 1px solid var(--calv-red);">
                {{ $message }}
            </div>
        @enderror

        @if($mergeStep === 1)
            <div class="px-6 py-5">
                <p class="text-[13.5px] leading-relaxed mb-4" style="color: var(--calv-slate);">
                    Pick the entry that is the same person. Everything on it — every call, every document,
                    every requirement — moves onto this record, and that entry goes away.
                </p>

                <label class="calv-subhead block mb-1.5" style="font-size: 11px;">Find the other entry</label>
                <input
                    type="text"
                    wire:model.live.debounce.400ms="mergeSearch"
                    placeholder="Name, phone, address, subject…"
                    class="calv-input w-full">

                <div class="mt-4">
                    @if(trim($mergeSearch) === '')
                        <div class="calv-subhead mb-2" style="font-size: 11px;">Looks like the same person</div>
                    @endif

                    @forelse($mergeCandidates as $c)
                        <div class="flex items-center gap-3 px-3 py-2.5 rounded-md mb-1.5" style="border: 1px solid var(--calv-slate-15);">
                            <div class="flex-1 min-w-0">
                                <div class="text-[13.5px] font-medium" style="color: var(--calv-slate);">
                                    {{ $c['who'] }}
                                    <span class="calv-subhead ml-1.5" style="font-size: 10.5px; letter-spacing: .05em;">COMM-{{ $c['id'] }}</span>
                                </div>
                                <div class="text-[12px] mt-0.5" style="color: var(--calv-slate-65);">
                                    @if($c['when']){{ $c['when'] }}@endif
                                    @if($c['where']) · {{ $c['where'] }}@endif
                                    @if($c['phone']) · ☏ {{ $c['phone'] }}@endif
                                </div>
                                @if($c['blocked'])
                                    <div class="text-[12px] mt-1" style="color: var(--calv-red);">{{ $c['blocked'] }}</div>
                                @endif
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <a href="{{ route('homes.call', $c['id']) }}" wire:navigate class="calv-link" style="font-size: 12px;">Open</a>
                                @unless($c['blocked'])
                                    <button type="button" wire:click="selectMergeCandidate({{ $c['id'] }})" class="calv-btn calv-btn--ghost" style="font-size: 12px;">
                                        Merge this in
                                    </button>
                                @endunless
                            </div>
                        </div>
                    @empty
                        <p class="text-[13px] py-3" style="color: var(--calv-slate-65);">
                            @if(trim($mergeSearch) === '')
                                No obvious duplicates of this person. Search above if you know which entry you mean.
                            @else
                                Nothing matched "{{ $mergeSearch }}".
                            @endif
                        </p>
                    @endforelse
                </div>
            </div>

            <footer class="px-6 py-4 flex justify-end gap-2" style="border-top: 1px solid var(--calv-slate-15); background: var(--calv-sand-15);">
                <button type="button" wire:click="closeMerge" class="calv-btn calv-btn--quiet">Cancel</button>
            </footer>

        @elseif($mergeDuplicate && $mergePreview)
            <div class="px-6 py-5 text-[13.5px] leading-relaxed" style="color: var(--calv-slate);">
                <div class="rounded-md px-3.5 py-3 mb-4" style="background: var(--calv-sand-15); border: 1px solid var(--calv-slate-15);">
                    <div class="text-[12px]" style="color: var(--calv-slate-65);">Folding in</div>
                    <div class="font-medium mt-0.5">
                        {{ $mergeDuplicate->contact_full_name ?: ($mergeDuplicate->subject ?: 'COMM-'.$mergeDuplicate->id) }}
                        <span class="calv-subhead ml-1.5" style="font-size: 10.5px; letter-spacing: .05em;">COMM-{{ $mergeDuplicate->id }}</span>
                    </div>
                </div>

                <ul class="space-y-1.5 mb-4">
                    <li>• <strong>{{ $mergePreview['timeline_entries'] }}</strong> timeline {{ \Illuminate\Support\Str::plural('entry', $mergePreview['timeline_entries']) }}, including that entry's own call.</li>
                    <li>• <strong>{{ $mergePreview['requirements'] }}</strong> {{ \Illuminate\Support\Str::plural('requirement', $mergePreview['requirements']) }} this entry does not already have.</li>
                    @if($mergePreview['requirements_deduped'] > 0)
                        <li>• <strong>{{ $mergePreview['requirements_deduped'] }}</strong> {{ \Illuminate\Support\Str::plural('requirement', $mergePreview['requirements_deduped']) }} you already track here — those fold together, and anything already marked received stays received.</li>
                    @endif
                    @if($mergePreview['files'] > 0)
                        <li>• <strong>{{ $mergePreview['files'] }}</strong> uploaded {{ \Illuminate\Support\Str::plural('file', $mergePreview['files']) }}.</li>
                    @endif
                    @if($mergePreview['counties'] > 0)
                        <li>• <strong>{{ $mergePreview['counties'] }}</strong> housing {{ \Illuminate\Support\Str::plural('service', $mergePreview['counties']) }}.</li>
                    @endif
                    @if($mergePreview['fund_codes'] > 0)
                        <li>• <strong>{{ $mergePreview['fund_codes'] }}</strong> anticipated fund {{ \Illuminate\Support\Str::plural('code', $mergePreview['fund_codes']) }}.</li>
                    @endif
                    @if($mergePreview['income_assessment'] === 'move')
                        <li>• The income eligibility determination.</li>
                    @endif
                    @if($mergePreview['follow_up_adopted'])
                        <li>• Its open follow-up becomes this entry's follow-up.</li>
                    @endif
                </ul>

                @if($mergePreview['income_assessment'] === 'keep_survivor')
                    <p class="rounded-md px-3.5 py-2.5 mb-3 text-[13px]" style="background: var(--calv-amber-15); color: #8A6410;">
                        Both entries have an income determination. This entry's figures are the ones that stay —
                        the other set is kept in the record's history but will not be shown here.
                    </p>
                @endif

                <p style="color: var(--calv-slate-65);">
                    Blank details here (a phone number, an email, an address) get filled in from the other entry.
                    Anything already filled in here wins.
                </p>
                <p class="mt-2.5" style="color: var(--calv-slate-65);">
                    COMM-{{ $mergeDuplicate->id }} then disappears from Calls &amp; Intakes. Nothing is destroyed —
                    an admin can see it in the recycle bin — but restoring it will not pull the entries back off
                    this record, so make sure this is the right pair.
                </p>
            </div>

            <footer class="px-6 py-4 flex justify-between gap-2" style="border-top: 1px solid var(--calv-slate-15); background: var(--calv-sand-15);">
                <button type="button" wire:click="backToMergePick" class="calv-btn calv-btn--quiet">← Pick a different entry</button>
                <div class="flex gap-2">
                    <button type="button" wire:click="closeMerge" class="calv-btn calv-btn--quiet">Cancel</button>
                    <button type="button" wire:click="doMerge" class="calv-btn calv-btn--primary" wire:loading.attr="disabled" wire:target="doMerge">
                        <span wire:loading.remove wire:target="doMerge">Merge them</span>
                        <span wire:loading wire:target="doMerge">Merging…</span>
                    </button>
                </div>
            </footer>
        @endif
    </div>
</div>
