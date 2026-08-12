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

        @if($siteFavicon)
            <link rel="icon" href="{{ Storage::url($siteFavicon) }}" sizes="any">
        @else
            <link rel="icon" href="/favicon.ico" sizes="any">
        @endif

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
