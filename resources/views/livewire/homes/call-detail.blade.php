<div class="px-6 lg:px-9 py-6 max-w-6xl mx-auto" style="min-height: 100vh;">

    @if (session('homes_message'))
        <div class="mb-4 rounded-md px-4 py-3 text-sm" style="background: var(--calv-sage-15); color: #2F5A41; border: 1px solid var(--calv-sage-35);">
            {{ session('homes_message') }}
        </div>
    @endif

    <a href="{{ route('homes.calls') }}" wire:navigate class="calv-link inline-block mb-3" style="font-size: 12.5px;">← Calls &amp; Intakes</a>

    @php
        $badgeFor = [
            'call' => 'calv-badge',
            'collecting' => 'calv-badge calv-badge--amber',
            'ready' => 'calv-badge calv-badge--sage',
            'converted' => 'calv-badge calv-badge--teal',
        ];
        $updateColors = [
            'gray' => ['background: var(--calv-slate-15)', 'color: var(--calv-slate-65)'],
            'amber' => ['background: var(--calv-amber-15)', 'color: #8A6410'],
            'blue' => ['background: var(--calv-teal-15)', 'color: var(--calv-teal)'],
            'green' => ['background: var(--calv-sage-15)', 'color: #2F5A41'],
            'purple' => ['background: var(--calv-sand-35)', 'color: var(--calv-slate)'],
        ];
    @endphp

    {{-- Header --}}
    <div class="flex items-start gap-5 mb-5 flex-wrap">
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-3 flex-wrap">
                <h1 class="calv-h1" style="font-size: 28px;">{{ $who }}</h1>
                <span class="{{ $badgeFor[$stage['key']] ?? 'calv-badge' }}">{{ $stage['label'] }}</span>
                @if($log->is_follow_up_overdue)
                    <span class="calv-badge calv-badge--red">Needs a call</span>
                @endif
            </div>
            <div class="text-[13.5px] mt-1.5" style="color: var(--calv-slate-65);">
                @if($where){{ $where }} · @endif
                @if($log->contact_role){{ ucfirst(str_replace('_',' ',$log->contact_role)) }} · @endif
                <span class="calv-subhead" style="font-size: 11px; letter-spacing: .05em;">COMM-{{ $log->id }}</span>
            </div>
            <div class="flex gap-4 mt-2 text-[12.5px] flex-wrap" style="color: var(--calv-slate);">
                @if($log->contact_phone)<span>☏ {{ $log->contact_phone }}</span>@endif
                @if($log->contact_email)<span>✉ {{ $log->contact_email }}</span>@endif
            </div>
        </div>
        <div class="flex gap-2 shrink-0">
            @if($canEdit)
                <button type="button" wire:click="openEdit" class="calv-btn calv-btn--ghost">Edit</button>
            @endif
            @can('communications_log.create')
                <button type="button" x-on:click="Livewire.dispatch('homes-open-quick-log')" class="calv-btn calv-btn--ghost">Log a call</button>
            @endcan
            @if($canConvert)
                <button type="button" wire:click="openConvert" class="calv-btn calv-btn--secondary">Convert to project →</button>
            @endif
            @if($log->is_converted && $log->convertedToJob)
                <a href="{{ route('homes.project', $log->converted_to_job_id) }}" wire:navigate class="calv-btn calv-btn--secondary">Open project {{ $log->convertedToJob->job_number }}</a>
            @endif
        </div>
    </div>

    <div class="grid gap-4 items-start" style="grid-template-columns: minmax(0,1.5fr) minmax(0,1fr);">

        {{-- Every touch --}}
        <section class="calv-card overflow-hidden">
            <header class="flex items-center px-4 py-3" style="border-bottom: 1px solid var(--calv-slate-15);">
                <h3 class="calv-subhead flex-1" style="font-size: 12px;">Every touch</h3>
                <span class="text-[11.5px]" style="color: var(--calv-slate-65);">newest first · nothing is ever deleted</span>
            </header>
            @foreach($timeline as $t)
                <div class="flex gap-3.5 px-4 py-3" style="border-bottom: 1px solid var(--calv-slate-15);">
                    <div class="shrink-0" style="width: 96px;">
                        <div class="calv-subhead" style="font-size: 11px; letter-spacing: .05em;">{{ $t['on']?->isoFormat('MMM D') }}</div>
                        <div class="text-[10.5px] mt-0.5" style="color: var(--calv-slate-65);">{{ $t['kind'] }}</div>
                        @if($t['by'])<div class="text-[10px] mt-0.5" style="color: var(--calv-slate-65);">{{ $t['by'] }}</div>@endif
                    </div>
                    <div class="flex-1 text-[13px] leading-relaxed" style="color: var(--calv-slate);">{{ $t['note'] }}</div>
                </div>
            @endforeach
            @can('communications_log.create')
                <div class="px-4 py-3">
                    <button type="button" x-on:click="Livewire.dispatch('homes-open-quick-log')" class="calv-link text-left" style="font-size: 12.5px; background: none; border: none; cursor: pointer;">
                        + Log another call with {{ Str::before($who, ' ') }}
                    </button>
                </div>
            @endcan
        </section>

        {{-- Right column --}}
        <div class="flex flex-col gap-4">

            {{-- Documents / requirements checklist --}}
            @if($requirements->count() > 0)
                <section class="calv-card overflow-hidden">
                    <header class="flex items-center px-4 py-3" style="border-bottom: 1px solid var(--calv-slate-15);">
                        <h3 class="calv-subhead flex-1" style="font-size: 12px;">Documents</h3>
                        <span class="text-xs font-semibold" style="color: {{ $gate['ready'] ? '#2F5A41' : 'var(--calv-slate)' }};">{{ $progress['met'] }} of {{ $progress['total'] }} in</span>
                    </header>
                    <div class="px-4 py-3 flex flex-col gap-2.5">
                        @foreach($requirements as $r)
                            <div class="flex items-center gap-2.5 text-[12.5px]">
                                <span class="inline-flex items-center justify-center shrink-0" style="width: 14px; height: 14px; border-radius: 3px; {{ $r['met'] ? 'background: var(--calv-sage);' : 'border: 1.5px solid var(--calv-slate-35); background: #fff;' }}">
                                    @if($r['met'])<svg width="9" height="9" viewBox="0 0 12 12" fill="none"><path d="M2.5 6.5L5 9L9.5 3.5" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>@endif
                                </span>
                                <span class="flex-1 min-w-0" style="color: {{ $r['met'] ? 'var(--calv-slate-65)' : 'var(--calv-slate)' }}; {{ $r['met'] ? 'text-decoration: line-through;' : '' }}">
                                    {{ $r['label'] }}
                                    @if(!$r['met'] && $r['status'] === 'requested')<span class="text-[10.5px]" style="color: #8A6410;"> · asked</span>@endif
                                </span>
                                @if($r['met'])
                                    <span class="text-[11px] shrink-0" style="color: var(--calv-slate-65);">{{ $r['received_on'] ?? $r['status_display'] }}</span>
                                @elseif($canEdit)
                                    <span class="inline-flex gap-1.5 shrink-0">
                                        @if($r['type'] === 'document')
                                            <button type="button" wire:click="openUpload({{ $r['id'] }})" class="calv-btn calv-btn--secondary calv-btn--sm">Upload</button>
                                        @endif
                                        <button type="button" wire:click="markReceived({{ $r['id'] }})" class="calv-btn calv-btn--quiet calv-btn--sm">Received</button>
                                        <button type="button" wire:click="nudge({{ $r['id'] }})" class="calv-btn calv-btn--ghost calv-btn--sm">Nudge</button>
                                    </span>
                                @endif
                            </div>
                            @if($r['worker_note'])
                                <div class="text-[11px] -mt-1" style="color: var(--calv-slate-65); padding-left: 24px;">{{ Str::limit($r['worker_note'], 140) }}</div>
                            @endif
                        @endforeach
                    </div>
                    @if($canEdit)
                        <div class="px-4 py-3" style="border-top: 1px solid var(--calv-slate-15);">
                            @if($showAddReq)
                                <div class="flex flex-col gap-2">
                                    <input wire:model="adHocLabel" type="text" class="calv-input" placeholder="What's needed? e.g. Benefit letter (SS)">
                                    @error('adHocLabel')<p class="text-xs" style="color: var(--calv-red);">{{ $message }}</p>@enderror
                                    <div class="flex items-center gap-2">
                                        <select wire:model="adHocType" class="calv-input" style="width: auto;">
                                            <option value="document">Document</option>
                                            <option value="task">Task</option>
                                        </select>
                                        <button type="button" wire:click="saveAdHocRequirement" class="calv-btn calv-btn--primary calv-btn--sm">Add</button>
                                        <button type="button" wire:click="$set('showAddReq', false)" class="calv-btn calv-btn--ghost calv-btn--sm">Cancel</button>
                                    </div>
                                </div>
                            @else
                                <button type="button" wire:click="$set('showAddReq', true)" class="calv-link" style="background:none;border:none;cursor:pointer;font-size:12.5px;">+ add a document or task</button>
                            @endif
                        </div>
                    @endif
                    @if($gate['ready'] && !$log->is_converted)
                        <div class="px-4 py-3" style="border-top: 1px solid var(--calv-slate-15); background: var(--calv-sage-15);">
                            <div class="text-[12.5px] font-semibold mb-2" style="color: #2F5A41;">Everything's in. This intake is ready.</div>
                            @if($canConvert)
                                <button type="button" wire:click="openConvert" class="calv-btn calv-btn--secondary calv-btn--sm">Convert to project →</button>
                            @endif
                        </div>
                    @endif
                </section>
            @else
                <div class="px-4 py-3.5 text-[12.5px] leading-relaxed" style="background: #fff; border: 1px dashed var(--calv-slate-35); border-radius: 8px; color: var(--calv-slate-65);">
                    <strong style="color: var(--calv-slate);">Just a call.</strong> No documents needed yet — pick a county below or add a document to start an intake. It stays searchable forever.
                    @if($canEdit)
                        <div class="mt-2.5">
                            @if($showAddReq)
                                <div class="flex flex-col gap-2">
                                    <input wire:model="adHocLabel" type="text" class="calv-input" placeholder="What's needed? e.g. Photo ID">
                                    @error('adHocLabel')<p class="text-xs" style="color: var(--calv-red);">{{ $message }}</p>@enderror
                                    <div class="flex items-center gap-2">
                                        <select wire:model="adHocType" class="calv-input" style="width: auto;">
                                            <option value="document">Document</option>
                                            <option value="task">Task</option>
                                        </select>
                                        <button type="button" wire:click="saveAdHocRequirement" class="calv-btn calv-btn--primary calv-btn--sm">Add</button>
                                        <button type="button" wire:click="$set('showAddReq', false)" class="calv-btn calv-btn--ghost calv-btn--sm">Cancel</button>
                                    </div>
                                </div>
                            @else
                                <button type="button" wire:click="$set('showAddReq', true)" class="calv-link" style="background:none;border:none;cursor:pointer;font-size:12.5px;">+ add a document or task</button>
                            @endif
                        </div>
                    @endif
                </div>
            @endif

            {{-- Counties — attaching one stamps its fund codes + requirements --}}
            @if($canEdit && $counties->count() > 0)
                <section class="calv-card overflow-hidden">
                    <header class="flex items-center px-4 py-3" style="border-bottom: 1px solid var(--calv-slate-15);">
                        <h3 class="calv-subhead flex-1" style="font-size: 12px;">Counties</h3>
                        <span class="text-[11px]" style="color: var(--calv-slate-65);">attaching one adds its funds &amp; requirements</span>
                    </header>
                    <div class="px-4 py-3 flex flex-wrap gap-1.5">
                        @foreach($counties as $county)
                            @php $attached = in_array($county->id, $attachedCountyIds); @endphp
                            <button type="button" wire:click="toggleCounty({{ $county->id }})"
                                    wire:loading.attr="disabled"
                                    style="font-family: var(--font-body); font-size: 12.5px; padding: 6px 12px; border-radius: 999px; cursor: pointer;
                                           background: {{ $attached ? 'var(--calv-teal)' : '#fff' }}; color: {{ $attached ? '#fff' : 'var(--calv-slate)' }};
                                           border: 1px solid {{ $attached ? 'var(--calv-teal)' : 'var(--calv-slate-35)' }};">
                                {{ $attached ? '✓ ' : '' }}{{ $county->name }}
                            </button>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Next --}}
            <section class="calv-card overflow-hidden">
                <header class="px-4 py-3" style="border-bottom: 1px solid var(--calv-slate-15);">
                    <h3 class="calv-subhead" style="font-size: 12px;">Next</h3>
                </header>
                <div class="px-4 py-3">
                    @if($editingFollowUp)
                        <div class="flex flex-col gap-2.5">
                            <input wire:model="followUpAction" type="text" class="calv-input" placeholder="What needs doing?">
                            @error('followUpAction')<p class="text-xs" style="color: var(--calv-red);">{{ $message }}</p>@enderror
                            <input wire:model="followUpDate" type="date" class="calv-input" style="max-width: 180px;">
                            @error('followUpDate')<p class="text-xs" style="color: var(--calv-red);">{{ $message }}</p>@enderror
                            <div class="flex gap-2">
                                <button type="button" wire:click="saveFollowUp" class="calv-btn calv-btn--primary calv-btn--sm">Save</button>
                                <button type="button" wire:click="$set('editingFollowUp', false)" class="calv-btn calv-btn--ghost calv-btn--sm">Cancel</button>
                            </div>
                        </div>
                    @elseif($log->follow_up_date && !$log->follow_up_completed)
                        <div class="text-[13px] font-semibold" style="color: {{ $log->is_follow_up_overdue ? 'var(--calv-red)' : 'var(--calv-slate)' }};">
                            {{ $log->follow_up_action ?: 'Follow up' }}
                        </div>
                        <div class="text-[11.5px] mt-1" style="color: var(--calv-slate-65);">
                            due {{ $log->follow_up_date->isoFormat('ddd, MMM D') }}
                            @if($canEdit)
                                · <button type="button" wire:click="startEditFollowUp" class="calv-link" style="background:none;border:none;cursor:pointer;font-size:11.5px;">change follow-up</button>
                                · <button type="button" wire:click="completeFollowUp" class="calv-link" style="background:none;border:none;cursor:pointer;font-size:11.5px;">mark done</button>
                            @endif
                        </div>
                    @else
                        <div class="text-[13px]" style="color: var(--calv-slate-65);">No follow-up scheduled.</div>
                        @if($canEdit)
                            <button type="button" wire:click="startEditFollowUp" class="calv-link mt-1" style="background:none;border:none;cursor:pointer;font-size:11.5px;">+ add a follow-up</button>
                        @endif
                    @endif
                </div>
            </section>
        </div>
    </div>

    {{-- Convert modal --}}
    @if($showConvert)
        <div class="fixed inset-0 z-[100] flex items-start justify-center overflow-y-auto" style="background: rgba(40,42,44,.55); padding: 56px 16px;">
            <div class="w-full" style="max-width: 560px; background: #fff; border-radius: 10px; box-shadow: var(--shadow-lg); overflow: hidden;" wire:click.stop>
                <header class="px-6 py-4" style="border-bottom: 1px solid var(--calv-slate-15);">
                    <h2 class="calv-h1" style="font-size: 20px;">Convert to project</h2>
                    <div class="text-xs mt-1" style="color: var(--calv-slate-65);">{{ $who }}@if($where) · {{ $where }}@endif · step {{ $convertStep }} of 2</div>
                </header>

                <div class="px-6 py-5 flex flex-col gap-4">
                    @if($convertStep === 1)
                        {{-- Requirements gate --}}
                        @if(!$gate['hasRequirements'])
                            <div class="rounded-lg px-3.5 py-3 text-[12.5px]" style="background: var(--calv-amber-15); border-left: 3px solid var(--calv-amber); color: var(--calv-slate);">
                                <strong>This entry has nothing to track yet.</strong> A project needs at least one requirement — close this and pick a county or add a document first.
                            </div>
                        @elseif($gate['ready'])
                            <div class="flex items-center gap-2.5 rounded-lg px-3.5 py-2.5 text-[12.5px]" style="background: var(--calv-sage-15); color: #2F5A41;">
                                ✓ All requirements met. Calls and documents come along automatically.
                            </div>
                        @elseif($gate['unmet'] > 0)
                            <div class="rounded-lg px-3.5 py-3 text-[12.5px]" style="background: var(--calv-amber-15); border-left: 3px solid var(--calv-amber); color: var(--calv-slate);">
                                <strong>{{ $gate['unmet'] }} of {{ $gate['total'] }} application {{ Str::plural('requirement', $gate['total']) }} still unmet.</strong>
                                <label class="flex items-center gap-2 mt-2">
                                    <input type="checkbox" wire:model.live="requirementOverride" style="accent-color: var(--calv-red);">
                                    <span>Convert anyway (override is audit-logged)</span>
                                </label>
                                @if($requirementOverride)
                                    <input wire:model="requirementOverrideReason" type="text" class="calv-input mt-2" placeholder="Reason for overriding — required">
                                @endif
                                @error('requirementOverride')<p class="mt-1.5" style="color: var(--calv-red);">{{ $message }}</p>@enderror
                                @error('requirementOverrideReason')<p class="mt-1.5" style="color: var(--calv-red);">{{ $message }}</p>@enderror
                            </div>
                        @endif

                        <div>
                            <label class="calv-label">Project name</label>
                            <input wire:model="convertTitle" type="text" class="calv-input">
                            @error('convertTitle')<p class="mt-1 text-xs" style="color: var(--calv-red);">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="calv-label">Scope of work <span style="font-weight:400; text-transform:none; letter-spacing:0; color: var(--calv-slate-65);">— you can change this later</span></label>
                            <textarea wire:model="convertDescription" rows="3" class="calv-input" style="resize: vertical;"></textarea>
                        </div>

                        {{-- Carries over (read-only, parity: ALL anticipated funds + counties) --}}
                        <div class="rounded-lg px-3.5 py-3" style="background: var(--calv-sand-15);">
                            <div class="calv-subhead mb-2" style="font-size: 10px; letter-spacing: .1em;">Carries over to the project</div>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($log->housingServices()->get() as $county)
                                    <span class="calv-badge">{{ $county->name }}</span>
                                @endforeach
                                @foreach($log->anticipatedFundCodes()->get() as $fund)
                                    <span class="calv-badge calv-badge--teal" title="{{ $fund->description }}">
                                        {{ $fund->code }}
                                        @if($fund->expiry_state === 'soon') · {{ $fund->days_to_expiry }}d ⚠@endif
                                    </span>
                                @endforeach
                            </div>
                            <div class="text-[11px] mt-2" style="color: var(--calv-slate-65);">The intake stays put — the project links back to it.</div>
                        </div>
                    @else
                        {{-- Step 2: duplicate review --}}
                        @if(!empty($participantMatches))
                            <div>
                                <div class="calv-subhead mb-2" style="font-size: 11px;">Possible existing people</div>
                                <div class="flex flex-col gap-1.5">
                                    @foreach($participantMatches as $m)
                                        <label class="flex items-center gap-2.5 rounded-lg px-3 py-2.5 cursor-pointer text-[13px]" style="border: 1px solid {{ $participantAction == $m['id'] ? 'var(--calv-teal)' : 'var(--calv-slate-15)' }}; background: {{ $participantAction == $m['id'] ? 'var(--calv-teal-15)' : '#fff' }};">
                                            <input type="radio" wire:click="$set('participantAction', {{ $m['id'] }})" @checked($participantAction == $m['id']) style="accent-color: var(--calv-teal);">
                                            <span class="flex-1 min-w-0">
                                                <span class="font-semibold" style="color: var(--calv-slate);">{{ $m['name'] }}</span>
                                                <span class="block text-[11px]" style="color: var(--calv-slate-65);">{{ $m['phone'] ?: '—' }} · {{ $m['email'] ?: '—' }}@if($m['household_code']) · HH {{ $m['household_code'] }}@endif</span>
                                            </span>
                                        </label>
                                    @endforeach
                                    <label class="flex items-center gap-2.5 rounded-lg px-3 py-2.5 cursor-pointer text-[13px]" style="border: 1px solid {{ $participantAction === 'create_new' ? 'var(--calv-teal)' : 'var(--calv-slate-15)' }}; background: {{ $participantAction === 'create_new' ? 'var(--calv-teal-15)' : '#fff' }};">
                                        <input type="radio" wire:click="$set('participantAction', 'create_new')" @checked($participantAction === 'create_new') style="accent-color: var(--calv-teal);">
                                        <span style="color: var(--calv-slate);">This is a new person — create a participant</span>
                                    </label>
                                </div>
                            </div>
                        @endif

                        @if(!empty($propertyMatches))
                            <div>
                                <div class="calv-subhead mb-2" style="font-size: 11px;">Possible existing properties</div>
                                <div class="flex flex-col gap-1.5">
                                    @foreach($propertyMatches as $m)
                                        <label class="flex items-center gap-2.5 rounded-lg px-3 py-2.5 cursor-pointer text-[13px]" style="border: 1px solid {{ $propertyAction == $m['id'] ? 'var(--calv-teal)' : 'var(--calv-slate-15)' }}; background: {{ $propertyAction == $m['id'] ? 'var(--calv-teal-15)' : '#fff' }};">
                                            <input type="radio" wire:click="$set('propertyAction', {{ $m['id'] }})" @checked($propertyAction == $m['id']) style="accent-color: var(--calv-teal);">
                                            <span style="color: var(--calv-slate);">{{ $m['full_address'] }}</span>
                                        </label>
                                    @endforeach
                                    <label class="flex items-center gap-2.5 rounded-lg px-3 py-2.5 cursor-pointer text-[13px]" style="border: 1px solid {{ $propertyAction === 'create_new' ? 'var(--calv-teal)' : 'var(--calv-slate-15)' }}; background: {{ $propertyAction === 'create_new' ? 'var(--calv-teal-15)' : '#fff' }};">
                                        <input type="radio" wire:click="$set('propertyAction', 'create_new')" @checked($propertyAction === 'create_new') style="accent-color: var(--calv-teal);">
                                        <span style="color: var(--calv-slate);">Create a new property</span>
                                    </label>
                                    <label class="flex items-center gap-2.5 rounded-lg px-3 py-2.5 cursor-pointer text-[13px]" style="border: 1px solid {{ $propertyAction === 'skip' ? 'var(--calv-teal)' : 'var(--calv-slate-15)' }}; background: {{ $propertyAction === 'skip' ? 'var(--calv-teal-15)' : '#fff' }};">
                                        <input type="radio" wire:click="$set('propertyAction', 'skip')" @checked($propertyAction === 'skip') style="accent-color: var(--calv-teal);">
                                        <span style="color: var(--calv-slate);">Skip the property for now</span>
                                    </label>
                                </div>
                            </div>
                        @endif

                        @error('requirementOverride')<p class="text-sm" style="color: var(--calv-red);">{{ $message }}</p>@enderror
                        @error('requirementOverrideReason')<p class="text-sm" style="color: var(--calv-red);">{{ $message }}</p>@enderror
                        @error('convertTitle')<p class="text-sm" style="color: var(--calv-red);">{{ $message }}</p>@enderror
                    @endif
                </div>

                <footer class="flex items-center gap-2.5 px-6 py-3.5" style="border-top: 1px solid var(--calv-slate-15); background: var(--calv-sand-15);">
                    @if($convertStep === 2)
                        <button type="button" wire:click="$set('convertStep', 1)" class="calv-btn calv-btn--ghost calv-btn--sm">← Back</button>
                    @endif
                    <span class="flex-1"></span>
                    <button type="button" wire:click="closeConvert" class="calv-btn calv-btn--ghost">Cancel</button>
                    @if($convertStep === 1)
                        <button type="button" wire:click="continueConvert" class="calv-btn calv-btn--primary" wire:loading.attr="disabled">Continue →</button>
                    @else
                        <button type="button" wire:click="doConvert" class="calv-btn calv-btn--primary" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="doConvert">Create project</span>
                            <span wire:loading wire:target="doConvert">Creating…</span>
                        </button>
                    @endif
                </footer>
            </div>
        </div>
    @endif

    {{-- Edit entry modal --}}
    @if($showEdit)
        <div class="fixed inset-0 z-[100] flex items-start justify-center overflow-y-auto" style="background: rgba(40,42,44,.55); padding: 48px 16px;">
            <div class="w-full" style="max-width: 620px; background: #fff; border-radius: 10px; box-shadow: var(--shadow-lg); overflow: hidden;" wire:click.stop>
                <header class="px-6 py-4" style="border-bottom: 1px solid var(--calv-slate-15);">
                    <h2 class="calv-h1" style="font-size: 20px;">Edit entry</h2>
                    <div class="text-xs mt-1" style="color: var(--calv-slate-65);">COMM-{{ $log->id }}</div>
                </header>
                <div class="px-6 py-5 grid gap-3.5" style="grid-template-columns: 1fr 1fr;">
                    <div>
                        <label class="calv-label">First name</label>
                        <input wire:model="edit.contact_first_name" type="text" class="calv-input">
                    </div>
                    <div>
                        <label class="calv-label">Last name</label>
                        <input wire:model="edit.contact_last_name" type="text" class="calv-input">
                    </div>
                    <div>
                        <label class="calv-label">Phone</label>
                        <input wire:model="edit.contact_phone" type="text" class="calv-input">
                    </div>
                    <div>
                        <label class="calv-label">Email</label>
                        <input wire:model="edit.contact_email" type="email" class="calv-input">
                        @error('edit.contact_email')<p class="mt-1 text-xs" style="color: var(--calv-red);">{{ $message }}</p>@enderror
                    </div>
                    <div style="grid-column: 1 / -1;">
                        <label class="calv-label">Street address</label>
                        <input wire:model="edit.contact_address" type="text" class="calv-input">
                    </div>
                    <div>
                        <label class="calv-label">City</label>
                        <input wire:model="edit.contact_city" type="text" class="calv-input">
                    </div>
                    <div class="grid gap-3" style="grid-template-columns: 1fr 1fr;">
                        <div>
                            <label class="calv-label">State</label>
                            <input wire:model="edit.contact_state" type="text" maxlength="2" class="calv-input">
                        </div>
                        <div>
                            <label class="calv-label">Zip</label>
                            <input wire:model="edit.contact_zip" type="text" class="calv-input">
                        </div>
                    </div>
                    <div>
                        <label class="calv-label">Role</label>
                        <select wire:model="edit.contact_role" class="calv-input">
                            <option value="">—</option>
                            <option value="homeowner">Homeowner</option>
                            <option value="contractor">Contractor</option>
                            <option value="inspector">Inspector</option>
                            <option value="vendor">Vendor</option>
                            <option value="agency">Agency</option>
                            <option value="applicant">Applicant</option>
                            <option value="family_member">Family member</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="calv-label">Priority</label>
                        <select wire:model="edit.priority" class="calv-input">
                            <option value="low">Low</option>
                            <option value="normal">Normal</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    <div>
                        <label class="calv-label">Type</label>
                        <select wire:model="edit.communication_type" class="calv-input">
                            @foreach(['phone_call' => 'Phone call', 'email' => 'Email', 'in_person' => 'In person', 'site_visit' => 'Site visit', 'video_call' => 'Video call', 'text' => 'Text', 'mail' => 'Mail', 'voicemail' => 'Voicemail', 'fax' => 'Fax', 'other' => 'Other'] as $v => $l)
                                <option value="{{ $v }}">{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="calv-label">Direction</label>
                        <select wire:model="edit.direction" class="calv-input">
                            <option value="incoming">Incoming</option>
                            <option value="outgoing">Outgoing</option>
                        </select>
                    </div>
                    <div style="grid-column: 1 / -1;">
                        <label class="calv-label">When</label>
                        <input wire:model="edit.communication_date" type="datetime-local" class="calv-input" style="max-width: 240px;">
                        @error('edit.communication_date')<p class="mt-1 text-xs" style="color: var(--calv-red);">{{ $message }}</p>@enderror
                    </div>
                    <div style="grid-column: 1 / -1;">
                        <label class="calv-label">Subject</label>
                        <input wire:model="edit.subject" type="text" class="calv-input">
                        @error('edit.subject')<p class="mt-1 text-xs" style="color: var(--calv-red);">{{ $message }}</p>@enderror
                    </div>
                    <div style="grid-column: 1 / -1;">
                        <label class="calv-label">What happened?</label>
                        <textarea wire:model="edit.summary" rows="3" class="calv-input" style="resize: vertical;"></textarea>
                    </div>
                    <div style="grid-column: 1 / -1;">
                        <label class="calv-label">Notes <span style="font-weight:400; text-transform:none; letter-spacing:0; color: var(--calv-slate-65);">— internal</span></label>
                        <textarea wire:model="edit.notes" rows="2" class="calv-input" style="resize: vertical;"></textarea>
                    </div>
                </div>
                <footer class="flex items-center gap-2.5 px-6 py-3.5 justify-end" style="border-top: 1px solid var(--calv-slate-15); background: var(--calv-sand-15);">
                    <button type="button" wire:click="closeEdit" class="calv-btn calv-btn--ghost">Cancel</button>
                    <button type="button" wire:click="saveEdit" class="calv-btn calv-btn--primary" wire:loading.attr="disabled" wire:target="saveEdit">
                        <span wire:loading.remove wire:target="saveEdit">Save changes</span>
                        <span wire:loading wire:target="saveEdit">Saving…</span>
                    </button>
                </footer>
            </div>
        </div>
    @endif

    {{-- Upload files onto a document requirement --}}
    @if($uploadingRequirementId)
        <div class="fixed inset-0 z-[100] flex items-start justify-center overflow-y-auto" style="background: rgba(40,42,44,.55); padding: 64px 16px;">
            <div class="w-full" style="max-width: 440px; background: #fff; border-radius: 10px; box-shadow: var(--shadow-lg); overflow: hidden;" wire:click.stop>
                <header class="px-6 py-4" style="border-bottom: 1px solid var(--calv-slate-15);">
                    <h2 class="calv-h1" style="font-size: 20px;">Upload document</h2>
                    <div class="text-xs mt-1" style="color: var(--calv-slate-65);">Uploading marks the requirement received automatically.</div>
                </header>
                <div class="px-6 py-5">
                    <label class="calv-label">Files</label>
                    <input type="file" wire:model="requirementFiles" multiple class="calv-input">
                    <div wire:loading wire:target="requirementFiles" class="text-xs mt-1" style="color: var(--calv-slate-65);">Uploading…</div>
                    @error('requirementFiles')<p class="mt-1 text-xs" style="color: var(--calv-red);">{{ $message }}</p>@enderror
                    @error('requirementFiles.*')<p class="mt-1 text-xs" style="color: var(--calv-red);">{{ $message }}</p>@enderror
                </div>
                <footer class="flex items-center gap-2.5 px-6 py-3.5 justify-end" style="border-top: 1px solid var(--calv-slate-15); background: var(--calv-sand-15);">
                    <button type="button" wire:click="closeUpload" class="calv-btn calv-btn--ghost">Cancel</button>
                    <button type="button" wire:click="uploadRequirementFiles" class="calv-btn calv-btn--primary" wire:loading.attr="disabled" wire:target="uploadRequirementFiles,requirementFiles">
                        <span wire:loading.remove wire:target="uploadRequirementFiles">Save</span>
                        <span wire:loading wire:target="uploadRequirementFiles">Saving…</span>
                    </button>
                </footer>
            </div>
        </div>
    @endif
</div>
