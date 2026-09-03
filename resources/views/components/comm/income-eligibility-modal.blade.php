{{-- Income eligibility calculator (main app / dark-capable chrome).

     The homes-portal twin is
     resources/views/livewire/homes/partials/income-eligibility.blade.php.
     Both are skins over App\Livewire\Concerns\ManagesIncomeEligibility, so
     the arithmetic, validation and requirement transition are shared. --}}
@props([
    'log' => null,
    'preview' => [],
    'sources' => [],
    'sourceTypes' => [],
    'docTypes' => [],
    'docGroups' => [],
    'period' => 'annual',
])

<div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-start justify-center z-[70] overflow-y-auto py-10 px-4"
     wire:click.self="closeIncomeEligibility">
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-3xl w-full">

        <div class="px-6 py-4 border-b border-gray-200 dark:border-zinc-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Income eligibility</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                Household income and percent of poverty for this intake
                @if($log) · COMM-{{ $log->id }} @endif
            </p>
        </div>

        <div class="px-6 py-5 space-y-5">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-1.5">People in the household</label>
                    <input type="number" min="1" max="30" step="1" wire:model.live.debounce.400ms="incomeForm.household_size"
                           placeholder="e.g. 3"
                           class="w-full px-3 py-2 text-sm rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                    @error('incomeForm.household_size') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-[11px] font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-1.5">Amounts below are</label>
                    <select wire:model.live="incomeForm.entry_period"
                            class="w-full px-3 py-2 text-sm rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="annual">Per year</option>
                        <option value="monthly">Per month</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Monthly figures are multiplied by 12.</p>
                </div>
            </div>

            {{-- Itemized income; leaving it blank falls back to the lump total. --}}
            <div>
                <div class="flex items-baseline gap-2 mb-2">
                    <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Where the money comes from</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">optional — or just enter a total below</span>
                </div>
                <div class="space-y-2">
                    @foreach($sources as $i => $line)
                        <div class="flex items-center gap-2">
                            <select wire:model.live="incomeSources.{{ $i }}.type"
                                    class="w-48 px-2 py-1.5 text-sm rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                                @foreach($sourceTypes as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <input type="text" wire:model.live.debounce.500ms="incomeSources.{{ $i }}.label" placeholder="Who / note (optional)"
                                   class="flex-1 px-2 py-1.5 text-sm rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                            <div class="relative w-36">
                                <span class="absolute left-2 top-1/2 -translate-y-1/2 text-sm text-gray-400">$</span>
                                <input type="number" min="0" step="0.01" wire:model.live.debounce.400ms="incomeSources.{{ $i }}.amount" placeholder="0.00"
                                       class="w-full pl-5 pr-2 py-1.5 text-sm rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                            </div>
                            <button type="button" wire:click="removeIncomeSource({{ $i }})"
                                    class="px-2 py-1.5 text-sm text-gray-500 hover:text-red-600" title="Remove this line">&times;</button>
                        </div>
                        @error('incomeSources.'.$i.'.amount') <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    @endforeach
                </div>
                <button type="button" wire:click="addIncomeSource" class="mt-2 text-xs text-indigo-600 dark:text-indigo-400 hover:underline">
                    + add another income source
                </button>
            </div>

            <div>
                <label class="block text-[11px] font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-1.5">
                    Or the total household income{{ $period === 'monthly' ? ' per month' : ' per year' }}
                </label>
                <div class="relative w-56">
                    <span class="absolute left-2 top-1/2 -translate-y-1/2 text-sm text-gray-400">$</span>
                    <input type="number" min="0" step="0.01" wire:model.live.debounce.400ms="incomeForm.entered_amount" placeholder="0.00"
                           class="w-full pl-5 pr-2 py-2 text-sm rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                </div>
                @error('incomeForm.entered_amount') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Ignored when any income line above has an amount.</p>
            </div>

            {{-- Live determination --}}
            <div class="rounded-md p-4 {{ ($preview['available'] ?? false) ? 'bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800' : 'bg-gray-50 dark:bg-zinc-900/40 border border-gray-200 dark:border-zinc-700' }}">
                @if($preview['available'] ?? false)
                    <div class="text-3xl font-bold text-emerald-700 dark:text-emerald-300 leading-none">
                        {{ rtrim(rtrim(number_format($preview['percentage'], 1, '.', ''), '0'), '.') }}%
                        <span class="text-sm font-normal text-gray-600 dark:text-gray-300">of the federal poverty level</span>
                    </div>
                    <div class="mt-2 text-xs text-gray-700 dark:text-gray-300">
                        ${{ number_format($preview['annual_income'], 2) }} a year
                        (${{ number_format($preview['monthly_income'], 2) }} a month)
                        · household of {{ $preview['household_size'] }}
                        · {{ $preview['guideline_year'] }} guideline for that size is ${{ number_format($preview['threshold'], 2) }}
                        @if($preview['bracket'])
                            · at or under {{ $preview['bracket'] }}% FPL
                        @else
                            · above the highest published bracket (500% FPL)
                        @endif
                    </div>
                @else
                    <div class="text-sm text-gray-700 dark:text-gray-300">
                        @if(($preview['annual_income'] ?? null) === null || empty($preview['household_size']))
                            <strong>Enter a household size and an income</strong> to see the percent of poverty.
                        @else
                            <strong>No poverty guidelines are loaded</strong> for a household of {{ $preview['household_size'] }}.
                            The income still saves; the percentage stays blank until the guideline year is added under Poverty Guidelines.
                        @endif
                    </div>
                @endif
            </div>

            {{-- Proof documents --}}
            <div>
                <div class="text-[11px] font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">Proof of income</div>

                <div class="space-y-1.5 mb-3">
                    @foreach($docGroups as $slot => $group)
                        <div class="flex items-baseline gap-2 text-sm">
                            <span class="w-40 text-gray-700 dark:text-gray-300">{{ $group['label'] }}</span>
                            @if(count($group['files']))
                                <span class="flex flex-wrap gap-1.5">
                                    @foreach($group['files'] as $f)
                                        <a href="{{ $f['preview_url'] }}" target="_blank" rel="noopener"
                                           class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 hover:underline"
                                           title="{{ $f['name'] }} · {{ $f['size'] }}">
                                            {{ \Illuminate\Support\Str::limit($f['name'], 26) }}
                                        </a>
                                    @endforeach
                                </span>
                            @else
                                <span class="text-xs text-gray-400 dark:text-gray-500">nothing yet</span>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="flex items-center gap-2">
                    <select wire:model="incomeDocSlot"
                            class="w-48 px-2 py-1.5 text-sm rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                        @foreach($docTypes as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <input type="file" wire:model="incomeDocFiles" multiple
                           class="flex-1 text-sm text-gray-700 dark:text-gray-200">
                    <button type="button" wire:click="uploadIncomeDocuments"
                            wire:loading.attr="disabled" wire:target="uploadIncomeDocuments,incomeDocFiles"
                            class="px-3 py-1.5 text-sm rounded-md bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-60">
                        <span wire:loading.remove wire:target="uploadIncomeDocuments">Attach</span>
                        <span wire:loading wire:target="uploadIncomeDocuments">Attaching…</span>
                    </button>
                </div>
                <div wire:loading wire:target="incomeDocFiles" class="mt-1 text-xs text-gray-500 dark:text-gray-400">Uploading…</div>
                @error('incomeDocFiles') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                @error('incomeDocFiles.*') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">Attaching happens right away — it doesn't wait for Save.</p>
            </div>

            <div>
                <label class="block text-[11px] font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-1.5">Notes</label>
                <textarea wire:model="incomeForm.notes" rows="2"
                          placeholder="e.g. seasonal work — averaged the last 6 pay stubs"
                          class="w-full px-3 py-2 text-sm rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                @error('incomeForm.notes') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="px-6 py-4 border-t border-gray-200 dark:border-zinc-700 flex justify-end gap-2">
            <button type="button" wire:click="closeIncomeEligibility"
                    class="px-4 py-2 text-sm rounded-md border border-gray-300 dark:border-zinc-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-zinc-700">
                Cancel
            </button>
            <button type="button" wire:click="saveIncomeEligibility"
                    wire:loading.attr="disabled" wire:target="saveIncomeEligibility"
                    class="px-4 py-2 text-sm rounded-md bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-60">
                <span wire:loading.remove wire:target="saveIncomeEligibility">Save income eligibility</span>
                <span wire:loading wire:target="saveIncomeEligibility">Saving…</span>
            </button>
        </div>
    </div>
</div>
