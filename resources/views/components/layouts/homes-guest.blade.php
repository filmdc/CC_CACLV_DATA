<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
        <meta name="theme-color" content="#54585A" />

        @php
            $siteName = \App\Models\SiteSetting::get('site_name', config('app.name'));
            $siteFavicon = \App\Models\SiteSetting::get('site_favicon');
        @endphp

        <title>{{ $title ?? 'Sign In - CACLV Housing' }}</title>

        {{-- Housing-portal mark: a house in the CALV palette. The main app and
             the approvals portal keep the generic CAP TRACK favicon. ?v= is a
             cache-buster — browsers hold on to favicons hard. --}}
        @if($siteFavicon)
            <link rel="icon" href="{{ Storage::url($siteFavicon) }}" sizes="any">
        @else
            <link rel="icon" href="/favicon-homes.ico?v=1" sizes="any">
            <link rel="icon" href="/favicon-homes.svg?v=1" type="image/svg+xml">
        @endif
        <link rel="apple-touch-icon" href="/favicon-homes-180.png?v=1">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=anton:400|barlow:300,400,500,600,700|oswald:500,600,700" rel="stylesheet" />

        @vite(['resources/css/homes.css', 'resources/js/homes.js'])
        @livewireStyles
    </head>
    <body class="min-h-screen antialiased flex items-center justify-center p-4" style="background: var(--calv-sand-15);">
        {{ $slot }}

        @livewireScripts
    </body>
</html>
