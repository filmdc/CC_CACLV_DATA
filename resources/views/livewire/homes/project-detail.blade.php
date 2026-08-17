<div class="px-6 lg:px-9 py-6 max-w-6xl mx-auto" style="min-height: 100vh;">

    @if (session('homes_message'))
        <div class="mb-4 rounded-md px-4 py-3 text-sm" style="background: var(--calv-sage-15); color: #2F5A41; border: 1px solid var(--calv-sage-35);">
            {{ session('homes_message') }}
        </div>
    @endif

    @php
        $statusBadge = [
            'attention' => ['label' => 'Needs attention', 'class' => 'calv-badge calv-badge--red'],
            'active' => ['label' => 'Moving along', 'class' => 'calv-badge calv-badge--teal'],
            'finished' => ['label' => 'Finished', 'class' => 'calv-badge calv-badge--sage'],
        ];
        $msBadge = [
            'completed' => ['label' => 'Done', 'class' => 'calv-badge calv-badge--sage'],
            'in_progress' => ['label' => 'In progress', 'class' => 'calv-badge calv-badge--teal'],
            'not_started' => ['label' => 'Waiting', 'class' => 'calv-badge'],
            'blocked' => ['label' => 'Waiting', 'class' => 'calv-badge'],
        ];
        $fundDot = [
            'soon' => 'var(--calv-red)', 'warning' => 'var(--calv-amber)', 'ok' => 'var(--calv-sage)',
            'perpetual' => 'var(--calv-slate-35)', 'expired' => 'var(--calv-slate)',
        ];
    @endphp

    <a href="{{ route('homes.projects') }}" wire:navigate class="calv-link inline-block mb-3" style="font-size: 12.5px;">← All projects</a>

    {{-- Header --}}
    <div class="flex items-start gap-5 mb-5 flex-wrap">
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-3 flex-wrap">
                <h1 class="calv-h1" style="font-size: 28px;">{{ $who }}</h1>
                <span class="{{ $statusBadge[$plainStatus]['class'] }}">{{ $statusBadge[$plainStatus]['label'] }}</span>
            </div>
            <div class="text-[13.5px] mt-1.5" style="color: var(--calv-slate-65);">
                @if($where){{ $where }} · @endif
                @if($job->program){{ $job->program->name }} · @endif
                {{ $job->job_title }} ·
                <span class="calv-subhead" style="font-size: 11px; letter-spacing: .05em;">{{ $job->job_number }}</span>
            </div>
            <div class="flex gap-1.5 mt-2.5 flex-wrap items-center">
                @foreach($job->housingServices as $county)
                    <span class="inline-flex items-center gap-1.5 text-[11.5px] font-semibold rounded px-2 py-1"
                          style="color: var(--calv-teal); background: var(--calv-teal-15); border: 1px solid var(--calv-teal-35);"
                          title="County{{ $county->trashed() ? ' (retired from the pick list)' : '' }}">
                        {{ $county->name }}
                    </span>
                @endforeach
                @foreach($job->fundCodes as $fund)
                    <span class="inline-flex items-center gap-1.5 text-[11.5px] font-semibold rounded px-2 py-1" style="color: var(--calv-slate); background: var(--calv-sand-15); border: 1px solid var(--calv-slate-15);" title="{{ $fund->description }}">
                        <span style="width: 8px; height: 8px; border-radius: 999px; background: {{ $fundDot[$fund->expiry_state] ?? 'var(--calv-sage)' }};"></span>
                        {{ $fund->code }}
                        @if($fund->expiry_state === 'soon')<span style="color: var(--calv-red);">{{ $fund->days_to_expiry }}d</span>@endif
                    </span>
                @endforeach
            </div>
        </div>
        <div class="flex gap-2 shrink-0">
            <button type="button" wire:click="openEditJob" class="calv-btn calv-btn--ghost">Edit</button>
            @can('communications_log.create')
                <button type="button" x-on:click="Livewire.dispatch('homes-open-quick-log')" class="calv-btn calv-btn--ghost">Log a call</button>
            @endcan
            @if($budgets->count() > 0)
                <button type="button" wire:click="openExpense" class="calv-btn calv-btn--secondary">$ Add expense</button>
            @endif
        </div>
    </div>

    <div class="grid gap-4 items-start" style="grid-template-columns: minmax(0,1.6fr) minmax(0,1fr);">

        {{-- Milestones --}}
        <section class="calv-card overflow-hidden">
            <header class="flex items-center gap-2.5 px-4 py-3" style="border-bottom: 1px solid var(--calv-slate-15);">
                <h3 class="calv-subhead flex-1" style="font-size: 12px;">Milestones</h3>
                <span class="text-[11.5px]" style="color: var(--calv-slate-65);">{{ $msDone }} of {{ $msTotal }} done · click a row for its requirements</span>
                <button type="button" wire:click="openMilestoneForm" class="calv-btn calv-btn--quiet calv-btn--sm">+ Milestone</button>
            </header>
            @forelse($milestones as $m)
                @php $open = $openMilestone === $m['id']; @endphp
                <div style="border-bottom: 1px solid var(--calv-slate-15);">
                    <button type="button" wire:click="toggleMilestone({{ $m['id'] }})"
                            class="flex items-center gap-3 w-full px-4 py-3 text-left"
                            style="background: {{ $open ? 'var(--calv-sand-15)' : '#fff' }}; border: none; cursor: pointer;">
                        <span class="inline-flex items-center justify-center shrink-0" style="width: 18px; height: 18px; border-radius: 999px; background: {{ $m['status'] === 'completed' ? 'var(--calv-sage)' : ($m['status'] === 'in_progress' ? 'var(--calv-teal-15)' : 'var(--calv-slate-15)') }};">
                            @if($m['status'] === 'completed')
                                <svg width="10" height="10" viewBox="0 0 12 12" fill="none"><path d="M2.5 6.5L5 9L9.5 3.5" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            @else
                                <span style="width: 6px; height: 6px; border-radius: 999px; background: {{ $m['status'] === 'in_progress' ? 'var(--calv-teal)' : 'var(--calv-slate-35)' }};"></span>
                            @endif
                        </span>
                        <span class="flex-1 min-w-0">
                            <span class="block text-[13.5px] font-semibold" style="color: var(--calv-slate);">{{ $m['name'] }}</span>
                            @if($m['blocked_by'] && $m['status'] !== 'completed')
                                <span class="block text-[11px] mt-0.5" style="color: var(--calv-slate-65);">Waiting on: {{ $m['blocked_by'] }}</span>
                            @endif
                        </span>
                        @if($m['reqs_total'] > 0)
                            <span class="text-[11px] whitespace-nowrap" style="color: var(--calv-slate-65);">{{ $m['reqs_met'] }}/{{ $m['reqs_total'] }} reqs</span>
                        @endif
                        <span class="{{ $msBadge[$m['effective_status']]['class'] ?? 'calv-badge' }}">{{ $msBadge[$m['effective_status']]['label'] ?? $m['status'] }}</span>
                        <span class="text-[11px] whitespace-nowrap text-right" style="color: var(--calv-slate-65); width: 48px;">{{ $m['target'] ?? '—' }}</span>
                        <span style="color: var(--calv-slate-65); transform: {{ $open ? 'rotate(90deg)' : 'none' }}; transition: transform .15s;">▸</span>
                    </button>
                    @if($open)
                        <div class="flex flex-col gap-1.5 px-4 pb-3.5" style="padding-left: 46px;">
                            @forelse($m['reqs'] as $r)
                                <div class="flex items-center gap-2.5 text-[12.5px]">
                                    <span class="inline-flex items-center justify-center shrink-0" style="width: 14px; height: 14px; border-radius: 3px; {{ ($r['complete'] || $r['na']) ? 'background: var(--calv-sage);' : 'border: 1.5px solid var(--calv-slate-35); background: #fff;' }}">
                                        @if($r['complete'] || $r['na'])<svg width="9" height="9" viewBox="0 0 12 12" fill="none"><path d="M2.5 6.5L5 9L9.5 3.5" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>@endif
                                    </span>
                                    <span class="flex-1 min-w-0" style="color: {{ ($r['complete'] || $r['na']) ? 'var(--calv-slate-65)' : 'var(--calv-slate)' }}; {{ $r['complete'] ? 'text-decoration: line-through;' : '' }}">
                                        {{ $r['title'] }}@if($r['na']) <span class="text-[10.5px]">(N/A)</span>@endif
                                    </span>
                                    <span class="text-[11px] shrink-0" style="color: var(--calv-slate-65);">{{ $r['owner'] }}</span>
                                    @if(!$r['complete'] && !$r['na'])
                                        @if($r['type'] === 'task')
                                            <button type="button" wire:click="completeTaskRequirement({{ $r['id'] }})" class="calv-btn calv-btn--quiet calv-btn--sm shrink-0">Mark done</button>
                                        @else
                                            <button type="button" wire:click="openCompletionModal({{ $r['id'] }})" class="calv-btn calv-btn--quiet calv-btn--sm shrink-0">Mark received</button>
                                        @endif
                                    @endif
                                </div>
                            @empty
                                <div class="text-[12px]" style="color: var(--calv-slate-65);">No requirements on this milestone.</div>
                            @endforelse

                            {{-- Add a requirement to this milestone --}}
                            @if($addingReqMilestoneId === $m['id'])
                                <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                                    <input wire:model="newReqTitle" type="text" class="calv-input" style="max-width: 260px;" placeholder="What's required? e.g. Roofing permit">
                                    <select wire:model="newReqType" class="calv-input" style="width: auto;">
                                        <option value="document">Document</option>
                                        <option value="task">Task</option>
                                    </select>
                                    <button type="button" wire:click="saveNewRequirement" class="calv-btn calv-btn--primary calv-btn--sm">Add</button>
                                    <button type="button" wire:click="$set('addingReqMilestoneId', null)" class="calv-btn calv-btn--ghost calv-btn--sm">Cancel</button>
                                </div>
                                @error('newReqTitle')<p class="text-xs mt-1" style="color: var(--calv-red);">{{ $message }}</p>@enderror
                            @else
                                <button type="button" wire:click="openAddRequirement({{ $m['id'] }})" class="calv-link text-left mt-1" style="background:none;border:none;cursor:pointer;font-size:12px;">+ add a requirement</button>
                            @endif

                            {{-- Milestone status actions --}}
                            <div class="flex items-center gap-2 mt-2 pt-2" style="border-top: 1px dashed var(--calv-slate-15);">
                                @if($m['status'] === 'not_started')
                                    <button type="button" wire:click="updateMilestoneStatus({{ $m['id'] }}, 'in_progress')" class="calv-btn calv-btn--quiet calv-btn--sm">Start milestone</button>
                                @elseif($m['status'] === 'in_progress')
                                    <button type="button" wire:click="updateMilestoneStatus({{ $m['id'] }}, 'completed')" class="calv-btn calv-btn--secondary calv-btn--sm">Mark milestone done</button>
                                @endif
                                @if($m['target'])<span class="text-[11px]" style="color: var(--calv-slate-65);">target {{ $m['target'] }}</span>@endif
                            </div>
                        </div>
                    @endif
                </div>
            @empty
                <div class="px-4 py-6 text-center text-sm" style="color: var(--calv-slate-65);">No milestones yet — they're managed on the main app's project page.</div>
            @endforelse
        </section>

        {{-- Right column --}}
        <div class="flex flex-col gap-4">

            {{-- Budget --}}
            <section class="calv-card overflow-hidden">
                <header class="flex items-center px-4 py-3" style="border-bottom: 1px solid var(--calv-slate-15);">
                    <h3 class="calv-subhead flex-1" style="font-size: 12px;">Budget</h3>
                    <span class="text-xs font-semibold" style="color: var(--calv-slate);">${{ number_format($totalSpent) }} <span style="color: var(--calv-slate-65); font-weight: 400;">of ${{ number_format($totalBudget) }}</span></span>
                </header>
                <div class="px-4 py-3.5 flex flex-col gap-3">
                    @forelse($budgets as $b)
                        <div>
                            <div class="flex justify-between text-[12px] mb-1">
                                <span class="font-semibold" style="color: var(--calv-slate);">{{ $b->category }}</span>
                                <span style="color: {{ $b->is_over_budget ? 'var(--calv-red)' : 'var(--calv-slate-65)' }};">
                                    ${{ number_format((float) $b->spent_amount) }} / ${{ number_format((float) $b->budgeted_amount) }}@if($b->fundCode) · {{ $b->fundCode->code }}@endif
                                </span>
                            </div>
                            @php $pct = (float) $b->budgeted_amount > 0 ? min(100, (int) round((float) $b->spent_amount / (float) $b->budgeted_amount * 100)) : 0; @endphp
                            <div style="background: var(--calv-slate-15); border-radius: 999px; height: 6px; overflow: hidden;">
                                <div style="width: {{ $pct }}%; height: 100%; background: {{ $b->is_over_budget ? 'var(--calv-red)' : ($pct >= 100 ? 'var(--calv-sage)' : 'var(--calv-teal)') }}; border-radius: 999px;"></div>
                            </div>
                        </div>
                    @empty
                        <div class="text-[12.5px]" style="color: var(--calv-slate-65);">No budget lines yet.</div>
                    @endforelse
                    <div class="flex items-center gap-3">
                        @if($job->fundCodes->count() > 0)
                            <button type="button" wire:click="openBudgetForm" class="calv-link text-left" style="background: none; border: none; cursor: pointer; font-size: 12px;">+ Add a budget line</button>
                        @else
                            <span class="text-[11.5px]" style="color: var(--calv-slate-65);">Attach a fund code to this project (main app) before adding budget lines.</span>
                        @endif
                        @if($budgets->count() > 0)
                            <button type="button" wire:click="openExpense" class="calv-link text-left" style="background: none; border: none; cursor: pointer; font-size: 12px;">+ Add an expense</button>
                        @endif
                    </div>
                </div>
            </section>

            {{-- Where this came from --}}
            @if($origin)
                <section class="calv-card overflow-hidden">
                    <header class="px-4 py-3" style="border-bottom: 1px solid var(--calv-slate-15);">
                        <h3 class="calv-subhead" style="font-size: 12px;">Where this came from</h3>
                    </header>
                    <div class="px-4 py-3 text-[12.5px] leading-relaxed" style="color: var(--calv-slate-65);">
                        Converted from intake
                        <a href="{{ route('homes.call', $origin->id) }}" wire:navigate class="calv-link">COMM-{{ $origin->id }}</a>
                        — all calls and documents from before the project stay linked there.
                    </div>
                </section>
            @endif

            {{-- Next --}}
            <div class="px-4 py-3.5 text-[12.5px] leading-relaxed" style="background: #fff; border: 1px dashed var(--calv-slate-35); border-radius: 8px; color: var(--calv-slate-65);">
                <strong style="color: var(--calv-slate);">Next:</strong> {{ $next['text'] }} @if($next['due'] !== '—')({{ $next['due'] }})@endif
            </div>
        </div>
    </div>

    {{-- Document completion modal --}}
    @if($completingRequirementId)
        <div class="fixed inset-0 z-[100] flex items-start justify-center overflow-y-auto" style="background: rgba(40,42,44,.55); padding: 64px 16px;"
             x-data x-on:keydown.escape.window="$wire.closeCompletionModal()" wire:click.self="closeCompletionModal">
            <div class="w-full" style="max-width: 460px; background: #fff; border-radius: 10px; box-shadow: var(--shadow-lg); overflow: hidden;" wire:click.stop>
                <header class="px-6 py-4" style="border-bottom: 1px solid var(--calv-slate-15);">
                    <h2 class="calv-h1" style="font-size: 20px;">Mark received</h2>
                    <div class="text-xs mt-1" style="color: var(--calv-slate-65);">Attach the document — it lands on the requirement's file list.</div>
                </header>
                <div class="px-6 py-5 flex flex-col gap-3.5">
                    <div>
                        <label class="calv-label">Files</label>
                        <input type="file" wire:model="completionFiles" multiple class="calv-input">
                        <div wire:loading wire:target="completionFiles" class="text-xs mt-1" style="color: var(--calv-slate-65);">Uploading…</div>
                        @error('completionFiles')<p class="mt-1 text-xs" style="color: var(--calv-red);">{{ $message }}</p>@enderror
                        @error('completionFiles.*')<p class="mt-1 text-xs" style="color: var(--calv-red);">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="calv-label">Note <span style="font-weight:400; text-transform:none; letter-spacing:0; color: var(--calv-slate-65);">— optional</span></label>
                        <input wire:model="completionNote" type="text" class="calv-input">
                    </div>
                </div>
                <footer class="flex items-center gap-2.5 px-6 py-3.5 justify-end" style="border-top: 1px solid var(--calv-slate-15); background: var(--calv-sand-15);">
                    <button type="button" wire:click="closeCompletionModal" class="calv-btn calv-btn--ghost">Cancel</button>
                    <button type="button" wire:click="saveRequirementCompletion" class="calv-btn calv-btn--primary" wire:loading.attr="disabled" wire:target="saveRequirementCompletion,completionFiles">
                        <span wire:loading.remove wire:target="saveRequirementCompletion">Save</span>
                        <span wire:loading wire:target="saveRequirementCompletion">Saving…</span>
                    </button>
                </footer>
            </div>
        </div>
    @endif

    {{-- Add expense modal --}}
    @if($showExpense)
        <div class="fixed inset-0 z-[100] flex items-start justify-center overflow-y-auto" style="background: rgba(40,42,44,.55); padding: 64px 16px;"
             x-data x-on:keydown.escape.window="$wire.closeExpense()" wire:click.self="closeExpense">
            <div class="w-full" style="max-width: 480px; background: #fff; border-radius: 10px; box-shadow: var(--shadow-lg); overflow: hidden;" wire:click.stop>
                <header class="px-6 py-4" style="border-bottom: 1px solid var(--calv-slate-15);">
                    <h2 class="calv-h1" style="font-size: 20px;">Add expense</h2>
                    <div class="text-xs mt-1" style="color: var(--calv-slate-65);">{{ $who }}@if($where) · {{ $where }}@endif</div>
                </header>
                <div class="px-6 py-5 flex flex-col gap-3.5">
                    <div>
                        <label class="calv-label">Which budget line?</label>
                        <div class="flex gap-2 flex-wrap">
                            @foreach($budgets as $b)
                                @php $active = $expenseCategory === $b->category; @endphp
                                <button type="button" wire:click="$set('expenseCategory', @js($b->category))"
                                        style="font-family: var(--font-body); font-size: 13px; padding: 8px 14px; border-radius: 999px; cursor: pointer;
                                               background: {{ $active ? 'var(--calv-teal)' : '#fff' }}; color: {{ $active ? '#fff' : 'var(--calv-slate)' }};
                                               border: 1px solid {{ $active ? 'var(--calv-teal)' : 'var(--calv-slate-35)' }};">
                                    {{ $b->category }}
                                </button>
                            @endforeach
                        </div>
                        @php $selected = $budgets->firstWhere('category', $expenseCategory); @endphp
                        @if($selected)
                            <div class="text-[11.5px] mt-1.5" style="color: var(--calv-slate-65);">
                                @if($selected->fundCode)Pays from <strong>{{ $selected->fundCode->code }}</strong> — @endif
                                ${{ number_format((float) $selected->remaining_amount) }} left of ${{ number_format((float) $selected->budgeted_amount) }}.
                            </div>
                        @endif
                        @error('expenseCategory')<p class="mt-1 text-xs" style="color: var(--calv-red);">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid gap-3" style="grid-template-columns: 1fr 1fr;">
                        <div>
                            <label class="calv-label">Amount</label>
                            <input wire:model="expenseAmount" type="number" step="0.01" class="calv-input" placeholder="0.00">
                            @error('expenseAmount')<p class="mt-1 text-xs" style="color: var(--calv-red);">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="calv-label">Date</label>
                            <input wire:model="expenseDate" type="date" class="calv-input">
                            @error('expenseDate')<p class="mt-1 text-xs" style="color: var(--calv-red);">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div>
                        <label class="calv-label">Paid to <span style="font-weight:400; text-transform:none; letter-spacing:0; color: var(--calv-slate-65);">— optional</span></label>
                        <input wire:model="expensePaidTo" type="text" class="calv-input" placeholder="Contractor or vendor">
                    </div>
                    <div>
                        <label class="calv-label">What was it for? <span style="font-weight:400; text-transform:none; letter-spacing:0; color: var(--calv-slate-65);">— optional</span></label>
                        <textarea wire:model="expenseDescription" rows="2" class="calv-input" style="resize: vertical;" placeholder="e.g. Shingles + underlayment, first delivery"></textarea>
                    </div>
                </div>
                <footer class="flex items-center gap-2.5 px-6 py-3.5 justify-end" style="border-top: 1px solid var(--calv-slate-15); background: var(--calv-sand-15);">
                    <button type="button" wire:click="closeExpense" class="calv-btn calv-btn--ghost">Cancel</button>
                    <button type="button" wire:click="saveExpense" class="calv-btn calv-btn--primary" wire:loading.attr="disabled" wire:target="saveExpense">
                        <span wire:loading.remove wire:target="saveExpense">Save expense</span>
                        <span wire:loading wire:target="saveExpense">Saving…</span>
                    </button>
                </footer>
            </div>
        </div>
    @endif

    {{-- Edit project modal --}}
    @if($showEditJob)
        <div class="fixed inset-0 z-[100] flex items-start justify-center overflow-y-auto" style="background: rgba(40,42,44,.55); padding: 56px 16px;"
             x-data x-on:keydown.escape.window="$wire.closeEditJob()" wire:click.self="closeEditJob">
            <div class="w-full" style="max-width: 540px; background: #fff; border-radius: 10px; box-shadow: var(--shadow-lg); overflow: hidden;" wire:click.stop>
                <header class="px-6 py-4" style="border-bottom: 1px solid var(--calv-slate-15);">
                    <h2 class="calv-h1" style="font-size: 20px;">Edit project</h2>
                    <div class="text-xs mt-1" style="color: var(--calv-slate-65);">{{ $job->job_number }}</div>
                </header>
                <div class="px-6 py-5 flex flex-col gap-3.5">
                    <div>
                        <label class="calv-label">Project name</label>
                        <input wire:model="editJob.job_title" type="text" class="calv-input">
                        @error('editJob.job_title')<p class="mt-1 text-xs" style="color: var(--calv-red);">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="calv-label">Scope of work</label>
                        <textarea wire:model="editJob.job_description" rows="3" class="calv-input" style="resize: vertical;"></textarea>
                    </div>
                    <div class="grid gap-3" style="grid-template-columns: 1fr 1fr;">
                        <div>
                            <label class="calv-label">Status</label>
                            <select wire:model="editJob.status" class="calv-input">
                                @foreach(['pending' => 'Pending', 'approved' => 'Approved', 'in_progress' => 'In progress', 'on_hold' => 'On hold', 'completed' => 'Completed', 'cancelled' => 'Cancelled', 'rejected' => 'Rejected'] as $v => $l)
                                    <option value="{{ $v }}">{{ $l }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="calv-label">Priority</label>
                            <select wire:model="editJob.priority" class="calv-input">
                                @foreach(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'emergency' => 'Emergency'] as $v => $l)
                                    <option value="{{ $v }}">{{ $l }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="calv-label">Start date</label>
                            <input wire:model="editJob.start_date" type="date" class="calv-input">
                        </div>
                        <div>
                            <label class="calv-label">Target completion</label>
                            <input wire:model="editJob.target_completion_date" type="date" class="calv-input">
                        </div>
                    </div>
                </div>
                <footer class="flex items-center gap-2.5 px-6 py-3.5 justify-end" style="border-top: 1px solid var(--calv-slate-15); background: var(--calv-sand-15);">
                    <button type="button" wire:click="closeEditJob" class="calv-btn calv-btn--ghost">Cancel</button>
                    <button type="button" wire:click="saveEditJob" class="calv-btn calv-btn--primary" wire:loading.attr="disabled" wire:target="saveEditJob">
                        <span wire:loading.remove wire:target="saveEditJob">Save changes</span>
                        <span wire:loading wire:target="saveEditJob">Saving…</span>
                    </button>
                </footer>
            </div>
        </div>
    @endif

    {{-- Add milestone modal --}}
    @if($showMilestoneForm)
        <div class="fixed inset-0 z-[100] flex items-start justify-center overflow-y-auto" style="background: rgba(40,42,44,.55); padding: 64px 16px;"
             x-data x-on:keydown.escape.window="$wire.set('showMilestoneForm', false)" wire:click.self="$set('showMilestoneForm', false)">
            <div class="w-full" style="max-width: 460px; background: #fff; border-radius: 10px; box-shadow: var(--shadow-lg); overflow: hidden;" wire:click.stop>
                <header class="px-6 py-4" style="border-bottom: 1px solid var(--calv-slate-15);">
                    <h2 class="calv-h1" style="font-size: 20px;">Add milestone</h2>
                </header>
                <div class="px-6 py-5 flex flex-col gap-3.5">
                    <div>
                        <label class="calv-label">Name</label>
                        <input wire:model="milestoneName" type="text" class="calv-input" placeholder="e.g. Final inspection">
                        @error('milestoneName')<p class="mt-1 text-xs" style="color: var(--calv-red);">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="calv-label">Description <span style="font-weight:400; text-transform:none; letter-spacing:0; color: var(--calv-slate-65);">— optional</span></label>
                        <textarea wire:model="milestoneDescription" rows="2" class="calv-input" style="resize: vertical;"></textarea>
                    </div>
                    <div>
                        <label class="calv-label">Target date <span style="font-weight:400; text-transform:none; letter-spacing:0; color: var(--calv-slate-65);">— optional</span></label>
                        <input wire:model="milestoneTargetDate" type="date" class="calv-input" style="max-width: 200px;">
                    </div>
                </div>
                <footer class="flex items-center gap-2.5 px-6 py-3.5 justify-end" style="border-top: 1px solid var(--calv-slate-15); background: var(--calv-sand-15);">
                    <button type="button" wire:click="$set('showMilestoneForm', false)" class="calv-btn calv-btn--ghost">Cancel</button>
                    <button type="button" wire:click="saveMilestone" class="calv-btn calv-btn--primary" wire:loading.attr="disabled" wire:target="saveMilestone">
                        <span wire:loading.remove wire:target="saveMilestone">Add milestone</span>
                        <span wire:loading wire:target="saveMilestone">Saving…</span>
                    </button>
                </footer>
            </div>
        </div>
    @endif

    {{-- Add budget line modal --}}
    @if($showBudgetForm)
        <div class="fixed inset-0 z-[100] flex items-start justify-center overflow-y-auto" style="background: rgba(40,42,44,.55); padding: 64px 16px;"
             x-data x-on:keydown.escape.window="$wire.set('showBudgetForm', false)" wire:click.self="$set('showBudgetForm', false)">
            <div class="w-full" style="max-width: 460px; background: #fff; border-radius: 10px; box-shadow: var(--shadow-lg); overflow: hidden;" wire:click.stop>
                <header class="px-6 py-4" style="border-bottom: 1px solid var(--calv-slate-15);">
                    <h2 class="calv-h1" style="font-size: 20px;">Add budget line</h2>
                    <div class="text-xs mt-1" style="color: var(--calv-slate-65);">A category of work funded from one of this project's funds.</div>
                </header>
                <div class="px-6 py-5 flex flex-col gap-3.5">
                    <div>
                        <label class="calv-label">Category</label>
                        <input wire:model="budgetCategory" type="text" class="calv-input" placeholder="e.g. Roofing">
                        @error('budgetCategory')<p class="mt-1 text-xs" style="color: var(--calv-red);">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid gap-3" style="grid-template-columns: 1fr 1fr;">
                        <div>
                            <label class="calv-label">Amount</label>
                            <input wire:model="budgetAmount" type="number" step="0.01" min="0" class="calv-input" placeholder="0.00">
                            @error('budgetAmount')<p class="mt-1 text-xs" style="color: var(--calv-red);">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="calv-label">Fund</label>
                            <select wire:model="budgetFundCodeId" class="calv-input">
                                @foreach($job->fundCodes as $fund)
                                    <option value="{{ $fund->id }}">{{ $fund->code }}</option>
                                @endforeach
                            </select>
                            @error('budgetFundCodeId')<p class="mt-1 text-xs" style="color: var(--calv-red);">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div>
                        <label class="calv-label">Milestone <span style="font-weight:400; text-transform:none; letter-spacing:0; color: var(--calv-slate-65);">— optional anchor</span></label>
                        <select wire:model="budgetMilestoneId" class="calv-input">
                            <option value="">—</option>
                            @foreach($milestones as $m)
                                <option value="{{ $m['id'] }}">{{ $m['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <footer class="flex items-center gap-2.5 px-6 py-3.5 justify-end" style="border-top: 1px solid var(--calv-slate-15); background: var(--calv-sand-15);">
                    <button type="button" wire:click="$set('showBudgetForm', false)" class="calv-btn calv-btn--ghost">Cancel</button>
                    <button type="button" wire:click="saveBudgetLine" class="calv-btn calv-btn--primary" wire:loading.attr="disabled" wire:target="saveBudgetLine">
                        <span wire:loading.remove wire:target="saveBudgetLine">Add budget line</span>
                        <span wire:loading wire:target="saveBudgetLine">Saving…</span>
                    </button>
                </footer>
            </div>
        </div>
    @endif
</div>
