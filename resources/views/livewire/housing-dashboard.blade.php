<div class="max-w-7xl mx-auto p-6">
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl p-6">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Housing Dashboard</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">Outstanding requirements across your projects.</p>
            </div>
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
