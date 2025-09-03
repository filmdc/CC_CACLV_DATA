<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

@php
    $siteName = \App\Models\SiteSetting::get('site_name', config('app.name'));
    $siteFavicon = \App\Models\SiteSetting::get('site_favicon');
@endphp

<title>{{ $title ?? $siteName }}</title>

@if($siteFavicon)
    <link rel="icon" href="{{ Storage::url($siteFavicon) }}" sizes="any">
@else
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
@endif
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance

<!-- Jodit Editor CSS -->
<link rel="stylesheet" href="//unpkg.com/jodit@4.1.16/es2021/jodit.min.css">

@stack('styles')
