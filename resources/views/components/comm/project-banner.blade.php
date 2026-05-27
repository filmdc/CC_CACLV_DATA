@props([
    'project' => null,
    'sticky' => true,
])

{{--
    Sticky teal banner used at the top of project-attached Edit + Viewer
    surfaces. Shows code chip, name, meta line (contact/fund-code counts +
    last-entry recency), and Change/Detach actions.
--}}
<div {{ $attributes->merge([
    'class' => ($sticky ? 'sticky top-0 z-10 ' : '')
        . 'flex items-center gap-4 px-6 py-3 bg-calv-teal text-white shadow-calv-sm',
]) }}>
    @if($project)
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-calv-sm bg-white/15 font-mono text-[12px] tracking-wide">
            {{ $project->job_number ?? $project->code ?? '—' }}
        </span>

        <div class="min-w-0 flex-1">
            <div class="calv-subhead text-[10px] text-white/70 mb-0.5">Project</div>
            <div class="calv-h1 text-[16px] text-white truncate">{{ $project->name ?? $project->title ?? '—' }}</div>
        </div>

        <div class="hidden md:flex items-center gap-5 text-[11.5px] text-white/80">
            {{ $slot }}
        </div>

        <button type="button"
                x-on:click="$dispatch('open-project-picker')"
                class="calv-subhead text-[11px] underline underline-offset-4 hover:text-white/80 calv-focus-ring">
            Change
        </button>
    @else
        <div class="calv-subhead text-[12px]">No project attached</div>
    @endif
</div>
