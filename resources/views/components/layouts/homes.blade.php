<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
        <meta name="theme-color" content="#54585A" />

        @php
            $siteName = \App\Models\SiteSetting::get('site_name', config('app.name'));
            $siteFavicon = \App\Models\SiteSetting::get('site_favicon');
            $user = auth()->user();
        @endphp

        <title>{{ $title ?? 'CACLV Housing' }}</title>

        @if($siteFavicon)
            <link rel="icon" href="{{ Storage::url($siteFavicon) }}" sizes="any">
        @else
            <link rel="icon" href="/favicon.ico" sizes="any">
        @endif

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=anton:400|barlow:300,400,500,600,700|oswald:500,600,700" rel="stylesheet" />

        @vite(['resources/css/homes.css', 'resources/js/homes.js'])
        @livewireStyles

        @stack('styles')
    </head>
    <body class="min-h-screen antialiased" style="background: var(--calv-sand-15); color: var(--calv-slate);">
        <div class="lg:flex min-h-screen">
            {{-- Sidebar --}}
            <aside
                class="lg:fixed lg:inset-y-0 lg:left-0 lg:w-[250px] flex flex-col shrink-0"
                style="background: var(--calv-slate); color: #fff;"
            >
                {{-- Brand --}}
                <div class="px-5 pt-5 pb-4">
                    <div class="calv-h1 text-lg leading-none" style="color:#fff;">CACLV Housing</div>
                    <div class="calv-subhead" style="font-size:9px; letter-spacing:.14em; color: var(--calv-amber); margin-top:4px;">Fighting for good</div>
                </div>

                {{-- Primary nav --}}
                <nav class="px-3 flex-1 flex flex-col gap-0.5">
                    @php
                        $navItems = [
                            ['route' => 'homes.today', 'active' => 'homes.today', 'label' => 'Today',            'sub' => 'Follow-ups & next actions', 'perm' => null],
                            ['route' => 'homes.calls', 'active' => 'homes.calls*', 'label' => 'Calls & Intakes', 'sub' => 'The communication log',      'perm' => 'communications_log.view'],
                            ['route' => 'homes.projects', 'active' => 'homes.projects*', 'label' => 'Projects',  'sub' => 'Active rehab jobs',          'perm' => 'projects.view'],
                            ['route' => 'homes.reports', 'active' => 'homes.reports*', 'label' => 'Reports',     'sub' => 'For funders & board',        'perm' => 'housing.reports.view'],
                        ];
                    @endphp

                    @foreach($navItems as $item)
                        @if($item['perm'] === null || ($user && $user->hasPermission($item['perm'])))
                            @php $isActive = request()->routeIs($item['active']); @endphp
                            <a
                                href="{{ route($item['route']) }}"
                                wire:navigate
                                class="flex items-center gap-2.5 rounded-md px-2.5 py-2.5 transition-colors"
                                style="border-left: 3px solid {{ $isActive ? 'var(--calv-amber)' : 'transparent' }}; background: {{ $isActive ? 'rgba(255,255,255,.12)' : 'transparent' }};"
                            >
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm {{ $isActive ? 'font-semibold' : 'font-medium' }} leading-tight">{{ $item['label'] }}</span>
                                    <span class="block text-[10.5px] mt-0.5" style="color: rgba(255,255,255,.55);">{{ $item['sub'] }}</span>
                                </span>
                            </a>
                        @endif
                    @endforeach

                    {{-- Log a call CTA --}}
                    @if($user && $user->hasPermission('communications_log.create'))
                        {{-- x-data makes this its own Alpine root: the sidebar lives outside
                             every Livewire component, so without it Alpine never compiles
                             the x-on directive and the click is silently ignored. --}}
                        <button
                            type="button"
                            x-data
                            x-on:click="Livewire.dispatch('homes-open-quick-log')"
                            class="calv-btn calv-btn--primary mt-4 mx-2.5"
                        >
                            + Log a call
                        </button>
                    @endif
                </nav>

                {{-- Settings + user --}}
                <div class="px-3 py-3 mt-auto" style="border-top: 1px solid rgba(255,255,255,.12);">
                    <a
                        href="{{ route('homes.settings') }}"
                        wire:navigate
                        class="flex items-center gap-2.5 rounded-md px-2.5 py-2 text-sm"
                        style="color: {{ request()->routeIs('homes.settings*') ? '#fff' : 'rgba(255,255,255,.75)' }}; background: {{ request()->routeIs('homes.settings*') ? 'rgba(255,255,255,.12)' : 'transparent' }};"
                    >
                        Settings
                    </a>
                    <div class="px-2.5 pt-1 text-[10.5px] leading-relaxed" style="color: rgba(255,255,255,.4);">
                        Fund codes · Requirements · PDF templates · Contractors · Properties
                    </div>

                    {{-- User row --}}
                    <div x-data="{ open: false }" class="relative mt-3 pt-3" style="border-top: 1px solid rgba(255,255,255,.12);">
                        <button
                            type="button"
                            x-on:click="open = !open"
                            class="flex items-center gap-2.5 w-full rounded-md px-2.5 py-2 text-left"
                        >
                            <span class="flex items-center justify-center h-8 w-8 rounded-full text-xs font-semibold shrink-0" style="background: var(--calv-amber); color: var(--calv-slate);">
                                {{ $user?->initials() }}
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm truncate">{{ $user?->name }}</span>
                            </span>
                            <svg class="h-4 w-4 shrink-0" style="color: rgba(255,255,255,.5);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" /></svg>
                        </button>
                        <div
                            x-show="open"
                            x-transition
                            x-on:click.away="open = false"
                            x-cloak
                            class="absolute bottom-full mb-2 left-2.5 right-2.5 rounded-lg py-1 shadow-xl"
                            style="background: #fff; color: var(--calv-slate); border: 1px solid var(--calv-slate-15);"
                        >
                            <div class="px-3 py-2 border-b" style="border-color: var(--calv-slate-15);">
                                <p class="text-sm font-semibold truncate">{{ $user?->name }}</p>
                                <p class="text-xs truncate" style="color: var(--calv-slate-65);">{{ $user?->email }}</p>
                            </div>
                            <a href="{{ config('app.main_app_url') }}" class="block px-3 py-2 text-sm hover:bg-[var(--calv-sand-15)]">Open main app</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="block w-full text-left px-3 py-2 text-sm hover:bg-[var(--calv-sand-15)]">Log out</button>
                            </form>
                        </div>
                    </div>
                </div>
            </aside>

            {{-- Main content --}}
            <main class="flex-1 lg:pl-[250px] min-w-0">
                <div class="page-enter">
                    {{ $slot }}
                </div>
            </main>
        </div>

        {{-- Quick-log modal is present on every page; opened via the 'homes-open-quick-log' event --}}
        @if($user && $user->hasPermission('communications_log.create'))
            @livewire('homes.quick-log')
        @endif

        @livewireScripts
        @stack('scripts')
    </body>
</html>
