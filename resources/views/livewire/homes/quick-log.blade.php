<div>
    @if($show)
        <div
            class="fixed inset-0 z-[100] flex items-start justify-center overflow-y-auto"
            style="background: rgba(40,42,44,.55); padding: 48px 16px;"
        >
            <div class="w-full" style="max-width: 560px; background: #fff; border-radius: 10px; box-shadow: var(--shadow-lg); overflow: hidden;" wire:click.stop>
                {{-- Header --}}
                <header class="flex items-center px-6 py-4" style="border-bottom: 1px solid var(--calv-slate-15);">
                    <h2 class="calv-h1 flex-1" style="font-size: 22px;">Log a call</h2>
                    <span class="text-[11.5px]" style="color: var(--calv-slate-65);">Today · Phone · Outgoing</span>
                </header>

                <div class="px-6 py-5 flex flex-col gap-4">
                    {{-- WHO --}}
                    <div>
                        <label class="calv-label">Who did you talk to?</label>
                        <input
                            wire:model.live.debounce.400ms="who"
                            type="text"
                            autofocus
                            placeholder="Name — or start typing to find someone you've logged before"
                            class="calv-input"
                            style="border: 1.5px solid var(--calv-teal); box-shadow: 0 0 0 3px var(--calv-teal-15);">
                        @error('who') <p class="mt-1.5 text-sm" style="color: var(--calv-red);">{{ $message }}</p> @enderror
                        @if($matchedParticipantId)
                            <div class="text-[11.5px] mt-1.5" style="color: var(--calv-teal);">Matches an existing participant — we'll link them automatically.</div>
                        @endif
                    </div>

                    {{-- WHAT --}}
                    <div>
                        <label class="calv-label">What happened?</label>
                        <textarea
                            wire:model="what"
                            rows="4"
                            placeholder="Plain words are fine. 'Roof leaking over kitchen, 80 years old, needs help with the application.'"
                            class="calv-input"
                            style="resize: vertical; line-height: 1.55;"></textarea>
                    </div>

                    {{-- FOLLOW-UP --}}
                    <div>
                        <label class="calv-label">Anything to follow up on?</label>
                        <div class="flex gap-2 flex-wrap items-center">
                            @foreach($followUpOptions as $opt)
                                @php $active = $followUp === $opt; @endphp
                                <button
                                    type="button"
                                    wire:click="setFollowUp(@js($opt))"
                                    style="font-family: var(--font-body); font-size: 13px; padding: 8px 14px; border-radius: 999px; cursor: pointer;
                                           background: {{ $active ? 'var(--calv-teal)' : '#fff' }}; color: {{ $active ? '#fff' : 'var(--calv-slate)' }};
                                           border: 1px solid {{ $active ? 'var(--calv-teal)' : 'var(--calv-slate-35)' }};">{{ $opt }}</button>
                            @endforeach

                            @if($followUp !== 'No follow-up')
                                <select wire:model="followUpWhen" class="calv-input" style="width: auto; min-width: 120px; border-radius: 999px; padding: 7px 12px; font-size: 12.5px;">
                                    @foreach($whenOptions as $w)
                                        <option value="{{ $w }}">in {{ $w }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>
                    </div>

                    {{-- Details disclosure --}}
                    <button type="button" wire:click="$toggle('showDetails')" class="calv-link text-left" style="font-size: 12.5px;">
                        {{ $showDetails ? '− hide details' : '+ add details (address, phone, email, role…)' }}
                    </button>

                    @if($showDetails)
                        <div class="grid gap-3" style="grid-template-columns: 1fr 1fr; padding: 14px; background: var(--calv-sand-15); border-radius: 8px;">
                            <div style="grid-column: 1 / -1;">
                                <label class="calv-label" style="font-size: 10px;">Street address</label>
                                <input wire:model="address" type="text" class="calv-input" placeholder="123 Main St">
                            </div>
                            <div style="grid-column: 1 / -1; display: grid; grid-template-columns: 1fr 64px 110px; gap: 12px;">
                                <div>
                                    <label class="calv-label" style="font-size: 10px;">City</label>
                                    <input wire:model="city" type="text" class="calv-input">
                                </div>
                                <div>
                                    <label class="calv-label" style="font-size: 10px;">State</label>
                                    <input wire:model="state" type="text" maxlength="2" class="calv-input">
                                </div>
                                <div>
                                    <label class="calv-label" style="font-size: 10px;">Zip</label>
                                    <input wire:model="zip" type="text" maxlength="10" class="calv-input">
                                </div>
                            </div>
                            <div>
                                <label class="calv-label" style="font-size: 10px;">Phone</label>
                                <input wire:model="phone" type="text" class="calv-input">
                            </div>
                            <div>
                                <label class="calv-label" style="font-size: 10px;">Email</label>
                                <input wire:model="email" type="email" class="calv-input">
                                @error('email') <p class="mt-1 text-xs" style="color: var(--calv-red);">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="calv-label" style="font-size: 10px;">Role</label>
                                <select wire:model="role" class="calv-input">
                                    <option value="">—</option>
                                    <option value="homeowner">Homeowner</option>
                                    <option value="contractor">Contractor</option>
                                    <option value="inspector">Inspector</option>
                                    <option value="vendor">Vendor</option>
                                    <option value="agency">Agency</option>
                                    <option value="applicant">Applicant</option>
                                    <option value="family_member">Family member</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Footer --}}
                <footer class="flex items-center gap-2.5 px-6 py-3.5" style="border-top: 1px solid var(--calv-slate-15); background: var(--calv-sand-15);">
                    <span class="flex-1"></span>
                    <button type="button" wire:click="close" class="calv-btn calv-btn--ghost">Cancel</button>
                    <button type="button" wire:click="save" class="calv-btn calv-btn--primary" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="save">Done</span>
                        <span wire:loading wire:target="save">Saving…</span>
                    </button>
                </footer>
            </div>
        </div>
    @endif
</div>
