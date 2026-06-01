@php
    // Project Detail redesign (rev 1 · May 30 2026): unified status vocabulary
    // mapping ProjectMilestone.status to CALV tones. Same vocab is used for
    // project.status fallback when it lines up; non-overlapping project
    // statuses (on_hold, cancelled) fall through to the default tone.
    $msStatusTone = [
        'not_started' => ['label' => 'Pending', 'bg' => 'var(--color-calv-slate-15)', 'fg' => 'var(--color-calv-slate-65)', 'dot' => 'var(--color-calv-slate-35)'],
        'pending' => ['label' => 'Pending', 'bg' => 'var(--color-calv-slate-15)', 'fg' => 'var(--color-calv-slate-65)', 'dot' => 'var(--color-calv-slate-35)'],
        'in_progress' => ['label' => 'In progress', 'bg' => 'var(--color-calv-teal-15)', 'fg' => 'var(--color-calv-teal)', 'dot' => 'var(--color-calv-teal)'],
        'completed' => ['label' => 'Completed', 'bg' => 'var(--color-calv-sage-15)', 'fg' => '#2F5A41', 'dot' => 'var(--color-calv-sage)'],
        'blocked' => ['label' => 'Blocked', 'bg' => 'var(--color-calv-red-15)', 'fg' => 'var(--color-calv-red)', 'dot' => 'var(--color-calv-red)'],
        'on_hold' => ['label' => 'On hold', 'bg' => 'var(--color-calv-amber-15)', 'fg' => '#8A6410', 'dot' => 'var(--color-calv-amber)'],
        'cancelled' => ['label' => 'Cancelled', 'bg' => 'var(--color-calv-slate-15)', 'fg' => 'var(--color-calv-slate-65)', 'dot' => 'var(--color-calv-slate-65)'],
        'approved' => ['label' => 'Approved', 'bg' => 'var(--color-calv-sage-15)', 'fg' => '#2F5A41', 'dot' => 'var(--color-calv-sage)'],
        'rejected' => ['label' => 'Rejected', 'bg' => 'var(--color-calv-red-15)', 'fg' => 'var(--color-calv-red)', 'dot' => 'var(--color-calv-red)'],
    ];
    $priorityTone = [
        'emergency' => ['label' => 'Urgent', 'dot' => 'var(--color-calv-red)'],
        'urgent' => ['label' => 'Urgent', 'dot' => 'var(--color-calv-red)'],
        'high' => ['label' => 'High', 'dot' => 'var(--color-calv-amber)'],
        'medium' => ['label' => 'Medium', 'dot' => 'var(--color-calv-teal)'],
        'low' => ['label' => 'Low', 'dot' => 'var(--color-calv-slate-65)'],
    ];

    $jobStatusKey = $job->status ?? 'pending';
    $jobStatus = $msStatusTone[$jobStatusKey] ?? $msStatusTone['pending'];
    $jobPriority = $priorityTone[$job->priority ?? 'medium'] ?? $priorityTone['medium'];

    // Unified-progress numbers (operational + financial).
    $milestones = $job->milestones ?? collect();
    $msTotal = $milestones->count();
    $msDone = $milestones->where('status', 'completed')->count();
    $msPct = $msTotal > 0 ? (int) round(($msDone / $msTotal) * 100) : 0;

    $budgets = $job->budgets ?? collect();
    $totalBudget = (float) $budgets->sum('budgeted_amount');
    $totalSpent = (float) $budgets->sum('spent_amount');
    $burnPct = $totalBudget > 0 ? (int) round(($totalSpent / $totalBudget) * 100) : 0;
@endphp

<div class="w-full bg-white" style="font-family: var(--font-calv-body);">
    {{-- ────────────────────────────────────────────────────────────────
         Consolidated Header — replaces the legacy indigo-band + gray
         status row + emerald fund block (Project Detail redesign §4.1).
         One block: breadcrumb (with optional 'from intake COMM-####'),
         address + homeowner as H1 (DIN Condensed → Oswald uppercase),
         status / priority / program / funds in chips on a single row,
         and primary Edit / ghost Save-as-template on the right.
         ──────────────────────────────────────────────────────────── --}}
    <header class="bg-white border-b" style="border-color: var(--color-calv-slate-15);">
        <div class="px-8 pt-5 pb-4 flex items-start gap-5">
            {{-- Back button (quiet, slate not indigo) --}}
            <a href="{{ route('projects.management') }}" wire:navigate
               aria-label="Back to projects"
               class="inline-flex items-center justify-center mt-1 shrink-0 rounded-md"
               style="width: 36px; height: 36px; border: 1px solid var(--color-calv-slate-35); color: var(--color-calv-slate); background: #fff;">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                    <path d="M10 3L5 8l5 5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </a>

            <div class="flex-1 min-w-0">
                {{-- Breadcrumb + origin chip --}}
                <div class="flex items-center gap-2 mb-1.5 flex-wrap"
                     style="font-family: var(--font-calv-sub); font-size: 10.5px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--color-calv-slate-65);">
                    <span>Housing</span>
                    <span style="color: var(--color-calv-slate-35);">›</span>
                    <span>Projects</span>
                    <span style="color: var(--color-calv-slate-35);">›</span>
                    <span style="color: var(--color-calv-slate);">{{ $job->job_number }}</span>
                    @if($originatingCommLog)
                        <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded-full"
                              style="background: var(--color-calv-sand-15); border: 1px solid var(--color-calv-sand-35); text-transform: none; letter-spacing: 0; font-weight: 500; font-size: 10.5px; color: var(--color-calv-slate-65);">
                            from intake
                            <a href="{{ route('communications-log.management') }}" wire:navigate
                               class="ml-1 underline font-bold"
                               style="color: var(--color-calv-teal);">{{ $originatingCommLog->log_number ?? ('COMM-' . str_pad($originatingCommLog->id, 5, '0', STR_PAD_LEFT)) }}</a>
                        </span>
                    @endif
                </div>

                {{-- H1 — address + homeowner --}}
                <div class="flex items-baseline gap-3 flex-wrap">
                    <h1 class="m-0"
                        style="font-family: var(--font-calv-h1); font-weight: 700; font-size: 32px; letter-spacing: 0.01em; text-transform: uppercase; color: var(--color-calv-slate); line-height: 1;">
                        {{ $job->property?->address_line_1 ?? 'No property on file' }}
                    </h1>
                    @if($job->participant)
                        <span style="font-size: 16px; color: var(--color-calv-slate-65);">{{ $job->participant->first_name }} {{ $job->participant->last_name }}</span>
                    @endif
                </div>

                {{-- Status / Priority / Program / Funds row — chips on a single line --}}
                <div class="flex items-center gap-4 mt-3 flex-wrap">
                    {{-- Status --}}
                    <div class="flex items-center gap-2">
                        <span style="font-family: var(--font-calv-sub); font-size: 9.5px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: var(--color-calv-slate-65);">Status</span>
                        <span class="inline-flex items-center gap-1.5 rounded-full"
                              style="padding: 3px 9px; font-family: var(--font-calv-sub); font-size: 10px; font-weight: 700; letter-spacing: 0.07em; text-transform: uppercase; background: {{ $jobStatus['bg'] }}; color: {{ $jobStatus['fg'] }};">
                            <span class="rounded-full" style="width: 6px; height: 6px; background: {{ $jobStatus['dot'] }};"></span>
                            {{ $jobStatus['label'] }}
                        </span>
                    </div>
                    <span class="block" style="width: 1px; height: 16px; background: var(--color-calv-slate-15);"></span>
                    {{-- Priority --}}
                    <div class="flex items-center gap-2">
                        <span style="font-family: var(--font-calv-sub); font-size: 9.5px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: var(--color-calv-slate-65);">Priority</span>
                        <span class="inline-flex items-center gap-1.5" style="font-family: var(--font-calv-sub); font-size: 10px; font-weight: 700; letter-spacing: 0.07em; text-transform: uppercase; color: var(--color-calv-slate);">
                            <span class="rounded-full" style="width: 7px; height: 7px; background: {{ $jobPriority['dot'] }};"></span>
                            {{ $jobPriority['label'] }}
                        </span>
                    </div>
                    <span class="block" style="width: 1px; height: 16px; background: var(--color-calv-slate-15);"></span>
                    {{-- Program --}}
                    <div class="flex items-center gap-2">
                        <span style="font-family: var(--font-calv-sub); font-size: 9.5px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: var(--color-calv-slate-65);">Program</span>
                        <span style="font-size: 13px; font-weight: 600; color: var(--color-calv-slate);">{{ $job->program?->name ?? 'N/A' }}</span>
                    </div>
                    @if($this->canAny(['projects.manage', 'housing_jobs.edit']) || $job->fundCodes->isNotEmpty())
                        <span class="block" style="width: 1px; height: 16px; background: var(--color-calv-slate-15);"></span>
                        {{-- Funds — quiet slate pills with expiry dots, inline. Replaces the
                             bright-green emerald-50 band that pulled focus (issue #5). --}}
                        <div class="flex items-center gap-2">
                            <span style="font-family: var(--font-calv-sub); font-size: 9.5px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: var(--color-calv-slate-65);">Funds</span>
                            <div class="flex gap-1.5 flex-wrap items-center">
                                @foreach($job->fundCodes as $fc)
                                    @php
                                        // Expiry dot: red < 30d, amber 30–90d, sage > 90d, slate if no expiry.
                                        $days = $fc->expires_at ? (int) now()->diffInDays($fc->expires_at, false) : null;
                                        $dotColor = match (true) {
                                            $days === null => 'var(--color-calv-slate-35)',
                                            $days < 30 => 'var(--color-calv-red)',
                                            $days < 90 => 'var(--color-calv-amber)',
                                            default => 'var(--color-calv-sage)',
                                        };
                                    @endphp
                                    <span title="{{ $fc->description }}{{ $days !== null ? ' · expires in '.$days.' days' : '' }}"
                                          class="inline-flex items-center gap-1.5 rounded"
                                          style="padding: 3px 8px; font-family: ui-monospace, Menlo, monospace; font-size: 11px; font-weight: 600; background: var(--color-calv-slate-15); color: var(--color-calv-slate);">
                                        <span class="rounded-full" style="width: 6px; height: 6px; background: {{ $dotColor }};"></span>
                                        {{ $fc->code }}
                                        @if($this->can('projects.manage'))
                                            <button wire:click="detachFundCode({{ $fc->id }})"
                                                    wire:confirm="Detach {{ $fc->code }} from this project?"
                                                    class="ml-0.5 opacity-60 hover:opacity-100" aria-label="Detach">
                                                <svg width="9" height="9" viewBox="0 0 16 16" fill="none">
                                                    <path d="M4 4l8 8M12 4l-8 8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                                </svg>
                                            </button>
                                        @endif
                                    </span>
                                @endforeach
                                @if($this->can('projects.manage') && $availableFundCodes->isNotEmpty())
                                    <details class="relative">
                                        <summary class="cursor-pointer list-none rounded"
                                                 style="padding: 3px 8px; font-family: var(--font-calv-sub); font-size: 10px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; background: transparent; border: 1px dashed var(--color-calv-slate-35); color: var(--color-calv-slate-65);">+ fund</summary>
                                        <div class="absolute z-20 mt-1 left-0 bg-white rounded shadow-lg p-2"
                                             style="min-width: 240px; border: 1px solid var(--color-calv-slate-15);">
                                            <select wire:model="newFundCodeId"
                                                    class="w-full px-2 py-1 text-sm rounded mb-1"
                                                    style="border: 1px solid var(--color-calv-slate-35);">
                                                <option value="">Select a fund code…</option>
                                                @foreach($availableFundCodes as $fc)
                                                    <option value="{{ $fc->id }}">{{ $fc->code }} – {{ $fc->description }}</option>
                                                @endforeach
                                            </select>
                                            <button wire:click="attachFundCode"
                                                    class="w-full px-2 py-1 text-xs rounded text-white"
                                                    style="background: var(--color-calv-teal); font-family: var(--font-calv-sub); font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase;">Attach</button>
                                        </div>
                                    </details>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Actions — ONE primary, ONE ghost. Resolves issue #4 (competing weights). --}}
            @if($this->can('projects.manage'))
                <div class="flex items-center gap-2 shrink-0">
                    @if($job->milestones()->count() > 0)
                        <button wire:click="showSaveAsTemplate"
                                class="rounded-md transition"
                                style="padding: 7px 14px; background: transparent; color: var(--color-calv-slate); border: 1.5px solid var(--color-calv-slate-35); font-family: var(--font-calv-sub); font-size: 11.5px; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase;">
                            Save as template
                        </button>
                    @endif
                    <button wire:click="showEditProject"
                            class="rounded-md transition inline-flex items-center gap-1.5"
                            style="padding: 7px 14px; background: var(--color-calv-slate); color: #fff; border: none; font-family: var(--font-calv-sub); font-size: 11.5px; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase;">
                        <svg width="13" height="13" viewBox="0 0 16 16" fill="none">
                            <path d="M11 2.5l2.5 2.5L5 13.5H2.5V11L11 2.5z" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Edit project
                    </button>
                </div>
            @endif
        </div>

        {{-- Unified progress strip (resolves issue #9 — two progress notions side by side). --}}
        <div class="px-8 pb-3.5 grid gap-6" style="grid-template-columns: 1fr 1fr;">
            <div>
                <div class="flex items-baseline justify-between mb-1.5">
                    <span style="font-family: var(--font-calv-sub); font-size: 9.5px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: var(--color-calv-slate-65);">Operational</span>
                    <span style="font-size: 12px; color: var(--color-calv-slate);">
                        {{ $msDone }} of {{ $msTotal }} milestone{{ $msTotal === 1 ? '' : 's' }} complete
                        <span style="color: var(--color-calv-slate-65);">· {{ $msPct }}%</span>
                    </span>
                </div>
                <div class="rounded-full overflow-hidden" style="height: 6px; background: var(--color-calv-slate-15);">
                    <div class="h-full" style="width: {{ $msPct }}%; background: var(--color-calv-teal);"></div>
                </div>
            </div>
            <div>
                <div class="flex items-baseline justify-between mb-1.5">
                    <span style="font-family: var(--font-calv-sub); font-size: 9.5px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: var(--color-calv-slate-65);">Financial</span>
                    <span style="font-size: 12px; color: var(--color-calv-slate);">
                        ${{ number_format($totalSpent, 0) }} of ${{ number_format($totalBudget, 0) }} spent
                        <span style="color: var(--color-calv-slate-65);">· {{ $burnPct }}%</span>
                    </span>
                </div>
                <div class="rounded-full overflow-hidden" style="height: 6px; background: var(--color-calv-slate-15);">
                    <div class="h-full" style="width: {{ min($burnPct, 100) }}%; background: var(--color-calv-sage);"></div>
                </div>
            </div>
        </div>

        {{-- Tabs — teal underline on active (no more indigo). --}}
        <div class="px-8 flex gap-1">
            <button wire:click="$set('activeTab', 'work')"
                    data-test="tab-work tab-requirements tab-milestones"
                    class="border-b-2 transition"
                    style="padding: 12px 14px; background: transparent; border-color: {{ $activeTab === 'work' ? 'var(--color-calv-teal)' : 'transparent' }}; color: {{ $activeTab === 'work' ? 'var(--color-calv-teal)' : 'var(--color-calv-slate-65)' }}; font-family: var(--font-calv-sub); font-size: 12px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase;">
                Work
            </button>
            <button wire:click="$set('activeTab', 'budget')"
                    data-test="tab-budget"
                    class="border-b-2 transition"
                    style="padding: 12px 14px; background: transparent; border-color: {{ $activeTab === 'budget' ? 'var(--color-calv-teal)' : 'transparent' }}; color: {{ $activeTab === 'budget' ? 'var(--color-calv-teal)' : 'var(--color-calv-slate-65)' }}; font-family: var(--font-calv-sub); font-size: 12px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase;">
                Budget & Expenses
            </button>
        </div>
    </header>

    {{-- ────────────────────────────────────────────────────────────────
         Origin tie-back strip (Project Detail redesign §4.5).
         Surfaces the originating CommunicationLog and the carried-forward
         intake application requirements (read-only chips). Hidden if the
         project has no originating comm log (legacy or non-converted jobs).
         ──────────────────────────────────────────────────────────── --}}
    @if($originatingCommLog)
        <div class="px-8 py-2.5 flex items-center gap-4 flex-wrap"
             style="background: var(--color-calv-sand-15); border-bottom: 1px solid var(--color-calv-sand-35);">
            <span style="font-family: var(--font-calv-sub); font-size: 9.5px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: var(--color-calv-slate-65);">Carried from intake</span>
            <a href="{{ route('communications-log.management') }}" wire:navigate
               style="font-size: 12px; color: var(--color-calv-teal); text-decoration: underline; font-weight: 600;">
                {{ $originatingCommLog->log_number ?? ('COMM-' . str_pad($originatingCommLog->id, 5, '0', STR_PAD_LEFT)) }}
                · {{ ucfirst(str_replace('_', ' ', $originatingCommLog->type ?? 'intake')) }}
                · {{ $originatingCommLog->created_at?->format('M d, Y') }}
            </a>
            @if($originatingCommLog->intakeRequirements->isNotEmpty())
                <span class="block" style="width: 1px; height: 16px; background: var(--color-calv-sand-65);"></span>
                <div class="flex flex-wrap gap-1.5 items-center">
                    <span style="font-size: 11px; color: var(--color-calv-slate-65);">App. requirements:</span>
                    @foreach($originatingCommLog->intakeRequirements->sortBy(fn ($r) => $r->definition?->label) as $r)
                        @php
                            $met = in_array($r->status, \App\Models\IntakeRequirement::MET_STATUSES, true);
                            $waived = $r->status === \App\Models\IntakeRequirement::STATUS_WAIVED;
                            $statusDot = $waived
                                ? 'var(--color-calv-sand-65)'
                                : ($met ? 'var(--color-calv-sage)' : 'var(--color-calv-slate-65)');
                        @endphp
                        <span class="inline-flex items-center gap-1.5 rounded-full"
                              title="Status: {{ str_replace('_', ' ', $r->status) }}"
                              style="padding: 2px 8px; font-size: 11.5px; color: var(--color-calv-slate); background: #fff; border: 1px solid var(--color-calv-slate-15);">
                            <span class="rounded-full" style="width: 6px; height: 6px; background: {{ $statusDot }};"></span>
                            {{ $r->definition?->label ?? $r->source_label_snapshot ?? '—' }}
                        </span>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    <!-- Success/Error Messages -->
    @if (session()->has('success'))
        <div class="p-4 mx-8 mt-4 rounded" style="background: var(--color-calv-sage-15); border-left: 3px solid var(--color-calv-sage);">
            <p class="text-sm" style="color: #2F5A41;">{{ session('success') }}</p>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="p-4 mx-8 mt-4 rounded" style="background: var(--color-calv-red-15); border-left: 3px solid var(--color-calv-red);">
            <p class="text-sm" style="color: var(--color-calv-red);">{{ session('error') }}</p>
        </div>
    @endif


        <!-- Tab Content — edge-to-edge for two-pane Work; padded for Budget -->
        @if($activeTab === 'work' || $activeTab === 'requirements' || $activeTab === 'milestones')
            @php
                // Build the two milestone groups the redesign expects:
                //   GENERAL  — milestones with no budget tie (compliance + un-budgeted)
                //   BUDGET   — milestones that anchor a budget category
                $budgetByMilestoneId = $budgets->keyBy('milestone_id');
                $groupGeneral = $milestones->reject(fn ($m) => $budgetByMilestoneId->has($m->id))->values();
                $groupBudget  = $milestones->filter(fn ($m) => $budgetByMilestoneId->has($m->id))->values();

                // Map each milestone's requirements + blocked-by status for the row meta.
                $allReqs = $job->requirements ?? collect();
                $fundCodeMilestoneMap = collect();
                foreach ($job->fundCodes ?? [] as $fc) {
                    $fundCodeMilestoneMap[$fc->id] = $fc->pivot?->milestone_id;
                }
                $reqsFor = function ($milestoneId) use ($allReqs, $fundCodeMilestoneMap) {
                    $own = $allReqs->where('source_type', 'milestone')->where('source_id', $milestoneId);
                    $viaFundCode = $allReqs->where('source_type', 'fund_code')->filter(
                        fn ($r) => ($fundCodeMilestoneMap[$r->source_id] ?? null) === $milestoneId
                    );
                    return $own->concat($viaFundCode)->values();
                };
                $blockedByMap = collect();
                foreach ($milestones as $m) {
                    $blockedByMap[$m->id] = $m->dependencies->filter(fn ($d) => $d->status !== 'completed')->values();
                }

                // Build a tiny array of {id, name, status, lowercaseLabels} for Alpine's filter.
                // Reduces the per-row x-show JS to a cheap predicate.
                $milestoneSearchIndex = $milestones->map(function ($m) use ($reqsFor) {
                    $reqLabels = $reqsFor($m->id)->pluck('title')->map(fn ($t) => strtolower($t))->all();
                    return [
                        'id' => $m->id,
                        'name' => strtolower($m->name),
                        'labels' => $reqLabels,
                    ];
                })->keyBy('id')->all();
            @endphp

            <div x-data="{
                    query: '',
                    filter: 'all',
                    index: @js($milestoneSearchIndex),
                    matches(id, status) {
                        if (this.filter !== 'all' && status !== this.filter) return false;
                        if (!this.query) return true;
                        const q = this.query.toLowerCase();
                        const row = this.index[id];
                        if (!row) return false;
                        if (row.name.includes(q)) return true;
                        return row.labels.some(l => l.includes(q));
                    },
                 }"
                 class="grid"
                 style="grid-template-columns: 440px 1fr; min-height: 640px;">

                {{-- ─── LEFT PANE — searchable, filterable milestone list ─── --}}
                <div class="flex flex-col"
                     style="border-right: 1px solid var(--color-calv-slate-15); background: var(--color-calv-sand-15);">
                    {{-- Search + filter header --}}
                    <div style="padding: 14px 18px 12px; border-bottom: 1px solid var(--color-calv-slate-15);">
                        <div class="flex items-center gap-2 mb-2.5">
                            <h2 class="m-0"
                                style="font-family: var(--font-calv-sub); font-size: 12px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--color-calv-slate);">
                                Milestones
                            </h2>
                            <span class="flex-1"></span>
                            @if($this->can('projects.manage'))
                                <button wire:click="showCreateMilestone"
                                        data-test="add-milestone-btn"
                                        class="inline-flex items-center gap-1 rounded text-white"
                                        style="padding: 5px 11px; background: var(--color-calv-red); font-family: var(--font-calv-sub); font-size: 10.5px; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase;">
                                    <svg width="11" height="11" viewBox="0 0 16 16" fill="none">
                                        <path d="M8 3v10M3 8h10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                    </svg>
                                    Milestone
                                </button>
                            @endif
                        </div>
                        {{-- Search --}}
                        <div class="relative mb-2">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2" style="color: var(--color-calv-slate-65);">
                                <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                                    <circle cx="7" cy="7" r="4.5" stroke="currentColor" stroke-width="1.4"/>
                                    <path d="M10.5 10.5L14 14" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                                </svg>
                            </span>
                            <input x-model="query"
                                   placeholder="Search milestones & requirements…"
                                   class="w-full rounded-md text-sm"
                                   style="padding: 7px 10px 7px 32px; border: 1px solid var(--color-calv-slate-35); font-family: var(--font-calv-body); color: var(--color-calv-slate); background: #fff;">
                        </div>
                        {{-- Filter pills --}}
                        <div class="flex gap-1.5">
                            @foreach(['all' => 'All', 'not_started' => 'Pending', 'in_progress' => 'In progress', 'completed' => 'Completed'] as $key => $label)
                                <button @click="filter = '{{ $key }}'"
                                        :class="filter === '{{ $key }}' ? 'is-active' : ''"
                                        x-bind:style="filter === '{{ $key }}'
                                            ? 'background: var(--color-calv-slate); color: #fff; border-color: var(--color-calv-slate);'
                                            : 'background: #fff; color: var(--color-calv-slate-65); border-color: var(--color-calv-slate-35);'"
                                        class="rounded-full cursor-pointer"
                                        style="padding: 4px 9px; font-family: var(--font-calv-sub); font-size: 9.5px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; border-width: 1px; border-style: solid;">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Groups + rows --}}
                    <div class="flex-1 overflow-y-auto" style="padding: 8px 0;">
                        @php
                            $renderGroup = function ($kind, $title, $items) use ($reqsFor, $blockedByMap, $msStatusTone, $currentMilestone) {
                                return compact('kind', 'title', 'items');
                            };
                        @endphp

                        @foreach([
                            ['kind' => 'GENERAL', 'title' => 'Compliance & un-budgeted work', 'items' => $groupGeneral],
                            ['kind' => 'BUDGET',  'title' => 'Scopes of work',                'items' => $groupBudget],
                        ] as $group)
                            @if($group['items']->isNotEmpty())
                                @php
                                    $budgeted = $group['kind'] === 'BUDGET';
                                    // Hide the whole group if no items match the active filter (Alpine-driven).
                                    $groupItemIds = $group['items']->pluck('id')->map(fn ($i) => (int) $i)->all();
                                @endphp
                                <div x-show="@js($groupItemIds).some(id => matches(id, index[id] ? Object.keys(index).find(k => +k === id) && (@js($group['items']->mapWithKeys(fn ($m) => [$m->id => $m->status])->all())[id]) : null))" style="margin-bottom: 8px;" data-test="milestone-group milestone-group-{{ strtolower($group['kind']) }}">
                                    <div class="flex items-center gap-2" style="padding: 8px 18px 6px;">
                                        <span class="rounded"
                                              style="padding: 2px 7px; font-family: var(--font-calv-sub); font-size: 9px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; background: {{ $budgeted ? 'var(--color-calv-teal-15)' : 'var(--color-calv-slate-15)' }}; color: {{ $budgeted ? 'var(--color-calv-teal)' : 'var(--color-calv-slate-65)' }};">
                                            {{ $group['kind'] }}
                                        </span>
                                        <span style="font-family: var(--font-calv-sub); font-size: 11px; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; color: var(--color-calv-slate);">
                                            {{ $group['title'] }}
                                        </span>
                                    </div>
                                    @foreach($group['items'] as $m)
                                        @php
                                            $reqs = $reqsFor($m->id);
                                            $reqMet = $reqs->where('status', 'complete')->count();
                                            $reqTotal = $reqs->count();
                                            $isBlocked = ($blockedByMap[$m->id] ?? collect())->isNotEmpty() && $m->status !== 'completed';
                                            $effectiveStatus = $isBlocked ? 'blocked' : $m->status;
                                            $tone = $msStatusTone[$effectiveStatus] ?? $msStatusTone['pending'];
                                            $isSelected = $currentMilestone && $currentMilestone->id === $m->id;
                                            $msBudget = $budgetByMilestoneId->get($m->id);
                                        @endphp
                                        <button wire:click="selectMilestone({{ $m->id }})"
                                                x-show="matches({{ $m->id }}, '{{ $m->status }}')"
                                                data-test="milestone-row"
                                                data-milestone-id="{{ $m->id }}"
                                                class="block w-full text-left cursor-pointer relative transition-colors"
                                                style="padding: 11px 18px; border-left: 3px solid {{ $isSelected ? 'var(--color-calv-teal)' : 'transparent' }}; background: {{ $isSelected ? '#fff' : 'transparent' }}; box-shadow: {{ $isSelected ? 'var(--shadow-calv-sm)' : 'none' }};">
                                            <div class="flex items-center gap-2 mb-0.5">
                                                <span class="flex-1 min-w-0 truncate"
                                                      style="font-family: var(--font-calv-body); font-size: 14px; font-weight: 600; color: var(--color-calv-slate);">
                                                    {{ $m->name }}
                                                </span>
                                                <span class="inline-flex items-center gap-1 rounded-full whitespace-nowrap"
                                                      style="padding: 2px 7px; font-family: var(--font-calv-sub); font-size: 9.5px; font-weight: 700; letter-spacing: 0.07em; text-transform: uppercase; background: {{ $tone['bg'] }}; color: {{ $tone['fg'] }};">
                                                    <span class="rounded-full" style="width: 5px; height: 5px; background: {{ $tone['dot'] }};"></span>
                                                    {{ $tone['label'] }}
                                                </span>
                                            </div>
                                            <div class="flex items-center gap-2" style="font-size: 11.5px; color: var(--color-calv-slate-65);">
                                                @if($m->target_date)
                                                    <span>Target {{ $m->target_date->format('M d, Y') }}</span>
                                                    <span>·</span>
                                                @endif
                                                <span style="color: {{ $reqTotal > 0 && $reqMet === $reqTotal ? 'var(--color-calv-sage)' : 'var(--color-calv-slate-65)' }};">
                                                    {{ $reqMet }}/{{ $reqTotal }} reqs
                                                </span>
                                                @if($msBudget)
                                                    <span>·</span>
                                                    <span style="font-family: ui-monospace, Menlo, monospace;">
                                                        ${{ number_format((float) $msBudget->spent_amount, 0) }}/${{ number_format((float) $msBudget->budgeted_amount, 0) }}
                                                    </span>
                                                @endif
                                                @if($isBlocked)
                                                    <span class="ml-auto inline-flex items-center gap-0.5"
                                                          style="color: var(--color-calv-red);"
                                                          title="Blocked by {{ ($blockedByMap[$m->id])->pluck('name')->join(', ') }}">
                                                        <svg width="11" height="11" viewBox="0 0 16 16" fill="none">
                                                            <path d="M5 7V5a3 3 0 0 1 6 0v2M4 7h8v6H4z" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                                                        </svg>
                                                        blocked
                                                    </span>
                                                @endif
                                            </div>
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        @endforeach

                        @if($milestones->isEmpty())
                            <div class="text-center" style="padding: 40px 18px;">
                                <p style="font-size: 13px; color: var(--color-calv-slate-65);">No milestones yet — Phase 2 defaults seed on conversion.</p>
                                @if($this->can('projects.manage'))
                                    <button wire:click="showCreateMilestone"
                                            class="mt-3 rounded text-white"
                                            style="padding: 6px 12px; background: var(--color-calv-red); font-family: var(--font-calv-sub); font-size: 11px; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase;">
                                        + Create first milestone
                                    </button>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                {{-- ─── RIGHT PANE — milestone detail ─── --}}
                <div class="overflow-y-auto" style="background: #fff;">
                    @if($currentMilestone)
                        @include('livewire.partials.project-milestone-detail', [
                            'm' => $currentMilestone,
                            'reqs' => $reqsFor($currentMilestone->id),
                            'blockedBy' => $blockedByMap[$currentMilestone->id] ?? collect(),
                            'msStatusTone' => $msStatusTone,
                            'budget' => $budgetByMilestoneId->get($currentMilestone->id),
                        ])
                    @else
                        <div class="text-center" style="padding: 80px 32px;">
                            <p style="font-size: 13px; color: var(--color-calv-slate-65);">
                                @if($milestones->isNotEmpty())
                                    Select a milestone on the left to see its gating requirements and lifecycle actions.
                                @else
                                    Add a milestone to begin tracking work on this project.
                                @endif
                            </p>
                        </div>
                    @endif
                </div>
            </div>

        @else
            {{-- Project Detail redesign §4.4 — Budget & Expenses tab.
                 Three summary cards on top, per-scope burn bars middle, expense
                 ledger bottom. CALV-styled throughout; replaces the legacy
                 over-budget banner + categories-table + expenses-table stack. --}}
            <div class="p-6" data-test="budget-tab">
                @php
                    $overBudgetCategories = $budgets->filter(fn ($b) => $b->is_over_budget);
                    $remaining = $totalBudget - $totalSpent;
                @endphp

                <div style="max-width: 1100px;">
                    {{-- Persistent over-budget banner (preserves P-23 Step 7 audit contract). --}}
                    @if($overBudgetCategories->isNotEmpty())
                        <div class="rounded-md mb-5" role="alert" data-over-budget-banner
                             style="background: var(--color-calv-red-15); border-left: 3px solid var(--color-calv-red); padding: 12px 16px;">
                            <div class="flex items-start gap-3">
                                <svg class="shrink-0 mt-0.5" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="color: var(--color-calv-red);">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4a2 2 0 00-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/>
                                </svg>
                                <div class="flex-1">
                                    <div style="font-family: var(--font-calv-sub); font-size: 10.5px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--color-calv-red); margin-bottom: 4px;">Budget exceeded</div>
                                    <p style="font-size: 13.5px; color: var(--color-calv-slate); margin: 0;">
                                        {{ $overBudgetCategories->count() }} {{ Str::plural('scope', $overBudgetCategories->count()) }} on this project {{ $overBudgetCategories->count() === 1 ? 'is' : 'are' }} over budget. Reduce or reassign expenses, or modify the budget cap with a comment.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Three summary cards (Total / Spent / Remaining) --}}
                    <div class="grid gap-3.5 mb-6" style="grid-template-columns: repeat(3, 1fr);">
                        <div class="rounded-md"
                             style="border: 1px solid var(--color-calv-slate-15); border-top: 3px solid var(--color-calv-slate); padding: 14px 16px;">
                            <div style="font-family: var(--font-calv-sub); font-size: 10px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: var(--color-calv-slate-65); margin-bottom: 6px;">Total budget</div>
                            <div style="font-family: var(--font-calv-h1); font-size: 28px; letter-spacing: 0.01em; color: var(--color-calv-slate); line-height: 1;">${{ number_format($totalBudget, 0) }}</div>
                        </div>
                        <div class="rounded-md"
                             style="border: 1px solid var(--color-calv-slate-15); border-top: 3px solid var(--color-calv-sage); padding: 14px 16px;">
                            <div style="font-family: var(--font-calv-sub); font-size: 10px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: var(--color-calv-slate-65); margin-bottom: 6px;">Spent to date</div>
                            <div style="font-family: var(--font-calv-h1); font-size: 28px; letter-spacing: 0.01em; color: var(--color-calv-slate); line-height: 1;">${{ number_format($totalSpent, 0) }}</div>
                        </div>
                        <div class="rounded-md"
                             style="border: 1px solid var(--color-calv-slate-15); border-top: 3px solid {{ $remaining < 0 ? 'var(--color-calv-red)' : 'var(--color-calv-teal)' }}; padding: 14px 16px;">
                            <div style="font-family: var(--font-calv-sub); font-size: 10px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: var(--color-calv-slate-65); margin-bottom: 6px;">Remaining</div>
                            <div style="font-family: var(--font-calv-h1); font-size: 28px; letter-spacing: 0.01em; color: {{ $remaining < 0 ? 'var(--color-calv-red)' : 'var(--color-calv-slate)' }}; line-height: 1;">${{ number_format($remaining, 0) }}</div>
                        </div>
                    </div>

                    {{-- Scopes of work — per-scope burn bars --}}
                    <section style="margin-bottom: 28px;">
                        <div class="flex items-center mb-3">
                            <h3 class="m-0" style="font-family: var(--font-calv-sub); font-size: 12px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--color-calv-slate);">
                                Scopes of work
                            </h3>
                            <span class="flex-1"></span>
                            @if($this->can('projects.manage'))
                                <button wire:click="showCreateBudget"
                                        class="inline-flex items-center gap-1 rounded text-white"
                                        style="padding: 5px 11px; background: var(--color-calv-red); font-family: var(--font-calv-sub); font-size: 10.5px; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase;">
                                    + Budget category
                                </button>
                            @endif
                        </div>

                        @if($budgets->isEmpty())
                            <div class="text-center rounded-md"
                                 style="padding: 32px 18px; background: var(--color-calv-sand-15); border: 1px dashed var(--color-calv-slate-35);">
                                <p style="font-size: 13px; color: var(--color-calv-slate-65);">No budget categories yet — add one to start tracking spend per scope.</p>
                            </div>
                        @else
                            <div class="flex flex-col gap-2.5">
                                @foreach($budgets as $b)
                                    @php
                                        $pct = (float) $b->budgeted_amount > 0
                                            ? (int) round((float) $b->spent_amount / (float) $b->budgeted_amount * 100)
                                            : 0;
                                        // CALV bar coloring: under-80 = teal, 80-99 = amber (soft warning,
                                        // preserves the P-23 Step 7 contract), exactly 100 = sage, over = red.
                                        if ((float) $b->spent_amount > (float) $b->budgeted_amount) {
                                            $barColor = 'var(--color-calv-red)';
                                            $budgetStatus = 'over';
                                        } elseif ($pct >= 100) {
                                            $barColor = 'var(--color-calv-sage)';
                                            $budgetStatus = 'met';
                                        } elseif ($pct >= 80) {
                                            $barColor = 'var(--color-calv-amber)';
                                            $budgetStatus = 'warning';
                                        } else {
                                            $barColor = 'var(--color-calv-teal)';
                                            $budgetStatus = 'on-track';
                                        }
                                        $scopeMilestone = $b->milestone_id ? $milestones->firstWhere('id', $b->milestone_id) : null;
                                        $scopeStatus = $scopeMilestone?->status ?? 'pending';
                                        $scopeTone = $msStatusTone[$scopeStatus] ?? $msStatusTone['pending'];
                                        // Fund-code badge text falls through to the legacy contract.grant_code
                                        // (preserves Phase 1 / P-22 fund-code prominence) and finally to "—".
                                        $fundLabel = $b->fundCode?->code ?? $b->contract?->grant_code ?? null;
                                        $fundTitle = $b->fundCode?->description ?? $b->contract?->title ?? '';
                                    @endphp
                                    <div class="rounded-md"
                                         style="border: 1px solid var(--color-calv-slate-15); padding: 14px 16px;"
                                         data-test="budget-scope-bar"
                                         data-budget-status="{{ $budgetStatus }}"
                                         data-budget-pct="{{ $pct }}">
                                        <div class="flex items-center gap-2.5 mb-2 flex-wrap">
                                            <span style="font-size: 14px; font-weight: 600; color: var(--color-calv-slate);">{{ $b->category }}</span>
                                            @if($fundLabel)
                                                {{-- Tailwind classes (text-sm font-semibold) kept for the
                                                     legacy Phase 1 prominence regression. CALV-mono spacing
                                                     stays the visual primitive. --}}
                                                <span class="rounded text-sm font-semibold" title="{{ $fundTitle }}"
                                                      data-test="budget-fund-code"
                                                      style="padding: 2px 7px; font-family: ui-monospace, Menlo, monospace; background: var(--color-calv-slate-15); color: var(--color-calv-slate);">
                                                    {{ $fundLabel }}
                                                </span>
                                            @else
                                                <span style="font-size: 13px; color: var(--color-calv-slate-35);">—</span>
                                            @endif
                                            @if($scopeMilestone)
                                                <span class="inline-flex items-center gap-1 rounded-full"
                                                      style="padding: 2px 7px; font-family: var(--font-calv-sub); font-size: 9.5px; font-weight: 700; letter-spacing: 0.07em; text-transform: uppercase; background: {{ $scopeTone['bg'] }}; color: {{ $scopeTone['fg'] }};">
                                                    <span class="rounded-full" style="width: 5px; height: 5px; background: {{ $scopeTone['dot'] }};"></span>
                                                    {{ $scopeTone['label'] }}
                                                </span>
                                                <span style="font-size: 11px; color: var(--color-calv-slate-65);">→ {{ $scopeMilestone->name }}</span>
                                            @endif
                                            <span class="flex-1"></span>
                                            <span style="font-family: ui-monospace, Menlo, monospace; font-size: 13px; color: var(--color-calv-slate);">
                                                ${{ number_format((float) $b->spent_amount, 0) }}
                                                <span style="color: var(--color-calv-slate-65);">/ ${{ number_format((float) $b->budgeted_amount, 0) }}</span>
                                                <span style="color: var(--color-calv-slate-65); font-size: 11px;">· {{ $pct }}%</span>
                                            </span>
                                            @if($b->modifications_count > 0)
                                                <button wire:click="showBudgetLedger({{ $b->id }})"
                                                        data-budget-modified-indicator
                                                        title="View modification history"
                                                        style="font-family: var(--font-calv-sub); font-size: 9.5px; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; color: var(--color-calv-amber); background: transparent; border: none; cursor: pointer;">
                                                    Modified ({{ $b->modifications_count }})
                                                </button>
                                            @endif
                                            @if($this->can('projects.manage'))
                                                <button wire:click="editBudget({{ $b->id }})"
                                                        aria-label="Edit"
                                                        style="width: 24px; height: 24px; background: transparent; border: none; color: var(--color-calv-slate-65); cursor: pointer; display: inline-flex; align-items: center; justify-content: center;">
                                                    <svg width="13" height="13" viewBox="0 0 16 16" fill="none">
                                                        <path d="M11 2.5l2.5 2.5L5 13.5H2.5V11L11 2.5z" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                                                    </svg>
                                                </button>
                                                <button wire:click="deleteBudget({{ $b->id }})"
                                                        wire:confirm="Delete this budget category?"
                                                        aria-label="Delete"
                                                        style="width: 24px; height: 24px; background: transparent; border: none; color: var(--color-calv-slate-35); cursor: pointer; display: inline-flex; align-items: center; justify-content: center;">
                                                    <svg width="13" height="13" viewBox="0 0 16 16" fill="none">
                                                        <path d="M3 4.5h10M6.5 4V3h3v1M5 4.5l.5 8h5l.5-8" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                                                    </svg>
                                                </button>
                                            @endif
                                        </div>
                                        <div class="rounded-full overflow-hidden" style="height: 6px; background: var(--color-calv-slate-15);">
                                            <div class="h-full" style="width: {{ min($pct, 100) }}%; background: {{ $barColor }};"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </section>

                    {{-- Expense ledger --}}
                    <section data-test="expense-ledger">
                        <div class="flex items-center mb-3">
                            <h3 class="m-0" style="font-family: var(--font-calv-sub); font-size: 12px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--color-calv-slate);">
                                Expenses
                            </h3>
                            <span class="flex-1"></span>
                            @if($this->can('projects.manage') && $budgets->isNotEmpty())
                                <button wire:click="showCreateExpense"
                                        class="inline-flex items-center gap-1 rounded text-white"
                                        style="padding: 5px 11px; background: var(--color-calv-red); font-family: var(--font-calv-sub); font-size: 10.5px; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase;">
                                    + Log expense
                                </button>
                            @endif
                        </div>

                        @if($job->expenses->isEmpty())
                            <div class="text-center rounded-md"
                                 style="padding: 28px 18px; background: var(--color-calv-sand-15); border: 1px dashed var(--color-calv-slate-35);">
                                <p style="font-size: 13px; color: var(--color-calv-slate-65);">No expenses logged yet.</p>
                            </div>
                        @else
                            <div class="rounded-md overflow-hidden" style="border: 1px solid var(--color-calv-slate-15);">
                                <table class="w-full" style="border-collapse: collapse; font-size: 13px;">
                                    <thead>
                                        <tr style="background: var(--color-calv-slate); color: #fff;">
                                            @foreach(['Date', 'Scope', 'Fund', 'Vendor', 'Memo', 'Amount'] as $h)
                                                <th style="font-family: var(--font-calv-sub); font-size: 10px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; text-align: {{ $h === 'Amount' ? 'right' : 'left' }}; padding: 9px 12px;">
                                                    {{ $h }}
                                                </th>
                                            @endforeach
                                            @if($this->can('projects.manage'))
                                                <th style="padding: 9px 12px; text-align: right;"></th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($job->expenses->sortByDesc('expense_date') as $expense)
                                            <tr>
                                                <td style="padding: 9px 12px; border-top: 1px solid var(--color-calv-slate-15); color: var(--color-calv-slate);">{{ $expense->expense_date->format('M d, Y') }}</td>
                                                <td style="padding: 9px 12px; border-top: 1px solid var(--color-calv-slate-15); color: var(--color-calv-slate);">{{ $expense->category }}</td>
                                                <td style="padding: 9px 12px; border-top: 1px solid var(--color-calv-slate-15); font-family: ui-monospace, Menlo, monospace; font-size: 11.5px; color: var(--color-calv-slate);">
                                                    {{ $expense->contract?->grant_code ?? '—' }}
                                                </td>
                                                <td style="padding: 9px 12px; border-top: 1px solid var(--color-calv-slate-15); color: var(--color-calv-slate);">{{ $expense->vendor ?? '—' }}</td>
                                                <td style="padding: 9px 12px; border-top: 1px solid var(--color-calv-slate-15); color: var(--color-calv-slate-65);">{{ $expense->description ?: '—' }}</td>
                                                <td style="padding: 9px 12px; border-top: 1px solid var(--color-calv-slate-15); text-align: right; font-family: ui-monospace, Menlo, monospace; font-weight: 600; color: {{ $expense->is_credit ? 'var(--color-calv-sage)' : 'var(--color-calv-slate)' }};">
                                                    {{ $expense->formatted_amount ?? '$' . number_format((float) $expense->amount, 2) }}
                                                </td>
                                                @if($this->can('projects.manage'))
                                                    <td style="padding: 9px 12px; border-top: 1px solid var(--color-calv-slate-15); text-align: right;">
                                                        <button wire:click="deleteExpense({{ $expense->id }})"
                                                                wire:confirm="Delete this expense?"
                                                                aria-label="Delete expense"
                                                                style="color: var(--color-calv-red); background: transparent; border: none; cursor: pointer;">
                                                            <svg width="13" height="13" viewBox="0 0 16 16" fill="none">
                                                                <path d="M3 4.5h10M6.5 4V3h3v1M5 4.5l.5 8h5l.5-8" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                                                            </svg>
                                                        </button>
                                                    </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </section>
                </div>
            </div>
        @endif

    {{-- Milestone Form Modal — restructured for Phase 3 of the milestone redesign.
         Was a 475px stack-of-everything column; now a two-column ~640px modal
         organized into three labelled sections:
           IDENTITY        — name, target date, description, color
           WHAT IT TRACKS  — fund codes served, budget items, Phase 2 atom multi-select
           WHEN IT CAN START — depends-on multi-select with explainer

         All wire:model bindings unchanged so the saveMilestone() pipeline on the
         component is unaffected. For a promoted (default-seeded) milestone the
         Phase 2 atoms multi-select renders read-only with the canonical atom
         visible. --}}
    @if($showMilestoneForm)
        @php
            $isPromoted = $editingMilestone
                && $job->milestones->firstWhere('id', $editingMilestone)?->isPromoted();
            $editing = $editingMilestone;
        @endphp
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50"
             wire:click.self="$set('showMilestoneForm', false)">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl w-full max-w-3xl mx-4 max-h-[90vh] overflow-y-auto">
                {{-- Header --}}
                <div class="px-6 py-4 border-b border-gray-200 dark:border-zinc-700 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $editing ? 'Edit milestone' : 'Create milestone' }}
                        </h3>
                        @if($isPromoted)
                            <p class="text-xs text-calv-teal mt-0.5">
                                ★ Default Phase 2 milestone — the canonical requirement is locked. Other fields are editable.
                            </p>
                        @endif
                    </div>
                    <button type="button" wire:click="$set('showMilestoneForm', false)"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="px-6 py-5 space-y-6">
                    {{-- ── IDENTITY ── --}}
                    <section data-test="milestone-form-identity">
                        <header class="flex items-baseline gap-2 mb-3 pb-2 border-b border-gray-100 dark:border-zinc-700">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] uppercase tracking-wider font-bold bg-calv-slate-15 text-calv-slate">Identity</span>
                            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-200">What is this milestone?</h4>
                        </header>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Name *</label>
                                <input type="text" wire:model="milestone_name"
                                       class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:border-indigo-500 focus:ring-indigo-500">
                                @error('milestone_name') <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                                <textarea wire:model="milestone_description" rows="2"
                                          class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Target date</label>
                                <input type="date" wire:model="milestone_target_date"
                                       class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Color</label>
                                <input type="color" wire:model="milestone_color"
                                       class="w-full h-9 border border-gray-300 dark:border-zinc-600 rounded-md cursor-pointer">
                            </div>
                        </div>
                    </section>

                    {{-- ── WHAT IT TRACKS ── --}}
                    @if(($projectFundCodes ?? collect())->isNotEmpty()
                        || ($projectBudgets ?? collect())->isNotEmpty()
                        || ($phase2Atoms ?? collect())->isNotEmpty())
                        <section data-test="milestone-form-tracks">
                            <header class="flex items-baseline gap-2 mb-3 pb-2 border-b border-gray-100 dark:border-zinc-700">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] uppercase tracking-wider font-bold bg-calv-teal-15 text-calv-teal">Tracks</span>
                                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-200">What does it cover?</h4>
                            </header>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @if(($projectFundCodes ?? collect())->isNotEmpty())
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Fund codes served</label>
                                        <select wire:model="milestone_fund_code_ids" multiple size="4"
                                                data-test="milestone-fund-codes-select"
                                                class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                                            @foreach($projectFundCodes->sortBy('code') as $fc)
                                                <option value="{{ $fc->id }}">{{ $fc->code }} — {{ $fc->description }}</option>
                                            @endforeach
                                        </select>
                                        <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                                            One milestone per fund code. Selected codes' Phase 2 reqs render here.
                                        </p>
                                    </div>
                                @endif

                                @if(($projectBudgets ?? collect())->isNotEmpty())
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Budget items</label>
                                        <select wire:model="milestone_budget_ids" multiple size="4"
                                                data-test="milestone-budgets-select"
                                                class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                                            @foreach($projectBudgets->sortBy('category') as $b)
                                                <option value="{{ $b->id }}">{{ $b->category }} (${{ number_format((float) $b->budgeted_amount, 0) }})</option>
                                            @endforeach
                                        </select>
                                        <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                                            Optional. Compliance milestones can have no budget.
                                        </p>
                                    </div>
                                @endif

                                @if(($phase2Atoms ?? collect())->isNotEmpty())
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Gating Phase 2 requirements
                                            @if($isPromoted)
                                                <span class="ml-2 text-calv-teal">(locked — duplicate this milestone to customize)</span>
                                            @endif
                                        </label>
                                        <select wire:model="milestone_requirement_definition_ids" multiple size="5"
                                                @disabled($isPromoted)
                                                data-test="milestone-requirement-atoms-select"
                                                class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md disabled:opacity-60 disabled:cursor-not-allowed">
                                            @foreach($phase2Atoms as $atom)
                                                <option value="{{ $atom->id }}">{{ $atom->label ?? $atom->key }} <span class="text-gray-500">({{ $atom->kind }})</span></option>
                                            @endforeach
                                        </select>
                                        <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                                            Hold Ctrl/Cmd to multi-select. Each pick creates a gating requirement on this milestone. Deselecting later preserves existing progress — only the link is cleared.
                                        </p>
                                    </div>
                                @endif
                            </div>
                        </section>
                    @endif

                    {{-- ── WHEN IT CAN START ── --}}
                    @if($job->milestones->where('id', '!=', $editingMilestone)->count() > 0)
                        <section data-test="milestone-form-when">
                            <header class="flex items-baseline gap-2 mb-3 pb-2 border-b border-gray-100 dark:border-zinc-700">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] uppercase tracking-wider font-bold bg-calv-amber-15 text-[#8A6410]">When</span>
                                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-200">When can it start?</h4>
                            </header>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Depends on</label>
                                <select wire:model="milestone_dependency_ids" multiple size="4"
                                        class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                                    @foreach($job->milestones->where('id', '!=', $editingMilestone)->sortBy('sequence_order') as $other)
                                        <option value="{{ $other->id }}">{{ $other->name }}</option>
                                    @endforeach
                                </select>
                                <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                                    Leave empty for an independent milestone. The Work tab shows a 🔒 badge while any selected dependency is still incomplete.
                                </p>
                            </div>
                        </section>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="px-6 py-4 border-t border-gray-200 dark:border-zinc-700 flex justify-end gap-2 bg-gray-50 dark:bg-zinc-900/30">
                    <button wire:click="$set('showMilestoneForm', false)"
                            class="px-4 py-2 text-sm bg-gray-200 dark:bg-zinc-600 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-500">
                        Cancel
                    </button>
                    <button wire:click="saveMilestone"
                            class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-md hover:bg-indigo-700 font-medium">
                        {{ $editing ? 'Save milestone' : 'Create milestone' }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Task Form Modal -->
    @if($showTaskForm)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50" wire:click.self="$set('showTaskForm', false)">
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    {{ $editingTask ? 'Edit Task' : 'Create Task' }}
                </h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Task Name *</label>
                        <input type="text" wire:model="task_name"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                        @error('task_name') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                        <textarea wire:model="task_description" rows="2"
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Assign To</label>
                        <select wire:model="task_assigned_to"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                            <option value="">Unassigned</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Deadline</label>
                        <input type="date" wire:model="task_deadline"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                    </div>
                    <div>
                        <label class="flex items-center space-x-2">
                            <input type="checkbox" wire:model="task_requires_approval"
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Requires Approval</span>
                        </label>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button wire:click="$set('showTaskForm', false)"
                            class="px-4 py-2 bg-gray-200 dark:bg-zinc-600 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-500">
                        Cancel
                    </button>
                    <button wire:click="saveTask"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                        Save
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Document Form Modal -->
    @if($showDocumentForm)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50" wire:click.self="$set('showDocumentForm', false)">
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Add Required Document</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Document Type *</label>
                        <input type="text" wire:model="document_type" placeholder="e.g., Building Permit, Invoice, Photo"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                        @error('document_type') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                        <textarea wire:model="document_description" rows="2"
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Assign To</label>
                        <select wire:model="document_assigned_to"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                            <option value="">Unassigned</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">PDF Form Template (Optional)</label>
                        <select wire:model="document_pdf_template_id"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                            <option value="">No Template (Manual Upload Only)</option>
                            @foreach($pdfTemplates as $template)
                                <option value="{{ $template->id }}">{{ $template->name }} ({{ $template->field_count }} fields)</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Select a PDF form template to auto-fill with project data
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Upload Files (Optional)</label>
                        <input type="file" wire:model="documentFiles" multiple
                               class="w-full text-sm text-gray-500 dark:text-gray-400
                                      file:mr-4 file:py-2 file:px-4
                                      file:rounded-md file:border-0
                                      file:text-sm file:font-semibold
                                      file:bg-indigo-50 file:text-indigo-700
                                      hover:file:bg-indigo-100">
                    </div>
                    <div>
                        <label class="flex items-center space-x-2">
                            <input type="checkbox" wire:model="document_requires_approval"
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Requires Approval</span>
                        </label>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button wire:click="$set('showDocumentForm', false)"
                            class="px-4 py-2 bg-gray-200 dark:bg-zinc-600 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-500">
                        Cancel
                    </button>
                    <button wire:click="saveDocument"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                        Save
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if($showRequirementForm)
        {{-- P-18: Requirement Form Modal (create/edit) --}}
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50" wire:click.self="$set('showRequirementForm', false)">
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    {{ $editingRequirementId ? 'Edit Requirement' : 'Add Requirement' }}
                </h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                        <select wire:model="requirement_type"
                                class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="task">Task — closed with a click + optional note</option>
                            <option value="document">Document — closed with a file upload OR an override reason</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Title</label>
                        <input type="text" wire:model="requirement_title"
                               class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                        @error('requirement_title') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description (optional)</label>
                        <textarea wire:model="requirement_description" rows="2"
                                  class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Owner</label>
                        <select wire:model="requirement_owner_id"
                                class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Unassigned</option>
                            @foreach(\App\Models\User::orderBy('name')->get() as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Milestone gate (optional)</label>
                        <input type="text" wire:model="requirement_milestone_gate_text"
                               placeholder="e.g., must be done before construction starts"
                               class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button wire:click="$set('showRequirementForm', false)"
                            class="px-4 py-2 bg-gray-200 dark:bg-zinc-600 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-500">
                        Cancel
                    </button>
                    <button wire:click="saveRequirement"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                        Save
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if($showDeleteMilestoneModal)
        {{-- P-18: Milestone Delete Confirmation (keep or delete generated requirements) --}}
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50" wire:click.self="closeDeleteMilestoneModal">
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Delete milestone</h3>
                <p class="text-sm text-gray-700 dark:text-gray-300 mb-4">
                    This milestone has <strong>{{ $deleteMilestoneRequirementCount }}</strong> requirement{{ $deleteMilestoneRequirementCount === 1 ? '' : 's' }} attached.
                    Keep them on the project (they'll appear as orphaned source=milestone rows for audit), or delete them along with the milestone?
                </p>
                <div class="mt-6 flex justify-end space-x-3">
                    <button wire:click="closeDeleteMilestoneModal"
                            class="px-4 py-2 bg-gray-200 dark:bg-zinc-600 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-500">
                        Cancel
                    </button>
                    <button wire:click="confirmDeleteMilestone(false)"
                            class="px-4 py-2 bg-amber-600 text-white rounded-md hover:bg-amber-700">
                        Keep requirements
                    </button>
                    <button wire:click="confirmDeleteMilestone(true)"
                            class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                        Delete everything
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if($showDetachFundCodeModal)
        {{-- P-18: Fund-code Detach Confirmation (keep or delete generated requirements) --}}
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50" wire:click.self="closeDetachFundCodeModal">
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Detach fund code</h3>
                <p class="text-sm text-gray-700 dark:text-gray-300 mb-4">
                    This fund code generated <strong>{{ $detachConfirmRequirementCount }}</strong> requirement{{ $detachConfirmRequirementCount === 1 ? '' : 's' }} on this project.
                    Keep them as-is for audit, or delete them along with the fund-code link?
                </p>
                <div class="mt-6 flex justify-end space-x-3">
                    <button wire:click="closeDetachFundCodeModal"
                            class="px-4 py-2 bg-gray-200 dark:bg-zinc-600 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-500">
                        Cancel
                    </button>
                    <button wire:click="confirmDetachFundCode(false)"
                            class="px-4 py-2 bg-amber-600 text-white rounded-md hover:bg-amber-700">
                        Keep requirements
                    </button>
                    <button wire:click="confirmDetachFundCode(true)"
                            class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                        Delete requirements
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if($showCompleteRequirementModal)
        {{-- P-18: Document Completion Modal (upload OR override) --}}
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50" wire:click.self="closeCompletionModal">
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-lg w-full mx-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">Mark Requirement Complete</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                    Either attach one or more files <strong>or</strong> provide an override reason.
                    Both fields can be filled to attach a file with explanatory context.
                </p>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Files</label>
                        <input type="file" wire:model="completionFiles" multiple
                               class="w-full text-sm text-gray-700 dark:text-gray-300">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Override reason (only if no file)</label>
                        <textarea wire:model="completion_override_reason" rows="2"
                                  placeholder="e.g., hard copy on file in Kaitlyn drawer; no scan available."
                                  class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Completion note (optional)</label>
                        <textarea wire:model="completion_note" rows="2"
                                  class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button wire:click="closeCompletionModal"
                            class="px-4 py-2 bg-gray-200 dark:bg-zinc-600 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-500">
                        Cancel
                    </button>
                    <button wire:click="saveRequirementCompletion"
                            class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                        Mark Complete
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Budget Form Modal -->
    @if($showBudgetForm)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50" wire:click.self="$set('showBudgetForm', false)">
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    {{ $editingBudget ? 'Edit Budget Category' : 'Add Budget Category' }}
                </h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category Name *</label>
                        <input type="text" wire:model="budget_category" placeholder="e.g., Labor, Materials, Permits"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                        @error('budget_category') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Budgeted Amount *</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 dark:text-gray-400">$</span>
                            <input type="number" step="0.01" wire:model="budget_amount"
                                   class="w-full pl-7 px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                        </div>
                        @error('budget_amount') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                    {{-- P-2.5: Fund Code dropdown — required. Options scoped to
                         the project's assigned fund codes (the
                         fund_code_housing_job pivot). The legacy Contract
                         dropdown that lived here was hidden in P-2.5 per
                         David's request; project_budgets.contract_id and the
                         belongsTo(Contract) relation remain in the schema for
                         legacy contract-bearing rows. --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Fund Code <span class="text-red-500">*</span>
                        </label>
                        @if($job->fundCodes->isEmpty())
                            <div class="px-3 py-2 text-sm text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-900/40 rounded-md">
                                No fund codes assigned to this project yet. Attach at least one in the Fund Codes section above before creating a budget category.
                            </div>
                        @else
                            <select wire:model="budget_fund_code_id"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                                <option value="">Select a fund code…</option>
                                @foreach($job->fundCodes as $fc)
                                    <option value="{{ $fc->id }}">{{ $fc->code }} – {{ $fc->description }}</option>
                                @endforeach
                            </select>
                        @endif
                        @error('budget_fund_code_id') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>

                    {{-- Milestone redesign (Phase 4): tie this budget category to a
                         milestone so it rolls up under that milestone's card on
                         the Work tab. Optional — leave blank for an unassigned
                         (general-pool) budget line. --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Attach to milestone <span class="text-xs text-gray-500 font-normal">(optional)</span>
                        </label>
                        @if($job->milestones->isEmpty())
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                No milestones on this project yet. Budget categories without a milestone live in the General pool.
                            </p>
                        @else
                            <select wire:model="budget_milestone_id"
                                    data-test="budget-milestone-select"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                                <option value="">— Unassigned (General pool) —</option>
                                @foreach($job->milestones->sortBy('sequence_order') as $m)
                                    <option value="{{ $m->id }}">{{ $m->name }}</option>
                                @endforeach
                            </select>
                            <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                                Drives the Work tab grouping. Each milestone can have at most one budget category.
                            </p>
                        @endif
                        @error('budget_milestone_id') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button wire:click="$set('showBudgetForm', false)"
                            class="px-4 py-2 bg-gray-200 dark:bg-zinc-600 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-500">
                        Cancel
                    </button>
                    <button wire:click="saveBudget"
                            @if($job->fundCodes->isEmpty()) disabled @endif
                            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        {{ $editingBudget ? 'Update' : 'Save' }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- P-23 Step 7: budget modification confirmation modal. Triggered
         by saveBudget when an existing budget's amount is changing.
         Server-side validation enforces a 10-char minimum on the
         comment so the audit ledger has substance. --}}
    @if($showBudgetModConfirmModal)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50" wire:click.self="cancelBudgetModification" data-budget-mod-modal>
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                    Confirm budget change
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">
                    You are changing the budgeted amount for <span class="font-medium">{{ $budget_category }}</span>.
                    Please add a short note explaining the change. The note is recorded in the audit ledger
                    and emailed to the configured over-budget recipients.
                </p>

                <div class="grid grid-cols-2 gap-4 text-sm bg-gray-50 dark:bg-zinc-700/50 rounded p-3 mb-4">
                    <div>
                        <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Previous</div>
                        <div class="font-semibold text-gray-900 dark:text-white">${{ number_format($budgetModOldAmount, 2) }}</div>
                    </div>
                    <div>
                        <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">New</div>
                        <div class="font-semibold text-gray-900 dark:text-white">${{ number_format($budgetModNewAmount, 2) }}</div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Reason for the change <span class="text-red-500">*</span>
                    </label>
                    <textarea wire:model="budgetModComment"
                              rows="3"
                              minlength="10"
                              placeholder="At least 10 characters explaining what changed and why."
                              class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md text-sm"
                              data-budget-mod-comment></textarea>
                    @error('budgetModComment')
                        <span class="text-red-600 text-xs">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button"
                            wire:click="cancelBudgetModification"
                            class="px-4 py-2 bg-gray-200 dark:bg-zinc-600 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-500">
                        Cancel
                    </button>
                    <button type="button"
                            wire:click="confirmBudgetModification"
                            class="px-4 py-2 bg-amber-600 text-white rounded-md hover:bg-amber-700">
                        Confirm change
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Budget delete confirmation: triggered by deleteBudget when the
         category has expenses. The (housing_job_id, category) tuple is
         the join key, so a silent delete would orphan the expenses and
         hide them from the over-budget banner. Three explicit choices:
         cancel, delete budget + expenses, or keep expenses as orphans. --}}
    @if($showDeleteBudgetModal)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50" wire:click.self="closeDeleteBudgetModal" data-budget-delete-modal>
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                    Delete budget category?
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">
                    <span class="font-medium">{{ $deleteBudgetCategory }}</span> has
                    <span class="font-medium">{{ $deleteBudgetExpenseCount }}</span>
                    {{ Str::plural('expense', $deleteBudgetExpenseCount) }} totaling
                    <span class="font-medium">${{ number_format($deleteBudgetExpenseTotal, 2) }}</span>
                    tied to it. What should happen to those expenses?
                </p>

                <div class="rounded-md bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-700 p-3 text-sm text-amber-800 dark:text-amber-200 mb-4">
                    <strong>Heads up:</strong> kept expenses stay on the project as uncategorized history.
                    If you later create a new category with the exact same name, those expenses will
                    automatically re-bind to it.
                </div>

                <div class="flex flex-col gap-2">
                    <button type="button"
                            wire:click="confirmDeleteBudget(true)"
                            class="w-full px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 text-sm">
                        Delete budget AND its {{ $deleteBudgetExpenseCount }} {{ Str::plural('expense', $deleteBudgetExpenseCount) }}
                    </button>
                    <button type="button"
                            wire:click="confirmDeleteBudget(false)"
                            class="w-full px-4 py-2 bg-amber-600 text-white rounded-md hover:bg-amber-700 text-sm">
                        Delete budget only — keep expenses as orphans
                    </button>
                    <button type="button"
                            wire:click="closeDeleteBudgetModal"
                            class="w-full px-4 py-2 bg-gray-200 dark:bg-zinc-600 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-500 text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- P-23 Step 7: budget modification ledger viewer. Read-only list
         of every modification on the selected budget. Append-only on
         the model side; this view never offers an edit affordance. --}}
    @if($showBudgetLedgerModal && $viewingLedgerBudgetId)
        @php
            $ledgerBudget = $job->budgets->firstWhere('id', $viewingLedgerBudgetId);
            $ledgerEntries = $ledgerBudget?->modifications()->with('modifier')->get() ?? collect();
        @endphp
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50" wire:click.self="closeBudgetLedger" data-budget-ledger-modal>
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-2xl w-full mx-4 max-h-[80vh] overflow-y-auto">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Modification history
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            {{ $ledgerBudget?->category ?? 'Budget' }} — append-only audit ledger
                        </p>
                    </div>
                    <button type="button"
                            wire:click="closeBudgetLedger"
                            class="text-gray-500 hover:text-gray-800 dark:hover:text-white">
                        ✕
                    </button>
                </div>

                @if($ledgerEntries->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-gray-400 italic">No modifications recorded.</p>
                @else
                    <ul class="divide-y divide-gray-200 dark:divide-zinc-700">
                        @foreach($ledgerEntries as $entry)
                            <li class="py-3" data-ledger-entry>
                                <div class="flex items-baseline justify-between gap-3">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        <span class="font-medium">{{ $entry->modifier?->name ?? 'Unknown' }}</span>
                                        <span class="text-gray-500 dark:text-gray-400">on {{ $entry->created_at->format('M d, Y g:i a') }}</span>
                                    </div>
                                    <div class="text-sm whitespace-nowrap">
                                        <span class="text-gray-500 dark:text-gray-400">${{ number_format($entry->old_amount, 2) }}</span>
                                        <span class="text-gray-400">→</span>
                                        <span class="font-semibold text-gray-900 dark:text-white">${{ number_format($entry->new_amount, 2) }}</span>
                                    </div>
                                </div>
                                <p class="mt-1 text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $entry->comment }}</p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    @endif

    <!-- Expense Form Modal -->
    @if($showExpenseForm)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50" wire:click.self="$set('showExpenseForm', false)">
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Record Expense</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category *</label>
                        <select wire:model="expense_category"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                            <option value="">Select a category</option>
                            @foreach($job->budgets as $budget)
                                <option value="{{ $budget->category }}">{{ $budget->category }}</option>
                            @endforeach
                        </select>
                        @error('expense_category') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                    {{-- Hotfix (post P-2.5): the expense's funding source is
                         redundant. Each expense rolls into a budget category
                         via its category string match (the existing observer
                         cascade), and that budget category now owns its own
                         fund_code_id. Asking for it again here was a stale
                         carryover from the pre-P-2.5 model. project_expenses
                         .contract_id and the belongsTo(Contract) relation
                         remain in the schema for legacy rows. --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Amount *</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 dark:text-gray-400">$</span>
                            <input type="number" step="0.01" wire:model="expense_amount"
                                   class="w-full pl-7 px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Use negative amount for credits</p>
                        @error('expense_amount') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date *</label>
                        <input type="date" wire:model="expense_date"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                        @error('expense_date') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                        <textarea wire:model="expense_description" rows="2"
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md"></textarea>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button wire:click="$set('showExpenseForm', false)"
                            class="px-4 py-2 bg-gray-200 dark:bg-zinc-600 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-500">
                        Cancel
                    </button>
                    <button wire:click="saveExpense"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                        Save
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Edit Project Modal -->
    @if($showEditProjectForm)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50" wire:click.self="$set('showEditProjectForm', false)">
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Edit Project Information</h3>

                <!-- Project Summary (Read-Only) -->
                <div class="mb-6 p-4 bg-gray-50 dark:bg-zinc-700 rounded-lg">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="font-medium text-gray-700 dark:text-gray-300">Job Number:</span>
                            <span class="ml-2 text-gray-900 dark:text-white">{{ $job->job_number }}</span>
                        </div>
                        <div>
                            <span class="font-medium text-gray-700 dark:text-gray-300">Program:</span>
                            <span class="ml-2 text-gray-900 dark:text-white">{{ $job->program?->name ?? 'N/A' }}</span>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <!-- Row 1: Title -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Project Title *</label>
                        <input type="text" wire:model="edit_job_title"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                        @error('edit_job_title') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <!-- Participant Selection -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Participant (Head of Household) *
                            </label>
                            <button type="button" wire:click="showQuickParticipantModal"
                                    class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline flex items-center">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                New Participant
                            </button>
                        </div>
                        <livewire:searchable-select
                            :items="$participants"
                            :selected="$edit_participant_id"
                            name="edit_participant"
                            placeholder="Search participants by name..."
                            display-field="display"
                            secondary-field="secondary"
                            :required="true"
                            empty-message="No heads of household found. Create a new participant above."
                            :key="'edit-participant-select-' . ($edit_participant_id ?: 'none')"
                        />
                        @error('edit_participant_id') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Changing participant will also update the associated household.</p>
                    </div>

                    <!-- Row 2: Status & Priority -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status *</label>
                            <select wire:model="edit_status"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="in_progress">In Progress</option>
                                <option value="on_hold">On Hold</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            @error('edit_status') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Priority *</label>
                            <select wire:model="edit_priority"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="emergency">Emergency</option>
                            </select>
                            @error('edit_priority') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Row 3: Dates -->
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Request Date</label>
                            <input type="date" wire:model="edit_request_date"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start Date</label>
                            <input type="date" wire:model="edit_start_date"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Target Completion</label>
                            <input type="date" wire:model="edit_target_completion_date"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                        </div>
                    </div>

                    <!-- Row 4: Property -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Property Address</label>
                        <select wire:model="edit_property_id"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                            <option value="">No Property Assigned</option>
                            @foreach($properties as $property)
                                <option value="{{ $property->id }}">
                                    {{ $property->address_line_1 }}
                                    @if($property->city), {{ $property->city }}@endif
                                    @if($property->state), {{ $property->state }}@endif
                                    @if($property->zip_code) {{ $property->zip_code }}@endif
                                    @if($property->participant)
                                        - ({{ $property->participant->first_name }} {{ $property->participant->last_name }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        @error('edit_property_id') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Select the property where work will be performed.</p>
                    </div>

                    <!-- Row 5: Contractor & Estimated Cost -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Contractor</label>
                            <select wire:model="edit_contractor_id"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                                <option value="">No Contractor Assigned</option>
                                @foreach($contractors as $contractor)
                                    <option value="{{ $contractor->id }}">{{ $contractor->company_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Estimated Cost</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 dark:text-gray-400">$</span>
                                <input type="number" step="0.01" wire:model="edit_estimated_cost"
                                       class="w-full pl-7 px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                            </div>
                        </div>
                    </div>

                    <!-- Row 6: Description -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                        <textarea wire:model="edit_job_description" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md"></textarea>
                    </div>

                    <!-- Row 7: Internal Notes -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Internal Notes</label>
                        <textarea wire:model="edit_internal_notes" rows="2" placeholder="Notes visible only to staff..."
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md"></textarea>
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button wire:click="$set('showEditProjectForm', false)"
                            class="px-4 py-2 bg-gray-200 dark:bg-zinc-600 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-500">
                        Cancel
                    </button>
                    <button wire:click="updateProjectInfo"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                        Save Changes
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Quick Participant Creation Modal -->
    @if($showQuickParticipantForm)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-[60]">
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-md w-full mx-4 max-h-[90vh] overflow-y-auto">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Create New Participant</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Creates a new participant as head of household. A new household will be automatically created. Full CSBG intake can be completed later.</p>
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">First Name *</label>
                            <input type="text" wire:model="quick_participant_first_name"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                            @error('quick_participant_first_name') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Last Name *</label>
                            <input type="text" wire:model="quick_participant_last_name"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                            @error('quick_participant_last_name') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Phone</label>
                        <input type="tel" wire:model="quick_participant_phone"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                        <input type="email" wire:model="quick_participant_email"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date of Birth</label>
                        <input type="date" wire:model="quick_participant_dob"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                    </div>

                    <!-- Household Address Section -->
                    <div class="border-t border-gray-200 dark:border-zinc-600 pt-4">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Household Address</h4>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Address *</label>
                                <input type="text" wire:model="quick_participant_address" placeholder="123 Main Street"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                                @error('quick_participant_address') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div class="grid grid-cols-3 gap-3">
                                <div class="col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">City *</label>
                                    <input type="text" wire:model="quick_participant_city" placeholder="Las Vegas"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                                    @error('quick_participant_city') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">State *</label>
                                    <input type="text" wire:model="quick_participant_state" maxlength="2" placeholder="NV"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md uppercase">
                                    @error('quick_participant_state') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Zip Code *</label>
                                <input type="text" wire:model="quick_participant_zip" placeholder="89101"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                                @error('quick_participant_zip') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-md p-3">
                        <p class="text-xs text-yellow-800 dark:text-yellow-300">
                            This creates a basic participant record with household address. Full CSBG intake should be completed later for reporting compliance.
                        </p>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" wire:click="$set('showQuickParticipantForm', false)"
                            class="px-4 py-2 bg-gray-200 dark:bg-zinc-600 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-300">
                        Cancel
                    </button>
                    <button type="button" wire:click="saveQuickParticipant"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                        Create Participant
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Save as Template Modal -->
    @if($showSaveAsTemplateForm)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50" wire:click.self="$set('showSaveAsTemplateForm', false)">
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Save as Template</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    This will create a reusable template with {{ $job->milestones()->count() }} milestones,
                    {{ $job->milestones->sum(fn($m) => $m->tasks->count()) }} tasks, and
                    {{ $job->milestones->sum(fn($m) => $m->documents->count()) }} document requirements.
                </p>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Template Name *</label>
                        <input type="text" wire:model="template_name" placeholder="e.g., Standard Whole Home Rehab"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                        @error('template_name') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                        <textarea wire:model="template_description" rows="3" placeholder="Describe when to use this template..."
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md"></textarea>
                    </div>
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-md p-3">
                        <p class="text-xs text-blue-800 dark:text-blue-300">
                            <strong>What's copied:</strong> Milestone structure, tasks, documents, budget categories<br>
                            <strong>What's NOT copied:</strong> Assignments, dates, status, uploaded files, expenses
                        </p>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" wire:click="$set('showSaveAsTemplateForm', false)"
                            class="px-4 py-2 bg-gray-200 dark:bg-zinc-600 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-300">
                        Cancel
                    </button>
                    <button type="button" wire:click="saveAsTemplate"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-50 cursor-not-allowed"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                        <span wire:loading.remove wire:target="saveAsTemplate">Create Template</span>
                        <span wire:loading wire:target="saveAsTemplate">Creating...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Document Upload Modal -->
    @if($showDocumentUploadForm)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50" wire:click.self="$set('showDocumentUploadForm', false)">
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-lg w-full mx-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Upload Document Files</h3>
                    <button wire:click="$set('showDocumentUploadForm', false)"
                            class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Select Files *</label>
                        <input type="file" wire:model="uploadFiles" multiple
                               class="w-full text-sm text-gray-900 dark:text-gray-300 border border-gray-300 dark:border-zinc-600 rounded-lg cursor-pointer bg-gray-50 dark:bg-zinc-700">
                        @error('uploadFiles.*') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Max 50MB per file. Multiple files allowed.</p>
                    </div>

                    @if($uploadFiles)
                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
                            <p class="text-xs font-medium text-blue-800 dark:text-blue-300 mb-2">Selected Files ({{ count($uploadFiles) }}):</p>
                            <ul class="text-xs text-blue-700 dark:text-blue-400 space-y-1">
                                @foreach($uploadFiles as $file)
                                    <li class="flex items-center">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M8 4a3 3 0 00-3 3v4a5 5 0 0010 0V7a1 1 0 112 0v4a7 7 0 11-14 0V7a5 5 0 0110 0v4a3 3 0 11-6 0V7a1 1 0 012 0v4a1 1 0 102 0V7a3 3 0 00-3-3z"></path>
                                        </svg>
                                        {{ $file->getClientOriginalName() }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div wire:loading wire:target="uploadFiles" class="text-sm text-gray-600 dark:text-gray-400">
                        Loading files...
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" wire:click="$set('showDocumentUploadForm', false)"
                            class="px-4 py-2 bg-gray-200 dark:bg-zinc-600 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-300">
                        Cancel
                    </button>
                    <button type="button" wire:click="uploadDocumentFiles"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-50 cursor-not-allowed"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        <span wire:loading.remove wire:target="uploadDocumentFiles">Upload Files</span>
                        <span wire:loading wire:target="uploadDocumentFiles">Uploading...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Document Viewer/Review Modal -->
    @if($showDocumentViewerModal)
        @php
            $viewingDocument = $job->milestones->flatMap->documents->firstWhere('id', $viewingDocumentId);
        @endphp
        @if($viewingDocument)
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50" wire:click.self="$set('showDocumentViewerModal', false)">
                <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                    <!-- Header -->
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-900">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $viewingDocument->document_type }}</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    Status: <span class="px-2 py-0.5 rounded {{ $viewingDocument->status_color === 'green' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : ($viewingDocument->status_color === 'yellow' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300' : ($viewingDocument->status_color === 'red' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300')) }}">
                                        {{ ucfirst(str_replace('_', ' ', $viewingDocument->status)) }}
                                    </span>
                                </p>
                            </div>
                            <button wire:click="$set('showDocumentViewerModal', false)"
                                    class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Document Details -->
                    <div class="px-6 py-4">
                        @if($viewingDocument->description)
                            <div class="mb-4">
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $viewingDocument->description }}</p>
                            </div>
                        @endif

                        <div class="grid grid-cols-2 gap-4 mb-4 text-sm">
                            @if($viewingDocument->assignedTo)
                                <div>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">Assigned To:</span>
                                    <p class="text-gray-600 dark:text-gray-400">{{ $viewingDocument->assignedTo->name }}</p>
                                </div>
                            @endif
                            @if($viewingDocument->uploaded_date)
                                <div>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">Uploaded:</span>
                                    <p class="text-gray-600 dark:text-gray-400">{{ $viewingDocument->uploaded_date->format('M j, Y g:i A') }}</p>
                                </div>
                            @endif
                            @if($viewingDocument->approved_at)
                                <div>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">Approved:</span>
                                    <p class="text-gray-600 dark:text-gray-400">{{ $viewingDocument->approved_at->format('M j, Y g:i A') }}</p>
                                </div>
                            @endif
                            @if($viewingDocument->approvedBy)
                                <div>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">Approved By:</span>
                                    <p class="text-gray-600 dark:text-gray-400">{{ $viewingDocument->approvedBy->name }}</p>
                                </div>
                            @endif
                        </div>

                        @if($viewingDocument->rejection_reason)
                            <div class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded">
                                <p class="text-sm font-medium text-red-800 dark:text-red-300">Rejection Reason:</p>
                                <p class="text-sm text-red-700 dark:text-red-400 mt-1">{{ $viewingDocument->rejection_reason }}</p>
                            </div>
                        @endif

                        <!-- Uploaded Files -->
                        @php
                            $viewerMedia = $viewingDocument->valid_document_media;
                            $missingViewerMedia = $viewingDocument->missing_document_media;
                        @endphp
                        @if($viewerMedia->count() > 0)
                            <div>
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                    Uploaded Files ({{ $viewerMedia->count() }})
                                </h4>
                                <div class="space-y-2">
                                    @foreach($viewerMedia as $media)
                                        <div class="p-4 bg-gray-50 dark:bg-zinc-700 rounded-lg">
                                            <div class="flex items-start justify-between">
                                                <div class="flex items-start space-x-3 flex-1">
                                                    <svg class="w-8 h-8 text-gray-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M8 4a3 3 0 00-3 3v4a5 5 0 0010 0V7a1 1 0 112 0v4a7 7 0 11-14 0V7a5 5 0 0110 0v4a3 3 0 11-6 0V7a1 1 0 012 0v4a1 1 0 102 0V7a3 3 0 00-3-3z"></path>
                                                    </svg>
                                                    <div class="flex-1 min-w-0">
                                                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $media->name }}</p>
                                                        <div class="flex items-center space-x-3 mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                            <span>{{ $media->human_readable_size }}</span>
                                                            <span>{{ $media->mime_type }}</span>
                                                            <span>Uploaded {{ $media->created_at->diffForHumans() }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="flex items-center space-x-2 ml-4">
                                                    <a href="{{ route('media.preview', $media) }}" target="_blank"
                                                       class="px-3 py-1 text-xs bg-green-600 text-white rounded hover:bg-green-700">
                                                        Preview
                                                    </a>
                                                    <a href="{{ route('media.download', $media) }}"
                                                       class="px-3 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700">
                                                        Download
                                                    </a>
                                                    @if($viewingDocument->status !== 'approved')
                                                        <button wire:click="deleteDocumentFile({{ $viewingDocument->id }}, {{ $media->id }})"
                                                                wire:confirm="Delete this file?"
                                                                class="text-red-600 hover:text-red-800 dark:text-red-400">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                            </svg>
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                @if($missingViewerMedia->isNotEmpty())
                                    <div class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded">
                                        <p class="text-xs text-red-700 dark:text-red-300">
                                            The following files are missing and should be re-uploaded:
                                            {{ $missingViewerMedia->pluck('name')->join(', ') }}
                                        </p>
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="text-center py-8 bg-gray-50 dark:bg-zinc-700 rounded-lg">
                                <svg class="w-12 h-12 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">No files uploaded yet</p>
                            </div>
                        @endif
                    </div>

                    <!-- Action Buttons -->
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-900">
                        @if($viewingDocument->status === 'pending_review' && $viewingDocument->requires_approval)
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Rejection Reason (if rejecting)</label>
                                <textarea wire:model="rejection_reason" rows="2"
                                          class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md text-sm"
                                          placeholder="Enter reason for rejection (required if rejecting)..."></textarea>
                                @error('rejection_reason') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                            </div>
                        @endif

                        <div class="flex justify-between items-center">
                            <div class="space-x-2">
                                @if($viewingDocument->status !== 'approved' && $viewerMedia->count() > 0)
                                    <button wire:click="$set('showDocumentViewerModal', false); showUploadDocument({{ $viewingDocument->id }})"
                                            class="px-4 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                        Add More Files
                                    </button>
                                @endif
                            </div>

                            <div class="flex space-x-3">
                                @if($viewingDocument->status === 'pending_review' && $viewingDocument->requires_approval)
                                    <button wire:click="rejectDocumentFromViewer"
                                            class="px-4 py-2 text-sm bg-red-600 text-white rounded-md hover:bg-red-700">
                                        Reject
                                    </button>
                                    <button wire:click="approveDocumentFromViewer"
                                            class="px-4 py-2 text-sm bg-green-600 text-white rounded-md hover:bg-green-700">
                                        Approve
                                    </button>
                                @endif
                                <button wire:click="$set('showDocumentViewerModal', false)"
                                        class="px-4 py-2 text-sm bg-gray-200 dark:bg-zinc-600 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-300">
                                    Close
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif

</div>
