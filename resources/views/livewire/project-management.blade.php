<div class="w-full px-0 py-6">
    <div class="bg-white dark:bg-zinc-800 shadow-lg rounded-lg overflow-hidden">
        <!-- Project Header -->
        <div class="bg-indigo-600 text-white p-6">
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center space-x-3">
                        <a href="{{ route('projects.management') }}" wire:navigate class="text-indigo-100 hover:text-white">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                        </a>
                        <div>
                            {{-- P-37: three-line header — address + head-of-household (prominent), fund codes (secondary, equal treatment), job number (small subtitle). --}}
                            <h1 class="text-2xl font-bold leading-tight">
                                {{ $job->property?->address_line_1 ?? 'No property on file' }}
                                @if($job->participant)
                                    <span class="font-semibold">— {{ $job->participant->first_name }} {{ $job->participant->last_name }}</span>
                                @endif
                            </h1>
                            <p class="text-sm text-indigo-100 mt-2">
                                @if($job->fundCodes->isNotEmpty())
                                    @foreach($job->fundCodes->sortBy('code') as $fc)
                                        <span class="font-mono">{{ $fc->code }}</span>@if(!$loop->last), @endif
                                    @endforeach
                                @else
                                    <span class="italic text-indigo-200">No fund codes assigned</span>
                                @endif
                            </p>
                            <p class="text-xs text-indigo-200 mt-1 font-mono">{{ $job->job_number }}</p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    @if($this->can('projects.manage'))
                        <button wire:click="showEditProject"
                                class="px-3 py-1 text-sm bg-white/20 hover:bg-white/30 text-white rounded-md border border-white/30">
                            ✏️ Edit Project
                        </button>
                    @endif
                    @if($job->milestones()->count() > 0 && $this->can('projects.manage'))
                        <button wire:click="showSaveAsTemplate"
                                class="px-3 py-1 text-sm bg-white/20 hover:bg-white/30 text-white rounded-md border border-white/30">
                            📋 Save as Template
                        </button>
                    @endif
                    <div class="text-right">
                        <div class="text-sm text-indigo-100">Program</div>
                        <div class="text-lg font-semibold">{{ $job->program ? $job->program->name : 'N/A' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        @if (session()->has('success'))
            <div class="p-4 bg-green-50 dark:bg-green-900/20 border-l-4 border-green-400">
                <p class="text-sm text-green-700 dark:text-green-300">{{ session('success') }}</p>
            </div>
        @endif
        @if (session()->has('error'))
            <div class="p-4 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-400">
                <p class="text-sm text-red-700 dark:text-red-300">{{ session('error') }}</p>
            </div>
        @endif

        <!-- Project Info Summary Bar -->
        <div class="bg-gray-50 dark:bg-zinc-900 border-b border-gray-200 dark:border-zinc-700 px-6 py-3">
            <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm">
                <div class="flex items-center">
                    <span class="text-gray-500 dark:text-gray-400 mr-2">Status:</span>
                    <span class="px-2 py-0.5 rounded text-xs font-medium
                        @if($job->status === 'completed') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300
                        @elseif($job->status === 'in_progress') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300
                        @elseif($job->status === 'pending') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300
                        @elseif($job->status === 'on_hold') bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300
                        @elseif($job->status === 'cancelled') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300
                        @else bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300
                        @endif">
                        {{ ucfirst(str_replace('_', ' ', $job->status)) }}
                    </span>
                </div>
                <div class="flex items-center">
                    <span class="text-gray-500 dark:text-gray-400 mr-2">Priority:</span>
                    <span class="px-2 py-0.5 rounded text-xs font-medium
                        @if($job->priority === 'emergency') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300
                        @elseif($job->priority === 'high') bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300
                        @elseif($job->priority === 'medium') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300
                        @else bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300
                        @endif">
                        {{ ucfirst($job->priority) }}
                    </span>
                </div>
                @if($job->contractor)
                    <div class="flex items-center">
                        <span class="text-gray-500 dark:text-gray-400 mr-2">Contractor:</span>
                        <span class="text-gray-900 dark:text-white">{{ $job->contractor->company_name }}</span>
                    </div>
                @endif
                @if($job->target_completion_date)
                    <div class="flex items-center">
                        <span class="text-gray-500 dark:text-gray-400 mr-2">Target:</span>
                        <span class="{{ $job->isOverdue() ? 'text-red-600 dark:text-red-400 font-medium' : 'text-gray-900 dark:text-white' }}">
                            {{ $job->target_completion_date->format('M d, Y') }}
                            @if($job->isOverdue())
                                <span class="text-xs">({{ $job->days_overdue }} days overdue)</span>
                            @endif
                        </span>
                    </div>
                @endif
                @if($job->estimated_cost)
                    <div class="flex items-center">
                        <span class="text-gray-500 dark:text-gray-400 mr-2">Est. Cost:</span>
                        <span class="text-gray-900 dark:text-white">${{ number_format($job->estimated_cost, 2) }}</span>
                    </div>
                @endif
                <div class="flex-1"></div>
                @if($this->can('projects.manage'))
                    <button wire:click="showEditProject" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 text-sm font-medium">
                        Edit Details
                    </button>
                @endif
            </div>
        </div>

        <!-- Counties carried from the originating comm log at conversion.
             Informational: the same county selection drove the fund codes
             and Phase 2 requirements below. -->
        @if($job->housingServices->isNotEmpty())
            <div class="bg-sky-50 dark:bg-sky-900/10 border-b border-sky-200 dark:border-sky-900/30 px-6 py-3">
                <div class="flex items-center gap-3 flex-wrap">
                    <h3 class="text-sm font-semibold text-sky-900 dark:text-sky-200 uppercase tracking-wide">
                        County
                    </h3>
                    @foreach($job->housingServices as $county)
                        <span class="inline-flex items-center px-3 py-1 bg-white dark:bg-zinc-800 border border-sky-300 dark:border-sky-700 rounded-md text-sm text-gray-900 dark:text-white">
                            {{ $county->name }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- P-08: Fund Code assignment — visible above tabs so the manager
             can see + manage funding without clicking into a tab. -->
        <div class="bg-emerald-50 dark:bg-emerald-900/10 border-b border-emerald-200 dark:border-emerald-900/30 px-6 py-4">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-semibold text-emerald-900 dark:text-emerald-200 uppercase tracking-wide">
                    Fund Codes
                </h3>
                @if($this->can('projects.manage') && $availableFundCodes->isNotEmpty())
                    <div class="flex items-center gap-2">
                        <select wire:model="newFundCodeId"
                                class="px-3 py-1 text-sm border border-emerald-300 dark:border-emerald-700 dark:bg-zinc-800 dark:text-white rounded-md">
                            <option value="">Select a fund code…</option>
                            @foreach($availableFundCodes as $fc)
                                <option value="{{ $fc->id }}">{{ $fc->code }} – {{ $fc->description }}</option>
                            @endforeach
                        </select>
                        <button wire:click="attachFundCode"
                                wire:loading.attr="disabled"
                                class="px-3 py-1 text-sm bg-emerald-600 text-white rounded-md hover:bg-emerald-700 disabled:opacity-50">
                            + Attach
                        </button>
                    </div>
                @endif
            </div>

            @if($job->fundCodes->isEmpty())
                <p class="text-sm text-emerald-700 dark:text-emerald-300 italic">
                    No fund codes assigned yet. Attach codes to surface their requirements on this project.
                </p>
            @else
                <div class="flex flex-wrap gap-2">
                    @foreach($job->fundCodes as $fc)
                        <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-zinc-800 border border-emerald-300 dark:border-emerald-700 rounded-md">
                            <span class="font-mono text-sm font-semibold text-gray-900 dark:text-white">{{ $fc->code }}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ Str::limit($fc->description, 40) }}</span>
                            @if($this->can('projects.manage'))
                                <button wire:click="detachFundCode({{ $fc->id }})"
                                        wire:confirm="Detach {{ $fc->code }} from this project?"
                                        class="ml-1 text-gray-400 hover:text-red-600"
                                        aria-label="Detach">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Milestone redesign (Phase 3): the old Requirements + Milestones & Tasks tabs
             collapse into a single Work tab. Budget stays separate. The legacy data-test
             attrs (tab-requirements, tab-milestones) are preserved on the unified Work
             button so existing tests that toggle them still address a real element. --}}
        <div class="border-b border-gray-200 dark:border-zinc-700">
            <nav class="flex -mb-px" data-test="project-tab-nav">
                <button wire:click="$set('activeTab', 'work')"
                        data-test="tab-work tab-requirements tab-milestones"
                        class="py-4 px-6 text-center border-b-2 font-medium text-sm
                        @if($activeTab === 'work')
                            border-indigo-500 text-indigo-600 dark:text-indigo-400
                        @else
                            border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300
                        @endif">
                    Work
                </button>
                <button wire:click="$set('activeTab', 'budget')"
                        data-test="tab-budget"
                        class="ml-8 py-4 px-6 text-center border-b-2 font-medium text-sm
                        @if($activeTab === 'budget')
                            border-indigo-500 text-indigo-600 dark:text-indigo-400
                        @else
                            border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300
                        @endif">
                    Budget & Expenses
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-6">
            @if($activeTab === 'work' || $activeTab === 'requirements' || $activeTab === 'milestones')
                {{-- Milestone redesign (Phase 3): unified Work tab.
                     Milestones are grouped by their budget category (a milestone with a
                     budget category lives under it; one without sits in a top "Compliance &
                     general" group). Selecting a milestone reveals its detail panel
                     beneath — the gating requirements, dependencies, and lifecycle
                     actions. Status pills use the CALV tone vocabulary
                     (outstanding=amber, in_progress=teal, complete=sage, blocked=red).

                     Legacy data-test attributes (requirements-tab, milestone-requirement-row,
                     etc.) live alongside the new ones so the existing regression suite
                     still finds the rows it expects.  --}}
                <div data-test="work-tab requirements-tab" class="space-y-4">
                    {{-- Fund-code requirements reference (legacy FundCodeRequirement
                         panel). Surfaces hard and informational requirements per
                         attached fund code as a quick at-a-glance reference at
                         the top of Work. Retained from the pre-redesign tab so
                         the manager still sees the per-code list — the new
                         milestone-level rows below are the actionable layer. --}}
                    @include('livewire.partials.fund-code-requirements-panel')

                    @php
                        $allReqs = $job->requirements ?? collect();
                        $milestones = $job->milestones ?? collect();
                        $budgets = $job->budgets ?? collect();

                        // Group milestones by their budget category. A milestone with no
                        // budget_id falls into the "general" bucket. We resolve the link
                        // either way: most milestones reach budget via
                        // project_budgets.milestone_id (one budget → one milestone) but
                        // some installations may pivot through the fund_code pivot's
                        // milestone_id too. We trust project_budgets.milestone_id as the
                        // authoritative tie (per CLAUDE.md Phase-1 decisions).
                        $budgetByMilestoneId = $budgets->keyBy('milestone_id');
                        $milestonesWithBudget = $milestones->filter(fn ($m) => $budgetByMilestoneId->has($m->id));
                        $milestonesGeneral = $milestones->reject(fn ($m) => $budgetByMilestoneId->has($m->id));

                        // Map fund_code_id -> milestone_id from the loaded pivot, used for
                        // the fund-code-source requirement re-parenting (kept for parity
                        // with the legacy Requirements tab so fund-code reqs still render
                        // under the milestone their fund code serves).
                        $fundCodeMilestoneMap = collect();
                        foreach ($job->fundCodes ?? [] as $fc) {
                            $fundCodeMilestoneMap[$fc->id] = $fc->pivot?->milestone_id;
                        }

                        // Helper: collect milestone-relevant requirements (own + fund-code re-parented).
                        $reqsFor = function ($milestoneId) use ($allReqs, $fundCodeMilestoneMap) {
                            $own = $allReqs->where('source_type', 'milestone')->where('source_id', $milestoneId);
                            $viaFundCode = $allReqs->where('source_type', 'fund_code')->filter(
                                fn ($r) => ($fundCodeMilestoneMap[$r->source_id] ?? null) === $milestoneId
                            );
                            return $own->concat($viaFundCode)->values();
                        };

                        // Status → CALV tone mapping for the row chip.
                        $statusTone = [
                            'outstanding' => 'amber',
                            'not_started' => 'neutral',
                            'in_progress' => 'teal',
                            'completed' => 'sage',
                            'complete' => 'sage',
                            'not_applicable' => 'sand',
                        ];

                        // Compute blocked-by edges so a row can flag itself as gated by
                        // upstream incomplete milestones. ProjectMilestone::dependencies
                        // is loaded lazily; eager-loading it would change the mount()
                        // payload, so we accept the n+1 here on the assumption project
                        // milestone counts are small (<50 typical).
                        $blockedBy = collect();
                        foreach ($milestones as $m) {
                            $unmet = $m->dependencies->filter(fn ($d) => $d->status !== 'completed');
                            $blockedBy[$m->id] = $unmet;
                        }

                        $totalCount = $milestones->count();
                        $doneCount = $milestones->where('status', 'completed')->count();
                    @endphp

                    {{-- Header strip: progress summary + add-milestone CTA. --}}
                    <div class="flex items-center justify-between pb-3 border-b border-gray-200 dark:border-zinc-700">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Project work</h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                {{ $doneCount }} of {{ $totalCount }} milestone{{ $totalCount === 1 ? '' : 's' }} complete
                                @if($totalCount > 0)
                                    · {{ round(($doneCount / max($totalCount, 1)) * 100) }}%
                                @endif
                            </p>
                        </div>
                        @if($this->can('projects.manage'))
                            <button wire:click="showCreateMilestone"
                                    data-test="add-milestone-btn"
                                    class="px-3 py-1.5 text-sm bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                                + Add milestone
                            </button>
                        @endif
                    </div>

                    {{-- ────────── Render one group per category, then general. ────────── --}}
                    @php
                        // Component-level helper to render one milestone row. Inlined as
                        // a closure here so we don't have to spin up an x-component for
                        // a one-page use case. Captures $blockedBy, $statusTone,
                        // $selectedMilestone, $reqsFor by reference.
                        $renderMilestoneRow = function ($m) use ($blockedBy, $statusTone, $selectedMilestone, $reqsFor) {
                            $reqs = $reqsFor($m->id);
                            $outstanding = $reqs->where('status', 'outstanding')->count();
                            $totalReq = $reqs->count();
                            $isSelected = (int) $selectedMilestone === (int) $m->id;
                            $isBlocked = ($blockedBy[$m->id] ?? collect())->isNotEmpty()
                                && $m->status !== 'completed';
                            $tone = $isBlocked ? 'red' : ($statusTone[$m->status] ?? 'neutral');
                            $label = $isBlocked ? 'blocked' : str_replace('_', ' ', $m->status);
                            return compact('reqs', 'outstanding', 'totalReq', 'isSelected', 'isBlocked', 'tone', 'label');
                        };
                    @endphp

                    {{-- Group: Compliance & general (un-budgeted milestones, including default-seeded Phase 2 compliance items). --}}
                    @if($milestonesGeneral->isNotEmpty())
                        <section class="border border-gray-200 dark:border-zinc-700 rounded-lg overflow-hidden"
                                 data-test="milestone-group milestone-group-general">
                            <header class="flex items-baseline justify-between bg-gray-50 dark:bg-zinc-900/40 px-4 py-2 border-b border-gray-200 dark:border-zinc-700">
                                <div class="flex items-baseline gap-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] uppercase tracking-wider font-bold bg-calv-slate-15 text-calv-slate">
                                        General
                                    </span>
                                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Compliance &amp; un-budgeted work</h3>
                                </div>
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $milestonesGeneral->count() }} milestone{{ $milestonesGeneral->count() === 1 ? '' : 's' }}</span>
                            </header>
                            <ul class="divide-y divide-gray-100 dark:divide-zinc-700">
                                @foreach($milestonesGeneral as $milestone)
                                    @php $row = $renderMilestoneRow($milestone); @endphp
                                    <li wire:click="selectMilestone({{ $milestone->id }})"
                                        data-test="milestone-row milestone-requirement-group"
                                        data-milestone-id="{{ $milestone->id }}"
                                        class="px-4 py-3 cursor-pointer transition-colors
                                               {{ $row['isSelected']
                                                  ? 'bg-indigo-50 dark:bg-indigo-900/20'
                                                  : 'hover:bg-gray-50 dark:hover:bg-zinc-700/40' }}">
                                        <div class="flex items-center gap-3">
                                            <span class="inline-block w-2.5 h-2.5 rounded-full flex-shrink-0" style="background-color: {{ $milestone->color }}"></span>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-baseline gap-2 flex-wrap">
                                                    <span class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $milestone->name }}</span>
                                                    @if($milestone->isPromoted())
                                                        <span class="text-[10px] uppercase tracking-wider font-semibold text-calv-teal" title="Auto-seeded from the requirements library">★ default</span>
                                                    @endif
                                                    @if($row['totalReq'] > 0)
                                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                                            {{ $row['totalReq'] - $row['outstanding'] }}/{{ $row['totalReq'] }} requirements met
                                                        </span>
                                                    @endif
                                                </div>
                                                @if($milestone->target_date)
                                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                                        Target {{ $milestone->target_date->format('M d, Y') }}
                                                    </div>
                                                @endif
                                            </div>
                                            @if($row['isBlocked'])
                                                <span class="text-calv-red text-xs" title="Blocked by {{ ($blockedBy[$milestone->id] ?? collect())->pluck('name')->join(', ') }}">🔒</span>
                                            @endif
                                            <x-comm.meta-chip :tone="$row['tone']">{{ $row['label'] }}</x-comm.meta-chip>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endif

                    {{-- Group per budget category. One card per ProjectBudget that has a milestone_id link. --}}
                    @foreach($milestonesWithBudget->groupBy('id') as $milestoneId => $msGroup)
                        @php
                            $milestone = $msGroup->first();
                            $budget = $budgetByMilestoneId->get($milestoneId);
                            $row = $renderMilestoneRow($milestone);
                        @endphp
                        <section class="border border-gray-200 dark:border-zinc-700 rounded-lg overflow-hidden"
                                 data-test="milestone-group milestone-group-budget"
                                 data-milestone-id="{{ $milestone->id }}">
                            <header class="flex items-baseline justify-between bg-emerald-50/60 dark:bg-emerald-900/10 px-4 py-2 border-b border-emerald-200 dark:border-emerald-900/30">
                                <div class="flex items-baseline gap-2 flex-wrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] uppercase tracking-wider font-bold bg-calv-sage-15 text-[#2F5A41]">
                                        Budget
                                    </span>
                                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $budget->category }}</h3>
                                    @if($budget->fundCode)
                                        <span class="font-mono text-[11px] text-emerald-700 dark:text-emerald-300">{{ $budget->fundCode->code }}</span>
                                    @endif
                                </div>
                                <span class="text-xs text-gray-500 dark:text-gray-400 tabular-nums">
                                    ${{ number_format((float) $budget->spent_amount, 0) }} / ${{ number_format((float) $budget->budgeted_amount, 0) }}
                                </span>
                            </header>
                            <ul class="divide-y divide-gray-100 dark:divide-zinc-700">
                                <li wire:click="selectMilestone({{ $milestone->id }})"
                                    data-test="milestone-row milestone-requirement-group"
                                    data-milestone-id="{{ $milestone->id }}"
                                    class="px-4 py-3 cursor-pointer transition-colors
                                           {{ $row['isSelected']
                                              ? 'bg-indigo-50 dark:bg-indigo-900/20'
                                              : 'hover:bg-gray-50 dark:hover:bg-zinc-700/40' }}">
                                    <div class="flex items-center gap-3">
                                        <span class="inline-block w-2.5 h-2.5 rounded-full flex-shrink-0" style="background-color: {{ $milestone->color }}"></span>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-baseline gap-2 flex-wrap">
                                                <span class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $milestone->name }}</span>
                                                @if($row['totalReq'] > 0)
                                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                                        {{ $row['totalReq'] - $row['outstanding'] }}/{{ $row['totalReq'] }} requirements met
                                                    </span>
                                                @endif
                                            </div>
                                            @if($milestone->target_date)
                                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                                    Target {{ $milestone->target_date->format('M d, Y') }}
                                                </div>
                                            @endif
                                        </div>
                                        @if($row['isBlocked'])
                                            <span class="text-calv-red text-xs" title="Blocked by {{ ($blockedBy[$milestone->id] ?? collect())->pluck('name')->join(', ') }}">🔒</span>
                                        @endif
                                        <x-comm.meta-chip :tone="$row['tone']">{{ $row['label'] }}</x-comm.meta-chip>
                                    </div>
                                </li>
                            </ul>
                        </section>
                    @endforeach

                    {{-- Empty state when no milestones exist at all. --}}
                    @if($milestones->isEmpty())
                        <div class="text-center py-12 bg-gray-50 dark:bg-zinc-700/40 rounded-lg border border-dashed border-gray-300 dark:border-zinc-600">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">No milestones yet — the default Phase 2 compliance set seeds at conversion.</p>
                            @if($this->can('projects.manage'))
                                <button wire:click="showCreateMilestone"
                                        class="mt-4 px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 text-sm">
                                    Create first milestone
                                </button>
                            @endif
                        </div>
                    @endif

                    {{-- ────────── Selected milestone detail panel. ────────── --}}
                    @if($currentMilestone)
                        @php
                            $detailReqs = $reqsFor($currentMilestone->id);
                            $detailBlockedBy = $blockedBy[$currentMilestone->id] ?? collect();
                            $detailDependents = $currentMilestone->dependents ?? collect();
                            $detailCanComplete = $detailBlockedBy->isEmpty()
                                && $detailReqs->where('status', 'outstanding')->isEmpty();
                        @endphp
                        <section class="border border-indigo-200 dark:border-indigo-700/40 bg-indigo-50/40 dark:bg-indigo-900/10 rounded-lg p-5 mt-6"
                                 data-test="milestone-detail">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-baseline gap-2 flex-wrap">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $currentMilestone->name }}</h3>
                                        @if($currentMilestone->isPromoted())
                                            <span class="text-[10px] uppercase tracking-wider font-semibold text-calv-teal">★ default Phase 2</span>
                                        @endif
                                    </div>
                                    @if($currentMilestone->description)
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $currentMilestone->description }}</p>
                                    @endif
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        Started: {{ $currentMilestone->started_at?->format('M d, Y g:i A') ?: '—' }}
                                        @if($currentMilestone->target_date) · Target: {{ $currentMilestone->target_date->format('M d, Y') }} @endif
                                    </p>
                                </div>
                                <div class="flex items-center gap-2 flex-shrink-0">
                                    @if($currentMilestone->status !== 'completed')
                                        <button wire:click="updateMilestoneStatus({{ $currentMilestone->id }}, 'completed')"
                                                @disabled(! $detailCanComplete)
                                                title="{{ $detailCanComplete ? '' : 'All gating requirements + upstream dependencies must be met first.' }}"
                                                class="px-3 py-1.5 text-sm rounded-md
                                                       {{ $detailCanComplete
                                                          ? 'bg-green-600 text-white hover:bg-green-700'
                                                          : 'bg-gray-300 dark:bg-zinc-600 text-gray-500 dark:text-gray-400 cursor-not-allowed' }}">
                                            Mark complete
                                        </button>
                                    @endif
                                    <button wire:click="showEditMilestone({{ $currentMilestone->id }})"
                                            class="px-3 py-1.5 text-sm bg-gray-200 dark:bg-zinc-600 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-500">
                                        Edit
                                    </button>
                                    <button wire:click="deleteMilestone({{ $currentMilestone->id }})"
                                            wire:confirm="Delete this milestone?"
                                            class="px-3 py-1.5 text-sm bg-red-600 text-white rounded-md hover:bg-red-700">
                                        Delete
                                    </button>
                                </div>
                            </div>

                            {{-- Dependency tray (blocked-by + dependents). --}}
                            @if($detailBlockedBy->isNotEmpty() || $detailDependents->isNotEmpty())
                                <div class="mt-4 pt-3 border-t border-indigo-200 dark:border-indigo-700/40 flex flex-wrap gap-x-6 gap-y-2 text-xs">
                                    @if($detailBlockedBy->isNotEmpty())
                                        <div>
                                            <span class="uppercase tracking-wider text-[10px] font-bold text-calv-slate-65 mr-2">Blocked by</span>
                                            @foreach($detailBlockedBy as $up)
                                                <x-comm.meta-chip tone="red">{{ $up->name }}</x-comm.meta-chip>
                                            @endforeach
                                        </div>
                                    @endif
                                    @if($detailDependents->isNotEmpty())
                                        <div>
                                            <span class="uppercase tracking-wider text-[10px] font-bold text-calv-slate-65 mr-2">Unblocks</span>
                                            @foreach($detailDependents as $down)
                                                <x-comm.meta-chip tone="slate">{{ $down->name }}</x-comm.meta-chip>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endif

                            {{-- Gating requirements list. Reuses the existing wire:click bindings
                                 (toggleTaskRequirementComplete, showCompleteRequirementModal, reopenRequirement,
                                 showEditRequirement, deleteRequirement) so the PHP component contract is
                                 unchanged. CALV-styled rows for the milestone-source ones; fund-code-source
                                 reqs render with a teal "via fund code" tag. --}}
                            <div class="mt-4 pt-3 border-t border-indigo-200 dark:border-indigo-700/40">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Gating requirements</h4>
                                    @if($this->can('projects.manage'))
                                        <div class="flex items-center gap-2">
                                            <button wire:click="showCreateRequirement('task')"
                                                    class="px-2.5 py-1 text-xs bg-indigo-600 text-white rounded hover:bg-indigo-700">
                                                + Task
                                            </button>
                                            <button wire:click="showCreateRequirement('document')"
                                                    class="px-2.5 py-1 text-xs bg-indigo-600 text-white rounded hover:bg-indigo-700">
                                                + Document
                                            </button>
                                        </div>
                                    @endif
                                </div>

                                @if($detailReqs->isEmpty())
                                    <p class="text-xs italic text-gray-500 dark:text-gray-400 text-center py-4">No gating requirements on this milestone yet. Add a task or document to track what must be done.</p>
                                @else
                                    <ul class="space-y-2" data-test="gating-requirements-list">
                                        @foreach($detailReqs as $r)
                                            @php
                                                $rTone = $statusTone[$r->status] ?? 'neutral';
                                                $rIcon = $r->type === 'document' ? '📄' : '✓';
                                                $hasFiles = $r->type === 'document' && method_exists($r, 'getMedia')
                                                    ? $r->getMedia('attachments')->isNotEmpty()
                                                    : false;
                                            @endphp
                                            <li class="bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 rounded-md p-3"
                                                data-test="milestone-requirement-row gating-requirement-row">
                                                <div class="flex items-start gap-3">
                                                    @if($r->type === 'task')
                                                        <input type="checkbox"
                                                               wire:click="toggleTaskRequirementComplete({{ $r->id }})"
                                                               {{ $r->isComplete() ? 'checked' : '' }}
                                                               class="mt-1 h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                                    @else
                                                        <span class="text-base leading-none mt-0.5">{{ $rIcon }}</span>
                                                    @endif
                                                    <div class="flex-1 min-w-0">
                                                        <div class="flex items-baseline gap-2 flex-wrap">
                                                            <span class="text-sm font-medium text-gray-900 dark:text-white {{ $r->isComplete() ? 'line-through' : '' }}">{{ $r->title }}</span>
                                                            <x-comm.meta-chip :tone="$rTone">{{ str_replace('_', ' ', $r->status) }}</x-comm.meta-chip>
                                                            @if($r->source_type === 'fund_code')
                                                                <span class="text-[10px] uppercase tracking-wider font-semibold text-calv-teal">via fund code</span>
                                                            @endif
                                                            @if($r->wasOverridden())
                                                                <x-comm.meta-chip tone="amber">override</x-comm.meta-chip>
                                                            @endif
                                                        </div>
                                                        @if($r->description)
                                                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">{{ $r->description }}</p>
                                                        @endif
                                                        @if($r->milestone_gate_text)
                                                            <p class="text-xs text-calv-red mt-1">⛔ {{ $r->milestone_gate_text }}</p>
                                                        @endif
                                                        <div class="flex flex-wrap items-center gap-3 mt-1.5 text-[11px] text-gray-500 dark:text-gray-400">
                                                            @if($r->owner)
                                                                <span>Owner: {{ $r->owner->name }}</span>
                                                            @else
                                                                <span class="italic">Unassigned</span>
                                                            @endif
                                                            @if($hasFiles)
                                                                <span class="inline-flex items-center gap-1">📎 {{ $r->getMedia('attachments')->count() }} file(s)</span>
                                                            @endif
                                                            @if($r->isComplete() && $r->completedBy)
                                                                <span class="text-green-700 dark:text-green-400">Done by {{ $r->completedBy->name }} {{ $r->completed_at?->format('M d, Y') }}</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <div class="flex items-center gap-1 flex-shrink-0">
                                                        @if($r->type === 'document' && ! $r->isComplete())
                                                            <button wire:click="showCompleteRequirementModal({{ $r->id }})"
                                                                    class="px-2 py-0.5 text-[11px] bg-green-600 text-white rounded hover:bg-green-700">
                                                                Mark complete
                                                            </button>
                                                        @elseif($r->isComplete())
                                                            <button wire:click="reopenRequirement({{ $r->id }})"
                                                                    class="px-2 py-0.5 text-[11px] bg-amber-600 text-white rounded hover:bg-amber-700">
                                                                Reopen
                                                            </button>
                                                        @endif
                                                        @if($r->source_type !== 'fund_code')
                                                            <button wire:click="showEditRequirement({{ $r->id }})"
                                                                    class="text-gray-500 hover:text-gray-800 dark:hover:text-gray-200 p-1"
                                                                    title="Edit">
                                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                                </svg>
                                                            </button>
                                                            <button wire:click="deleteRequirement({{ $r->id }})"
                                                                    wire:confirm="Delete this requirement?"
                                                                    class="text-red-500 hover:text-red-700 p-1"
                                                                    title="Delete">
                                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                                </svg>
                                                            </button>
                                                        @endif
                                                    </div>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </section>
                    @else
                        @if($milestones->isNotEmpty())
                            <p class="text-sm text-center text-gray-500 dark:text-gray-400 italic py-4">Select a milestone above to see its gating requirements and lifecycle actions.</p>
                        @endif
                    @endif
                </div>

            @else
                {{-- Phase 3 of the milestone redesign deleted the legacy
                     Requirements + "Milestones & Tasks" tab bodies that
                     used to live between this @else and the Work tab
                     above. Both became the unified Work tab. --}}
                <!-- Budget Tab -->
                <div class="space-y-6">
                    {{-- P-23 Step 7: persistent over-budget banner.
                         Shown whenever any category on this project is over.
                         Cannot be dismissed by the user; resolution paths
                         (reduce expense, reassign expense, modify budget cap)
                         are the only ways to clear it. --}}
                    @php
                        $overBudgetCategories = $job->budgets->filter(fn ($b) => $b->is_over_budget);
                    @endphp
                    @if($overBudgetCategories->isNotEmpty())
                        <div class="rounded-md border border-red-300 bg-red-50 dark:bg-red-900/30 dark:border-red-700 p-4" role="alert" data-over-budget-banner>
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4a2 2 0 00-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/>
                                </svg>
                                <div class="flex-1">
                                    <h3 class="text-sm font-semibold text-red-800 dark:text-red-200">Budget exceeded</h3>
                                    <p class="text-sm text-red-700 dark:text-red-300 mt-1">
                                        {{ $overBudgetCategories->count() }} {{ Str::plural('category', $overBudgetCategories->count()) }} on this project {{ $overBudgetCategories->count() === 1 ? 'is' : 'are' }} over budget. Reduce or reassign expenses, or modify the budget cap with a comment.
                                    </p>
                                    <details class="mt-2 text-sm text-red-700 dark:text-red-300">
                                        <summary class="cursor-pointer underline decoration-dotted underline-offset-2 hover:text-red-900 dark:hover:text-red-100">
                                            How do I modify the budget cap?
                                        </summary>
                                        <ol class="list-decimal pl-5 mt-2 space-y-1 text-red-700 dark:text-red-300">
                                            <li>Find the over-budget category in the <span class="font-medium">Budget Categories</span> table below.</li>
                                            <li>Click <span class="font-medium">Edit</span> in that row's Actions column.</li>
                                            <li>Change the <span class="font-medium">Budgeted Amount</span> and click <span class="font-medium">Save</span>.</li>
                                            <li>A confirmation prompt will ask for a comment (10 characters minimum) explaining the change.</li>
                                            <li>The change is logged to the budget's modification ledger and emailed to the configured over-budget recipients.</li>
                                        </ol>
                                    </details>
                                    <ul class="mt-2 space-y-1 text-sm text-red-700 dark:text-red-300">
                                        @foreach($overBudgetCategories as $b)
                                            @php
                                                $overage = (float) $b->spent_amount - (float) $b->budgeted_amount;
                                            @endphp
                                            <li>
                                                <span class="font-medium">{{ $b->category }}</span>:
                                                ${{ number_format($b->spent_amount, 2) }} spent of ${{ number_format($b->budgeted_amount, 2) }} budgeted —
                                                <span class="font-semibold">${{ number_format($overage, 2) }} over</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Budget Categories -->
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Budget Categories</h2>
                            <button wire:click="showCreateBudget"
                                    class="px-3 py-1 text-sm bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                                + Add Category
                            </button>
                        </div>

                        @if($job->budgets->count() > 0)
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                                    <thead class="bg-gray-50 dark:bg-zinc-900">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Category</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Fund Code</th>
                                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Budgeted</th>
                                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Spent</th>
                                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Remaining</th>
                                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">% Used</th>
                                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-700">
                                        @foreach($job->budgets as $budget)
                                            <tr>
                                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $budget->category }}</td>
                                                <td class="px-4 py-3">
                                                    {{-- P-2.5: prefer the budget row's own fund code (the new
                                                         per-row column). For legacy contract-bearing rows that
                                                         pre-date this phase, fall through to the contract
                                                         grant_code / title chain so the visual is preserved
                                                         until the operator edits the row. The Phase 2
                                                         project-wide fundCodes fallback is intentionally NOT in
                                                         this chain — for legacy NULL+NULL rows, "—" is the
                                                         honest answer; the row doesn't have a code. --}}
                                                    @if($budget->fundCode)
                                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-sm font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200" title="{{ $budget->fundCode->description }}">
                                                            {{ $budget->fundCode->code }}
                                                        </span>
                                                    @elseif($budget->contract && $budget->contract->grant_code)
                                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-sm font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200" title="{{ $budget->contract->title }}">
                                                            {{ $budget->contract->grant_code }}
                                                        </span>
                                                    @elseif($budget->contract)
                                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-sm font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200" title="{{ $budget->contract->title }}">
                                                            {{ Str::limit($budget->contract->title, 20) }}
                                                        </span>
                                                    @else
                                                        <span class="text-sm text-gray-400 dark:text-gray-500">—</span>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-white">${{ number_format($budget->budgeted_amount, 2) }}</td>
                                                <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-white">${{ number_format($budget->spent_amount, 2) }}</td>
                                                <td class="px-4 py-3 text-sm text-right {{ $budget->remaining_amount < 0 ? 'text-red-600 dark:text-red-400 font-semibold' : 'text-gray-900 dark:text-white' }}">
                                                    ${{ number_format($budget->remaining_amount, 2) }}
                                                </td>
                                                <td class="px-4 py-3 text-sm text-right">
                                                    {{-- P-23 Step 7: 80% soft warning replaces the previous >90% threshold. --}}
                                                    <span class="px-2 py-1 rounded text-xs {{ $budget->is_over_budget ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' : ($budget->percentage_spent >= 80 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300' : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300') }}">
                                                        {{ $budget->percentage_spent }}%
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-sm text-right space-x-2">
                                                    {{-- P-23 Step 7: ledger button only renders when modifications exist for this budget. --}}
                                                    @if($budget->modifications_count > 0)
                                                        <button wire:click="showBudgetLedger({{ $budget->id }})"
                                                                class="text-amber-600 dark:text-amber-400 hover:text-amber-800 dark:hover:text-amber-300"
                                                                data-budget-modified-indicator
                                                                title="View modification history">
                                                            Modified ({{ $budget->modifications_count }})
                                                        </button>
                                                    @endif
                                                    <button wire:click="editBudget({{ $budget->id }})"
                                                            class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300">
                                                        Edit
                                                    </button>
                                                    <button wire:click="deleteBudget({{ $budget->id }})"
                                                            wire:confirm="Delete this budget category?"
                                                            class="text-red-600 hover:text-red-800 dark:text-red-400">
                                                        Delete
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                        <tr class="bg-gray-100 dark:bg-zinc-900 font-semibold">
                                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">TOTAL</td>
                                            <td class="px-4 py-3"></td>
                                            <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-white">${{ number_format($job->getTotalBudget(), 2) }}</td>
                                            <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-white">${{ number_format($job->getTotalSpent(), 2) }}</td>
                                            <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-white">${{ number_format($job->getTotalBudget() - $job->getTotalSpent(), 2) }}</td>
                                            <td class="px-4 py-3 text-sm text-right"></td>
                                            <td class="px-4 py-3"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-8 bg-gray-50 dark:bg-zinc-700 rounded-lg">
                                <p class="text-sm text-gray-600 dark:text-gray-400">No budget categories defined</p>
                                <button wire:click="showCreateBudget"
                                        class="mt-2 text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                                    Add first category
                                </button>
                            </div>
                        @endif
                    </div>

                    <!-- Expenses -->
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Expenses</h2>
                            <button wire:click="showCreateExpense"
                                    class="px-3 py-1 text-sm bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                                + Record Expense
                            </button>
                        </div>

                        @if($job->expenses->count() > 0)
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                                    <thead class="bg-gray-50 dark:bg-zinc-900">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Date</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Category</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Fund Code</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Description</th>
                                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Amount</th>
                                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-700">
                                        @foreach($job->expenses->sortByDesc('expense_date') as $expense)
                                            <tr>
                                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $expense->expense_date->format('M d, Y') }}</td>
                                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $expense->category }}</td>
                                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                                    @if($expense->contract)
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300" title="{{ $expense->contract->title }}">
                                                            {{ $expense->contract->grant_code ?: Str::limit($expense->contract->title, 15) }}
                                                        </span>
                                                    @else
                                                        <span class="text-gray-400 dark:text-gray-500">-</span>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $expense->description ?: '-' }}</td>
                                                <td class="px-4 py-3 text-sm text-right {{ $expense->is_credit ? 'text-green-600 dark:text-green-400' : 'text-gray-900 dark:text-white' }}">
                                                    {{ $expense->formatted_amount }}
                                                </td>
                                                <td class="px-4 py-3 text-sm text-right">
                                                    <button wire:click="deleteExpense({{ $expense->id }})"
                                                            wire:confirm="Delete this expense?"
                                                            class="text-red-600 hover:text-red-800 dark:text-red-400">
                                                        Delete
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-8 bg-gray-50 dark:bg-zinc-700 rounded-lg">
                                <p class="text-sm text-gray-600 dark:text-gray-400">No expenses recorded</p>
                                <button wire:click="showCreateExpense"
                                        class="mt-2 text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                                    Record first expense
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

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
