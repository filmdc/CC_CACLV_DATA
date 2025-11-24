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

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
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
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-amber-100 dark:bg-amber-900">
                        <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pending Follow-ups</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $pendingFollowUps }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-red-100 dark:bg-red-900">
                        <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Overdue Follow-ups</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $overdueFollowUps }}</p>
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
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 gap-3">
                    <!-- Search -->
                    <div class="relative">
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

                    <!-- Follow-up Filter -->
                    <select wire:model.live="followUpFilter"
                            class="px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md text-sm">
                        <option value="">All Follow-ups</option>
                        <option value="pending">Pending</option>
                        <option value="overdue">Overdue</option>
                    </select>
                </div>
            </div>

            <!-- Communications List -->
            <div class="divide-y divide-gray-200 dark:divide-zinc-700">
                @forelse($logs as $log)
                    <div class="p-4 hover:bg-gray-50 dark:hover:bg-zinc-700/50">
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
                                            {{ $log->contact_name }}
                                            @if($log->contact_organization)
                                                <span class="text-gray-400 dark:text-gray-500">| {{ $log->contact_organization }}</span>
                                            @endif
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
                                    <span class="text-gray-500 dark:text-gray-400">
                                        {{ $log->communication_date->format('M d, Y g:i A') }}
                                    </span>
                                </div>

                                @if($log->summary)
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">{{ Str::limit($log->summary, 200) }}</p>
                                @endif

                                @if($log->outcome)
                                    <div class="mt-2 p-2 bg-blue-50 dark:bg-blue-900/20 rounded text-sm">
                                        <span class="font-medium text-blue-800 dark:text-blue-300">Outcome:</span>
                                        <span class="text-blue-700 dark:text-blue-400">{{ $log->outcome }}</span>
                                    </div>
                                @endif

                                @if($log->follow_up_date)
                                    <div class="mt-2 flex items-center gap-2 text-sm">
                                        @if($log->follow_up_completed)
                                            <span class="flex items-center text-green-600 dark:text-green-400">
                                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                </svg>
                                                Follow-up completed
                                            </span>
                                        @else
                                            <span class="flex items-center {{ $log->is_follow_up_overdue ? 'text-red-600 dark:text-red-400' : 'text-amber-600 dark:text-amber-400' }}">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                Follow-up: {{ $log->follow_up_date->format('M d, Y') }}
                                                @if($log->is_follow_up_overdue)
                                                    <span class="font-semibold ml-1">(OVERDUE)</span>
                                                @endif
                                            </span>
                                            @if($log->follow_up_action)
                                                <span class="text-gray-500 dark:text-gray-400">- {{ $log->follow_up_action }}</span>
                                            @endif
                                        @endif
                                    </div>
                                @endif

                                <div class="mt-2 text-xs text-gray-400 dark:text-gray-500">
                                    Created by {{ $log->createdBy?->name ?? 'Unknown' }} on {{ $log->created_at->format('M d, Y') }}
                                </div>
                            </div>

                            <div class="flex items-start space-x-2 ml-4">
                                @if($log->follow_up_date && !$log->follow_up_completed)
                                    @can('communications_log.update')
                                    <button wire:click="markFollowUpComplete({{ $log->id }})"
                                            wire:confirm="Mark this follow-up as complete?"
                                            class="px-2 py-1 text-xs bg-green-600 text-white rounded hover:bg-green-700">
                                        Complete
                                    </button>
                                    @endcan
                                @endif
                                @can('communications_log.update')
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

    <!-- Form Modal -->
    @if($showForm)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-3xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    {{ $editingId ? 'Edit Communication' : 'New Communication Entry' }}
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Date/Time -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date & Time *</label>
                        <input type="datetime-local" wire:model="communication_date"
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

                    <!-- Priority -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Priority *</label>
                        <select wire:model="priority"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                            <option value="low">Low</option>
                            <option value="normal">Normal</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>

                    <!-- Contact Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Contact Name *</label>
                        <input type="text" wire:model="contact_name" placeholder="e.g., John Smith"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                        @error('contact_name') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
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

                    <!-- Contact Organization -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Organization</label>
                        <input type="text" wire:model="contact_organization" placeholder="e.g., ABC Company"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                    </div>

                    <!-- Link to entities section -->
                    <div class="md:col-span-2 border-t border-gray-200 dark:border-zinc-600 pt-4 mt-2">
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Link to Record (Optional)</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Participant</label>
                                <input type="text"
                                       wire:model.live.debounce.300ms="participantSearch"
                                       placeholder="Search participants..."
                                       class="w-full px-3 py-1.5 mb-1 text-sm border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                                <select wire:model="participant_id"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                                    <option value="">None</option>
                                    @foreach($participants as $participant)
                                        <option value="{{ $participant->id }}">{{ $participant->last_name }}, {{ $participant->first_name }}</option>
                                    @endforeach
                                </select>
                                @if($participantSearch && $participants->count() === 50)
                                    <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">Showing first 50 results. Refine your search for more specific results.</p>
                                @endif
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Household</label>
                                <input type="text"
                                       wire:model.live.debounce.300ms="householdSearch"
                                       placeholder="Search household code..."
                                       class="w-full px-3 py-1.5 mb-1 text-sm border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                                <select wire:model="household_id"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                                    <option value="">None</option>
                                    @foreach($households as $household)
                                        <option value="{{ $household->id }}">{{ $household->household_code }}</option>
                                    @endforeach
                                </select>
                                @if($householdSearch && $households->count() === 50)
                                    <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">Showing first 50 results. Refine your search for more specific results.</p>
                                @endif
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Property</label>
                                <input type="text"
                                       wire:model.live.debounce.300ms="propertySearch"
                                       placeholder="Search address..."
                                       class="w-full px-3 py-1.5 mb-1 text-sm border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                                <select wire:model="property_id"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                                    <option value="">None</option>
                                    @foreach($properties as $property)
                                        <option value="{{ $property->id }}">{{ $property->address_line_1 }}, {{ $property->city }}</option>
                                    @endforeach
                                </select>
                                @if($propertySearch && $properties->count() === 50)
                                    <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">Showing first 50 results. Refine your search for more specific results.</p>
                                @endif
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Contractor</label>
                                <input type="text"
                                       wire:model.live.debounce.300ms="contractorSearch"
                                       placeholder="Search contractors..."
                                       class="w-full px-3 py-1.5 mb-1 text-sm border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                                <select wire:model="contractor_id"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                                    <option value="">None</option>
                                    @foreach($contractors as $contractor)
                                        <option value="{{ $contractor->id }}">
                                            {{ $contractor->company_name }}
                                            @if($contractor->contact_first_name || $contractor->contact_last_name)
                                                ({{ trim($contractor->contact_first_name . ' ' . $contractor->contact_last_name) }})
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                @if($contractorSearch && $contractors->count() === 50)
                                    <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">Showing first 50 results. Refine your search for more specific results.</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Subject -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Subject *</label>
                        <input type="text" wire:model="subject" placeholder="Brief description of the communication"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                        @error('subject') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <!-- Summary -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Summary</label>
                        <textarea wire:model="summary" rows="2" placeholder="Brief summary of the communication..."
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md"></textarea>
                    </div>

                    <!-- Notes -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Detailed Notes</label>
                        <textarea wire:model="notes" rows="3" placeholder="Detailed notes about the communication..."
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md"></textarea>
                    </div>

                    <!-- Outcome -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Outcome</label>
                        <textarea wire:model="outcome" rows="2" placeholder="Result of the communication..."
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md"></textarea>
                    </div>

                    <!-- Follow-up Section -->
                    <div class="md:col-span-2 border-t border-gray-200 dark:border-zinc-600 pt-4 mt-2">
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Follow-up (Optional)</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Follow-up Date</label>
                                <input type="date" wire:model="follow_up_date"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Follow-up Action</label>
                                <input type="text" wire:model="follow_up_action" placeholder="e.g., Call back to confirm"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
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
    @endif
</div>
