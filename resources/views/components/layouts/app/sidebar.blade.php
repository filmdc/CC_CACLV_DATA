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

                @if(auth()->user()->hasAnyPermission(['programs.view', 'services.view', 'work_plans.view', 'work_plan_goals.view']) || auth()->user()->isAdmin())
                <flux:navlist.group :heading="__('Programs')" class="grid">
                    <flux:navlist.item icon="folder" :href="route('program.service.hub')" :current="request()->routeIs('program.service.hub')" wire:navigate>{{ __('Programs & Work Plans') }}</flux:navlist.item>
                </flux:navlist.group>
                @endif

                @if(auth()->user()->hasAnyPermission(['contracts.view', 'funders.view', 'due_dates.view']) || auth()->user()->isAdmin())
                <flux:navlist.group :heading="__('Contracts')" class="grid">
                    <flux:navlist.item icon="document-text" :href="route('contract.management')" :current="request()->routeIs('contract.management')" wire:navigate>{{ __('Contract Management') }}</flux:navlist.item>
                    <flux:navlist.item icon="bell" :href="route('notification-recipients.management')" :current="request()->routeIs('notification-recipients.management')" wire:navigate>{{ __('Notification Recipients') }}</flux:navlist.item>
                </flux:navlist.group>
                @endif

                @if(auth()->user()->hasAnyPermission(['properties.view', 'contractors.view', 'housing_jobs.view', 'projects.view']))
                <flux:navlist.group :heading="__('Housing & Projects')" class="grid">
                    @if(auth()->user()->hasAnyPermission(['housing_jobs.view', 'projects.view']))
                        <flux:navlist.item icon="view-columns" :href="route('projects.management')" :current="request()->routeIs(['projects.management', 'project.management', 'housing-job.management', 'project.pipeline'])" wire:navigate>{{ __('Projects') }}</flux:navlist.item>
                    @endif
                    @if(auth()->user()->hasPermission('properties.view'))
                        <flux:navlist.item icon="home-modern" :href="route('property.management')" :current="request()->routeIs('property.management')" wire:navigate>{{ __('Properties') }}</flux:navlist.item>
                    @endif
                    @if(auth()->user()->hasPermission('contractors.view'))
                        <flux:navlist.item icon="wrench-screwdriver" :href="route('contractor.management')" :current="request()->routeIs('contractor.management')" wire:navigate>{{ __('Contractors') }}</flux:navlist.item>
                    @endif
                </flux:navlist.group>
                @endif

                @if(auth()->user()->hasPermission('approvals.view_dashboard'))
                <flux:navlist.group :heading="__('Approvals')" class="grid">
                    <flux:navlist.item icon="clipboard-document-check" :href="route('approvals.pending')" :current="request()->routeIs('approvals.pending')" wire:navigate>
                        <div class="flex items-center justify-between w-full">
                            <span>{{ __('Pending Approvals') }}</span>
                            @php
                                $pendingCount = \App\Models\PendingApproval::where('status', 'pending')->count();
                            @endphp
                            @if($pendingCount > 0)
                                <span class="ml-2 px-2 py-0.5 text-xs font-semibold rounded-full bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">
                                    {{ $pendingCount }}
                                </span>
                            @endif
                        </div>
                    </flux:navlist.item>
                </flux:navlist.group>
                @endif

                @if(auth()->user()->hasPermission('pdf_templates.view'))
                <flux:navlist.group :heading="__('Administration')" class="grid">
                    <flux:navlist.item icon="document-text" :href="route('pdf-templates.index')" :current="request()->routeIs('pdf-templates.*')" wire:navigate>{{ __('PDF Form Templates') }}</flux:navlist.item>
                </flux:navlist.group>
                @endif

                @if(auth()->user()->hasAnyPermission(['reports.csbg']) || auth()->user()->isAdmin())
                <flux:navlist.group :heading="__('Reports')" class="grid">
                    @if(auth()->user()->hasPermission('reports.csbg') || auth()->user()->isAdmin())
                        <flux:navlist.item icon="chart-bar" :href="route('reports')" :current="request()->routeIs('reports')" wire:navigate>{{ __('Reporting Dashboard') }}</flux:navlist.item>
                    @endif
                </flux:navlist.group>
                @endif

                @if(auth()->user()->hasAnyPermission(['donor_gifts.view', 'donor_gifts.configure', 'intacct.configure']))
                <flux:navlist.group :heading="__('Accounting & Fiscal')" class="grid">
                    @if(auth()->user()->hasPermission('donor_gifts.view'))
                        <flux:navlist.item icon="currency-dollar" :href="route('donor-gifts.feed')" :current="request()->routeIs('donor-gifts.feed')" wire:navigate>{{ __('DonorPerfect Gifts') }}</flux:navlist.item>
                    @endif
                    @if(auth()->user()->hasPermission('donor_gifts.configure'))
                        <flux:navlist.item icon="cog" :href="route('donor-gifts.configuration')" :current="request()->routeIs('donor-gifts.configuration')" wire:navigate>{{ __('DP Configuration') }}</flux:navlist.item>
                    @endif
                    @if(auth()->user()->hasRole('Administrator'))
                        <flux:navlist.item icon="document-text" :href="route('donor-gifts.audit-log')" :current="request()->routeIs('donor-gifts.audit-log')" wire:navigate>{{ __('Check Image Audit Log') }}</flux:navlist.item>
                    @endif
                    @if(auth()->user()->hasPermission('intacct.configure'))
                        <flux:navlist.item icon="cloud-arrow-up" :href="route('sage-intacct.configuration')" :current="request()->routeIs('sage-intacct.configuration')" wire:navigate>{{ __('Sage Intacct Sync') }}</flux:navlist.item>
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

        @livewireScripts
        @fluxScripts

        <!-- Jodit Editor JavaScript -->
        <script src="https://unpkg.com/jodit@4.1.16/es2021/jodit.min.js"></script>

        <!-- Jodit Editor Initialization Script -->
        <script>
        console.log('=== JODIT INITIALIZATION SCRIPT LOADED ===');
        console.log('Jodit available:', typeof Jodit !== 'undefined');
        console.log('Livewire available:', typeof Livewire !== 'undefined');
        </script>

        <script>
        // Global Jodit Editor Manager - handles multiple editors safely
        if (typeof window.JoditEditorManager === 'undefined') {
            window.JoditEditorManager = {
                editors: {},
                initialized: false,
                activeEditorId: null, // Track which editor currently has focus

                initializeEditor(elementId, model, component) {
                    // NEVER reinitialize an editor that has focus or is already initialized
                    if (this.editors[elementId]) {
                        // Check if this editor has focus
                        const existingEditor = this.editors[elementId];
                        if (existingEditor.selection && existingEditor.selection.isFocused()) {
                            console.log(`Skipping reinitialization - editor has focus: ${elementId}`);
                            return this.editors[elementId];
                        }

                        // Check if this is the active editor
                        if (this.activeEditorId === elementId) {
                            console.log(`Skipping reinitialization - active editor: ${elementId}`);
                            return this.editors[elementId];
                        }

                        // Only destroy if truly needed
                        console.log(`Destroying existing editor: ${elementId}`);
                        this.editors[elementId].destruct();
                        delete this.editors[elementId];
                    }

                    const element = document.getElementById(elementId);
                    if (!element) {
                        console.log(`Element not found: ${elementId}`);
                        return;
                    }

                    // Skip if element is in a wire:ignore container
                    if (element.closest('[wire\\:ignore]')) {
                        console.log(`Skipping editor in wire:ignore container: ${elementId}`);
                        return;
                    }

                    // Check if Jodit is loaded
                    if (typeof Jodit === 'undefined') {
                        console.error('Jodit is not loaded!');
                        return;
                    }

                    console.log(`Creating new editor: ${elementId}`);

                    // Create editor instance
                    const editor = Jodit.make(element, {
                        "autofocus": false,
                        "toolbarSticky": false,
                        "uploader": {
                            "insertImageAsBase64URI": true
                        },
                        "toolbarButtonSize": "medium",
                        "showCharsCounter": false,
                        "showWordsCounter": false,
                        "showXPathInStatusbar": false,
                        "defaultActionOnPaste": "insert_clear_html",
                        "buttons": [
                            'bold', 'italic', 'underline', '|',
                            'ul', 'ol', '|',
                            'left', 'center', 'right', '|',
                            'link', 'image', '|',
                            'undo', 'redo'
                        ],
                        "height": 200
                    });

                    // Store editor reference
                    this.editors[elementId] = editor;

                    // Track focus state
                    editor.events.on('focus', () => {
                        this.activeEditorId = elementId;
                        console.log(`Editor focused: ${elementId}`);
                    });

                    editor.events.on('blur', () => {
                        if (this.activeEditorId === elementId) {
                            this.activeEditorId = null;
                            console.log(`Editor blurred: ${elementId}`);
                        }
                    });

                    // Set up change event with increased debouncing and focus check
                    let timeout;
                    editor.events.on('change', (value) => {
                        clearTimeout(timeout);
                        timeout = setTimeout(() => {
                            // Only update if this editor is still active or has focus
                            if (this.activeEditorId === elementId || (editor.selection && editor.selection.isFocused())) {
                                console.log(`Updating Livewire for: ${elementId}`);
                                component.set(model, value);
                            }
                        }, 500); // Increased from 300ms to 500ms
                    });

                    console.log(`Jodit editor initialized for: ${elementId}`);
                    return editor;
                },

                initializeAllEditors() {
                    console.log('Initializing all Jodit editors...');
                    const textareas = document.querySelectorAll('.jodit-textarea');
                    console.log('Found textareas:', textareas.length);

                    textareas.forEach(textarea => {
                        const elementId = textarea.getAttribute('data-editor-id');
                        const model = textarea.getAttribute('data-model');

                        console.log(`Processing textarea: ${elementId}, model: ${model}`);

                        // Skip if already initialized and active
                        if (this.editors[elementId] && this.activeEditorId === elementId) {
                            console.log(`Skipping active editor: ${elementId}`);
                            return;
                        }

                        if (elementId && model && window.Livewire) {
                            const wireIdElement = textarea.closest('[wire\\:id]');
                            if (wireIdElement) {
                                const wireId = wireIdElement.getAttribute('wire:id');
                                const component = Livewire.find(wireId);
                                if (component) {
                                    this.initializeEditor(elementId, model, component);
                                } else {
                                    console.error(`Livewire component not found for wire:id ${wireId}`);
                                }
                            } else {
                                console.error(`No wire:id found for textarea ${elementId}`);
                            }
                        } else {
                            console.error(`Missing data for textarea ${elementId}: elementId=${elementId}, model=${model}, Livewire=${!!window.Livewire}`);
                        }
                    });
                },

                destroyAll() {
                    Object.values(this.editors).forEach(editor => {
                        if (editor && editor.destruct) {
                            editor.destruct();
                        }
                    });
                    this.editors = {};
                }
            };

            // Initialize when Livewire is ready
            document.addEventListener('livewire:initialized', () => {
                console.log('Livewire initialized - setting up Jodit');
                setTimeout(() => {
                    window.JoditEditorManager.initializeAllEditors();
                }, 100);
            });

            // Reinitialize on Livewire navigation
            document.addEventListener('livewire:navigated', () => {
                console.log('Livewire navigated - reinitializing Jodit');
                setTimeout(() => {
                    window.JoditEditorManager.initializeAllEditors();
                }, 100);
            });

            // Initialize editors when new Livewire components are created (e.g., modals)
            Livewire.hook('component.init', ({ component }) => {
                console.log('New Livewire component initialized - checking for editors');
                // Only initialize after a delay to ensure DOM is ready
                setTimeout(() => {
                    window.JoditEditorManager.initializeAllEditors();
                }, 250);
            });

            // Clean up on page unload
            window.addEventListener('beforeunload', () => {
                window.JoditEditorManager.destroyAll();
            });

            window.JoditEditorManager.initialized = true;
        }

        // Initialize editors on DOM ready
        document.addEventListener('DOMContentLoaded', () => {
            console.log('DOM Ready - checking for Jodit and textareas');
            setTimeout(() => {
                if (window.JoditEditorManager) {
                    window.JoditEditorManager.initializeAllEditors();
                }
            }, 500);
        });
        </script>

        @stack('scripts')
    </body>
</html>
