<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky stashable class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            <a href="{{ route('dashboard') }}" class="me-5 flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
                <x-app-logo />
            </a>

            <flux:navlist variant="outline">
                <flux:navlist.group :heading="__('Platform')" class="grid">
                    <flux:navlist.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>{{ __('Dashboard') }}</flux:navlist.item>
                </flux:navlist.group>

                @if(auth()->user()->hasAnyPermission(['participants.create', 'participants.view', 'households.view', 'enrollments.view']))
                <flux:navlist.group :heading="__('Data Management')" class="grid">
                    <flux:navlist.item icon="users" :href="route('participant.household.hub')" :current="request()->routeIs('participant.household.hub')" wire:navigate>{{ __('Participants & Households') }}</flux:navlist.item>
                </flux:navlist.group>
                @endif

                @if(auth()->user()->hasAnyPermission(['programs.view', 'services.view', 'contracts.view', 'work_plans.view', 'work_plan_goals.view']) || auth()->user()->isAdmin())
                <flux:navlist.group :heading="__('Programs')" class="grid">
                    <flux:navlist.item icon="folder" :href="route('program.service.hub')" :current="request()->routeIs('program.service.hub')" wire:navigate>{{ __('Programs & Services') }}</flux:navlist.item>
                </flux:navlist.group>
                @endif

                @if(auth()->user()->hasAnyPermission(['properties.view', 'contractors.view', 'housing_jobs.view']))
                <flux:navlist.group :heading="__('Housing Module')" class="grid">
                    @if(auth()->user()->hasPermission('properties.view'))
                        <flux:navlist.item icon="home-modern" :href="route('property.management')" :current="request()->routeIs('property.management')" wire:navigate>{{ __('Property Management') }}</flux:navlist.item>
                    @endif
                    @if(auth()->user()->hasPermission('contractors.view'))
                        <flux:navlist.item icon="wrench-screwdriver" :href="route('contractor.management')" :current="request()->routeIs('contractor.management')" wire:navigate>{{ __('Contractor Management') }}</flux:navlist.item>
                    @endif
                    @if(auth()->user()->hasPermission('housing_jobs.view'))
                        <flux:navlist.item icon="clipboard-document-list" :href="route('housing-job.management')" :current="request()->routeIs('housing-job.management')" wire:navigate>{{ __('Housing Jobs') }}</flux:navlist.item>
                    @endif
                </flux:navlist.group>
                @endif

                @if(auth()->user()->hasAnyPermission(['reports.csbg']) || auth()->user()->isAdmin())
                <flux:navlist.group :heading="__('Reports')" class="grid">
                    @if(auth()->user()->hasPermission('reports.csbg') || auth()->user()->isAdmin())
                        <flux:navlist.item icon="chart-bar" :href="route('reports')" :current="request()->routeIs('reports')" wire:navigate>{{ __('Reporting Dashboard') }}</flux:navlist.item>
                    @endif
                </flux:navlist.group>
                @endif

                @if(auth()->user()->hasAnyPermission(['users.view', 'roles.view', 'poverty_guidelines.view', 'reference_data.view', 'reference_data_sets.view', 'admin.access']) || auth()->user()->isAdmin())
                <flux:navlist.group :heading="__('Administration')" class="grid">
                    <flux:navlist.item icon="cog-6-tooth" :href="route('admin.panel')" :current="request()->routeIs('admin.panel')" wire:navigate>{{ __('Admin Panel') }}</flux:navlist.item>
                </flux:navlist.group>
                @endif
            </flux:navlist>

            <flux:spacer />

            <flux:navlist variant="outline">
                <flux:navlist.item icon="folder-git-2" href="https://github.com/laravel/livewire-starter-kit" target="_blank">
                {{ __('Repository') }}
                </flux:navlist.item>

                <flux:navlist.item icon="book-open-text" href="https://laravel.com/docs/starter-kits#livewire" target="_blank">
                {{ __('Documentation') }}
                </flux:navlist.item>
            </flux:navlist>

            <!-- Desktop User Menu -->
            <flux:dropdown position="bottom" align="start">
                <flux:profile
                    :name="auth()->user()->name"
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevrons-up-down"
                />

                <flux:menu class="w-[220px]">
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('settings.profile')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('settings.profile')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @fluxScripts
        @livewireScripts
    </body>
</html>
