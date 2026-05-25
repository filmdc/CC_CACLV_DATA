<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Communications Log
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto p-6">
        <!-- Flash Messages -->
        @if (session()->has('success'))
            <div class="mb-4 rounded-md bg-green-50 dark:bg-green-900/50 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="mb-4 rounded-md bg-red-50 dark:bg-red-900/50 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-red-800 dark:text-red-200">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-1 gap-4 mb-6 max-w-sm">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Today's Communications</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $todayCount }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl">
            <!-- Header -->
            <div class="p-6 border-b border-gray-200 dark:border-zinc-700">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Communications Log</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Track all communications not tied to specific projects</p>
                    </div>
                    @can('communications_log.create')
                    <button wire:click="showCreateForm"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 text-sm font-medium">
                        + New Communication
                    </button>
                    @endcan
                </div>
            </div>

            <!-- Filters -->
            <div class="p-4 border-b border-gray-200 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-900/50">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-8 gap-3">
                    <!-- Search -->
                    <div class="relative xl:col-span-2">
                        <input type="text" wire:model.live.debounce.300ms="search"
                               placeholder="Search..."
                               class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md text-sm">
                        <svg class="w-4 h-4 absolute left-3 top-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>

                    <!-- Type Filter -->
                    <select wire:model.live="typeFilter"
                            class="px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md text-sm">
                        <option value="">All Types</option>
                        <option value="phone_call">Phone Call</option>
                        <option value="email">Email</option>
                        <option value="in_person">In Person</option>
                        <option value="site_visit">Site Visit</option>
                        <option value="video_call">Video Call</option>
                        <option value="text">Text Message</option>
                        <option value="mail">Mail</option>
                        <option value="voicemail">Voicemail</option>
                        <option value="fax">Fax</option>
                        <option value="other">Other</option>
                    </select>

                    <!-- Category Filter -->
                    <select wire:model.live="categoryFilter"
                            class="px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md text-sm">
                        <option value="">All Categories</option>
                        <option value="general">General</option>
                        <option value="intake">Intake</option>
                        <option value="follow_up">Follow-up</option>
                        <option value="complaint">Complaint</option>
                        <option value="inquiry">Inquiry</option>
                        <option value="referral">Referral</option>
                        <option value="outreach">Outreach</option>
                        <option value="scheduling">Scheduling</option>
                        <option value="documentation">Documentation</option>
                        <option value="other">Other</option>
                    </select>

                    <!-- Priority Filter -->
                    <select wire:model.live="priorityFilter"
                            class="px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md text-sm">
                        <option value="">All Priorities</option>
                        <option value="low">Low</option>
                        <option value="normal">Normal</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>

                    <!-- Direction Filter -->
                    <select wire:model.live="directionFilter"
                            class="px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md text-sm">
                        <option value="">All Directions</option>
                        <option value="incoming">Incoming</option>
                        <option value="outgoing">Outgoing</option>
                    </select>

                    <!-- Service Filter -->
                    <select wire:model.live="serviceFilter"
                            class="px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md text-sm">
                        <option value="">All Services</option>
                        @foreach($allServices as $service)
                            <option value="{{ $service->id }}">{{ $service->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- P-03: second row for tag, fund code (multi), and date range -->
                <div class="mt-3 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3" data-filter-row="2">
                    @if($allTags->isNotEmpty())
                        <select wire:model.live="tagFilter"
                                class="px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md text-sm">
                            <option value="">All Tags</option>
                            @foreach($allTags as $tag)
                                <option value="{{ $tag->id }}">{{ $tag->name }}</option>
                            @endforeach
                        </select>
                    @endif

                    @if($allFundCodes->isNotEmpty())
                        <select wire:model.live="fundCodeFilter"
                                multiple
                                size="3"
                                aria-label="Fund codes (hold Ctrl/Cmd to select multiple)"
                                class="px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md text-sm">
                            @foreach($allFundCodes as $fundCode)
                                <option value="{{ $fundCode->id }}">{{ $fundCode->full_display }}</option>
                            @endforeach
                        </select>
                    @endif

                    <input type="date"
                           wire:model.live="dateFrom"
                           aria-label="From date"
                           placeholder="From"
                           class="px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md text-sm">

                    <input type="date"
                           wire:model.live="dateTo"
                           aria-label="To date"
                           placeholder="To"
                           class="px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md text-sm">
                </div>

                <!-- P-03: third row — multi-select status pills (follow-up + conversion) + clear -->
                <div class="mt-3 flex flex-wrap items-center gap-x-6 gap-y-2" data-filter-row="3">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wide">Follow-up:</span>
                        @foreach (['pending' => 'Pending', 'overdue' => 'Overdue', 'completed' => 'Completed'] as $value => $label)
                            <label class="inline-flex items-center gap-1 px-2 py-1 rounded-md border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-xs cursor-pointer hover:bg-gray-50 dark:hover:bg-zinc-600">
                                <input type="checkbox"
                                       wire:model.live="followUpFilter"
                                       value="{{ $value }}"
                                       class="rounded border-gray-300 dark:border-zinc-600 text-indigo-600 focus:ring-indigo-500">
                                <span class="text-gray-900 dark:text-white">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wide">Conversion:</span>
                        @foreach (['converted' => 'Converted', 'not_converted' => 'Not converted'] as $value => $label)
                            <label class="inline-flex items-center gap-1 px-2 py-1 rounded-md border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-xs cursor-pointer hover:bg-gray-50 dark:hover:bg-zinc-600">
                                <input type="checkbox"
                                       wire:model.live="conversionFilter"
                                       value="{{ $value }}"
                                       class="rounded border-gray-300 dark:border-zinc-600 text-indigo-600 focus:ring-indigo-500">
                                <span class="text-gray-900 dark:text-white">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>

                    <!-- P-04: view-mode toggle (table | card). URL-persisted, no per-user default. -->
                    <div class="ml-auto flex items-center gap-2" data-view-mode-toggle>
                        <div class="inline-flex rounded-md border border-gray-300 dark:border-zinc-600 overflow-hidden text-xs">
                            <button type="button"
                                    wire:click="$set('viewMode', 'table')"
                                    aria-pressed="{{ $viewMode === 'table' ? 'true' : 'false' }}"
                                    class="px-2 py-1 inline-flex items-center gap-1 {{ $viewMode === 'table' ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-zinc-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-zinc-600' }}">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                                </svg>
                                Table
                            </button>
                            <button type="button"
                                    wire:click="$set('viewMode', 'card')"
                                    aria-pressed="{{ $viewMode === 'card' ? 'true' : 'false' }}"
                                    class="px-2 py-1 inline-flex items-center gap-1 border-l border-gray-300 dark:border-zinc-600 {{ $viewMode === 'card' ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-zinc-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-zinc-600' }}">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                                </svg>
                                Cards
                            </button>
                        </div>

                        <button type="button"
                                wire:click="clearFilters"
                                class="text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:underline">
                            Clear all filters
                        </button>
                    </div>
                </div>
            </div>

            <!-- Communications List — table or card view per viewMode -->
            <div class="divide-y divide-gray-200 dark:divide-zinc-700" data-view-mode="{{ $viewMode }}">
                @forelse($logs as $log)
                    <div class="{{ $viewMode === 'card' ? 'p-6' : 'p-4' }} hover:bg-gray-50 dark:hover:bg-zinc-700/50 cursor-pointer" wire:click="viewDetail({{ $log->id }})">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <!-- Type Icon -->
                                    <span class="flex items-center justify-center w-10 h-10 rounded-full
                                        @if($log->communication_type === 'phone_call') bg-green-100 text-green-600 dark:bg-green-900 dark:text-green-400
                                        @elseif($log->communication_type === 'email') bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-400
                                        @elseif($log->communication_type === 'in_person') bg-purple-100 text-purple-600 dark:bg-purple-900 dark:text-purple-400
                                        @elseif($log->communication_type === 'site_visit') bg-orange-100 text-orange-600 dark:bg-orange-900 dark:text-orange-400
                                        @else bg-gray-100 text-gray-600 dark:bg-gray-900 dark:text-gray-400
                                        @endif">
                                        @if($log->communication_type === 'phone_call')
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                            </svg>
                                        @elseif($log->communication_type === 'email')
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                            </svg>
                                        @elseif($log->communication_type === 'in_person')
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                        @elseif($log->communication_type === 'site_visit')
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                            </svg>
                                        @else
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                            </svg>
                                        @endif
                                    </span>
                                    <div>
                                        <h4 class="font-medium text-gray-900 dark:text-white">{{ $log->subject }}</h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ $log->contact_full_name }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Tags -->
                                <div class="flex flex-wrap items-center gap-2 text-xs mb-2">
                                    <span class="px-2 py-1 rounded-full {{ $log->direction === 'outgoing' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' }}">
                                        {{ $log->direction_display }}
                                    </span>
                                    <span class="px-2 py-1 rounded-full bg-gray-100 text-gray-800 dark:bg-zinc-600 dark:text-gray-300">
                                        {{ $log->communication_type_display }}
                                    </span>
                                    <span class="px-2 py-1 rounded-full bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300">
                                        {{ $log->category_display }}
                                    </span>
                                    @if($log->priority !== 'normal')
                                        <span class="px-2 py-1 rounded-full
                                            @if($log->priority === 'urgent') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300
                                            @elseif($log->priority === 'high') bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300
                                            @else bg-gray-100 text-gray-600 dark:bg-zinc-600 dark:text-gray-400
                                            @endif">
                                            {{ $log->priority_display }}
                                        </span>
                                    @endif
                                    @if($log->related_entity)
                                        <span class="px-2 py-1 rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300">
                                            {{ $log->related_entity }}
                                        </span>
                                    @endif
                                    @if($log->converted_to_job_id)
                                        <span class="px-2 py-1 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-300">
                                            Converted to Project
                                        </span>
                                    @endif
                                    <span class="text-gray-500 dark:text-gray-400">
                                        {{ $log->communication_date->format('M d, Y') }}
                                    </span>
                                </div>

                                <!-- Services -->
                                @if($log->services->isNotEmpty())
                                    <div class="flex flex-wrap gap-1 mb-2">
                                        @foreach($log->services as $service)
                                            <span class="px-2 py-0.5 text-xs rounded bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-300">
                                                {{ $service->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif

                                <!-- Custom Tags -->
                                @if($log->tags->isNotEmpty())
                                    <div class="flex flex-wrap gap-1 mb-2">
                                        @foreach($log->tags as $tag)
                                            <span class="px-2 py-0.5 text-xs rounded" style="background-color: {{ $tag->color }}20; color: {{ $tag->color }};">
                                                {{ $tag->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif

                                @if($log->summary)
                                    {{-- P-04: table view clips at 200 chars; card view shows the full summary. --}}
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-2 {{ $viewMode === 'card' ? 'whitespace-pre-line' : '' }}">
                                        {{ $viewMode === 'card' ? $log->summary : Str::limit($log->summary, 200) }}
                                    </p>
                                @endif

                                @if($log->outcome)
                                    <div class="mt-2 p-2 bg-blue-50 dark:bg-blue-900/20 rounded text-sm">
                                        <span class="font-medium text-blue-800 dark:text-blue-300">Outcome:</span>
                                        <span class="text-blue-700 dark:text-blue-400">{{ $log->outcome }}</span>
                                    </div>
                                @endif

                                {{-- P-04 card-only sections: full notes body, fund-code badges, contact metadata. --}}
                                @if($viewMode === 'card' && $log->notes)
                                    <div class="mt-3 p-3 bg-gray-50 dark:bg-zinc-700/40 rounded text-sm text-gray-700 dark:text-gray-200 whitespace-pre-line" data-card-notes>
                                        {{ $log->notes }}
                                    </div>
                                @endif

                                @if($viewMode === 'card' && $log->anticipatedFundCodes->isNotEmpty())
                                    <div class="mt-3 flex flex-wrap items-center gap-2 text-xs" data-card-fund-codes>
                                        <span class="font-medium text-gray-600 dark:text-gray-400">Fund codes:</span>
                                        @foreach($log->anticipatedFundCodes as $fundCode)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200" title="{{ $fundCode->description }}">
                                                {{ $fundCode->code }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif

                                @if($viewMode === 'card' && ($log->contact_organization || $log->contact_email || $log->contact_phone || $log->contact_address))
                                    <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-1 text-xs text-gray-600 dark:text-gray-300" data-card-contact-metadata>
                                        @if($log->contact_organization)
                                            <div><span class="font-medium text-gray-700 dark:text-gray-200">Organization:</span> {{ $log->contact_organization }}</div>
                                        @endif
                                        @if($log->contact_email)
                                            <div><span class="font-medium text-gray-700 dark:text-gray-200">Email:</span> {{ $log->contact_email }}</div>
                                        @endif
                                        @if($log->contact_phone)
                                            <div><span class="font-medium text-gray-700 dark:text-gray-200">Phone:</span> {{ $log->contact_phone }}</div>
                                        @endif
                                        @if($log->contact_address)
                                            <div class="sm:col-span-2"><span class="font-medium text-gray-700 dark:text-gray-200">Address:</span> {{ $log->contact_address }}@if($log->contact_city), {{ $log->contact_city }}@endif @if($log->contact_state) {{ $log->contact_state }}@endif @if($log->contact_zip) {{ $log->contact_zip }}@endif</div>
                                        @endif
                                    </div>
                                @endif

                                <div class="mt-2 text-xs text-gray-400 dark:text-gray-500">
                                    Created by {{ $log->createdBy?->name ?? 'Unknown' }} on {{ $log->created_at->format('M d, Y') }}
                                    @if($log->updates_count ?? $log->updates->count() > 0)
                                        <span class="ml-2 text-indigo-500">{{ $log->updates_count ?? $log->updates->count() }} update(s)</span>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-start space-x-2 ml-4" wire:click.stop>
                                @can('communications_log.edit')
                                <button wire:click="showEditForm({{ $log->id }})"
                                        class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                                @endcan
                                @can('communications_log.delete')
                                <button wire:click="delete({{ $log->id }})"
                                        wire:confirm="Delete this communication log?"
                                        class="text-red-600 hover:text-red-800 dark:text-red-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                                @endcan
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No communications logged</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by creating a new communication entry.</p>
                        @can('communications_log.create')
                        <button wire:click="showCreateForm"
                                class="mt-4 px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                            + New Communication
                        </button>
                        @endcan
                    </div>
                @endforelse
            </div>

            <!-- Pagination -->
            @if($logs->hasPages())
                <div class="px-4 py-3 border-t border-gray-200 dark:border-zinc-700">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Detail View Modal -->
    @if($showDetailModal && $viewingLog)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50"
             wire:click.self="closeDetailModal"
             x-data="{
                 handleKey(e) {
                     const tag = document.activeElement?.tagName;
                     const inField = tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT';
                     // E — Edit (spec §6). Don't intercept when typing in a field.
                     if (!inField && (e.key === 'e' || e.key === 'E')) {
                         e.preventDefault();
                         $wire.call('editFromViewer');
                         return;
                     }
                     // N — focus the Activity Updates composer.
                     if (!inField && (e.key === 'n' || e.key === 'N')) {
                         e.preventDefault();
                         document.querySelector('[data-comm-composer]')?.focus();
                         return;
                     }
                     if (e.key === 'Escape' && !inField) {
                         $wire.call('closeDetailModal');
                     }
                 },
             }"
             x-on:keydown.window="handleKey">
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white">{{ $viewingLog->subject }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $viewingLog->communication_date->format('F d, Y') }}</p>
                    </div>
                    <button wire:click="closeDetailModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Phase 11: three-focus quick-nav. Kaitlyn moves between the three
                     mental models of a Comm Log entry: logging, services, requirements. --}}
                <nav class="flex flex-wrap gap-2 mb-4 text-[11px]" aria-label="Sections">
                    <a href="#focus-logging" class="px-3 py-1.5 rounded-full bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-200 hover:bg-blue-200 dark:hover:bg-blue-900/70 font-medium">
                        1 · Communication logging
                    </a>
                    <a href="#focus-services" class="px-3 py-1.5 rounded-full bg-emerald-100 dark:bg-emerald-900/40 text-emerald-800 dark:text-emerald-200 hover:bg-emerald-200 dark:hover:bg-emerald-900/70 font-medium">
                        2 · Service selection
                    </a>
                    <a href="#focus-requirements" class="px-3 py-1.5 rounded-full bg-purple-100 dark:bg-purple-900/40 text-purple-800 dark:text-purple-200 hover:bg-purple-200 dark:hover:bg-purple-900/70 font-medium">
                        3 · Requirements &amp; compliance
                    </a>
                </nav>

                {{-- Focus 1 — Communication logging --}}
                <section id="focus-logging" class="scroll-mt-4">
                    <div class="flex items-baseline gap-2 mb-2 border-b border-blue-200 dark:border-blue-800 pb-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] uppercase tracking-wider font-bold bg-blue-600 text-white">Focus 1</span>
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Communication logging</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">What happened — date, type, contact, summary, activity thread.</p>
                    </div>

                <!-- Status Tags -->
                <div class="flex flex-wrap gap-2 mb-4">
                    <span class="px-3 py-1 rounded-full text-sm {{ $viewingLog->direction === 'outgoing' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' }}">
                        {{ $viewingLog->direction_display }}
                    </span>
                    <span class="px-3 py-1 rounded-full text-sm bg-gray-100 text-gray-800 dark:bg-zinc-600 dark:text-gray-300">
                        {{ $viewingLog->communication_type_display }}
                    </span>
                    <span class="px-3 py-1 rounded-full text-sm bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300">
                        {{ $viewingLog->category_display }}
                    </span>
                    <span class="px-3 py-1 rounded-full text-sm
                        @if($viewingLog->priority === 'urgent') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300
                        @elseif($viewingLog->priority === 'high') bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300
                        @elseif($viewingLog->priority === 'low') bg-gray-100 text-gray-600 dark:bg-zinc-600 dark:text-gray-400
                        @else bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300
                        @endif">
                        {{ $viewingLog->priority_display }} Priority
                    </span>
                    @if($viewingLog->converted_to_job_id)
                        <span class="px-3 py-1 rounded-full text-sm bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-300 flex items-center gap-2">
                            <span>Converted to Project: {{ $viewingLog->convertedToJob->job_number ?? 'N/A' }}</span>
                            @if(auth()->user()->isAdmin())
                                <button wire:click="resetConversion({{ $viewingLog->id }})"
                                        wire:confirm="Are you sure you want to reset this conversion? This will allow you to re-convert this communication log with the updated validation and automation. The original project ({{ $viewingLog->convertedToJob->job_number ?? 'N/A' }}) will not be deleted."
                                        class="ml-1 text-xs text-emerald-700 dark:text-emerald-400 hover:text-emerald-900 dark:hover:text-emerald-200 underline"
                                        title="Admin: Reset conversion to re-run with new validation">
                                    Reset
                                </button>
                            @endif
                        </span>
                    @endif
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <!-- Contact Information -->
                    <div class="bg-gray-50 dark:bg-zinc-700/50 rounded-lg p-4">
                        <h4 class="font-medium text-gray-900 dark:text-white mb-3">Contact Information</h4>
                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <dt class="text-gray-500 dark:text-gray-400">Name:</dt>
                                <dd class="text-gray-900 dark:text-white font-medium">{{ $viewingLog->contact_full_name }}</dd>
                            </div>
                            @if($viewingLog->contact_role)
                            <div class="flex justify-between">
                                <dt class="text-gray-500 dark:text-gray-400">Role:</dt>
                                <dd class="text-gray-900 dark:text-white">{{ $viewingLog->contact_role_display }}</dd>
                            </div>
                            @endif
                            @if($viewingLog->contact_full_address)
                            <div class="flex justify-between">
                                <dt class="text-gray-500 dark:text-gray-400">Address:</dt>
                                <dd class="text-gray-900 dark:text-white">{{ $viewingLog->contact_full_address }}</dd>
                            </div>
                            @endif
                            @if($viewingLog->contact_phone)
                            <div class="flex justify-between">
                                <dt class="text-gray-500 dark:text-gray-400">Phone:</dt>
                                <dd class="text-gray-900 dark:text-white">{{ $viewingLog->contact_phone }}</dd>
                            </div>
                            @endif
                            @if($viewingLog->contact_email)
                            <div class="flex justify-between">
                                <dt class="text-gray-500 dark:text-gray-400">Email:</dt>
                                <dd class="text-gray-900 dark:text-white">{{ $viewingLog->contact_email }}</dd>
                            </div>
                            @endif
                        </dl>
                    </div>

                    <!-- Linked Records -->
                    <div class="bg-gray-50 dark:bg-zinc-700/50 rounded-lg p-4">
                        <h4 class="font-medium text-gray-900 dark:text-white mb-3">Linked Records</h4>
                        <dl class="space-y-2 text-sm">
                            @if($viewingLog->participant)
                            <div class="flex justify-between items-center">
                                <dt class="text-gray-500 dark:text-gray-400">Participant:</dt>
                                <dd class="flex items-center gap-2">
                                    <span class="text-gray-900 dark:text-white">{{ $viewingLog->participant->full_name }}</span>
                                    @if($viewingLog->household)
                                        <span class="text-xs text-gray-500 dark:text-gray-400">({{ $viewingLog->household->household_code }})</span>
                                    @endif
                                    @if(auth()->user()->isAdmin())
                                    <button wire:click="unlinkParticipant({{ $viewingLog->id }})"
                                            wire:confirm="Are you sure you want to unlink this participant? This will also unlink the household."
                                            class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 text-xs"
                                            title="Unlink participant">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                    @endif
                                </dd>
                            </div>
                            @endif
                            @if($viewingLog->property)
                            <div class="flex justify-between items-center">
                                <dt class="text-gray-500 dark:text-gray-400">Property:</dt>
                                <dd class="flex items-center gap-2">
                                    <span class="text-gray-900 dark:text-white">{{ $viewingLog->property->address_line_1 }}, {{ $viewingLog->property->city }}</span>
                                    @if(auth()->user()->isAdmin())
                                    <button wire:click="unlinkProperty({{ $viewingLog->id }})"
                                            wire:confirm="Are you sure you want to unlink this property?"
                                            class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 text-xs"
                                            title="Unlink property">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                    @endif
                                </dd>
                            </div>
                            @endif
                            @if($viewingLog->housingJob)
                            <div class="flex justify-between items-center">
                                <dt class="text-gray-500 dark:text-gray-400">Project:</dt>
                                <dd class="flex items-center gap-2">
                                    <span class="text-gray-900 dark:text-white">{{ $viewingLog->housingJob->job_number }} - {{ Str::limit($viewingLog->housingJob->job_title, 30) }}</span>
                                    @if(auth()->user()->isAdmin())
                                    <button wire:click="unlinkHousingJob({{ $viewingLog->id }})"
                                            wire:confirm="Are you sure you want to unlink this project?"
                                            class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 text-xs"
                                            title="Unlink project">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                    @endif
                                </dd>
                            </div>
                            @endif
                            @if(!$viewingLog->participant && !$viewingLog->property && !$viewingLog->housingJob)
                            <p class="text-gray-500 dark:text-gray-400 italic">No linked records</p>
                            @endif
                        </dl>
                        @if(auth()->user()->isAdmin() && ($viewingLog->participant || $viewingLog->property || $viewingLog->housingJob))
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-3 pt-2 border-t border-gray-200 dark:border-zinc-600">
                            <span class="text-red-600 dark:text-red-400">Admin:</span> Click <svg class="w-3 h-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg> to unlink a record
                        </p>
                        @endif
                    </div>
                </div>

                <!-- Services -->
                @if($viewingLog->services->isNotEmpty())
                <div class="mb-6">
                    <h4 class="font-medium text-gray-900 dark:text-white mb-2">Associated Services</h4>
                    <div class="flex flex-wrap gap-2">
                        @foreach($viewingLog->services as $service)
                            <span class="px-3 py-1 text-sm rounded-full bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-300">
                                {{ $service->name }}
                            </span>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Fund Codes -->
                @if($viewingLog->anticipatedFundCodes->isNotEmpty())
                <div class="mb-6">
                    <h4 class="font-medium text-gray-900 dark:text-white mb-2">Fund Codes</h4>
                    <div class="flex flex-wrap gap-2">
                        @foreach($viewingLog->anticipatedFundCodes as $fundCode)
                            <span class="px-3 py-1 text-sm rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300">
                                <span class="font-mono font-semibold">{{ $fundCode->code }}</span>
                                <span class="opacity-75">– {{ $fundCode->description }}</span>
                            </span>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Tags -->
                @if($viewingLog->tags->isNotEmpty())
                <div class="mb-6">
                    <h4 class="font-medium text-gray-900 dark:text-white mb-2">Tags</h4>
                    <div class="flex flex-wrap gap-2">
                        @foreach($viewingLog->tags as $tag)
                            <span class="px-3 py-1 text-sm rounded-full" style="background-color: {{ $tag->color }}20; color: {{ $tag->color }};">
                                {{ $tag->name }}
                            </span>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Summary & Notes -->
                @if($viewingLog->summary)
                <div class="mb-4">
                    <h4 class="font-medium text-gray-900 dark:text-white mb-2">Summary</h4>
                    <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $viewingLog->summary }}</p>
                </div>
                @endif

                @if($viewingLog->notes)
                <div class="mb-4">
                    <h4 class="font-medium text-gray-900 dark:text-white mb-2">Notes</h4>
                    <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $viewingLog->notes }}</p>
                </div>
                @endif

                @if($viewingLog->outcome)
                <div class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <h4 class="font-medium text-blue-800 dark:text-blue-300 mb-1">Outcome</h4>
                    <p class="text-blue-700 dark:text-blue-400">{{ $viewingLog->outcome }}</p>
                </div>
                @endif

                </section>{{-- /Focus 1 --}}

                {{-- Focus 2 — Service selection --}}
                <section id="focus-services" class="scroll-mt-4 mt-6">
                    <div class="flex items-baseline gap-2 mb-3 border-b border-emerald-200 dark:border-emerald-800 pb-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] uppercase tracking-wider font-bold bg-emerald-600 text-white">Focus 2</span>
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Service selection</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">What we're offering — housing services + anticipated fund codes.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        {{-- Housing services — interactive add/remove from the viewer. --}}
                        <div class="bg-emerald-50/40 dark:bg-emerald-900/15 rounded-lg p-4 border border-emerald-100 dark:border-emerald-900/40">
                            <div class="flex items-baseline justify-between mb-2">
                                <h5 class="text-xs font-semibold uppercase tracking-wider text-emerald-700 dark:text-emerald-300">Housing services</h5>
                                <span class="text-[10px] text-gray-500 dark:text-gray-400">Tick to attach · untick to remove</span>
                            </div>
                            @php
                                $attachedSvcIds = $viewingLog->housingServices()->pluck('housing_services.id')->map(fn ($v) => (int) $v)->all();
                            @endphp
                            @if($allHousingServices->isEmpty())
                                <p class="text-xs text-gray-500 dark:text-gray-400 italic">
                                    No housing services configured yet. Create them on
                                    <a href="{{ route('fund-codes.management', ['tab' => 'housing_services']) }}" class="text-emerald-700 dark:text-emerald-300 underline">/fund-codes</a>.
                                </p>
                            @else
                                <div class="space-y-1 max-h-72 overflow-y-auto">
                                    @foreach($allHousingServices as $hs)
                                        <label class="flex items-start gap-2 px-2 py-1.5 rounded hover:bg-emerald-50 dark:hover:bg-emerald-900/30 cursor-pointer">
                                            <input type="checkbox"
                                                   wire:click="toggleHousingServiceOnLog({{ $hs->id }})"
                                                   @checked(in_array($hs->id, $attachedSvcIds, true))
                                                   class="mt-0.5 rounded border-gray-300 dark:border-zinc-600 text-emerald-600 focus:ring-emerald-500" />
                                            <div class="min-w-0 flex-1">
                                                <div class="text-sm text-gray-900 dark:text-white truncate">{{ $hs->name }}</div>
                                                @if($hs->description)
                                                    <div class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $hs->description }}</div>
                                                @endif
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        {{-- Fund codes anticipated --}}
                        <div class="bg-amber-50/40 dark:bg-amber-900/15 rounded-lg p-4 border border-amber-100 dark:border-amber-900/40">
                            <h5 class="text-xs font-semibold uppercase tracking-wider text-amber-700 dark:text-amber-300 mb-2">Anticipated fund codes</h5>
                            @php $vfc = $viewingLog->anticipatedFundCodes()->get(); @endphp
                            @if($vfc->isEmpty())
                                <p class="text-xs text-gray-500 dark:text-gray-400 italic">None yet — fund codes auto-attach when you pick a housing service.</p>
                            @else
                                <ul class="space-y-1.5">
                                    @foreach($vfc as $fc)
                                        <li class="flex items-start gap-2">
                                            <span class="font-mono text-xs text-amber-700 dark:text-amber-300 bg-amber-100 dark:bg-amber-900/40 px-1.5 py-0.5 rounded">{{ $fc->code }}</span>
                                            <span class="text-xs text-gray-700 dark:text-gray-200 truncate">{{ $fc->description ?? '' }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                </section>{{-- /Focus 2 --}}

                {{-- Focus 3 — Requirements & compliance --}}
                <section id="focus-requirements" class="scroll-mt-4 mt-6">
                    <div class="flex items-baseline gap-2 mb-3 border-b border-purple-200 dark:border-purple-800 pb-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] uppercase tracking-wider font-bold bg-purple-600 text-white">Focus 3</span>
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Requirements &amp; compliance</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">What's needed to satisfy this case — Phase 1 (eligibility + compliance).</p>
                    </div>

                {{-- v2 §4.4 / §4.5: state banner + Requirements panel between Summary and Activity. --}}
                @if(! $viewingLog->housing_job_id)
                    <div class="mb-4 px-4 py-3 rounded-md bg-amber-50 dark:bg-amber-900/30 border-l-4 border-amber-500">
                        <div class="flex items-start justify-between gap-3 flex-wrap">
                            <div>
                                <div class="text-[10px] uppercase tracking-wider font-semibold text-amber-800 dark:text-amber-300">General intake</div>
                                <p class="text-sm text-gray-700 dark:text-gray-300 mt-1">
                                    Not yet attached to a project. Promote when the application requirements are met.
                                </p>
                            </div>
                            <div class="flex gap-2">
                                @can('communications_log.create')
                                    <button type="button" wire:click="startConvertToProject({{ $viewingLog->id }})"
                                            class="px-3 py-1.5 rounded bg-teal-600 hover:bg-teal-700 text-white text-xs font-medium">
                                        Convert to project
                                    </button>
                                @endcan
                                <button type="button"
                                        x-on:click="$dispatch('open-project-picker')"
                                        class="px-3 py-1.5 rounded bg-gray-200 dark:bg-zinc-600 text-gray-700 dark:text-gray-200 text-xs font-medium hover:bg-gray-300 dark:hover:bg-zinc-500">
                                    Attach to existing
                                </button>
                            </div>
                        </div>
                    </div>
                @else
                    @php $jb = $viewingLog->convertedToJob ?? \App\Models\HousingJob::find($viewingLog->housing_job_id); @endphp
                    @if($jb)
                        <div class="mb-4 flex items-center gap-3 px-4 py-3 rounded-md bg-teal-700 text-white shadow">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-sm bg-white/15 font-mono text-xs">{{ $jb->job_number }}</span>
                            <div class="min-w-0 flex-1">
                                <div class="text-[10px] uppercase tracking-wider text-white/70">Project</div>
                                <div class="text-sm font-medium truncate">{{ $jb->job_title }}</div>
                            </div>
                        </div>
                    @endif
                @endif

                @php
                    /* Phase 10: filter the viewer panel to Phase 1 categories (eligibility + compliance).
                       Service-delivery-compliance items live on the same log but surface on the project page. */
                    $viewerReqs = $viewingLog->intakeRequirements()
                        ->where('is_orphaned', false)
                        ->with('definition')
                        ->get()
                        ->filter(fn ($r) => in_array(
                            $r->definition?->category ?? \App\Models\RequirementDefinition::CATEGORY_ELIGIBILITY,
                            \App\Models\RequirementDefinition::PHASE_1_CATEGORIES,
                            true
                        ))
                        ->values();
                @endphp
                @if($viewerReqs->isNotEmpty())
                    <div class="mb-4">
                        <x-comm.requirements-panel :items="$viewerReqs" title="Phase 1 · Eligibility &amp; Compliance" />
                    </div>
                @endif

                </section>{{-- /Focus 3 --}}

                <!-- Updates/Activity Log — audit trail across all three focuses -->
                <div class="border-t border-gray-200 dark:border-zinc-600 pt-4 mb-4 mt-6">
                    <h4 class="font-medium text-gray-900 dark:text-white mb-3">Activity Updates & Follow-up History</h4>

                    <!-- Add Update Form -->
                    @can('communications_log.edit')
                    <div class="mb-4 p-3 bg-gray-50 dark:bg-zinc-700/50 rounded-lg">
                        <div class="flex flex-wrap gap-3">
                            <select wire:model.live="newUpdateType" class="px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md text-sm">
                                <option value="note">Note</option>
                                <option value="follow_up">Follow-up</option>
                                <option value="action">Action</option>
                                <option value="email">Email</option>
                                {{-- status_change is system-only per spec §5.4 — kept here for completeness when an admin needs to log one manually. --}}
                                <option value="status_change">Status (system)</option>
                                <option value="outcome">Outcome (legacy)</option>
                                <option value="other">Other (legacy)</option>
                            </select>
                            @if($newUpdateType === 'follow_up')
                            <input type="date" wire:model="newUpdateDate"
                                   class="px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md text-sm"
                                   title="Follow-up date (when the follow-up occurred)">
                            @endif
                            <input type="text" wire:model="newUpdateContent"
                                   data-comm-composer
                                   placeholder="{{ $newUpdateType === 'follow_up' ? 'What happened during follow-up...' : 'Add an update or note...' }}"
                                   wire:keydown.enter="addUpdate"
                                   class="flex-1 min-w-[200px] px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md text-sm">
                            <button wire:click="addUpdate"
                                    class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 text-sm">
                                Add
                            </button>
                        </div>
                        @if($newUpdateType === 'follow_up')
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                            <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Record the date when the follow-up occurred and what happened
                        </p>
                        @endif
                        @error('newUpdateContent') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    @endcan

                    <!-- Updates List -->
                    <div class="space-y-3 max-h-80 overflow-y-auto">
                        @forelse($viewingLog->updates as $update)
                            <div class="flex items-start gap-3 p-3 {{ $update->update_type === 'follow_up' ? 'bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800' : 'bg-gray-50 dark:bg-zinc-700/30' }} rounded-lg">
                                <div class="flex flex-col items-center gap-1">
                                    <span class="px-2 py-0.5 text-xs rounded whitespace-nowrap
                                        @if($update->update_type === 'follow_up') bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300
                                        @elseif($update->update_type === 'status_change') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300
                                        @elseif($update->update_type === 'outcome') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300
                                        @else bg-gray-100 text-gray-800 dark:bg-zinc-600 dark:text-gray-300
                                        @endif">
                                        {{ $update->update_type_display }}
                                    </span>
                                    @if($update->update_date)
                                    <span class="text-xs font-semibold text-amber-700 dark:text-amber-400">
                                        {{ $update->update_date->format('M d, Y') }}
                                    </span>
                                    @endif
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm text-gray-700 dark:text-gray-300">{{ $update->update_content }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        Added by {{ $update->createdBy?->name ?? 'Unknown' }} on {{ $update->created_at->format('M d, Y g:i A') }}
                                    </p>
                                </div>
                                @can('communications_log.edit')
                                <button wire:click="deleteUpdate({{ $update->id }})"
                                        wire:confirm="Delete this update?"
                                        class="text-red-500 hover:text-red-700 text-xs">
                                    Delete
                                </button>
                                @endcan
                            </div>
                        @empty
                            <p class="text-gray-500 dark:text-gray-400 text-sm italic">No updates yet. Use the form above to add notes or record follow-up interactions.</p>
                        @endforelse
                    </div>
                </div>

                <!-- Meta Information -->
                <div class="text-xs text-gray-400 dark:text-gray-500 border-t border-gray-200 dark:border-zinc-600 pt-3">
                    <p>Created by {{ $viewingLog->createdBy?->name ?? 'Unknown' }} on {{ $viewingLog->created_at->format('M d, Y g:i A') }}</p>
                    @if($viewingLog->updatedBy)
                        <p>Last updated by {{ $viewingLog->updatedBy->name }} on {{ $viewingLog->updated_at->format('M d, Y g:i A') }}</p>
                    @endif
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-between items-center mt-6 pt-4 border-t border-gray-200 dark:border-zinc-600">
                    <div class="flex gap-2">
                        @if(!$viewingLog->converted_to_job_id)
                            @can('projects.create')
                            <button wire:click="openConvertModal({{ $viewingLog->id }})"
                                    class="px-4 py-2 bg-emerald-600 text-white rounded-md hover:bg-emerald-700 text-sm">
                                Convert to Project
                            </button>
                            @endcan
                        @else
                            @if(auth()->user()->isAdmin())
                                <button wire:click="resetConversion({{ $viewingLog->id }})"
                                        wire:confirm="Are you sure you want to reset this conversion?\n\nThis will:\n• Clear the conversion status from this communication log\n• Allow you to re-convert with the updated validation and automation\n• NOT delete the original project ({{ $viewingLog->convertedToJob->job_number ?? 'N/A' }})\n\nYou can then re-convert to create a new project with proper participant and property validation."
                                        class="px-4 py-2 bg-amber-600 text-white rounded-md hover:bg-amber-700 text-sm flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                    Admin: Reset Conversion
                                </button>
                            @endif
                        @endif
                        @can('communications_log.edit')
                        <button wire:click="showEditForm({{ $viewingLog->id }})"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 text-sm">
                            Edit
                        </button>
                        @endcan
                    </div>
                    <button wire:click="closeDetailModal"
                            class="px-4 py-2 bg-gray-200 dark:bg-zinc-600 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-500 text-sm">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Convert to Project Modal -->
    @if($showConvertModal)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-[60]" wire:click.self="closeConvertModal">
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                <!-- Step Indicator -->
                <div class="flex items-center justify-center mb-6">
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-8 h-8 rounded-full {{ $convertStep >= 1 ? 'bg-indigo-600 text-white' : 'bg-gray-300 text-gray-600' }}">1</div>
                        <div class="w-16 h-1 {{ $convertStep >= 2 ? 'bg-indigo-600' : 'bg-gray-300' }}"></div>
                        <div class="flex items-center justify-center w-8 h-8 rounded-full {{ $convertStep >= 2 ? 'bg-indigo-600 text-white' : 'bg-gray-300 text-gray-600' }}">2</div>
                    </div>
                </div>

                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    @if($convertStep === 1)
                        Convert to Project
                    @else
                        Review Potential Matches
                    @endif
                </h3>

                <!-- Step 1: Basic Info -->
                @if($convertStep === 1)
                <div class="space-y-4">
                    {{-- v2 (Phase 7): Requirements Gate at the top of step 1.
                         Phase 10: gate only counts Phase 1 categories — service-delivery-
                         compliance items don't block promotion (they're evaluated post-conversion). --}}
                    @if($convertingLog)
                        @php
                            $convertReqs = $convertingLog->intakeRequirements()
                                ->where('is_orphaned', false)
                                ->with('definition')
                                ->get()
                                ->filter(fn ($r) => in_array(
                                    $r->definition?->category ?? \App\Models\RequirementDefinition::CATEGORY_ELIGIBILITY,
                                    \App\Models\RequirementDefinition::PHASE_1_CATEGORIES,
                                    true
                                ))
                                ->values();
                        @endphp
                        @if($convertReqs->isNotEmpty())
                            <x-comm.requirements-gate :items="$convertReqs"
                                                      override-model="requirementOverride"
                                                      override-reason-model="requirementOverrideReason" />
                            @error('requirementOverride')
                                <div class="p-3 rounded-md bg-red-50 dark:bg-red-900/30 border-l-4 border-red-500 text-sm text-red-800 dark:text-red-200">{{ $message }}</div>
                            @enderror
                            @error('requirementOverrideReason')
                                <div class="p-3 rounded-md bg-red-50 dark:bg-red-900/30 border-l-4 border-red-500 text-sm text-red-800 dark:text-red-200">{{ $message }}</div>
                            @enderror
                        @endif
                    @endif

                    <!-- Contact Info Summary -->
                    @if($convertingLog)
                    <div class="p-3 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-700 rounded-md text-sm">
                        <p class="font-medium text-blue-800 dark:text-blue-200 mb-1">Contact Information from Log:</p>
                        <div class="text-blue-700 dark:text-blue-300 space-y-1">
                            @if($convertingLog->contact_first_name || $convertingLog->contact_last_name)
                                <p><span class="font-medium">Name:</span> {{ $convertingLog->contact_first_name }} {{ $convertingLog->contact_last_name }}</p>
                            @endif
                            @if($convertingLog->contact_phone)
                                <p><span class="font-medium">Phone:</span> {{ $convertingLog->contact_phone }}</p>
                            @endif
                            @if($convertingLog->contact_email)
                                <p><span class="font-medium">Email:</span> {{ $convertingLog->contact_email }}</p>
                            @endif
                            @if($convertingLog->contact_address)
                                <p><span class="font-medium">Address:</span> {{ $convertingLog->contact_address }}, {{ $convertingLog->contact_city }}, {{ $convertingLog->contact_state }} {{ $convertingLog->contact_zip }}</p>
                            @endif
                            @if($convertingLog->participant_id)
                                <p class="text-green-700 dark:text-green-400"><span class="font-medium">Participant:</span> Already linked ({{ $convertingLog->participant?->full_name }})</p>
                            @endif
                            @if($convertingLog->property_id)
                                <p class="text-green-700 dark:text-green-400"><span class="font-medium">Property:</span> Already linked ({{ $convertingLog->property?->address_line_1 }})</p>
                            @endif
                        </div>
                    </div>
                    @endif

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Project Title *</label>
                        <input type="text" wire:model="convertJobTitle"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                        @error('convertJobTitle') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                        <textarea wire:model="convertJobDescription" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Program</label>
                        @if($housingPrograms->count() === 1)
                            {{-- Only one housing program - show as read-only --}}
                            <div class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700/50 dark:text-white rounded-md bg-gray-50">
                                {{ $housingPrograms->first()->name }}
                            </div>
                        @elseif($housingPrograms->count() > 1)
                            {{-- Multiple housing programs - show dropdown --}}
                            <select wire:model.live="convertProgramId"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                                @foreach($housingPrograms as $program)
                                    <option value="{{ $program->id }}">{{ $program->name }}</option>
                                @endforeach
                            </select>
                        @else
                            <div class="w-full px-3 py-2 border border-red-300 dark:border-red-600 rounded-md bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 text-sm">
                                No housing-enabled programs available. Please enable housing module on a program first.
                            </div>
                        @endif
                        @error('convertProgramId') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>

                    @if($convertProgramId)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Service (Optional)</label>
                        <select wire:model="convertServiceId"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                            <option value="">Select a service...</option>
                            @foreach($housingPrograms->firstWhere('id', $convertProgramId)?->services ?? [] as $service)
                                <option value="{{ $service->id }}">{{ $service->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button wire:click="closeConvertModal"
                            class="px-4 py-2 bg-gray-200 dark:bg-zinc-600 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-500">
                        Cancel
                    </button>
                    <button wire:click="checkForDuplicates"
                            wire:loading.attr="disabled"
                            {{ $housingPrograms->isEmpty() ? 'disabled' : '' }}
                            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span wire:loading.remove wire:target="checkForDuplicates">Continue</span>
                        <span wire:loading wire:target="checkForDuplicates">Checking...</span>
                    </button>
                </div>

                <!-- Step 2: Review Duplicates -->
                @else
                <div class="space-y-6">
                    <!-- Participant Matches -->
                    @if(count($potentialParticipantMatches) > 0)
                    <div class="border border-amber-300 dark:border-amber-600 rounded-lg p-4 bg-amber-50 dark:bg-amber-900/30">
                        <h4 class="font-medium text-amber-800 dark:text-amber-200 mb-3 flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            Potential Participant Matches Found
                        </h4>
                        <p class="text-sm text-amber-700 dark:text-amber-300 mb-3">
                            We found existing participants that might match "<strong>{{ $convertingLog?->contact_first_name }} {{ $convertingLog?->contact_last_name }}</strong>".
                            Please select one to link, or choose to create a new participant.
                        </p>

                        <div class="space-y-2">
                            @foreach($potentialParticipantMatches as $match)
                            <label class="flex items-start p-3 border rounded-md cursor-pointer transition-colors
                                {{ $selectedParticipantAction == $match['id'] ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30' : 'border-gray-300 dark:border-zinc-600 hover:bg-gray-50 dark:hover:bg-zinc-700' }}">
                                <input type="radio" name="participant_match" value="{{ $match['id'] }}"
                                       wire:click="selectExistingParticipant({{ $match['id'] }})"
                                       {{ $selectedParticipantAction == $match['id'] ? 'checked' : '' }}
                                       class="mt-1 mr-3 text-indigo-600">
                                <div class="flex-1">
                                    <p class="font-medium text-gray-900 dark:text-white">{{ $match['name'] }}</p>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        @if($match['phone'])<span class="mr-3">Phone: {{ $match['phone'] }}</span>@endif
                                        @if($match['email'])<span class="mr-3">Email: {{ $match['email'] }}</span>@endif
                                        @if($match['household_code'])<span>Household: {{ $match['household_code'] }}</span>@endif
                                    </div>
                                </div>
                            </label>
                            @endforeach

                            <label class="flex items-start p-3 border rounded-md cursor-pointer transition-colors
                                {{ $selectedParticipantAction === 'create_new' ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/30' : 'border-gray-300 dark:border-zinc-600 hover:bg-gray-50 dark:hover:bg-zinc-700' }}">
                                <input type="radio" name="participant_match" value="create_new"
                                       wire:click="selectCreateNewParticipant"
                                       {{ $selectedParticipantAction === 'create_new' ? 'checked' : '' }}
                                       class="mt-1 mr-3 text-emerald-600">
                                <div class="flex-1">
                                    <p class="font-medium text-emerald-700 dark:text-emerald-400">Create New Participant</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        This is a different person - create a new participant record for "{{ $convertingLog?->contact_first_name }} {{ $convertingLog?->contact_last_name }}"
                                    </p>
                                </div>
                            </label>
                        </div>
                    </div>
                    @endif

                    <!-- Property Matches -->
                    @if(count($potentialPropertyMatches) > 0)
                    <div class="border border-amber-300 dark:border-amber-600 rounded-lg p-4 bg-amber-50 dark:bg-amber-900/30">
                        <h4 class="font-medium text-amber-800 dark:text-amber-200 mb-3 flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            Potential Property Matches Found
                        </h4>
                        <p class="text-sm text-amber-700 dark:text-amber-300 mb-3">
                            We found existing properties that might match "<strong>{{ $convertingLog?->contact_address }}, {{ $convertingLog?->contact_city }}</strong>".
                            Please select one to link, or choose to create a new property.
                        </p>

                        <div class="space-y-2">
                            @foreach($potentialPropertyMatches as $match)
                            <label class="flex items-start p-3 border rounded-md cursor-pointer transition-colors
                                {{ $selectedPropertyAction == $match['id'] ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30' : 'border-gray-300 dark:border-zinc-600 hover:bg-gray-50 dark:hover:bg-zinc-700' }}">
                                <input type="radio" name="property_match" value="{{ $match['id'] }}"
                                       wire:click="selectExistingProperty({{ $match['id'] }})"
                                       {{ $selectedPropertyAction == $match['id'] ? 'checked' : '' }}
                                       class="mt-1 mr-3 text-indigo-600">
                                <div class="flex-1">
                                    <p class="font-medium text-gray-900 dark:text-white">{{ $match['address'] }}</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $match['city'] }}, {{ $match['state'] }} {{ $match['zip'] }}</p>
                                </div>
                            </label>
                            @endforeach

                            <label class="flex items-start p-3 border rounded-md cursor-pointer transition-colors
                                {{ $selectedPropertyAction === 'create_new' ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/30' : 'border-gray-300 dark:border-zinc-600 hover:bg-gray-50 dark:hover:bg-zinc-700' }}">
                                <input type="radio" name="property_match" value="create_new"
                                       wire:click="selectCreateNewProperty"
                                       {{ $selectedPropertyAction === 'create_new' ? 'checked' : '' }}
                                       class="mt-1 mr-3 text-emerald-600">
                                <div class="flex-1">
                                    <p class="font-medium text-emerald-700 dark:text-emerald-400">Create New Property</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        This is a different property - create a new property record for "{{ $convertingLog?->contact_address }}"
                                    </p>
                                </div>
                            </label>

                            <label class="flex items-start p-3 border rounded-md cursor-pointer transition-colors
                                {{ $selectedPropertyAction === 'skip' ? 'border-gray-500 bg-gray-100 dark:bg-zinc-700' : 'border-gray-300 dark:border-zinc-600 hover:bg-gray-50 dark:hover:bg-zinc-700' }}">
                                <input type="radio" name="property_match" value="skip"
                                       wire:click="selectSkipProperty"
                                       {{ $selectedPropertyAction === 'skip' ? 'checked' : '' }}
                                       class="mt-1 mr-3 text-gray-600">
                                <div class="flex-1">
                                    <p class="font-medium text-gray-700 dark:text-gray-300">Skip Property</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        Don't link or create a property for this project
                                    </p>
                                </div>
                            </label>
                        </div>
                    </div>
                    @endif

                    <!-- No duplicates message (shouldn't normally show since we skip step 2 if no duplicates) -->
                    @if(count($potentialParticipantMatches) === 0 && count($potentialPropertyMatches) === 0)
                    <div class="p-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 rounded-md">
                        <p class="text-green-700 dark:text-green-300">No potential duplicates found. Ready to create the project.</p>
                    </div>
                    @endif
                </div>

                <div class="mt-6 flex justify-between">
                    <button wire:click="backToStep1"
                            class="px-4 py-2 bg-gray-200 dark:bg-zinc-600 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-500">
                        Back
                    </button>
                    <div class="space-x-3">
                        <button wire:click="closeConvertModal"
                                class="px-4 py-2 bg-gray-200 dark:bg-zinc-600 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-500">
                            Cancel
                        </button>
                        <button wire:click="convertToProject"
                                wire:loading.attr="disabled"
                                class="px-4 py-2 bg-emerald-600 text-white rounded-md hover:bg-emerald-700">
                            <span wire:loading.remove wire:target="convertToProject">Create Project</span>
                            <span wire:loading wire:target="convertToProject">Creating...</span>
                        </button>
                    </div>
                </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Form Modal -->
    @if($showForm)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50"
             x-data="{
                 handleKey(e) {
                     // ⌘/⌃ + Enter: Save
                     if ((e.metaKey || e.ctrlKey) && e.key === 'Enter' && !e.shiftKey) {
                         e.preventDefault();
                         $wire.call('save');
                         return;
                     }
                     // ⌘/⌃ + Shift + Enter: Save & New (spec §6 calls this ⌘⏎N)
                     if ((e.metaKey || e.ctrlKey) && e.shiftKey && e.key === 'Enter') {
                         e.preventDefault();
                         $wire.call('saveAndNew');
                         return;
                     }
                     // Esc: close modal (unless the picker is currently swallowing it)
                     if (e.key === 'Escape') {
                         const tag = document.activeElement?.tagName;
                         if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') {
                             return;
                         }
                         $wire.set('showForm', false);
                     }
                 },
             }"
             x-on:keydown.window="handleKey">
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    {{ $editingId ? 'Edit Communication' : 'New Communication Entry' }}
                </h3>

                {{-- v2 anchor selector / project banner ─────────────────── --}}
                @if($this->state === 'intake')
                    {{-- INTAKE: two radio cards at top per spec §4.1 --}}
                    <div class="grid grid-cols-2 gap-3 mb-5">
                        <button type="button" wire:click="setAnchor('intake')"
                                class="text-left p-3 rounded-md border-2 {{ $anchor === 'intake' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30' : 'border-gray-200 dark:border-zinc-600 bg-white dark:bg-zinc-700' }}">
                            <div class="flex items-center gap-2">
                                <input type="radio" @checked($anchor === 'intake') class="text-indigo-600 focus:ring-indigo-500" readonly>
                                <span class="font-medium text-gray-900 dark:text-white">General intake</span>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1.5 pl-6">
                                Log an interaction not (yet) tied to a project — outreach, partner call, exploratory inquiry.
                            </p>
                        </button>
                        <button type="button" wire:click="setAnchor('project')"
                                class="text-left p-3 rounded-md border-2 border-gray-200 dark:border-zinc-600 bg-white dark:bg-zinc-700 hover:border-teal-500">
                            <div class="flex items-center gap-2">
                                <input type="radio" class="text-teal-600 focus:ring-teal-500" readonly>
                                <span class="font-medium text-gray-900 dark:text-white">Attach to project</span>
                                <span class="ml-auto text-[10px] uppercase tracking-wider text-gray-500 dark:text-gray-400">⌘P</span>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1.5 pl-6">
                                Pick an existing project; fund codes and program services scope to it.
                            </p>
                        </button>
                    </div>
                @else
                    {{-- PROJECT: teal sticky banner per spec §4.2 --}}
                    @php
                        $linkedJob = $housingJobs->firstWhere('id', (int) $housing_job_id);
                    @endphp
                    @if($linkedJob)
                        <div class="mb-5 flex items-center gap-3 px-4 py-3 rounded-md bg-teal-700 text-white shadow">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-sm bg-white/15 font-mono text-xs">{{ $linkedJob->job_number }}</span>
                            <div class="min-w-0 flex-1">
                                <div class="text-[10px] uppercase tracking-wider text-white/70">Project</div>
                                <div class="text-sm font-medium truncate">{{ $linkedJob->job_title }}</div>
                            </div>
                            <button type="button" x-on:click="$dispatch('open-project-picker')"
                                    class="text-xs underline underline-offset-2 hover:text-white/80">
                                Change
                            </button>
                            <button type="button" wire:click="setAnchor('intake')"
                                    class="text-xs underline underline-offset-2 hover:text-white/80">
                                Detach
                            </button>
                        </div>
                    @endif
                @endif

                {{-- v2 requirements summary pill (visible whenever there are requirements) --}}
                @if($editingId && $currentRequirements->isNotEmpty())
                    <div class="mb-5">
                        <x-comm.requirements-summary-pill :items="$currentRequirements" />
                    </div>
                @endif

                <!-- Validation Errors Display -->
                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-50 dark:bg-red-900/50 border border-red-200 dark:border-red-700 rounded-md">
                        <h4 class="text-sm font-medium text-red-800 dark:text-red-200 mb-2">Please correct the following errors:</h4>
                        <ul class="list-disc list-inside text-sm text-red-700 dark:text-red-300">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Linked Entities Section (only shown when editing and has links) -->
                @if($editingId && ($participant_id || $property_id || $housing_job_id))
                <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg">
                    <h4 class="text-sm font-semibold text-blue-800 dark:text-blue-200 mb-3 flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                        </svg>
                        Currently Linked Records
                    </h4>
                    <div class="flex flex-wrap gap-3">
                        @if($participant_id)
                            @php $linkedParticipant = \App\Models\Participant::with('household')->find($participant_id); @endphp
                            @if($linkedParticipant)
                            <div class="flex items-center gap-2 px-3 py-2 bg-white dark:bg-zinc-700 rounded-md border border-blue-200 dark:border-blue-600">
                                <div>
                                    <span class="text-xs text-blue-600 dark:text-blue-400 font-medium block">Participant</span>
                                    <span class="text-sm text-gray-900 dark:text-white">{{ $linkedParticipant->full_name }}</span>
                                    @if($linkedParticipant->household)
                                        <span class="text-xs text-gray-500 dark:text-gray-400">({{ $linkedParticipant->household->household_code }})</span>
                                    @endif
                                </div>
                                @if(auth()->user()->isAdmin())
                                <button type="button" wire:click="unlinkParticipant({{ $editingId }})"
                                        wire:confirm="Are you sure you want to unlink this participant? This will also unlink the household."
                                        class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 ml-2"
                                        title="Unlink participant">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                                @endif
                            </div>
                            @endif
                        @endif
                        @if($property_id)
                            @php $linkedProperty = \App\Models\Property::find($property_id); @endphp
                            @if($linkedProperty)
                            <div class="flex items-center gap-2 px-3 py-2 bg-white dark:bg-zinc-700 rounded-md border border-blue-200 dark:border-blue-600">
                                <div>
                                    <span class="text-xs text-blue-600 dark:text-blue-400 font-medium block">Property</span>
                                    <span class="text-sm text-gray-900 dark:text-white">{{ $linkedProperty->address_line_1 }}, {{ $linkedProperty->city }}</span>
                                </div>
                                @if(auth()->user()->isAdmin())
                                <button type="button" wire:click="unlinkProperty({{ $editingId }})"
                                        wire:confirm="Are you sure you want to unlink this property?"
                                        class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 ml-2"
                                        title="Unlink property">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                                @endif
                            </div>
                            @endif
                        @endif
                        @if($housing_job_id)
                            @php $linkedJob = \App\Models\HousingJob::find($housing_job_id); @endphp
                            @if($linkedJob)
                            <div class="flex items-center gap-2 px-3 py-2 bg-white dark:bg-zinc-700 rounded-md border border-blue-200 dark:border-blue-600">
                                <div>
                                    <span class="text-xs text-blue-600 dark:text-blue-400 font-medium block">Project</span>
                                    <span class="text-sm text-gray-900 dark:text-white">{{ $linkedJob->job_number }} - {{ Str::limit($linkedJob->job_title, 25) }}</span>
                                </div>
                                @if(auth()->user()->isAdmin())
                                <button type="button" wire:click="unlinkHousingJob({{ $editingId }})"
                                        wire:confirm="Are you sure you want to unlink this project?"
                                        class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 ml-2"
                                        title="Unlink project">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                                @endif
                            </div>
                            @endif
                        @endif
                    </div>
                    @if(auth()->user()->isAdmin())
                    <p class="text-xs text-blue-600 dark:text-blue-400 mt-3">
                        <span class="font-medium">Admin:</span> Click <svg class="w-3 h-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg> to unlink a record. You can also change links using the dropdowns below.
                    </p>
                    @endif
                </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Date -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date *</label>
                        <input type="date" wire:model="communication_date"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                        @error('communication_date') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <!-- Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type *</label>
                        <select wire:model="communication_type"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                            <option value="phone_call">Phone Call</option>
                            <option value="email">Email</option>
                            <option value="in_person">In Person</option>
                            <option value="site_visit">Site Visit</option>
                            <option value="video_call">Video Call</option>
                            <option value="text">Text Message</option>
                            <option value="mail">Mail</option>
                            <option value="voicemail">Voicemail</option>
                            <option value="fax">Fax</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <!-- Direction -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Direction *</label>
                        <select wire:model="direction"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                            <option value="outgoing">Outgoing</option>
                            <option value="incoming">Incoming</option>
                        </select>
                    </div>

                    <!-- Category -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category *</label>
                        <select wire:model="category"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                            <option value="general">General</option>
                            <option value="intake">Intake</option>
                            <option value="follow_up">Follow-up</option>
                            <option value="complaint">Complaint</option>
                            <option value="inquiry">Inquiry</option>
                            <option value="referral">Referral</option>
                            <option value="outreach">Outreach</option>
                            <option value="scheduling">Scheduling</option>
                            <option value="documentation">Documentation</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    {{-- v2: legacy dropdown hidden — anchor selector / picker is the new UI.
                         Kept in the DOM (inside the `@if`) so wire:model binding survives. --}}
                    @if(false)
                    <!-- Related to a Project? -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Related to a Project?</label>
                        <select wire:model="housing_job_id"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                            <option value="">None</option>
                            @foreach($housingJobs as $job)
                                <option value="{{ $job->id }}">
                                    {{ $job->job_number }} - {{ Str::limit($job->job_title, 30) }}
                                    @if($job->status === 'pending')
                                        (Pending)
                                    @elseif($job->status === 'in_progress')
                                        (In Progress)
                                    @elseif($job->status === 'on_hold')
                                        (On Hold)
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <!-- Contact First Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">First Name *</label>
                        <input type="text" wire:model="contact_first_name" placeholder="e.g., John"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                        @error('contact_first_name') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <!-- Contact Last Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Last Name</label>
                        <input type="text" wire:model="contact_last_name" placeholder="e.g., Smith"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                        @error('contact_last_name') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <!-- Contact Role -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Contact Role</label>
                        <select wire:model="contact_role"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                            <option value="">Select role...</option>
                            <option value="homeowner">Homeowner</option>
                            <option value="contractor">Contractor</option>
                            <option value="inspector">Inspector</option>
                            <option value="vendor">Vendor</option>
                            <option value="agency">Agency</option>
                            <option value="applicant">Applicant</option>
                            <option value="family_member">Family Member</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <!-- Contact Phone -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Phone</label>
                        <input type="text" wire:model="contact_phone" placeholder="(702) 555-1234"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                    </div>

                    <!-- Contact Email -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                        <input type="email" wire:model="contact_email" placeholder="contact@example.com"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                        @error('contact_email') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <!-- Summary -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Summary</label>
                        <textarea wire:model="summary" rows="2" placeholder="Brief summary of the communication..."
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md"></textarea>
                    </div>

                    <!-- Create Project and Enrollment Checkbox -->
                    <div class="md:col-span-2 border-t border-gray-200 dark:border-zinc-600 pt-4 mt-2">
                        @if(!$editingId)
                        <label class="flex items-center gap-3 cursor-pointer p-3 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg hover:bg-indigo-100 dark:hover:bg-indigo-900/30 transition-colors">
                            <input type="checkbox" wire:model.live="create_project_enrollment"
                                   class="rounded border-gray-300 dark:border-zinc-600 text-indigo-600 focus:ring-indigo-500 w-5 h-5">
                            <div>
                                <span class="text-sm font-semibold text-indigo-700 dark:text-indigo-300">Create Project and Enrollment</span>
                                <p class="text-xs text-indigo-600 dark:text-indigo-400 mt-0.5">
                                    Automatically create property (with duplicate check), participant + household with address, and housing job from this communication log
                                </p>
                            </div>
                        </label>
                        @error('create_project_enrollment') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                        @endif
                    </div>

                    <!-- Contact Address Section -->
                    <div class="md:col-span-2 border-t border-gray-200 dark:border-zinc-600 pt-4 mt-2">
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                            Contact Address
                            @if($create_project_enrollment)
                                <span class="text-indigo-600 dark:text-indigo-400 text-xs font-normal">(Required for Project Enrollment)</span>
                            @else
                                <span class="text-gray-500 dark:text-gray-400 text-xs font-normal">(Optional - Auto-populates Subject)</span>
                            @endif
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- P-01 duplicate-address warning panel -->
                            @if(!empty($duplicateMatches))
                                <div class="md:col-span-2 rounded-md border border-amber-300 bg-amber-50 dark:border-amber-700 dark:bg-amber-900/30 p-3">
                                    <div class="flex items-start gap-3">
                                        <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86a2 2 0 001.74-2.99l-6.93-12a2 2 0 00-3.48 0l-6.93 12A2 2 0 005.07 19z"/>
                                        </svg>
                                        <div class="flex-1">
                                            <p class="text-sm font-semibold text-amber-900 dark:text-amber-100">
                                                Possible duplicate{{ count($duplicateMatches) === 1 ? '' : 's' }} at this address
                                            </p>
                                            <ul class="mt-1 space-y-1 text-xs text-amber-800 dark:text-amber-200">
                                                @foreach($duplicateMatches as $match)
                                                    <li>
                                                        <span class="font-medium">{{ ucfirst($match['kind']) }}:</span>
                                                        {{ $match['address'] }}
                                                        @if($match['kind'] === 'log')
                                                            @if($match['contact'])— {{ $match['contact'] }}@endif
                                                            @if($match['date']) ({{ $match['date'] }})@endif
                                                            @if($match['subject']) — “{{ \Illuminate\Support\Str::limit($match['subject'], 60) }}”@endif
                                                        @endif
                                                    </li>
                                                @endforeach
                                            </ul>
                                            <div class="mt-2 flex items-center gap-3">
                                                <button type="button" wire:click="acknowledgeDuplicatesAndSave"
                                                        class="text-xs px-3 py-1 rounded bg-amber-600 text-white hover:bg-amber-700 dark:bg-amber-700 dark:hover:bg-amber-600">
                                                    Save anyway
                                                </button>
                                                <span class="text-xs text-amber-700 dark:text-amber-300">
                                                    Or edit the address above to clear this warning.
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Address -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Street Address</label>
                                <input type="text" wire:model.live.debounce.500ms="contact_address" placeholder="123 Main Street"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                                @error('contact_address') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <!-- City -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">City</label>
                                <input type="text" wire:model.live.debounce.500ms="contact_city" placeholder="Las Vegas"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                                @error('contact_city') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <!-- State & ZIP -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">State</label>
                                    <select wire:model="contact_state"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                                        <option value="">Select...</option>
                                        <option value="AL">AL</option>
                                        <option value="AK">AK</option>
                                        <option value="AZ">AZ</option>
                                        <option value="AR">AR</option>
                                        <option value="CA">CA</option>
                                        <option value="CO">CO</option>
                                        <option value="CT">CT</option>
                                        <option value="DE">DE</option>
                                        <option value="FL">FL</option>
                                        <option value="GA">GA</option>
                                        <option value="HI">HI</option>
                                        <option value="ID">ID</option>
                                        <option value="IL">IL</option>
                                        <option value="IN">IN</option>
                                        <option value="IA">IA</option>
                                        <option value="KS">KS</option>
                                        <option value="KY">KY</option>
                                        <option value="LA">LA</option>
                                        <option value="ME">ME</option>
                                        <option value="MD">MD</option>
                                        <option value="MA">MA</option>
                                        <option value="MI">MI</option>
                                        <option value="MN">MN</option>
                                        <option value="MS">MS</option>
                                        <option value="MO">MO</option>
                                        <option value="MT">MT</option>
                                        <option value="NE">NE</option>
                                        <option value="NV" selected>NV</option>
                                        <option value="NH">NH</option>
                                        <option value="NJ">NJ</option>
                                        <option value="NM">NM</option>
                                        <option value="NY">NY</option>
                                        <option value="NC">NC</option>
                                        <option value="ND">ND</option>
                                        <option value="OH">OH</option>
                                        <option value="OK">OK</option>
                                        <option value="OR">OR</option>
                                        <option value="PA">PA</option>
                                        <option value="RI">RI</option>
                                        <option value="SC">SC</option>
                                        <option value="SD">SD</option>
                                        <option value="TN">TN</option>
                                        <option value="TX">TX</option>
                                        <option value="UT">UT</option>
                                        <option value="VT">VT</option>
                                        <option value="VA">VA</option>
                                        <option value="WA">WA</option>
                                        <option value="WV">WV</option>
                                        <option value="WI">WI</option>
                                        <option value="WY">WY</option>
                                    </select>
                                    @error('contact_state') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ZIP</label>
                                    <input type="text" wire:model="contact_zip" placeholder="89101"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                                    @error('contact_zip') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- v2 / UI shift: Housing Services + Fund Codes are managed from the
                         detail viewer (Focus 2 — Service selection), not from the edit form.
                         The form is for logging the communication; service selection happens
                         after the entry exists. --}}

                    <!-- Service Association Section (v2: hidden by default in INTAKE) -->
                    @if($accessiblePrograms->isNotEmpty() && ($this->state === 'project' || $showServicesInIntake))
                    <div class="md:col-span-2 border-t border-gray-200 dark:border-zinc-600 pt-4 mt-2">
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Associate with Program Services (Optional)</h4>
                        <div class="space-y-3">
                            @foreach($accessiblePrograms as $program)
                                @if($program->services->isNotEmpty())
                                <div>
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">{{ $program->name }}</p>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($program->services as $service)
                                            <label class="inline-flex items-center">
                                                <input type="checkbox" wire:model="selectedServices" value="{{ $service->id }}"
                                                       class="rounded border-gray-300 dark:border-zinc-600 text-indigo-600 focus:ring-indigo-500">
                                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $service->name }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Tags Section -->
                    <div class="md:col-span-2 border-t border-gray-200 dark:border-zinc-600 pt-4 mt-2">
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Tags (Optional)</h4>

                        <!-- Selected Tags -->
                        @if(count($selectedTagIds) > 0)
                        <div class="flex flex-wrap gap-2 mb-3">
                            @foreach($allTags->whereIn('id', $selectedTagIds) as $tag)
                                <span class="inline-flex items-center px-2 py-1 text-sm rounded" style="background-color: {{ $tag->color }}20; color: {{ $tag->color }};">
                                    {{ $tag->name }}
                                    <button type="button" wire:click="removeTag({{ $tag->id }})" class="ml-1 hover:opacity-75">&times;</button>
                                </span>
                            @endforeach
                        </div>
                        @endif

                        <!-- Tag Input -->
                        <div class="flex gap-2">
                            <div class="flex-1 relative">
                                <input type="text" wire:model.live.debounce.300ms="tagSearch"
                                       placeholder="Search existing tags..."
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md text-sm">
                                @if($searchedTags->isNotEmpty())
                                    <div class="absolute z-10 w-full mt-1 bg-white dark:bg-zinc-700 border border-gray-300 dark:border-zinc-600 rounded-md shadow-lg max-h-40 overflow-y-auto">
                                        @foreach($searchedTags as $tag)
                                            @if(!in_array($tag->id, $selectedTagIds))
                                                <button type="button" wire:click="selectTag({{ $tag->id }})"
                                                        class="w-full px-3 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-zinc-600 flex items-center gap-2">
                                                    <span class="w-3 h-3 rounded-full" style="background-color: {{ $tag->color }};"></span>
                                                    {{ $tag->name }}
                                                </button>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            <div class="flex gap-2">
                                <input type="text" wire:model="newTagName"
                                       placeholder="Or create new tag..."
                                       wire:keydown.enter="addTag"
                                       class="px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md text-sm">
                                <button type="button" wire:click="addTag"
                                        class="px-3 py-2 bg-gray-200 dark:bg-zinc-600 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-500 text-sm">
                                    Add
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Fund codes are managed indirectly via Housing Services on the
                         viewer's Focus 2 panel. Picking a service auto-attaches its funds;
                         removing a service leaves funds in place for manual review. --}}

                    <!-- Subject (hidden - auto-generated from property address) -->
                    <input type="hidden" wire:model="subject">

                </div>

                <div class="mt-6 flex items-center justify-between gap-3 flex-wrap">
                    <div class="text-[11px] text-gray-500 dark:text-gray-400 flex items-center gap-2 flex-wrap">
                        <x-comm.kbd :keys="['⌘', '⏎']" /> Save
                        <span class="text-gray-300 dark:text-zinc-600">·</span>
                        <x-comm.kbd :keys="['⌘', '⇧', '⏎']" /> Save &amp; new
                        <span class="text-gray-300 dark:text-zinc-600">·</span>
                        <x-comm.kbd :keys="['⌘', 'P']" /> Project picker
                        <span class="text-gray-300 dark:text-zinc-600">·</span>
                        <x-comm.kbd>Esc</x-comm.kbd> Cancel
                    </div>
                    <div class="flex items-center gap-3">
                        <button wire:click="$set('showForm', false)"
                                class="px-4 py-2 bg-gray-200 dark:bg-zinc-600 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-500">
                            Cancel
                        </button>
                        <button wire:click="save"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-50 cursor-not-allowed"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                            <span wire:loading.remove wire:target="save">{{ $editingId ? 'Update' : 'Save' }}</span>
                            <span wire:loading wire:target="save">Saving...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- v2 project picker mount — listens for `open-project-picker` and
             ⌘P/⌃P globally; calls back to selectProject($id|null). --}}
        <x-comm.project-picker
            :projects="$pickerProjects"
            search-model="projectPickerSearch"
            select-method="selectProject" />
    @endif
</div>
