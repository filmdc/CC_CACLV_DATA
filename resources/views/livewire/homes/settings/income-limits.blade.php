<div class="px-6 lg:px-9 py-7 max-w-4xl mx-auto" style="min-height: 100vh;">

    @if (session('homes_message'))
        <div class="mb-4 rounded-md px-4 py-3 text-sm" style="background: var(--calv-sage-15); color: #2F5A41; border: 1px solid var(--calv-sage-35);">
            {{ session('homes_message') }}
        </div>
    @endif

    <a href="{{ route('homes.settings') }}" wire:navigate class="calv-link inline-block mb-3" style="font-size: 12.5px;">← Settings</a>

    <div class="flex items-end gap-4 mb-5">
        <div class="flex-1 min-w-0">
            <h1 class="calv-h1" style="font-size: 30px;">HOME income limits</h1>
            <div class="text-[13.5px] mt-1.5" style="color: var(--calv-slate-65);">
                HUD's LOW INCOME (80% of area median) ceiling for the Allentown&#8209;Bethlehem&#8209;Easton metro area. A housing intake qualifies when the household earns at or under the figure for its size, and is reported at its percent of area median income.
            </div>
        </div>
        @if($canCreate)
            <button type="button" wire:click="openAddYear" class="calv-btn calv-btn--primary">+ Year</button>
        @endif
    </div>

    @if(empty($years))
        <div class="calv-card p-6">
            <div class="text-[14px] font-semibold mb-1" style="color: var(--calv-slate);">No HOME limits loaded</div>
            <div class="text-[12.5px]" style="color: var(--calv-slate-65);">
                Housing intakes will read “no guidelines loaded” until a year is entered.
                @if($canCreate) Use <strong>+ Year</strong> to add HUD's published figures. @endif
            </div>
        </div>
    @else
        {{-- Year picker + actions --}}
        <div class="flex flex-wrap gap-2.5 items-center mb-4">
            <label class="text-[12px]" style="color: var(--calv-slate-65);">Fiscal year</label>
            <select wire:model.live="year" class="calv-input" style="width: auto; min-width: 120px; border-radius: 8px;" @disabled($editing)>
                @foreach($years as $y)
                    <option value="{{ $y }}">FY{{ $y }}</option>
                @endforeach
            </select>

            @if($unsavedYear)
                <span class="calv-badge calv-badge--amber">Not saved yet</span>
            @endif

            <span class="flex-1"></span>

            @if(! $editing)
                @if($canEdit)
                    <button type="button" wire:click="startEdit" class="calv-btn calv-btn--quiet calv-btn--sm">Edit figures</button>
                @endif
                @if($canDelete)
                    <button type="button" wire:click="deleteYear"
                            wire:confirm="Remove the FY{{ $year }} HOME limits? Housing intakes measured against this year will read “no guidelines loaded” until another year is entered. Determinations already recorded keep their figures."
                            class="calv-btn calv-btn--ghost calv-btn--sm" style="color: var(--calv-red);">Remove year</button>
                @endif
            @endif
        </div>

        {{-- Area median income --}}
        <div class="calv-card mb-3" style="padding: 14px 16px;">
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                <div class="flex-1" style="min-width: 240px;">
                    <div class="calv-subhead" style="font-size: 11px;">Area median income &mdash; 4&#8209;person</div>
                    <div class="text-[12.5px] mt-1 leading-snug" style="color: var(--calv-slate-65);">
                        What a household's percentage is measured against. HUD prints it on the income&#8209;limits summary beside the limits.
                    </div>
                </div>
                <div style="text-align: right;">
                    @if($editing)
                        <div class="flex items-center gap-1.5 justify-end">
                            <span class="text-[13px]" style="color: var(--calv-slate-65);">$</span>
                            <input wire:model.live.debounce.500ms="mfi" type="number" step="1" min="0"
                                   class="calv-input" style="max-width: 160px; border-radius: 8px;"
                                   placeholder="{{ $medianFamilyIncome ? number_format($medianFamilyIncome) : 'from HUD' }}"
                                   inputmode="numeric">
                        </div>
                        @error('mfi')<p class="mt-1 text-xs" style="color: var(--calv-red);">{{ $message }}</p>@enderror
                        <p class="mt-1 text-[11.5px]" style="color: var(--calv-slate-65);">Leave blank to derive it from the 4&#8209;person limit.</p>
                    @else
                        <div class="text-[18px] font-semibold" style="color: var(--calv-slate); font-variant-numeric: tabular-nums;">
                            {{ $medianFamilyIncome === null ? '—' : '$'.number_format($medianFamilyIncome) }}
                        </div>
                        @if($medianFamilyIncome !== null)
                            <div class="text-[11.5px] mt-0.5" style="color: var(--calv-slate-65);">
                                {{ $mfiIsDerived ? 'derived from the 4-person limit ÷ 0.8' : "HUD's published figure" }}
                            </div>
                        @endif
                    @endif
                </div>
            </div>
            @if($mfiIsDerived && $medianFamilyIncome !== null && ! $editing)
                <div class="mt-2.5 text-[11.5px] leading-snug" style="color: var(--calv-slate-65); border-top: 1px solid var(--calv-slate-15); padding-top: 9px;">
                    HUD caps the 80% limit in some areas, and where it does this derived figure runs low. If HUD's summary prints a different median, enter it here.
                </div>
            @endif
        </div>

        {{-- The figures --}}
        <div class="calv-card overflow-hidden">
            <div class="flex items-center gap-4 px-4 py-2.5" style="border-bottom: 1px solid var(--calv-slate-15); background: var(--calv-sand-15);">
                <div class="calv-subhead" style="font-size: 11px; width: 140px;">Household size</div>
                <div class="calv-subhead flex-1" style="font-size: 11px;">Annual limit (80% AMI)</div>
                <div class="calv-subhead" style="font-size: 11px; width: 120px; text-align: right;">Monthly</div>
                <div class="calv-subhead" style="font-size: 11px; width: 120px; text-align: right;">100% AMI</div>
            </div>

            @foreach($rows as $row)
                <div class="flex items-center gap-4 px-4 py-2.5" style="border-bottom: 1px solid var(--calv-slate-15);">
                    <div class="text-[13.5px] font-semibold" style="color: var(--calv-slate); width: 140px;">
                        {{ $row['size'] }} {{ Str::plural('person', $row['size']) }}
                        @if($row['derived'])
                            <span class="calv-badge" style="margin-left: 6px;">Extra</span>
                        @endif
                    </div>

                    <div class="flex-1 min-w-0">
                        @if($editing)
                            <div class="flex items-center gap-1.5">
                                <span class="text-[13px]" style="color: var(--calv-slate-65);">$</span>
                                <input wire:model="ceilings.{{ $row['size'] }}" type="number" step="1" min="0"
                                       class="calv-input" style="max-width: 160px; border-radius: 8px;"
                                       inputmode="numeric">
                            </div>
                            @error('ceilings.'.$row['size'])
                                <p class="mt-1 text-xs" style="color: var(--calv-red);">{{ $message }}</p>
                            @enderror
                        @else
                            <span class="text-[14px]" style="color: var(--calv-slate); font-variant-numeric: tabular-nums;">
                                @if($row['annual'] === null)
                                    <span style="color: var(--calv-red);">Not set</span>
                                @else
                                    ${{ number_format($row['annual']) }}
                                @endif
                            </span>
                        @endif
                    </div>

                    <div class="text-[13px]" style="color: var(--calv-slate-65); width: 120px; text-align: right; font-variant-numeric: tabular-nums;">
                        {{ $row['monthly'] === null ? '—' : '$'.number_format($row['monthly'], 2) }}
                    </div>

                    <div class="text-[13px]" style="color: var(--calv-slate-65); width: 120px; text-align: right; font-variant-numeric: tabular-nums;">
                        {{ $row['ami'] === null ? '—' : '$'.number_format($row['ami']) }}
                    </div>
                </div>
            @endforeach

            @if($editing)
                <div class="flex items-center gap-2.5 px-4 py-3.5" style="background: var(--calv-sand-15);">
                    <button type="button" wire:click="save" class="calv-btn calv-btn--primary calv-btn--sm" wire:loading.attr="disabled" wire:target="save">Save FY{{ $year }}</button>
                    <button type="button" wire:click="cancelEdit" class="calv-btn calv-btn--ghost calv-btn--sm">Cancel</button>
                    <span class="text-[11.5px]" style="color: var(--calv-slate-65);">
                        Enter the annual figure straight off HUD's table — the monthly column and the percentage brackets are worked out from it.
                    </span>
                </div>
            @endif
        </div>

        {{-- How the edges behave --}}
        <div class="mt-4 grid gap-3" style="grid-template-columns: 1fr 1fr;">
            <div class="calv-card" style="padding: 14px 16px;">
                <div class="calv-subhead mb-1.5" style="font-size: 11px;">Households over 8 people</div>
                <div class="text-[12.5px] leading-snug" style="color: var(--calv-slate-65);">
                    Worked out automatically by HUD's rule — the 8&#8209;person limit plus 8% of the 4&#8209;person limit for each extra person.
                    @if(! empty($preview))
                        <span class="block mt-1.5" style="color: var(--calv-slate); font-variant-numeric: tabular-nums;">
                            @foreach($preview as $size => $amount)
                                {{ $size }} people ${{ number_format($amount) }}@if(! $loop->last) · @endif
                            @endforeach
                        </span>
                    @endif
                </div>
            </div>
            <div class="calv-card" style="padding: 14px 16px;">
                <div class="calv-subhead mb-1.5" style="font-size: 11px;">How intakes are reported</div>
                <div class="text-[12.5px] leading-snug" style="color: var(--calv-slate-65);">
                    Each intake shows its income as a percent of area median income and lands in a HUD band ({{ implode('% · ', $tiers) }}%).
                    The figures are stored with the determination, so editing a year here never rewrites a decision already recorded.
                </div>
            </div>
        </div>

        <div class="mt-3 text-[12px]" style="color: var(--calv-slate-65);">
            Non&#8209;housing intakes are measured against the federal poverty guidelines instead — those live in CAP TRACK under Poverty Guidelines.
        </div>
    @endif

    {{-- Add-year modal --}}
    @if($showAddYear)
        <div class="fixed inset-0 z-[100] flex items-start justify-center overflow-y-auto" style="background: rgba(40,42,44,.55); padding: 56px 16px;">
            <div class="w-full" style="max-width: 440px; background: #fff; border-radius: 10px; box-shadow: var(--shadow-lg); overflow: hidden;" @click.stop>
                <header class="px-6 py-4" style="border-bottom: 1px solid var(--calv-slate-15);">
                    <h2 class="calv-h1" style="font-size: 20px;">Add a year</h2>
                </header>
                <div class="px-6 py-5 flex flex-col gap-3.5">
                    <div>
                        <label class="calv-label">Fiscal year</label>
                        <input wire:model="newYear" type="number" step="1" class="calv-input" placeholder="e.g. 2027">
                        @error('newYear')<p class="mt-1 text-xs" style="color: var(--calv-red);">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="calv-label">Start from</label>
                        <select wire:model="copyFromYear" class="calv-input">
                            <option value="">Blank — type every figure</option>
                            @foreach($years as $y)
                                <option value="{{ $y }}">Copy FY{{ $y }} figures forward</option>
                            @endforeach
                        </select>
                        @error('copyFromYear')<p class="mt-1 text-xs" style="color: var(--calv-red);">{{ $message }}</p>@enderror
                        <p class="mt-1.5 text-[11.5px]" style="color: var(--calv-slate-65);">
                            Nothing is saved until you review the figures and press Save.
                        </p>
                    </div>
                </div>
                <footer class="flex items-center gap-2.5 px-6 py-3.5 justify-end" style="border-top: 1px solid var(--calv-slate-15); background: var(--calv-sand-15);">
                    <button type="button" wire:click="closeAddYear" class="calv-btn calv-btn--ghost">Cancel</button>
                    <button type="button" wire:click="addYear" class="calv-btn calv-btn--primary" wire:loading.attr="disabled" wire:target="addYear">Continue</button>
                </footer>
            </div>
        </div>
    @endif
</div>
