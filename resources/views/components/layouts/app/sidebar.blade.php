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

                @if(auth()->user()->hasAnyPermission(['participants.create', 'participants.view', 'households.view']))
                <flux:navlist.group :heading="__('Participants')" class="grid">
                    @if(auth()->user()->hasPermission('participants.create'))
                        <flux:navlist.item icon="user-plus" :href="route('participant.intake')" :current="request()->routeIs('participant.intake')" wire:navigate>{{ __('Participant Intake') }}</flux:navlist.item>
                    @endif
                    @if(auth()->user()->hasPermission('participants.view'))
                        <flux:navlist.item icon="user" :href="route('participant.management')" :current="request()->routeIs('participant.management')" wire:navigate>{{ __('Participant Management') }}</flux:navlist.item>
                    @endif
                    @if(auth()->user()->hasAnyPermission(['participants.export', 'participants.import']))
                        <flux:navlist.item icon="arrow-up-tray" :href="route('participant.import-export')" :current="request()->routeIs('participant.import-export')" wire:navigate>{{ __('Import/Export Data') }}</flux:navlist.item>
                    @endif
                    @if(auth()->user()->hasPermission('households.view'))
                        <flux:navlist.item icon="users" :href="route('household.management')" :current="request()->routeIs('household.management')" wire:navigate>{{ __('Household Management') }}</flux:navlist.item>
                    @endif
                </flux:navlist.group>
                @endif

                @if(auth()->user()->hasAnyPermission(['programs.view', 'services.view', 'enrollments.view', 'contracts.view', 'work_plans.view', 'work_plan_goals.view']) || auth()->user()->isAdmin())
                <flux:navlist.group :heading="__('Programs')" class="grid">
                    @if(auth()->user()->hasPermission('programs.view') || auth()->user()->isAdmin())
                        <flux:navlist.item icon="folder" :href="route('program.management')" :current="request()->routeIs('program.management')" wire:navigate>{{ __('Program Management') }}</flux:navlist.item>
                    @endif
                    @if(auth()->user()->hasPermission('services.view') || auth()->user()->isAdmin())
                        <flux:navlist.item icon="briefcase" :href="route('service.management')" :current="request()->routeIs('service.management')" wire:navigate>{{ __('Service Management') }}</flux:navlist.item>
                    @endif
                    @if(auth()->user()->hasPermission('enrollments.view') || auth()->user()->isAdmin())
                        <flux:navlist.item icon="user-group" :href="route('enrollment.management')" :current="request()->routeIs('enrollment.management')" wire:navigate>{{ __('Enrollment Management') }}</flux:navlist.item>
                    @endif
                    @if(auth()->user()->hasPermission('contracts.view') || auth()->user()->isAdmin())
                        <flux:navlist.item icon="document-text" :href="route('contract.management')" :current="request()->routeIs('contract.management')" wire:navigate>{{ __('Contract Management') }}</flux:navlist.item>
                    @endif
                    @if(auth()->user()->hasPermission('funders.view') || auth()->user()->isAdmin())
                        <flux:navlist.item icon="building-office" :href="route('funder.management')" :current="request()->routeIs('funder.management')" wire:navigate>{{ __('Funder Management') }}</flux:navlist.item>
                    @endif
                    @if(auth()->user()->hasPermission('due_dates.view') || auth()->user()->isAdmin())
                        <flux:navlist.item icon="calendar-days" :href="route('contract-report-due-dates.management')" :current="request()->routeIs('contract-report-due-dates.management')" wire:navigate>{{ __('Contract Report Due Dates') }}</flux:navlist.item>
                    @endif
                    @if(auth()->user()->hasPermission('work_plans.view') || auth()->user()->isAdmin())
                        <flux:navlist.item icon="calendar" :href="route('work-plan.management')" :current="request()->routeIs('work-plan.management')" wire:navigate>{{ __('Work Plans') }}</flux:navlist.item>
                    @endif
                    @if(auth()->user()->hasPermission('work_plan_goals.view') || auth()->user()->isAdmin())
                        <flux:navlist.item icon="flag" :href="route('work-plan-goal.management')" :current="request()->routeIs('work-plan-goal.management')" wire:navigate>{{ __('Work Plan Goals') }}</flux:navlist.item>
                    @endif
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
                    @if(auth()->user()->hasPermission('users.view') || auth()->user()->isAdmin())
                        <flux:navlist.item icon="users" :href="route('user.management')" :current="request()->routeIs('user.management')" wire:navigate>{{ __('User Management') }}</flux:navlist.item>
                    @endif
                    @if(auth()->user()->hasPermission('roles.view') || auth()->user()->isAdmin())
                        <flux:navlist.item icon="shield-check" :href="route('role-permission.management')" :current="request()->routeIs('role-permission.management')" wire:navigate>{{ __('Roles & Permissions') }}</flux:navlist.item>
                    @endif
                    @if(auth()->user()->hasPermission('poverty_guidelines.view') || auth()->user()->isAdmin())
                        <flux:navlist.item icon="currency-dollar" :href="route('poverty-guideline.management')" :current="request()->routeIs('poverty-guideline.management')" wire:navigate>{{ __('Poverty Guidelines') }}</flux:navlist.item>
                    @endif
                    @if(auth()->user()->hasPermission('reference_data.view') || auth()->user()->isAdmin())
                        <flux:navlist.item icon="table-cells" :href="route('reference-data.management')" :current="request()->routeIs('reference-data.management')" wire:navigate>{{ __('Reference Data') }}</flux:navlist.item>
                    @endif
                    @if(auth()->user()->hasPermission('reference_data_sets.view') || auth()->user()->isAdmin())
                        <flux:navlist.item icon="folder-git-2" :href="route('reference-data-set.management')" :current="request()->routeIs('reference-data-set.management')" wire:navigate>{{ __('Reference Data Sets') }}</flux:navlist.item>
                    @endif
                    @if(auth()->user()->hasPermission('admin.access') || auth()->user()->isAdmin())
                        <flux:navlist.item icon="cog-6-tooth" :href="route('admin.panel')" :current="request()->routeIs('admin.panel')" wire:navigate>{{ __('Admin Panel') }}</flux:navlist.item>
                    @endif
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
