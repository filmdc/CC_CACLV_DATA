<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
        <meta name="theme-color" content="#171717" />
        <meta name="apple-mobile-web-app-capable" content="yes" />
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />

        @php
            $siteName = \App\Models\SiteSetting::get('site_name', config('app.name'));
            $siteFavicon = \App\Models\SiteSetting::get('site_favicon');
        @endphp

        <title>{{ $title ?? 'Sign In - Approvals' }}</title>

        @if($siteFavicon)
            <link rel="icon" href="{{ Storage::url($siteFavicon) }}" sizes="any">
        @else
            <link rel="icon" href="/favicon.ico" sizes="any">
        @endif

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @vite(['resources/css/approvals.css', 'resources/js/approvals.js'])
        @livewireStyles
    </head>
    <body class="min-h-screen bg-zinc-950 text-white antialiased flex items-center justify-center p-4">
        {{ $slot }}

        @livewireScripts
    </body>
</html>
