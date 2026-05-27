<div class="max-w-7xl mx-auto p-6">
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl p-6">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Housing Dashboard</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">Outstanding requirements across your projects.</p>
            </div>
            <a href="{{ route('communications-log.management') }}"
               class="text-sm text-indigo-600 dark:text-indigo-300 hover:underline mr-4">
                Open Communications Log →
            </a>
            <div class="inline-flex rounded-md border border-gray-300 dark:border-zinc-600 overflow-hidden" data-test="dashboard-tab-toggle">
                <button wire:click="setTab('projects')"
                        class="px-4 py-2 text-sm {{ $tab === 'projects' ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-zinc-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50' }}">
                    Per project
                </button>
                <button wire:click="setTab('requirements')"
                        class="px-4 py-2 text-sm {{ $tab === 'requirements' ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-zinc-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50' }}">
                    Per requirement
                </button>
            </div>
        </div>

        {{-- Phase 12.3: Pinned comm logs — compact-card row of the user's
             manually-pinned entries. Newest-pinned first, capped at 10. --}}
        @if($pinnedLogs->isNotEmpty())
            <section data-test="pinned-logs-card" class="mb-6 rounded-lg border border-amber-200 dark:border-amber-900/40 bg-amber-50/40 dark:bg-amber-900/10 overflow-hidden">
                <header class="flex items-center justify-between px-4 py-2.5 bg-amber-100/70 dark:bg-amber-900/30 border-b border-amber-200 dark:border-amber-900/40">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-amber-700 dark:text-amber-300" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M16 9V4l1 .01V2H7v2l1-.01V9l-2 4v2h5.2v7h1.6v-7H18v-2l-2-4z"/>
                        </svg>
                        <h2 class="text-sm font-semibold text-amber-900 dark:text-amber-100">Pinned</h2>
                        <span class="text-[11px] text-amber-800/70 dark:text-amber-200/70">Front-and-center cases you've pinned.</span>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-600 text-white">
                        {{ $pinnedLogs->count() }}
                    </span>
                </header>

                <div class="p-3 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                    @foreach($pinnedLogs as $pin)
                        <div class="relative group aspect-square rounded-md border border-amber-200 dark:border-amber-900/40 bg-white dark:bg-zinc-800 hover:shadow-md transition-shadow flex flex-col justify-between p-2.5">
                            <button type="button"
                                    wire:click="togglePin({{ $pin['id'] }})"
                                    title="Unpin"
                                    class="absolute top-1 right-1 text-amber-500 hover:text-red-600 opacity-60 hover:opacity-100">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M16 9V4l1 .01V2H7v2l1-.01V9l-2 4v2h5.2v7h1.6v-7H18v-2l-2-4z"/>
                                </svg>
                            </button>

                            <a href="{{ route('communications-log.management') }}?view={{ $pin['id'] }}"
                               class="block min-w-0">
                                <div class="text-[11px] uppercase tracking-wider text-amber-700 dark:text-amber-300 font-semibold mb-1">
                                    @if($pin['converted'])
                                        Converted
                                    @elseif($pin['priority'] === 'urgent' || $pin['priority'] === 'high')
                                        {{ ucfirst($pin['priority']) }}
                                    @else
                                        Intake
                                    @endif
                                </div>
                                <div class="text-sm font-medium text-gray-900 dark:text-white line-clamp-2 leading-snug">
                                    {{ $pin['name'] ?: 'Unnamed contact' }}
                                </div>
                                @if($pin['subject'])
                                    <div class="text-[11px] text-gray-500 dark:text-gray-400 line-clamp-2 mt-0.5">
                                        {{ $pin['subject'] }}
                                    </div>
                                @endif
                            </a>

                            <a href="{{ route('communications-log.management') }}?view={{ $pin['id'] }}"
                               class="self-end text-[11px] text-indigo-600 dark:text-indigo-300 hover:underline">
                                Open →
                            </a>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Phase 12.2/12.3: side-by-side intake-pipeline (closest to convert) +
             oldest-first non-converted logs. Renders only when at least one
             column has data. --}}
        @if($intakePipeline->isNotEmpty() || $oldestLogs->isNotEmpty())
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
                {{-- Closest to convert --}}
                @if($intakePipeline->isNotEmpty())
                    <section data-test="intake-pipeline-card" class="rounded-lg border border-indigo-200 dark:border-indigo-900/50 bg-indigo-50/50 dark:bg-indigo-900/10 overflow-hidden">
                        <header class="px-4 py-2.5 bg-indigo-100/70 dark:bg-indigo-900/30 border-b border-indigo-200 dark:border-indigo-900/50">
                            <h2 class="text-sm font-semibold text-indigo-900 dark:text-indigo-100">Closest to convert</h2>
                            <p class="text-[11px] text-indigo-800/70 dark:text-indigo-200/70">Phase 1 most-complete first.</p>
                        </header>
                        <ul class="divide-y divide-indigo-100 dark:divide-indigo-900/40">
                            @foreach($intakePipeline as $row)
                                @php
                                    $ready = $row['pct'] >= 100;
                                    $stale = !is_null($row['days_idle']) && $row['days_idle'] >= 7;
                                    $barColor = $ready ? 'bg-emerald-500' : ($row['pct'] >= 70 ? 'bg-amber-500' : 'bg-indigo-400');
                                @endphp
                                <li class="px-3 py-2 hover:bg-white/60 dark:hover:bg-zinc-800/40 flex items-center gap-2">
                                    <button type="button" wire:click="togglePin({{ $row['id'] }})"
                                            title="Pin to top"
                                            class="text-gray-400 hover:text-amber-600 shrink-0">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 9V4l1 .01V2H7v2l1-.01V9l-2 4v2h5.2v7h1.6v-7H18v-2l-2-4z"/>
                                        </svg>
                                    </button>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <a href="{{ route('communications-log.management') }}?view={{ $row['id'] }}"
                                               class="text-sm font-medium text-gray-900 dark:text-white hover:underline truncate">
                                                {{ $row['name'] }}
                                            </a>
                                            @if($ready)
                                                <span class="text-[10px] uppercase tracking-wider text-emerald-700 dark:text-emerald-300 bg-emerald-100 dark:bg-emerald-900/40 px-1.5 py-0.5 rounded">ready</span>
                                            @elseif($stale)
                                                <span class="text-[10px] uppercase tracking-wider text-amber-700 dark:text-amber-300 bg-amber-100 dark:bg-amber-900/40 px-1.5 py-0.5 rounded">follow-up</span>
                                            @endif
                                        </div>
                                        <div class="mt-1 flex items-center gap-2">
                                            <div class="flex-1 h-1.5 rounded-full bg-indigo-100 dark:bg-indigo-900/40 overflow-hidden">
                                                <div class="h-full {{ $barColor }}" style="width: {{ $row['pct'] }}%"></div>
                                            </div>
                                            <span class="text-[11px] tabular-nums text-gray-700 dark:text-gray-200 shrink-0">{{ $row['met'] }}/{{ $row['total'] }}</span>
                                            <span class="text-[11px] text-gray-500 dark:text-gray-400 shrink-0">
                                                @if($row['days_idle'] === null)
                                                    —
                                                @elseif($row['days_idle'] === 0)
                                                    today
                                                @else
                                                    {{ $row['days_idle'] }}d
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                {{-- Oldest first (languishing intakes) --}}
                @if($oldestLogs->isNotEmpty())
                    <section data-test="oldest-logs-card" class="rounded-lg border border-rose-200 dark:border-rose-900/40 bg-rose-50/40 dark:bg-rose-900/10 overflow-hidden">
                        <header class="px-4 py-2.5 bg-rose-100/70 dark:bg-rose-900/30 border-b border-rose-200 dark:border-rose-900/40">
                            <h2 class="text-sm font-semibold text-rose-900 dark:text-rose-100">Oldest open intakes</h2>
                            <p class="text-[11px] text-rose-800/70 dark:text-rose-200/70">Longest in queue first. Don't let them go stale.</p>
                        </header>
                        <ul class="divide-y divide-rose-100 dark:divide-rose-900/40">
                            @foreach($oldestLogs as $row)
                                <li class="px-3 py-2 hover:bg-white/60 dark:hover:bg-zinc-800/40 flex items-center gap-2">
                                    <button type="button" wire:click="togglePin({{ $row['id'] }})"
                                            title="Pin to top"
                                            class="text-gray-400 hover:text-amber-600 shrink-0">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 9V4l1 .01V2H7v2l1-.01V9l-2 4v2h5.2v7h1.6v-7H18v-2l-2-4z"/>
                                        </svg>
                                    </button>
                                    <div class="min-w-0 flex-1">
                                        <a href="{{ route('communications-log.management') }}?view={{ $row['id'] }}"
                                           class="text-sm font-medium text-gray-900 dark:text-white hover:underline truncate block">
                                            {{ $row['name'] }}
                                        </a>
                                        @if($row['subject'])
                                            <div class="text-[11px] text-gray-500 dark:text-gray-400 truncate">{{ $row['subject'] }}</div>
                                        @endif
                                    </div>
                                    <div class="text-right shrink-0">
                                        <div class="text-[11px] font-semibold text-rose-700 dark:text-rose-300 tabular-nums">
                                            @if($row['days_old'] === null)
                                                —
                                            @else
                                                {{ $row['days_old'] }}d
                                            @endif
                                        </div>
                                        <div class="text-[10px] text-gray-500 dark:text-gray-400">{{ $row['created_at']?->format('M j') }}</div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            </div>
        @endif


        @if($tab === 'projects')
            {{-- Cut A: per-project view --}}
            <div data-test="dashboard-cut-a">
                @if($projects->isEmpty())
                    <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                        No housing projects accessible to you yet.
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($projects as $project)
                            @php
                                $reqs = $project->requirements;
                                $outstanding = $reqs->where('status', 'outstanding')->count();
                                $total = $reqs->count();

                                // Soonest expiring fund attached to this project.
                                $soonestExpiringFund = $project->fundCodes
                                    ->filter(fn($fc) => $fc->expirationIndicator() !== null && $fc->expirationIndicator() !== 'expired')
                                    ->sortBy(fn($fc) => $fc->expires_at?->timestamp ?? PHP_INT_MAX)
                                    ->first();
                                $expiredFunds = $project->fundCodes->filter(fn($fc) => $fc->expirationIndicator() === 'expired');
                                $preStartFunds = $project->fundCodes->filter(fn($fc) => $fc->isStartingInFuture());

                                $hardOutstanding = $reqs->filter(function ($r) use ($hardTemplateIds) {
                                    return $r->source_type === 'fund_code'
                                        && $r->status === 'outstanding'
                                        && in_array($r->source_template_id, $hardTemplateIds, true);
                                })->count();

                                $blockedMilestones = $project->milestones->filter(fn($m) => in_array($m->id, $blockedMilestoneIds, true));
                                $overdueMilestones = $project->milestones->filter(fn($m) => $m->deadlineIndicator() === 'overdue');
                                $approachingMilestones = $project->milestones->filter(fn($m) => $m->deadlineIndicator() === 'approaching');
                            @endphp
                            <div class="border border-gray-200 dark:border-zinc-700 rounded-lg p-4 bg-gray-50 dark:bg-zinc-900"
                                 data-test="dashboard-project-row" data-project-id="{{ $project->id }}">
                                <div class="flex items-start justify-between gap-4 flex-wrap">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <a href="{{ route('project.management', $project->id) }}"
                                               wire:navigate
                                               class="font-semibold text-gray-900 dark:text-white hover:text-indigo-600">
                                                {{ $project->property?->address_line_1 ?? $project->job_title }}
                                            </a>
                                            @if($project->participant)
                                                <span class="text-sm text-gray-500 dark:text-gray-400">— {{ $project->participant->first_name }} {{ $project->participant->last_name }}</span>
                                            @endif
                                            <span class="text-xs font-mono text-gray-500 dark:text-gray-400">{{ $project->job_number }}</span>
                                        </div>
                                        <p class="text-sm text-gray-700 dark:text-gray-300 mt-1">
                                            <span data-test="outstanding-count">{{ $outstanding }} outstanding</span>
                                            <span class="text-gray-400">/ {{ $total }} total</span>
                                            @if($hardOutstanding > 0)
                                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">{{ $hardOutstanding }} hard</span>
                                            @endif
                                        </p>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2" data-test="indicators">
                                        @if($expiredFunds->isNotEmpty())
                                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-rose-100 text-rose-900 dark:bg-rose-950/40 dark:text-rose-300 ring-1 ring-rose-300 dark:ring-rose-800"
                                                  data-test="indicator-fund-expired">⨯ Fund expired</span>
                                        @endif
                                        @if($soonestExpiringFund)
                                            @php
                                                $bucket = $soonestExpiringFund->expirationIndicator();
                                                $days = (int) now()->startOfDay()->diffInDays($soonestExpiringFund->expires_at, false);
                                                $color = $bucket === 'urgent' ? 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200 ring-1 ring-red-300 dark:ring-red-700'
                                                                              : 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200 ring-1 ring-amber-300 dark:ring-amber-700';
                                            @endphp
                                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $color }}"
                                                  data-test="indicator-fund-{{ $bucket }}">⏱ Fund expires in {{ $days }}d</span>
                                        @endif
                                        @if($preStartFunds->isNotEmpty())
                                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200"
                                                  data-test="indicator-fund-prestart">▷ Fund starts {{ $preStartFunds->first()->starts_at->format('M j') }}</span>
                                        @endif
                                        @if($overdueMilestones->isNotEmpty())
                                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-rose-100 text-rose-900 dark:bg-rose-950/40 dark:text-rose-200 ring-1 ring-rose-300 dark:ring-rose-800"
                                                  data-test="indicator-milestone-overdue">⏰ {{ $overdueMilestones->count() }} overdue</span>
                                        @endif
                                        @if($approachingMilestones->isNotEmpty())
                                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200"
                                                  data-test="indicator-milestone-approaching">→ {{ $approachingMilestones->count() }} due soon</span>
                                        @endif
                                        @if($blockedMilestones->isNotEmpty())
                                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200"
                                                  data-test="indicator-blocked">⊘ {{ $blockedMilestones->count() }} blocked</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @else
            {{-- Cut B: per-requirement view --}}
            <div class="mb-4 grid grid-cols-1 md:grid-cols-5 gap-3" data-test="dashboard-filters">
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Owner</label>
                    <select wire:model.live="filter_owner_id"
                            class="w-full text-sm rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                        <option value="">Me</option>
                        <option value="0">Anyone (chase view)</option>
                        @foreach($userOptions as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                    <select wire:model.live="filter_status"
                            class="w-full text-sm rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                        <option value="outstanding">Outstanding</option>
                        <option value="complete">Complete</option>
                        <option value="not_applicable">Not applicable</option>
                        <option value="all">All</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                    <select wire:model.live="filter_type"
                            class="w-full text-sm rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                        <option value="all">All</option>
                        <option value="task">Task</option>
                        <option value="document">Document</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Source</label>
                    <select wire:model.live="filter_source"
                            class="w-full text-sm rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                        <option value="all">All</option>
                        <option value="milestone">Milestone</option>
                        <option value="fund_code">Fund code</option>
                        <option value="ad_hoc">Ad-hoc</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Project</label>
                    <select wire:model.live="filter_project_id"
                            class="w-full text-sm rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                        <option value="">All projects</option>
                        @foreach($projectOptions as $p)
                            <option value="{{ $p->id }}">{{ $p->property?->address_line_1 ?? $p->job_title }} ({{ $p->job_number }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div data-test="dashboard-cut-b">
                @if($requirements->isEmpty())
                    <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                        No requirements match the current filters.
                    </div>
                @else
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                        <thead class="bg-gray-50 dark:bg-zinc-900">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Requirement</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Source</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Project</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Owner</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Indicators</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-700">
                            @foreach($requirements as $req)
                                @php
                                    $job = $req->housingJob;
                                    $fundCode = $req->source_type === 'fund_code'
                                        ? $fundCodesById->get($req->source_id)
                                        : null;
                                    $milestone = $req->source_type === 'milestone'
                                        ? $job?->milestones?->firstWhere('id', $req->source_id)
                                        : null;
                                    $isBlocked = $milestone && in_array($milestone->id, $blockedMilestoneIds, true);
                                @endphp
                                <tr data-test="dashboard-requirement-row" data-requirement-id="{{ $req->id }}">
                                    <td class="px-4 py-2 text-sm">
                                        <div class="font-medium text-gray-900 dark:text-white">
                                            @if($req->type === 'document')📄@else✓@endif {{ $req->title }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">
                                        @if($req->source_type === 'fund_code')
                                            <span class="font-mono text-xs">{{ optional($fundCode)->code ?? '—' }}</span>
                                        @elseif($req->source_type === 'milestone')
                                            <span class="text-xs">{{ optional($milestone)->name ?? '(milestone deleted)' }}</span>
                                        @else
                                            <span class="text-xs italic">ad-hoc</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-sm">
                                        <a href="{{ route('project.management', $job->id) }}"
                                           wire:navigate
                                           class="text-indigo-600 hover:underline">
                                            {{ $job?->property?->address_line_1 ?? $job?->job_title }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">
                                        {{ $req->owner?->name ?? '—' }}
                                    </td>
                                    <td class="px-4 py-2 text-sm">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs {{ $req->isComplete() ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : ($req->isNotApplicable() ? 'bg-gray-200 text-gray-700' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300') }}">
                                            {{ ucfirst(str_replace('_', ' ', $req->status)) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-sm">
                                        <div class="flex flex-wrap gap-1" data-test="row-indicators">
                                            @if($fundCode)
                                                @php $bucket = $fundCode->expirationIndicator(); @endphp
                                                @if($bucket === 'expired')
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-rose-100 text-rose-900 dark:bg-rose-950/40 dark:text-rose-300 ring-1 ring-rose-300 dark:ring-rose-800"
                                                          data-test="row-indicator-fund-expired">expired</span>
                                                @elseif($bucket === 'urgent')
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200"
                                                          data-test="row-indicator-fund-urgent">urgent</span>
                                                @elseif($bucket === 'warning')
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200"
                                                          data-test="row-indicator-fund-warning">warning</span>
                                                @endif
                                                @if($fundCode->isStartingInFuture())
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200"
                                                          data-test="row-indicator-fund-prestart">pre-start</span>
                                                @endif
                                            @endif
                                            @if($milestone)
                                                @php $deadline = $milestone->deadlineIndicator(); @endphp
                                                @if($deadline === 'overdue')
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-rose-100 text-rose-900 dark:bg-rose-950/40 dark:text-rose-300 ring-1 ring-rose-300 dark:ring-rose-800"
                                                          data-test="row-indicator-milestone-overdue">overdue</span>
                                                @elseif($deadline === 'approaching')
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200"
                                                          data-test="row-indicator-milestone-approaching">due soon</span>
                                                @endif
                                                @if($isBlocked)
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200"
                                                          data-test="row-indicator-blocked">blocked</span>
                                                @endif
                                            @endif
                                            @if($req->wasOverridden())
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-200"
                                                      data-test="row-indicator-override">override</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        @endif
    </div>
</div>
