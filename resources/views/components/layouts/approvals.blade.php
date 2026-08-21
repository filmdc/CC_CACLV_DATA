<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
        <meta name="theme-color" content="#171717" />
        <meta name="apple-mobile-web-app-capable" content="yes" />
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
        <meta name="mobile-web-app-capable" content="yes" />

        @php
            $siteName = \App\Models\SiteSetting::get('site_name', config('app.name'));
            $siteFavicon = \App\Models\SiteSetting::get('site_favicon');
        @endphp

        <title>{{ $title ?? 'Approvals - ' . $siteName }}</title>

        @if($siteFavicon)
            <link rel="icon" href="{{ Storage::url($siteFavicon) }}" sizes="any">
        @else
            <link rel="icon" href="/favicon.ico" sizes="any">
            <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        @endif
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @vite(['resources/css/approvals.css', 'resources/js/approvals.js'])
        @livewireStyles

        @stack('styles')
    </head>
    <body class="min-h-screen bg-zinc-950 text-white antialiased">
        {{-- Fixed top header --}}
        <header class="fixed top-0 left-0 right-0 z-50 h-14 bg-zinc-900 border-b border-zinc-800 safe-top lg:pl-60">
            <div class="flex items-center justify-between h-full px-4 lg:px-6">
                {{-- Left: Logo & app name (visible on mobile, hidden on desktop since sidebar has it) --}}
                <a href="{{ route('mobile.dashboard') }}" class="flex items-center gap-2 min-h-0 min-w-0 lg:hidden" wire:navigate>
                    <x-app-logo-icon class="size-6 fill-current text-indigo-400" />
                    <span class="text-sm font-semibold text-white truncate">Approvals</span>
                </a>

                {{-- Desktop: Page context / breadcrumb area --}}
                <div class="hidden lg:block">
                    <span class="text-sm text-zinc-400">Approvals Portal</span>
                </div>

                {{-- Right: User avatar dropdown --}}
                <div x-data="{ open: false }" class="relative">
                    <button
                        @click="open = !open"
                        class="flex items-center gap-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-zinc-900 rounded-full lg:rounded-lg lg:px-3 lg:py-1.5 lg:hover:bg-zinc-800 transition-colors"
                    >
                        <span class="flex items-center justify-center h-8 w-8 min-h-0 min-w-0 rounded-full bg-indigo-600 text-white text-xs font-semibold">
                            {{ auth()->user()->initials() }}
                        </span>
                        <span class="hidden lg:block text-sm text-zinc-300">{{ auth()->user()->name }}</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="hidden lg:block h-4 w-4 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>

                    {{-- Dropdown menu --}}
                    <div
                        x-show="open"
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        @click.away="open = false"
                        class="absolute right-0 mt-2 w-56 rounded-lg bg-zinc-800 border border-zinc-700 shadow-xl py-1 z-50"
                        x-cloak
                    >
                        {{-- User info --}}
                        <div class="px-4 py-3 border-b border-zinc-700">
                            <p class="text-sm font-semibold text-white truncate">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-zinc-400 truncate">{{ auth()->user()->email }}</p>
                        </div>

                        {{-- Link to main app --}}
                        <a
                            href="{{ config('app.main_app_url') }}"
                            class="flex items-center gap-2 px-4 py-2.5 text-sm text-zinc-300 hover:bg-zinc-700 hover:text-white"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                            </svg>
                            Open Main App
                        </a>

                        {{-- Report links (permission-gated) --}}
                        @if(auth()->user()->hasPermission('approvals.view_audit_log') || auth()->user()->hasPermission('approvals.executive_view'))
                            <div class="border-t border-zinc-700"></div>
                            @if(auth()->user()->hasPermission('approvals.view_audit_log'))
                            <a
                                href="{{ route('mobile.timeline-report') }}"
                                class="flex items-center gap-2 px-4 py-2.5 text-sm text-zinc-300 hover:bg-zinc-700 hover:text-white"
                                wire:navigate
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Timeline Report
                            </a>
                            @endif
                            @if(auth()->user()->hasPermission('approvals.executive_view'))
                            <a
                                href="{{ route('mobile.executive-overview') }}"
                                class="flex items-center gap-2 px-4 py-2.5 text-sm text-zinc-300 hover:bg-zinc-700 hover:text-white"
                                wire:navigate
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5" />
                                </svg>
                                Executive Overview
                            </a>
                            @endif
                        @endif

                        {{-- Logout --}}
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button
                                type="submit"
                                class="flex items-center gap-2 w-full px-4 py-2.5 text-sm text-zinc-300 hover:bg-zinc-700 hover:text-white"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                                </svg>
                                Log Out
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        {{-- Desktop sidebar navigation (hidden on mobile) --}}
        <aside class="hidden lg:flex lg:flex-col fixed top-0 left-0 bottom-0 w-60 bg-zinc-900 border-r border-zinc-800 z-50">
            {{-- Sidebar header with logo --}}
            <div class="h-14 flex items-center gap-2.5 px-5 border-b border-zinc-800 flex-shrink-0">
                <x-app-logo-icon class="size-7 fill-current text-indigo-400" />
                <span class="text-base font-semibold text-white">Approvals</span>
            </div>

            {{-- Navigation links --}}
            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                {{-- Home --}}
                <a
                    href="{{ route('mobile.dashboard') }}"
                    class="sidebar-nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('mobile.dashboard') ? 'bg-indigo-600/15 text-indigo-400' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white' }}"
                    wire:navigate
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ request()->routeIs('mobile.dashboard') ? '2' : '1.5' }}">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                    </svg>
                    Dashboard
                </a>

                {{-- Review (permission-gated) --}}
                @if(auth()->user()->hasPermission('approvals.view_dashboard'))
                <a
                    href="{{ route('mobile.review') }}"
                    class="sidebar-nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('mobile.review*') ? 'bg-indigo-600/15 text-indigo-400' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white' }}"
                    wire:navigate
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ request()->routeIs('mobile.review*') ? '2' : '1.5' }}">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z" />
                    </svg>
                    Review Queue
                </a>
                @endif

                {{-- Submit (permission-gated) --}}
                @if(auth()->user()->hasPermission('approvals.submit'))
                <a
                    href="{{ route('mobile.submit') }}"
                    class="sidebar-nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('mobile.submit') ? 'bg-indigo-600/15 text-indigo-400' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white' }}"
                    wire:navigate
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ request()->routeIs('mobile.submit') ? '2' : '1.5' }}">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Submit Request
                </a>
                @endif

                {{-- My Requests --}}
                <a
                    href="{{ route('mobile.my-submissions') }}"
                    class="sidebar-nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('mobile.my-submissions') ? 'bg-indigo-600/15 text-indigo-400' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white' }}"
                    wire:navigate
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ request()->routeIs('mobile.my-submissions') ? '2' : '1.5' }}">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                    My Requests
                </a>

                {{-- Reports section --}}
                @if(auth()->user()->hasPermission('approvals.view_audit_log') || auth()->user()->hasPermission('approvals.executive_view'))
                <div class="mt-4 pt-4 border-t border-zinc-800">
                    <p class="px-3 mb-2 text-[10px] font-semibold uppercase tracking-wider text-zinc-600">Reports</p>

                    @if(auth()->user()->hasPermission('approvals.view_audit_log'))
                    <a
                        href="{{ route('mobile.timeline-report') }}"
                        class="sidebar-nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('mobile.timeline-report') ? 'bg-indigo-600/15 text-indigo-400' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white' }}"
                        wire:navigate
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ request()->routeIs('mobile.timeline-report') ? '2' : '1.5' }}">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Timeline Report
                    </a>
                    @endif

                    @if(auth()->user()->hasPermission('approvals.executive_view'))
                    <a
                        href="{{ route('mobile.executive-overview') }}"
                        class="sidebar-nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('mobile.executive-overview') ? 'bg-indigo-600/15 text-indigo-400' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white' }}"
                        wire:navigate
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ request()->routeIs('mobile.executive-overview') ? '2' : '1.5' }}">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5" />
                        </svg>
                        Executive Overview
                    </a>
                    @endif
                </div>
                @endif
            </nav>

            {{-- Sidebar footer with main app link --}}
            <div class="px-3 py-3 border-t border-zinc-800 flex-shrink-0">
                <a
                    href="{{ config('app.main_app_url') }}"
                    class="flex items-center gap-2 px-3 py-2 text-xs text-zinc-500 hover:text-zinc-300 rounded-lg hover:bg-zinc-800 transition-colors"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                    </svg>
                    Open Main App
                </a>
            </div>
        </aside>

        {{-- Main content area --}}
        <main class="pt-14 pb-20 lg:pb-6 lg:pl-60">
            <div class="lg:max-w-5xl lg:mx-auto lg:px-6">
                {{ $slot }}
            </div>
        </main>

        {{-- Fixed bottom tab navigation (mobile only) --}}
        <nav class="fixed bottom-0 left-0 right-0 z-50 bg-zinc-900 border-t border-zinc-800 lg:hidden">
            <div class="flex items-center justify-around h-16 safe-bottom">
                {{-- Home tab --}}
                <a
                    href="{{ route('mobile.dashboard') }}"
                    class="flex flex-col items-center justify-center flex-1 h-full gap-0.5 {{ request()->routeIs('mobile.dashboard') ? 'bottom-nav-active' : 'text-zinc-500' }}"
                    wire:navigate
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ request()->routeIs('mobile.dashboard') ? '2.5' : '1.5' }}">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                    </svg>
                    <span class="text-[10px] font-medium">Home</span>
                </a>

                {{-- Review tab (permission-gated) --}}
                @if(auth()->user()->hasPermission('approvals.view_dashboard'))
                <a
                    href="{{ route('mobile.review') }}"
                    class="flex flex-col items-center justify-center flex-1 h-full gap-0.5 {{ request()->routeIs('mobile.review*') ? 'bottom-nav-active' : 'text-zinc-500' }}"
                    wire:navigate
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ request()->routeIs('mobile.review*') ? '2.5' : '1.5' }}">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z" />
                    </svg>
                    <span class="text-[10px] font-medium">Review</span>
                </a>
                @endif

                {{-- Submit tab (permission-gated) --}}
                @if(auth()->user()->hasPermission('approvals.submit'))
                <a
                    href="{{ route('mobile.submit') }}"
                    class="flex flex-col items-center justify-center flex-1 h-full gap-0.5 {{ request()->routeIs('mobile.submit') ? 'bottom-nav-active' : 'text-zinc-500' }}"
                    wire:navigate
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ request()->routeIs('mobile.submit') ? '2.5' : '1.5' }}">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-[10px] font-medium">Submit</span>
                </a>
                @endif

                {{-- My Requests tab --}}
                <a
                    href="{{ route('mobile.my-submissions') }}"
                    class="flex flex-col items-center justify-center flex-1 h-full gap-0.5 {{ request()->routeIs('mobile.my-submissions') ? 'bottom-nav-active' : 'text-zinc-500' }}"
                    wire:navigate
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ request()->routeIs('mobile.my-submissions') ? '2.5' : '1.5' }}">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                    <span class="text-[10px] font-medium">My Requests</span>
                </a>
            </div>
        </nav>

        @livewireScripts

        @stack('scripts')
    </body>
</html>
