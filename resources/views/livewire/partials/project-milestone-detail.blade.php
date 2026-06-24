{{--
    Project Detail redesign §4.3 — Milestone detail right pane.
    Renders inside the Work tab two-pane grid; visible when a milestone is
    selected.

    Receives:
        $m            — the selected ProjectMilestone
        $reqs         — Collection of gating Requirements (own + via fund-code)
        $blockedBy    — Collection of upstream milestones still incomplete
        $msStatusTone — array status => [label,bg,fg,dot] (defined in parent blade)
        $budget       — ProjectBudget|null tied to this milestone
--}}
@php
    $reqMet = $reqs->where('status', 'complete')->count();
    $reqTotal = $reqs->count();
    $isBlocked = $blockedBy->isNotEmpty() && $m->status !== 'completed';
    $effectiveStatus = $isBlocked ? 'blocked' : $m->status;
    $tone = $msStatusTone[$effectiveStatus] ?? $msStatusTone['pending'];
    $canMarkComplete = $blockedBy->isEmpty() && $reqs->where('status', 'outstanding')->isEmpty();
    $dependents = $m->dependents ?? collect();
    $firstFundCode = $m->fundCodes->first() ?? null;
@endphp

<div style="padding: 24px 32px 40px; max-width: 880px;" data-test="milestone-detail">
    {{-- Detail header: H2 + status, meta line, status select + ⋯ overflow menu --}}
    <div class="flex items-start gap-4" style="margin-bottom: 6px;">
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-3 flex-wrap" style="margin-bottom: 6px;">
                <h2 class="m-0"
                    style="font-family: var(--font-calv-h1); font-size: 26px; font-weight: 700; letter-spacing: 0.01em; text-transform: uppercase; color: var(--color-calv-slate); line-height: 1;">
                    {{ $m->name }}
                </h2>
                <span class="inline-flex items-center gap-1.5 rounded-full whitespace-nowrap"
                      style="padding: 3px 9px; font-family: var(--font-calv-sub); font-size: 10px; font-weight: 700; letter-spacing: 0.07em; text-transform: uppercase; background: {{ $tone['bg'] }}; color: {{ $tone['fg'] }};">
                    <span class="rounded-full" style="width: 6px; height: 6px; background: {{ $tone['dot'] }};"></span>
                    {{ $tone['label'] }}
                </span>
                @if($m->isPromoted())
                    <span style="font-family: var(--font-calv-sub); font-size: 10px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: var(--color-calv-teal);"
                          title="Auto-seeded from the requirements library">★ default Phase 2</span>
                @endif
            </div>
            <div class="flex gap-3.5 flex-wrap" style="font-size: 12.5px; color: var(--color-calv-slate-65);">
                <span>Started: <strong style="color: var(--color-calv-slate);">{{ $m->started_at?->format('M d, Y') ?: '—' }}</strong></span>
                @if($m->target_date)
                    <span>Target: <strong style="color: var(--color-calv-slate);">{{ $m->target_date->format('M d, Y') }}</strong></span>
                @endif
                @if($budget)
                    <span>Scope: <strong style="color: var(--color-calv-slate);">{{ $budget->category }}</strong></span>
                @endif
                @if($firstFundCode)
                    <span>Fund: <strong style="font-family: ui-monospace, Menlo, monospace; color: var(--color-calv-slate);">{{ $firstFundCode->code }}</strong></span>
                @endif
            </div>
        </div>

        {{-- Status control + overflow menu (§5.4 safe destructive actions) --}}
        <div class="flex items-center gap-2 shrink-0 relative" x-data="{ menuOpen: false }">
            @if($this->can('projects.manage') && $m->status !== 'completed')
                <button wire:click="updateMilestoneStatus({{ $m->id }}, 'in_progress')"
                        @if($m->status === 'in_progress') disabled @endif
                        x-show="'{{ $m->status }}' !== 'in_progress'"
                        title="{{ $isBlocked ? 'Blocked by upstream milestones.' : 'Move to in-progress.' }}"
                        @if($isBlocked) disabled @endif
                        class="rounded-md transition"
                        style="padding: 6px 11px; font-family: var(--font-calv-sub); font-size: 11px; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; background: {{ $isBlocked ? 'var(--color-calv-slate-15)' : 'var(--color-calv-teal)' }}; color: {{ $isBlocked ? 'var(--color-calv-slate-65)' : '#fff' }}; border: none; cursor: {{ $isBlocked ? 'not-allowed' : 'pointer' }};">
                    Start
                </button>
                <button wire:click="updateMilestoneStatus({{ $m->id }}, 'completed')"
                        @if(! $canMarkComplete) disabled @endif
                        title="{{ $canMarkComplete ? 'Mark milestone complete.' : 'All gating requirements and upstream dependencies must complete first.' }}"
                        class="rounded-md transition"
                        style="padding: 6px 11px; font-family: var(--font-calv-sub); font-size: 11px; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; background: {{ $canMarkComplete ? 'var(--color-calv-sage)' : 'var(--color-calv-slate-15)' }}; color: {{ $canMarkComplete ? '#fff' : 'var(--color-calv-slate-65)' }}; border: none; cursor: {{ $canMarkComplete ? 'pointer' : 'not-allowed' }};">
                    Complete
                </button>
            @elseif($m->status === 'completed')
                <button wire:click="updateMilestoneStatus({{ $m->id }}, 'in_progress')"
                        class="rounded-md transition"
                        style="padding: 6px 11px; font-family: var(--font-calv-sub); font-size: 11px; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; background: transparent; color: var(--color-calv-slate); border: 1px solid var(--color-calv-slate-35);">
                    Reopen
                </button>
            @endif

            @if($this->can('projects.manage'))
                <button @click="menuOpen = !menuOpen" aria-label="More" data-test="milestone-overflow-btn"
                        class="rounded-md"
                        style="width: 34px; height: 34px; border: 1px solid var(--color-calv-slate-35); background: #fff; color: var(--color-calv-slate-65); cursor: pointer; font-size: 18px; line-height: 1;">
                    ⋯
                </button>
                <div x-show="menuOpen" x-cloak @click.outside="menuOpen = false"
                     class="absolute z-10 bg-white rounded-md"
                     style="top: 40px; right: 0; border: 1px solid var(--color-calv-slate-15); box-shadow: var(--shadow-calv-md); min-width: 180px; padding: 4px;">
                    <button wire:click="showEditMilestone({{ $m->id }})" @click="menuOpen = false"
                            class="block w-full text-left rounded"
                            style="padding: 7px 10px; font-size: 13px; color: var(--color-calv-slate); background: transparent; border: none; cursor: pointer;">
                        Edit milestone
                    </button>
                    <div style="height: 1px; background: var(--color-calv-slate-15); margin: 4px 0;"></div>
                    <button wire:click="deleteMilestone({{ $m->id }})"
                            wire:confirm="Delete this milestone? Dependent milestones will lose their gate."
                            class="block w-full text-left rounded"
                            data-test="milestone-delete-btn"
                            style="padding: 7px 10px; font-size: 13px; color: var(--color-calv-red); background: transparent; border: none; cursor: pointer;">
                        Delete milestone
                    </button>
                </div>
            @endif
        </div>
    </div>

    {{-- Dependency tray — both directions surfaced (red blocked-by / teal unblocks) --}}
    @if($blockedBy->isNotEmpty() || $dependents->isNotEmpty())
        <div class="flex gap-4 flex-wrap rounded-md"
             style="padding: 10px 14px; margin-top: 14px; background: var(--color-calv-sand-15); border: 1px solid var(--color-calv-sand-35);"
             data-test="dependency-tray">
            @if($blockedBy->isNotEmpty())
                <div class="flex items-center gap-2 flex-wrap">
                    <span style="font-family: var(--font-calv-sub); font-size: 9.5px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--color-calv-slate-65);">
                        Blocked by
                    </span>
                    @foreach($blockedBy as $up)
                        <span class="rounded-full"
                              style="padding: 3px 9px; font-family: var(--font-calv-sub); font-size: 10px; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; background: #fff; border: 1px solid var(--color-calv-red); color: var(--color-calv-red);">
                            {{ $up->name }}
                        </span>
                    @endforeach
                </div>
            @endif
            @if($dependents->isNotEmpty())
                <div class="flex items-center gap-2 flex-wrap">
                    <span style="font-family: var(--font-calv-sub); font-size: 9.5px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--color-calv-slate-65);">
                        Unblocks
                    </span>
                    @foreach($dependents as $down)
                        <span class="rounded-full"
                              style="padding: 3px 9px; font-family: var(--font-calv-sub); font-size: 10px; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; background: #fff; border: 1px solid var(--color-calv-teal); color: var(--color-calv-teal);">
                            {{ $down->name }}
                        </span>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    {{-- Gating requirements --}}
    <section style="margin-top: 22px;" data-test="gating-requirements-section">
        <header class="flex items-center gap-2.5" style="margin-bottom: 12px;">
            <h3 class="m-0"
                style="font-family: var(--font-calv-sub); font-size: 12px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--color-calv-slate);">
                Gating requirements
            </h3>
            <span style="font-family: var(--font-calv-sub); font-size: 11px; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; color: {{ $reqTotal > 0 && $reqMet === $reqTotal ? 'var(--color-calv-sage)' : 'var(--color-calv-amber)' }};">
                {{ $reqMet }}/{{ $reqTotal }} met
            </span>
            <span class="flex-1"></span>
            @if($this->can('projects.manage'))
                <button wire:click="showCreateRequirement('task')"
                        class="inline-flex items-center gap-1 rounded"
                        style="padding: 4px 9px; font-family: var(--font-calv-sub); font-size: 10px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; background: var(--color-calv-slate-15); color: var(--color-calv-slate); border: none;">
                    + Task
                </button>
                <button wire:click="showCreateRequirement('document')"
                        class="inline-flex items-center gap-1 rounded"
                        style="padding: 4px 9px; font-family: var(--font-calv-sub); font-size: 10px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; background: var(--color-calv-slate-15); color: var(--color-calv-slate); border: none;">
                    + Document
                </button>
            @endif
        </header>

        @if($reqs->isEmpty())
            <p class="text-center" style="padding: 20px 0; font-size: 13px; color: var(--color-calv-slate-65); font-style: italic;">
                No gating requirements yet. Add a task or document to track what must be done.
            </p>
        @else
            <div class="flex flex-col gap-2" data-test="gating-requirements-list">
                @foreach($reqs as $r)
                    @php
                        $done = $r->status === 'complete';
                        $hasFiles = $r->type === 'document' && method_exists($r, 'getMedia')
                            ? $r->getMedia('attachments')->isNotEmpty()
                            : false;
                    @endphp
                    <div class="flex items-center gap-3 rounded-md"
                         style="padding: 11px 14px; border: 1px solid var(--color-calv-slate-15); background: {{ $done ? 'var(--color-calv-sage-15)' : '#fff' }};"
                         data-test="milestone-requirement-row gating-requirement-row">
                        {{-- Checkbox / completion control --}}
                        @if($r->type === 'task')
                            <button wire:click="toggleTaskRequirementComplete({{ $r->id }})"
                                    @if(! $this->can('projects.manage')) disabled @endif
                                    class="inline-flex items-center justify-center shrink-0 rounded"
                                    style="width: 18px; height: 18px; border: {{ $done ? 'none' : '1.5px solid var(--color-calv-slate-35)' }}; background: {{ $done ? 'var(--color-calv-sage)' : '#fff' }}; cursor: pointer;">
                                @if($done)
                                    <svg width="11" height="11" viewBox="0 0 12 12" fill="none">
                                        <path d="M2.5 6.5L5 9l4.5-5" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                @endif
                            </button>
                        @else
                            <span class="inline-flex items-center justify-center shrink-0 rounded"
                                  style="width: 18px; height: 18px; border: {{ $done ? 'none' : '1.5px solid var(--color-calv-slate-35)' }}; background: {{ $done ? 'var(--color-calv-sage)' : '#fff' }};">
                                @if($done)
                                    <svg width="11" height="11" viewBox="0 0 12 12" fill="none">
                                        <path d="M2.5 6.5L5 9l4.5-5" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                @endif
                            </span>
                        @endif

                        {{-- Kind icon --}}
                        <span style="color: var(--color-calv-slate-65);">
                            @if($r->type === 'task')
                                <svg width="14" height="14" viewBox="0 0 16 16">
                                    <rect x="2.5" y="2.5" width="11" height="11" rx="2" stroke="currentColor" stroke-width="1.4" fill="none"/>
                                    <path d="M5 8l2 2 4-4.5" stroke="currentColor" stroke-width="1.4" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            @else
                                <svg width="14" height="14" viewBox="0 0 16 16">
                                    <path d="M4 2h5l3 3v9H4V2Z" stroke="currentColor" stroke-width="1.4" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M9 2v3h3" stroke="currentColor" stroke-width="1.4" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            @endif
                        </span>

                        <div class="flex-1 min-w-0">
                            <div style="font-size: 13.5px; font-weight: 500; color: var(--color-calv-slate); line-height: 1.3;">
                                {{ $r->title }}
                            </div>
                            <div style="font-size: 11px; color: var(--color-calv-slate-65); margin-top: 1px;">
                                {{ $r->type === 'task' ? 'Task' : 'Document' }} ·
                                Owner: {{ $r->owner?->name ?? '—' }}
                                @if($r->completed_at) · {{ $r->completed_at->format('M d') }} @endif
                                @if($hasFiles) · 📎 {{ $r->getMedia('attachments')->count() }} file(s) @endif
                                @if($r->source_type === 'fund_code') · <span style="color: var(--color-calv-teal); font-weight: 600;">via fund code</span>@endif
                            </div>
                            @if($r->milestone_gate_text)
                                <p style="font-size: 11px; color: var(--color-calv-red); margin-top: 3px;">⛔ {{ $r->milestone_gate_text }}</p>
                            @endif
                            @if(filled($r->worker_note))
                                <p style="font-size: 11px; color: var(--color-calv-slate-65); margin-top: 3px; white-space: pre-line;">📝 {{ \Illuminate\Support\Str::limit($r->worker_note, 280) }}</p>
                            @endif
                        </div>

                        @if(! $done)
                            <span class="rounded-full"
                                  style="padding: 3px 8px; font-family: var(--font-calv-sub); font-size: 9.5px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; background: var(--color-calv-amber-15); color: #8A6410;">
                                Outstanding
                            </span>
                            @if($r->type === 'document' && $this->can('projects.manage'))
                                <button wire:click="openCompleteRequirementModal({{ $r->id }})"
                                        class="rounded text-white"
                                        style="padding: 4px 9px; font-family: var(--font-calv-sub); font-size: 10px; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; background: var(--color-calv-teal); border: none; cursor: pointer;">
                                    Upload
                                </button>
                            @endif
                        @elseif($this->can('projects.manage'))
                            <button wire:click="reopenRequirement({{ $r->id }})"
                                    class="rounded text-white"
                                    style="padding: 4px 9px; font-family: var(--font-calv-sub); font-size: 10px; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; background: var(--color-calv-amber); color: #5a4109; border: none; cursor: pointer;">
                                Reopen
                            </button>
                        @endif

                        {{-- Note (📝): available for EVERY requirement, including
                             fund-code-sourced ones whose Edit form is hidden below.
                             Teal when a note already exists. --}}
                        @if($this->can('projects.manage'))
                            <button wire:click="openRequirementNote({{ $r->id }})" aria-label="Note"
                                    title="{{ filled($r->worker_note) ? 'Edit note' : 'Add note' }}"
                                    class="inline-flex items-center justify-center rounded"
                                    style="width: 28px; height: 28px; background: transparent; color: {{ filled($r->worker_note) ? 'var(--color-calv-teal)' : 'var(--color-calv-slate-65)' }}; border: none; cursor: pointer;">
                                <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                                    <path d="M3 2.5h7l3 3V13a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V3.5a1 1 0 0 1 1-1Z" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round"/>
                                    <path d="M5 7h6M5 9.5h6M5 12h4" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/>
                                </svg>
                            </button>
                        @endif

                        @if($r->source_type !== 'fund_code' && $this->can('projects.manage'))
                            <button wire:click="showEditRequirement({{ $r->id }})" aria-label="Edit"
                                    class="inline-flex items-center justify-center rounded"
                                    style="width: 28px; height: 28px; background: transparent; color: var(--color-calv-slate-65); border: none; cursor: pointer;">
                                <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                                    <path d="M11 2.5l2.5 2.5L5 13.5H2.5V11L11 2.5z" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                            <button wire:click="deleteRequirement({{ $r->id }})"
                                    wire:confirm="Remove this requirement?"
                                    aria-label="Remove"
                                    class="inline-flex items-center justify-center rounded"
                                    style="width: 28px; height: 28px; background: transparent; color: var(--color-calv-slate-35); border: none; cursor: pointer;">
                                <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                                    <path d="M3 4.5h10M6.5 4V3h3v1M5 4.5l.5 8h5l.5-8" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</div>

<style>[x-cloak] { display: none !important; }</style>
