@props(['showText' => true])

@php
    $siteName = \App\Models\SiteSetting::get('site_name', 'CAP TRACK');
    $siteLogo = \App\Models\SiteSetting::get('site_logo');
@endphp

<span {{ $attributes->class(['inline-flex items-center gap-2']) }}>
    @if($siteLogo)
        <img src="{{ Storage::url($siteLogo) }}" alt="{{ $siteName }}" class="h-8 w-auto">
    @else
        <div class="flex aspect-square size-8 items-center justify-center rounded-md bg-accent-content text-accent-foreground">
            <x-app-logo-icon class="size-5 fill-current text-white dark:text-black" />
        </div>
    @endif

    @if($showText)
        <span class="grid text-start text-sm leading-tight">
            <span class="truncate font-semibold">{{ $siteName }}</span>
        </span>
    @endif
</span>
