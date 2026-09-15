{{-- Income eligibility calculator (CALV light theme).

     The dark-app twin is resources/views/components/comm/income-eligibility-modal.blade.php.
     Both are thin skins over App\Livewire\Concerns\ManagesIncomeEligibility,
     so the numbers and the lifecycle cannot drift between the two portals.

     Explicit Cancel is the only close path — the portal's modal convention. --}}
@php
    $preview = $this->incomePreview;
@endphp

<div class="fixed inset-0 z-[100] flex items-start justify-center overflow-y-auto" style="background: rgba(40,42,44,.55); padding: 48px 16px;">
    <div class="w-full" style="max-width: 760px; background: #fff; border-radius: 10px; box-shadow: var(--shadow-lg); overflow: hidden;" wire:click.stop>

        <header class="px-6 py-4" style="border-bottom: 1px solid var(--calv-slate-15);">
            <h2 class="calv-h1" style="font-size: 20px;">Income eligibility</h2>
            <div class="text-xs mt-1" style="color: var(--calv-slate-65);">{{ $who }} · COMM-{{ $log->id }}</div>
        </header>

        <div class="px-6 py-5 flex flex-col gap-5">

            {{-- Household size + how the amounts are being entered --}}
            <div class="grid gap-3" style="grid-template-columns: 1fr 1fr;">
                <div>
                    <label class="calv-subhead block mb-1.5" style="font-size: 11px;">People in the household</label>
                    <input type="number" min="1" max="30" step="1" wire:model.live.debounce.400ms="incomeForm.household_size"
                           class="calv-input" placeholder="e.g. 3">
                    @error('incomeForm.household_size')<p class="mt-1 text-xs" style="color: var(--calv-red);">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="calv-subhead block mb-1.5" style="font-size: 11px;">Amounts below are</label>
                    <select wire:model.live="incomeForm.entry_period" class="calv-input">
                        <option value="annual">Per year</option>
                        <option value="monthly">Per month</option>
                    </select>
                    <p class="text-[11px] mt-1" style="color: var(--calv-slate-65);">Monthly figures are multiplied by 12.</p>
                </div>
            </div>

            {{-- Itemized income. Leaving every line blank falls back to the
                 lump total underneath. --}}
            <div>
                <div class="flex items-baseline gap-2 mb-2">
                    <span class="calv-subhead" style="font-size: 11px;">Where the money comes from</span>
                    <span class="text-[11px]" style="color: var(--calv-slate-65);">optional — or just enter a total below</span>
                </div>
                <div class="flex flex-col gap-2">
                    @foreach($incomeSources as $i => $line)
                        <div class="flex items-center gap-2">
                            <select wire:model.live="incomeSources.{{ $i }}.type" class="calv-input" style="width: 180px;">
                                @foreach($incomeSourceTypes as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <input type="text" wire:model.live.debounce.500ms="incomeSources.{{ $i }}.label"
                                   class="calv-input flex-1" placeholder="Who / note (optional)">
                            <div class="relative" style="width: 150px;">
                                <span class="absolute" style="left: 10px; top: 50%; transform: translateY(-50%); color: var(--calv-slate-65); font-size: 12.5px;">$</span>
                                <input type="number" min="0" step="0.01" wire:model.live.debounce.400ms="incomeSources.{{ $i }}.amount"
                                       class="calv-input" style="padding-left: 22px;" placeholder="0.00">
                            </div>
                            <button type="button" wire:click="removeIncomeSource({{ $i }})"
                                    class="calv-btn calv-btn--ghost calv-btn--sm" title="Remove this line">✕</button>
                        </div>
                        @error('incomeSources.'.$i.'.amount')<p class="text-xs" style="color: var(--calv-red);">{{ $message }}</p>@enderror
                    @endforeach
                </div>
                <button type="button" wire:click="addIncomeSource" class="calv-link mt-2"
                        style="background:none;border:none;cursor:pointer;font-size:12.5px;">+ add another income source</button>
            </div>

            {{-- Lump total, used only when nothing is itemized. --}}
            <div>
                <label class="calv-subhead block mb-1.5" style="font-size: 11px;">
                    Or the total household income{{ $incomeForm['entry_period'] === 'monthly' ? ' per month' : ' per year' }}
                </label>
                <div class="relative" style="max-width: 220px;">
                    <span class="absolute" style="left: 10px; top: 50%; transform: translateY(-50%); color: var(--calv-slate-65); font-size: 12.5px;">$</span>
                    <input type="number" min="0" step="0.01" wire:model.live.debounce.400ms="incomeForm.entered_amount"
                           class="calv-input" style="padding-left: 22px;" placeholder="0.00">
                </div>
                @error('incomeForm.entered_amount')<p class="mt-1 text-xs" style="color: var(--calv-red);">{{ $message }}</p>@enderror
                <p class="text-[11px] mt-1" style="color: var(--calv-slate-65);">Ignored when any income line above has an amount.</p>
            </div>

            {{-- Live determination --}}
            @php
                // HOME determinations carry an AMI; FPL ones don't. An
                // over-income household must not read as a green pass.
                $isHome = ($preview['ami'] ?? null) !== null;
                $overIncome = $isHome && ($preview['eligible'] ?? true) === false;
                $toneOk = $preview['available'] && ! $overIncome;
            @endphp
            <div class="px-4 py-3.5" style="border-radius: 8px; background: {{ $toneOk ? 'var(--calv-sage-15)' : 'var(--calv-sand-15)' }}; border: 1px solid {{ $toneOk ? 'var(--calv-sage)' : ($overIncome ? 'var(--calv-amber, #C98A2E)' : 'var(--calv-slate-15)') }};">
                @if($preview['available'])
                    <div class="calv-h1" style="font-size: 26px; line-height: 1; color: {{ $overIncome ? '#8A5A12' : '#2F5A41' }};">
                        {{ rtrim(rtrim(number_format($preview['percentage'], 1, '.', ''), '0'), '.') }}%
                        <span style="font-size: 13px; font-weight: normal; color: var(--calv-slate-65);">of {{ $preview['basis'] }}</span>
                    </div>

                    @if($isHome)
                        <div class="flex flex-wrap items-center gap-1.5 mt-2">
                            @if($preview['tier_label'])
                                <span class="calv-badge">{{ $preview['tier_label'] }} · at or under {{ $preview['bracket'] }}% AMI</span>
                            @endif
                            @if($preview['eligible'])
                                <span class="calv-badge calv-badge--sage">Income eligible</span>
                            @else
                                <span class="calv-badge calv-badge--amber">Over income</span>
                            @endif
                        </div>
                    @endif

                    <div class="text-[12px] mt-2" style="color: var(--calv-slate);">
                        ${{ number_format($preview['annual_income'], 2) }} a year
                        (${{ number_format($preview['monthly_income'], 2) }} a month)
                        · household of {{ $preview['household_size'] }}
                        @if($isHome)
                            · area median income for that size is ${{ number_format($preview['ami'], 2) }}
                            · FY{{ $preview['guideline_year'] }} eligibility limit is ${{ number_format($preview['threshold'], 2) }}
                        @else
                            · {{ $preview['guideline_year'] }} guideline for that size is ${{ number_format($preview['threshold'], 2) }}
                            @if($preview['bracket'])
                                · at or under {{ $preview['bracket'] }}% {{ $preview['bracket_unit'] }}
                            @else
                                · above the highest published bracket (500% {{ $preview['bracket_unit'] }})
                            @endif
                        @endif
                    </div>
                @else
                    <div class="text-[12.5px]" style="color: var(--calv-slate);">
                        @if($preview['annual_income'] === null || ! $preview['household_size'])
                            <strong>Enter a household size and an income</strong> to see the eligibility percentage.
                        @else
                            <strong>No income guidelines are loaded</strong> for a household of {{ $preview['household_size'] }}.
                            The income still saves; the percentage stays blank until an admin adds the guideline year under Poverty Guidelines.
                        @endif
                    </div>
                @endif
            </div>

            {{-- Proof documents --}}
            <div>
                <div class="calv-subhead mb-2" style="font-size: 11px;">Proof of income</div>

                <div class="flex flex-col gap-2 mb-3">
                    @foreach($incomeDocGroups as $slot => $group)
                        <div class="flex items-baseline gap-2 text-[12.5px]">
                            <span style="width: 150px; color: var(--calv-slate);">{{ $group['label'] }}</span>
                            @if(count($group['files']))
                                <span class="flex flex-wrap gap-1.5">
                                    @foreach($group['files'] as $f)
                                        <a href="{{ $f['preview_url'] }}" target="_blank" rel="noopener"
                                           class="inline-flex items-center gap-1 text-[11px]"
                                           style="color: var(--calv-teal); background: var(--calv-teal-15); border: 1px solid var(--calv-teal-35); border-radius: 999px; padding: 2px 8px; text-decoration: none;"
                                           title="{{ $f['name'] }} · {{ $f['size'] }}">
                                            <span aria-hidden="true">📎</span>{{ Str::limit($f['name'], 26) }}
                                        </a>
                                    @endforeach
                                </span>
                            @else
                                <span class="text-[11px]" style="color: var(--calv-slate-65);">nothing yet</span>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="flex items-center gap-2">
                    <select wire:model="incomeDocSlot" class="calv-input" style="width: 190px;">
                        @foreach($incomeDocTypes as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <input type="file" wire:model="incomeDocFiles" multiple class="calv-input flex-1">
                    <button type="button" wire:click="uploadIncomeDocuments" class="calv-btn calv-btn--secondary calv-btn--sm"
                            wire:loading.attr="disabled" wire:target="uploadIncomeDocuments,incomeDocFiles">
                        <span wire:loading.remove wire:target="uploadIncomeDocuments">Attach</span>
                        <span wire:loading wire:target="uploadIncomeDocuments">Attaching…</span>
                    </button>
                </div>
                <div wire:loading wire:target="incomeDocFiles" class="text-xs mt-1" style="color: var(--calv-slate-65);">Uploading…</div>
                @error('incomeDocFiles')<p class="mt-1 text-xs" style="color: var(--calv-red);">{{ $message }}</p>@enderror
                @error('incomeDocFiles.*')<p class="mt-1 text-xs" style="color: var(--calv-red);">{{ $message }}</p>@enderror
                <p class="text-[11px] mt-1.5" style="color: var(--calv-slate-65);">Attaching happens right away — it doesn't wait for Save.</p>
            </div>

            <div>
                <label class="calv-subhead block mb-1.5" style="font-size: 11px;">Notes</label>
                <textarea wire:model="incomeForm.notes" rows="2" class="calv-input"
                          placeholder="e.g. seasonal work — averaged the last 6 pay stubs"></textarea>
                @error('incomeForm.notes')<p class="mt-1 text-xs" style="color: var(--calv-red);">{{ $message }}</p>@enderror
            </div>
        </div>

        <footer class="flex items-center gap-2.5 px-6 py-3.5 justify-end" style="border-top: 1px solid var(--calv-slate-15); background: var(--calv-sand-15);">
            <button type="button" wire:click="closeIncomeEligibility" class="calv-btn calv-btn--ghost">Cancel</button>
            <button type="button" wire:click="saveIncomeEligibility" class="calv-btn calv-btn--primary"
                    wire:loading.attr="disabled" wire:target="saveIncomeEligibility">
                <span wire:loading.remove wire:target="saveIncomeEligibility">Save income eligibility</span>
                <span wire:loading wire:target="saveIncomeEligibility">Saving…</span>
            </button>
        </footer>
    </div>
</div>
