{{-- P-07: Fund-code requirements panel. Renders requirements grouped by
     attached fund code so the manager sees every hard and informational
     requirement at a glance. Hidden when no attached code has any
     requirements; an empty/no-codes state is handled by the assignment
     section above. The wrapping HTML comment markers (snapshot-region:*)
     are extracted by RequirementsPanelTest for the
     project_detail_requirements_panel.html snapshot. --}}
<!-- snapshot-region: fund-code-requirements-panel-start -->
@if($job->fundCodes->flatMap->requirements->isNotEmpty())
<div class="bg-white dark:bg-zinc-800 border-b border-gray-200 dark:border-zinc-700 px-6 py-4">
    <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wide mb-3">
        Fund Code Requirements
    </h3>
    <div class="space-y-4">
        @foreach($job->fundCodes->sortBy('code') as $fc)
            @if($fc->requirements->isNotEmpty())
                <div class="border-l-4 border-emerald-500 pl-3">
                    <div class="flex items-center gap-2 mb-2 flex-wrap">
                        <span class="font-mono text-sm font-semibold text-gray-900 dark:text-white">{{ $fc->code }}</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $fc->description }}</span>
                    </div>
                    <ul class="space-y-2">
                        @foreach($fc->requirements as $req)
                            <li class="flex items-start gap-2">
                                @if($req->is_hard_requirement)
                                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-bold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 flex-shrink-0">
                                        Required
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-200 flex-shrink-0">
                                        Informational
                                    </span>
                                @endif
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $req->label }}</p>
                                    @if($req->description)
                                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">{{ $req->description }}</p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @endforeach
    </div>
</div>
@endif
<!-- snapshot-region: fund-code-requirements-panel-end -->
