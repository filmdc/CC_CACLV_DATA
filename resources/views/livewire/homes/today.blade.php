<div class="px-6 lg:px-9 py-7 max-w-6xl mx-auto" style="min-height: 100vh;">

    @if (session('homes_message'))
        <div class="mb-4 rounded-md px-4 py-3 text-sm" style="background: var(--calv-sage-15); color: #2F5A41; border: 1px solid var(--calv-sage-35);">
            {{ session('homes_message') }}
        </div>
    @endif

    {{-- Greeting + primary action --}}
    <div class="flex items-end gap-5 mb-6">
        <div class="flex-1 min-w-0">
            <div class="calv-lead-in">{{ $today }}</div>
            <h1 class="calv-h1" style="font-size: 34px; margin-top: 4px;">Good {{ (int) now()->format('H') < 12 ? 'morning' : ((int) now()->format('H') < 18 ? 'afternoon' : 'evening') }}, {{ $greetingName }}</h1>
            <div class="text-sm mt-2" style="color: var(--calv-slate-65);">
                @if($followUps->count())
                    {{ $followUps->count() }} {{ Str::plural('follow-up', $followUps->count()) }} need you today. Everything else can wait.
                @else
                    Nothing needs you right now. Nicely done.
                @endif
            </div>
        </div>
        @can('communications_log.create')
            <button type="button" x-on:click="Livewire.dispatch('homes-open-quick-log')" class="calv-btn calv-btn--primary calv-btn--lg">
                + Log a call
            </button>
        @endcan
    </div>

    @unless($canViewIntakes)
        <div class="calv-card p-6 text-sm" style="color: var(--calv-slate-65);">
            You don't have access to the communications log. Contact your administrator if you need it.
        </div>
    @else
    <div class="grid gap-4 items-start" style="grid-template-columns: minmax(0,1.5fr) minmax(0,1fr);">

        {{-- LEFT — the work queue --}}
        <div class="flex flex-col gap-4">

            {{-- Follow-ups due --}}
            <section class="calv-card overflow-hidden">
                <header class="flex items-center gap-2.5 px-4 py-3" style="border-bottom: 1px solid var(--calv-slate-15);">
                    <span class="inline-flex items-center justify-center h-6 w-6 rounded-md text-sm calv-h1" style="background: var(--calv-red-15); color: var(--calv-red);">{{ $followUps->count() }}</span>
                    <h3 class="calv-subhead flex-1" style="font-size: 12px;">Follow-ups due</h3>
                </header>
                @forelse($followUps as $row)
                    @php
                        $dueColor = $row['tone'] === 'red' ? 'var(--calv-red)' : ($row['tone'] === 'amber' ? '#8A6410' : 'var(--calv-slate-65)');
                    @endphp
                    <div class="flex items-center gap-3.5 px-4 py-3" style="border-bottom: 1px solid var(--calv-slate-15);">
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-semibold" style="color: var(--calv-slate);">{{ $row['who'] }}@if($row['where'])<span style="color: var(--calv-slate-65); font-weight: 400;"> · {{ $row['where'] }}</span>@endif</div>
                            @if($row['what'])<div class="text-[12.5px] mt-0.5 leading-snug" style="color: var(--calv-slate-65);">{{ $row['what'] }}</div>@endif
                        </div>
                        <div class="text-right shrink-0">
                            <div class="calv-subhead" style="font-size: 10px; color: {{ $dueColor }};">{{ $row['due_label'] }}</div>
                        </div>
                        <div class="flex items-center gap-1.5 shrink-0">
                            <a href="{{ route('homes.calls') }}?open={{ $row['id'] }}" wire:navigate class="calv-btn calv-btn--quiet calv-btn--sm">Open</a>
                            <button type="button" wire:click="completeFollowUp({{ $row['id'] }})" class="calv-btn calv-btn--quiet calv-btn--sm" title="Mark follow-up done">Done</button>
                        </div>
                    </div>
                @empty
                    <div class="px-4 py-6 text-center text-sm" style="color: var(--calv-slate-65);">No follow-ups due. You're all caught up.</div>
                @endforelse
            </section>

            {{-- Waiting on documents --}}
            <section class="calv-card overflow-hidden">
                <header class="flex items-center gap-2.5 px-4 py-3" style="border-bottom: 1px solid var(--calv-slate-15);">
                    <span class="inline-flex items-center justify-center h-6 w-6 rounded-md text-sm calv-h1" style="background: var(--calv-amber-15); color: #8A6410;">{{ $waiting->count() }}</span>
                    <h3 class="calv-subhead flex-1" style="font-size: 12px;">Waiting on documents</h3>
                    <a href="{{ route('homes.calls') }}?pill=collecting" wire:navigate class="calv-link" style="font-size: 11.5px;">see all</a>
                </header>
                @forelse($waiting as $row)
                    <div class="flex items-center gap-3.5 px-4 py-3" style="border-bottom: 1px solid var(--calv-slate-15);">
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-semibold" style="color: var(--calv-slate);">{{ $row['who'] }}@if($row['where'])<span style="color: var(--calv-slate-65); font-weight: 400;"> · {{ $row['where'] }}</span>@endif</div>
                            <div class="text-[12.5px] mt-0.5 leading-snug" style="color: var(--calv-slate-65);">
                                {{ $row['missing'] }}@if($row['total']) — {{ $row['met'] }} of {{ $row['total'] }} requirements met @endif
                            </div>
                        </div>
                        <a href="{{ route('homes.calls') }}?open={{ $row['id'] }}" wire:navigate class="calv-btn calv-btn--quiet calv-btn--sm shrink-0">Open</a>
                    </div>
                @empty
                    <div class="px-4 py-6 text-center text-sm" style="color: var(--calv-slate-65);">Nothing outstanding right now.</div>
                @endforelse
            </section>
        </div>

        {{-- RIGHT — glanceable --}}
        <div class="flex flex-col gap-4">

            {{-- Ready to become projects --}}
            <section class="calv-card overflow-hidden">
                <header class="flex items-center gap-2.5 px-4 py-3" style="border-bottom: 1px solid var(--calv-slate-15);">
                    <span class="inline-flex items-center justify-center h-6 w-6 rounded-md text-sm calv-h1" style="background: var(--calv-sage-15); color: #2F5A41;">{{ $ready->count() }}</span>
                    <h3 class="calv-subhead flex-1" style="font-size: 12px;">Ready to become projects</h3>
                </header>
                @forelse($ready as $row)
                    <div class="px-4 py-3.5" style="border-bottom: 1px solid var(--calv-slate-15);">
                        <div class="text-sm font-semibold" style="color: var(--calv-slate);">{{ $row['who'] }}@if($row['where'])<span style="color: var(--calv-slate-65); font-weight: 400;"> · {{ $row['where'] }}</span>@endif</div>
                        @if($row['summary'])<div class="text-[12.5px] mt-1 mb-2.5 leading-snug" style="color: var(--calv-slate-65);">{{ Str::limit($row['summary'], 120) }}</div>@endif
                        <div class="text-[11.5px]" style="color: var(--calv-slate-65);">All {{ $row['total'] }} requirements met · convert in the main app for now.</div>
                    </div>
                @empty
                    <div class="px-4 py-6 text-center text-sm" style="color: var(--calv-slate-65);">No intakes ready to convert yet.</div>
                @endforelse
            </section>

            {{-- This week at a glance --}}
            <section class="calv-card overflow-hidden">
                <header class="flex items-center gap-2.5 px-4 py-3" style="border-bottom: 1px solid var(--calv-slate-15);">
                    <span class="inline-flex items-center justify-center h-6 w-6 rounded-md text-sm calv-h1" style="background: var(--calv-teal-15); color: var(--calv-teal);">·</span>
                    <h3 class="calv-subhead flex-1" style="font-size: 12px;">This week at a glance</h3>
                </header>
                <div class="px-4 py-3 flex flex-col gap-2.5">
                    @php
                        $rows = [
                            ['Calls logged', $glance['calls_logged'], false],
                            ['New intakes', $glance['new_intakes'], false],
                            ['Projects active', $glance['projects_active'], false],
                        ];
                    @endphp
                    @foreach($rows as [$k, $v, $warn])
                        <div class="flex justify-between text-[13px]">
                            <span style="color: var(--calv-slate-65);">{{ $k }}</span>
                            <span class="font-semibold" style="color: var(--calv-slate);">{{ $v }}</span>
                        </div>
                    @endforeach
                    <div class="flex justify-between text-[13px]">
                        <span style="color: var(--calv-slate-65);">Fund expiring soon</span>
                        @if($glance['fund_expiring'])
                            <span class="font-semibold" style="color: var(--calv-red);">{{ $glance['fund_expiring']['code'] }} · {{ $glance['fund_expiring']['days'] }}d</span>
                        @else
                            <span class="font-semibold" style="color: var(--calv-slate);">—</span>
                        @endif
                    </div>
                </div>
            </section>

            <div class="px-4 py-3.5 text-[12.5px] leading-relaxed" style="background: #fff; border: 1px dashed var(--calv-slate-35); border-radius: 8px; color: var(--calv-slate-65);">
                <strong style="color: var(--calv-slate);">Nothing else needs you.</strong> Reports, fund codes, and setup live under Settings — out of the way until you want them.
            </div>
        </div>
    </div>
    @endunless
</div>
