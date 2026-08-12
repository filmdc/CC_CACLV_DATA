<div class="px-6 lg:px-9 py-7 max-w-6xl mx-auto" style="min-height: 100vh;">

    @php
        $fundDot = [
            'soon' => 'var(--calv-red)', 'warning' => 'var(--calv-amber)', 'ok' => 'var(--calv-sage)',
            'perpetual' => 'var(--calv-slate-35)', 'expired' => 'var(--calv-slate)',
        ];
        $statusBadge = [
            'attention' => ['label' => 'Needs attention', 'class' => 'calv-badge calv-badge--red'],
            'active' => ['label' => 'Moving along', 'class' => 'calv-badge calv-badge--teal'],
            'finished' => ['label' => 'Finished', 'class' => 'calv-badge calv-badge--sage'],
        ];
    @endphp

    {{-- Header --}}
    <div class="flex items-end gap-4 mb-5">
        <div class="flex-1 min-w-0">
            <h1 class="calv-h1" style="font-size: 30px;">Reports</h1>
            <div class="text-[13.5px] mt-1.5" style="color: var(--calv-slate-65);">Four reports, pre-built. Pick one, check the numbers, export. No query builder.</div>
        </div>
        <button type="button" wire:click="exportCsv" class="calv-btn calv-btn--secondary" wire:loading.attr="disabled" wire:target="exportCsv">
            Export "{{ $current['label'] }}" (CSV)
        </button>
    </div>

    <div class="grid gap-4 items-start" style="grid-template-columns: 230px minmax(0,1fr);">

        {{-- Report picker --}}
        <div class="flex flex-col gap-2">
            @foreach($reports as $id => $r)
                @php $active = $report === $id; @endphp
                <button type="button" wire:click="setReport('{{ $id }}')"
                        class="text-left rounded-lg px-3.5 py-3"
                        style="cursor: pointer; background: {{ $active ? '#fff' : 'transparent' }};
                               border: {{ $active ? '1.5px solid var(--calv-teal)' : '1px solid var(--calv-slate-15)' }};
                               box-shadow: {{ $active ? '0 0 0 3px var(--calv-teal-15)' : 'none' }};">
                    <span class="flex items-center gap-2">
                        <span class="text-[13.5px] font-semibold flex-1" style="color: var(--calv-slate);">{{ $r['label'] }}</span>
                        <span class="calv-badge" style="font-size: 9px;">{{ $r['audience'] }}</span>
                    </span>
                    <span class="block text-[11.5px] mt-1 leading-snug" style="color: var(--calv-slate-65);">{{ $r['sub'] }}</span>
                </button>
            @endforeach
            <div class="text-[11.5px] leading-relaxed px-1 py-1.5" style="color: var(--calv-slate-65);">Need a different cut? Export the CSV and slice it in Excel — nothing here is locked.</div>
        </div>

        {{-- Report body --}}
        <div class="flex flex-col gap-4 min-w-0">

            @if($report === 'activity')
                <div class="flex gap-3.5 flex-wrap">
                    <div class="calv-card px-4 py-3.5 flex-1" style="min-width: 130px;">
                        <div class="calv-subhead" style="font-size: 10px; color: var(--calv-slate-65);">Calls logged</div>
                        <div class="calv-h1" style="font-size: 28px; margin-top: 4px;">{{ $data['tiles']['calls'] }}</div>
                        <div class="text-[11.5px] mt-0.5" style="color: var(--calv-slate-65);">{{ $data['window'] }}</div>
                    </div>
                    <div class="calv-card px-4 py-3.5 flex-1" style="min-width: 130px;">
                        <div class="calv-subhead" style="font-size: 10px; color: var(--calv-slate-65);">New intakes</div>
                        <div class="calv-h1" style="font-size: 28px; margin-top: 4px;">{{ $data['tiles']['intakes'] }}</div>
                        @if($data['tiles']['intake_rate'])<div class="text-[11.5px] mt-0.5" style="color: var(--calv-slate-65);">{{ $data['tiles']['intake_rate'] }}</div>@endif
                    </div>
                    <div class="calv-card px-4 py-3.5 flex-1" style="min-width: 130px;">
                        <div class="calv-subhead" style="font-size: 10px; color: var(--calv-slate-65);">Converted to projects</div>
                        <div class="calv-h1" style="font-size: 28px; margin-top: 4px;">{{ $data['tiles']['converted'] }}</div>
                    </div>
                </div>

                {{-- CSS dual bar chart --}}
                <section class="calv-card px-4 pt-3.5 pb-2.5">
                    <div class="flex items-center gap-3.5 mb-1">
                        <h3 class="calv-subhead flex-1" style="font-size: 12px;">Calls &amp; intakes by month</h3>
                        <span class="inline-flex items-center gap-1.5 text-[11px]" style="color: var(--calv-slate-65);"><span style="width: 10px; height: 10px; border-radius: 2px; background: var(--calv-teal);"></span>Calls</span>
                        <span class="inline-flex items-center gap-1.5 text-[11px]" style="color: var(--calv-slate-65);"><span style="width: 10px; height: 10px; border-radius: 2px; background: var(--calv-amber);"></span>Intakes</span>
                    </div>
                    @php $max = max(1, $data['rows']->max('calls')); @endphp
                    <div class="flex items-end gap-4" style="height: 170px; padding: 10px 6px 0;">
                        @foreach($data['rows'] as $m)
                            <div class="flex-1 flex flex-col items-center gap-1.5 h-full justify-end">
                                <span class="text-[11px] font-semibold" style="color: var(--calv-slate);">{{ $m['calls'] }}</span>
                                <div class="flex gap-1 items-end justify-center h-full w-full" style="max-width: 46px;">
                                    <div style="width: 16px; height: {{ (int) round($m['calls'] / $max * 100) }}%; min-height: {{ $m['calls'] > 0 ? '3px' : '0' }}; background: var(--calv-teal); border-radius: 3px 3px 0 0;"></div>
                                    <div style="width: 16px; height: {{ (int) round($m['intakes'] / $max * 100) }}%; min-height: {{ $m['intakes'] > 0 ? '3px' : '0' }}; background: var(--calv-amber); border-radius: 3px 3px 0 0;"></div>
                                </div>
                                <span class="calv-subhead" style="font-size: 10px; color: var(--calv-slate-65);">{{ $m['month'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </section>

            @elseif($report === 'funds')
                <div class="flex gap-3.5 flex-wrap">
                    <div class="calv-card px-4 py-3.5 flex-1" style="min-width: 140px;">
                        <div class="calv-subhead" style="font-size: 10px; color: var(--calv-slate-65);">Spent (project budgets)</div>
                        <div class="calv-h1" style="font-size: 26px; margin-top: 4px;">${{ number_format($data['tiles']['spent']) }}</div>
                    </div>
                    <div class="calv-card px-4 py-3.5 flex-1" style="min-width: 140px;">
                        <div class="calv-subhead" style="font-size: 10px; color: var(--calv-slate-65);">Remaining</div>
                        <div class="calv-h1" style="font-size: 26px; margin-top: 4px;">${{ number_format($data['tiles']['remaining']) }}</div>
                    </div>
                    <div class="calv-card px-4 py-3.5 flex-1" style="min-width: 140px;">
                        <div class="calv-subhead" style="font-size: 10px; color: var(--calv-slate-65);">Expiring in 30 days</div>
                        <div class="calv-h1" style="font-size: 26px; margin-top: 4px; color: {{ $data['tiles']['expiring_soon'] > 0 ? 'var(--calv-red)' : 'var(--calv-slate)' }};">{{ $data['tiles']['expiring_soon'] }}</div>
                        @if($data['tiles']['at_risk_note'])<div class="text-[11.5px] mt-0.5" style="color: var(--calv-slate-65);">{{ $data['tiles']['at_risk_note'] }}</div>@endif
                    </div>
                </div>

                <div class="calv-card overflow-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr style="background: var(--calv-slate); color: #fff;">
                                @foreach(['Fund', 'What it pays for', 'Allocated', 'Spent', 'Balance', 'Status'] as $h)
                                    <th class="text-left px-3.5 py-2.5 whitespace-nowrap" style="font-family: var(--font-sub); font-size: 10px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase;">{{ $h }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($data['rows'] as $f)
                                <tr style="border-top: 1px solid var(--calv-slate-15);">
                                    <td class="px-3.5 py-2.5 text-[13px] font-semibold whitespace-nowrap" style="color: var(--calv-slate);">{{ $f['code'] }}</td>
                                    <td class="px-3.5 py-2.5 text-[13px]" style="color: var(--calv-slate);">{{ $f['name'] }}</td>
                                    <td class="px-3.5 py-2.5 text-[13px]" style="color: var(--calv-slate);">${{ number_format($f['allocated']) }}</td>
                                    <td class="px-3.5 py-2.5 text-[13px]" style="color: var(--calv-slate);">${{ number_format($f['spent']) }}</td>
                                    <td class="px-3.5 py-2.5 text-[13px]" style="color: var(--calv-slate);">${{ number_format($f['balance']) }}</td>
                                    <td class="px-3.5 py-2.5 whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1.5 text-xs" style="color: {{ $f['expiry_state'] === 'soon' ? 'var(--calv-red)' : 'var(--calv-slate-65)' }};">
                                            <span style="width: 8px; height: 8px; border-radius: 999px; background: {{ $fundDot[$f['expiry_state']] ?? 'var(--calv-sage)' }};"></span>
                                            {{ $f['expiry_note'] }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-6 text-center text-sm" style="color: var(--calv-slate-65);">No project budget lines carry a fund code yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="text-[11.5px]" style="color: var(--calv-slate-65);">Numbers come from the project budget tracker (your own data entry) — Fiscal's systems remain the accounting source of truth.</div>

            @elseif($report === 'funnel')
                <section class="calv-card px-5 py-4.5 flex flex-col gap-3.5" style="padding-top: 18px; padding-bottom: 18px;">
                    @php $max = max(1, $data['rows']->first()['n']); @endphp
                    @foreach($data['rows'] as $i => $s)
                        <div>
                            <div class="flex justify-between items-baseline mb-1">
                                <span class="text-[13px] font-semibold" style="color: var(--calv-slate);">{{ $s['stage'] }} <span class="font-normal text-[11.5px]" style="color: var(--calv-slate-65);">— {{ $s['note'] }}</span></span>
                                <span class="calv-h1" style="font-size: 18px;">{{ $s['n'] }}</span>
                            </div>
                            <div style="background: var(--calv-slate-15); border-radius: 999px; height: 14px; overflow: hidden;">
                                <div style="width: {{ max(4, (int) round($s['n'] / $max * 100)) }}%; height: 100%; border-radius: 999px; background: {{ $loop->last ? 'var(--calv-sage)' : 'var(--calv-teal)' }}; opacity: {{ 1 - $i * 0.12 }};"></div>
                            </div>
                        </div>
                    @endforeach
                    <div class="text-xs mt-1" style="color: var(--calv-slate-65);">{{ $data['window'] }}.</div>
                </section>

            @else
                {{-- Project status --}}
                <div class="calv-card overflow-auto">
                    <table class="w-full border-collapse" style="min-width: 800px;">
                        <thead>
                            <tr style="background: var(--calv-slate); color: #fff;">
                                @foreach(['Homeowner', 'Address', 'Program', 'Milestones', 'Budget', 'Spent', 'Status'] as $h)
                                    <th class="text-left px-3.5 py-2.5 whitespace-nowrap" style="font-family: var(--font-sub); font-size: 10px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase;">{{ $h }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($data['rows'] as $p)
                                <tr style="border-top: 1px solid var(--calv-slate-15); background: {{ $p['status'] === 'attention' ? 'var(--calv-red-15)' : '#fff' }};">
                                    <td class="px-3.5 py-2.5 text-[13px] font-semibold" style="color: var(--calv-slate);">{{ $p['who'] }}</td>
                                    <td class="px-3.5 py-2.5 text-[13px]" style="color: var(--calv-slate);">{{ $p['where'] }}</td>
                                    <td class="px-3.5 py-2.5 text-[13px]" style="color: var(--calv-slate);">{{ $p['program'] }}</td>
                                    <td class="px-3.5 py-2.5 text-[13px]" style="color: var(--calv-slate);">{{ $p['milestones'] }}</td>
                                    <td class="px-3.5 py-2.5 text-[13px]" style="color: var(--calv-slate);">${{ number_format($p['budget']) }}</td>
                                    <td class="px-3.5 py-2.5 text-[13px]" style="color: var(--calv-slate);">${{ number_format($p['spent']) }}</td>
                                    <td class="px-3.5 py-2.5"><span class="{{ $statusBadge[$p['status']]['class'] }}">{{ $statusBadge[$p['status']]['label'] }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-4 py-6 text-center text-sm" style="color: var(--calv-slate-65);">No open projects.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
