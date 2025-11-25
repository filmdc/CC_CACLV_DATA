<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            User Feedback
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
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">New</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $newCount }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 dark:bg-purple-900">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Open</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $openCount }}</p>
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
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Open Bugs</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $bugCount }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 dark:bg-green-900">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Feature Requests</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $featureCount }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl">
            <!-- Header -->
            <div class="p-6 border-b border-gray-200 dark:border-zinc-700">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Feedback Management</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Review and manage user-submitted bug reports and feature requests</p>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="p-4 border-b border-gray-200 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-900/50">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3">
                    <!-- Search -->
                    <div class="relative">
                        <input type="text" wire:model.live.debounce.300ms="search"
                               placeholder="Search..."
                               class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md text-sm">
                        <svg class="w-4 h-4 absolute left-3 top-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>

                    <!-- Category Filter -->
                    <select wire:model.live="categoryFilter"
                            class="px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md text-sm">
                        <option value="">All Categories</option>
                        <option value="bug_report">Bug Report</option>
                        <option value="feature_request">Feature Request</option>
                        <option value="usability_issue">Usability Issue</option>
                        <option value="question">Question</option>
                    </select>

                    <!-- Status Filter -->
                    <select wire:model.live="statusFilter"
                            class="px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md text-sm">
                        <option value="">All Statuses</option>
                        <option value="new">New</option>
                        <option value="acknowledged">Acknowledged</option>
                        <option value="in_progress">In Progress</option>
                        <option value="resolved">Resolved</option>
                        <option value="closed">Closed</option>
                        <option value="wont_fix">Won't Fix</option>
                    </select>

                    <!-- Priority Filter -->
                    <select wire:model.live="priorityFilter"
                            class="px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md text-sm">
                        <option value="">All Priorities</option>
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                    </select>

                    <!-- Assignee Filter -->
                    <select wire:model.live="assigneeFilter"
                            class="px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md text-sm">
                        <option value="">All Assignees</option>
                        <option value="unassigned">Unassigned</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                    <thead class="bg-gray-50 dark:bg-zinc-900/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Feedback</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Priority</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Submitted</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Assigned</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-700">
                        @forelse($feedbacks as $feedback)
                            <tr class="hover:bg-gray-50 dark:hover:bg-zinc-700/50">
                                <td class="px-6 py-4">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0">
                                            @if($feedback->category === 'bug_report')
                                                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                                </svg>
                                            @elseif($feedback->category === 'feature_request')
                                                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                                </svg>
                                            @elseif($feedback->category === 'usability_issue')
                                                <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/>
                                                </svg>
                                            @else
                                                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                            @endif
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ Str::limit($feedback->title, 50) }}</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">by {{ $feedback->user?->name ?? 'Unknown' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs rounded-full
                                        @if($feedback->category === 'bug_report') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                        @elseif($feedback->category === 'feature_request') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                        @elseif($feedback->category === 'usability_issue') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                        @else bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                        @endif">
                                        {{ $feedback->category_label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs rounded-full
                                        @if($feedback->priority === 'critical') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                        @elseif($feedback->priority === 'high') bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200
                                        @elseif($feedback->priority === 'medium') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                        @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200
                                        @endif">
                                        {{ $feedback->priority_label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs rounded-full
                                        @if($feedback->status === 'new') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                        @elseif($feedback->status === 'acknowledged') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                        @elseif($feedback->status === 'in_progress') bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200
                                        @elseif($feedback->status === 'resolved') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                        @elseif($feedback->status === 'wont_fix') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                        @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200
                                        @endif">
                                        {{ $feedback->status_label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $feedback->created_at->format('M d, Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $feedback->assignedTo?->name ?? '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button wire:click="viewFeedback({{ $feedback->id }})"
                                            class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 mr-3">
                                        View
                                    </button>
                                    @can('feedback.manage')
                                        <button wire:click="editFeedback({{ $feedback->id }})"
                                                class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300">
                                            Edit
                                        </button>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                    </svg>
                                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No feedback found</h3>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">No feedback items match your current filters.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="p-4 border-t border-gray-200 dark:border-zinc-700">
                {{ $feedbacks->links() }}
            </div>
        </div>
    </div>

    <!-- View Modal -->
    @if($showViewModal && $selectedFeedback)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div wire:click="closeModals" class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75 transition-opacity"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                <div class="inline-block align-bottom bg-white dark:bg-zinc-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <!-- Header -->
                    <div class="bg-indigo-600 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-white">Feedback Details</h3>
                            <button wire:click="closeModals" class="text-indigo-200 hover:text-white">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="px-6 py-4 max-h-[70vh] overflow-y-auto">
                        <!-- Details -->
                        <div class="space-y-4">
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $selectedFeedback->title }}</h4>
                                <div class="flex flex-wrap gap-2 mt-2">
                                    <span class="px-2 py-1 text-xs rounded-full bg-{{ $selectedFeedback->status_color }}-100 text-{{ $selectedFeedback->status_color }}-800 dark:bg-{{ $selectedFeedback->status_color }}-900 dark:text-{{ $selectedFeedback->status_color }}-200">
                                        {{ $selectedFeedback->status_label }}
                                    </span>
                                    <span class="px-2 py-1 text-xs rounded-full bg-{{ $selectedFeedback->priority_color }}-100 text-{{ $selectedFeedback->priority_color }}-800 dark:bg-{{ $selectedFeedback->priority_color }}-900 dark:text-{{ $selectedFeedback->priority_color }}-200">
                                        {{ $selectedFeedback->priority_label }}
                                    </span>
                                    <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                        {{ $selectedFeedback->category_label }}
                                    </span>
                                </div>
                            </div>

                            <div class="border-t border-gray-200 dark:border-zinc-700 pt-4">
                                <h5 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Description</h5>
                                <p class="text-sm text-gray-900 dark:text-white whitespace-pre-wrap">{{ $selectedFeedback->description }}</p>
                            </div>

                            <div class="grid grid-cols-2 gap-4 border-t border-gray-200 dark:border-zinc-700 pt-4">
                                <div>
                                    <h5 class="text-sm font-medium text-gray-500 dark:text-gray-400">Submitted By</h5>
                                    <p class="text-sm text-gray-900 dark:text-white">{{ $selectedFeedback->user?->name ?? 'Unknown' }}</p>
                                </div>
                                <div>
                                    <h5 class="text-sm font-medium text-gray-500 dark:text-gray-400">Submitted On</h5>
                                    <p class="text-sm text-gray-900 dark:text-white">{{ $selectedFeedback->created_at->format('M d, Y g:i A') }}</p>
                                </div>
                                <div>
                                    <h5 class="text-sm font-medium text-gray-500 dark:text-gray-400">Assigned To</h5>
                                    <p class="text-sm text-gray-900 dark:text-white">{{ $selectedFeedback->assignedTo?->name ?? 'Unassigned' }}</p>
                                </div>
                                <div>
                                    <h5 class="text-sm font-medium text-gray-500 dark:text-gray-400">Page URL</h5>
                                    <p class="text-sm text-gray-900 dark:text-white truncate">{{ $selectedFeedback->page_url ?? 'N/A' }}</p>
                                </div>
                            </div>

                            @if($selectedFeedback->resolution_notes)
                                <div class="border-t border-gray-200 dark:border-zinc-700 pt-4">
                                    <h5 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Resolution Notes</h5>
                                    <p class="text-sm text-gray-900 dark:text-white whitespace-pre-wrap">{{ $selectedFeedback->resolution_notes }}</p>
                                </div>
                            @endif

                            <!-- Comments -->
                            <div class="border-t border-gray-200 dark:border-zinc-700 pt-4">
                                <h5 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">Comments</h5>

                                @if (session()->has('comment-success'))
                                    <div class="mb-3 p-2 bg-green-50 dark:bg-green-900/50 rounded text-sm text-green-800 dark:text-green-200">
                                        {{ session('comment-success') }}
                                    </div>
                                @endif

                                <div class="space-y-3 mb-4 max-h-48 overflow-y-auto">
                                    @forelse($selectedFeedback->comments as $comment)
                                        <div class="flex space-x-3 p-3 rounded-lg {{ $comment->is_internal ? 'bg-yellow-50 dark:bg-yellow-900/20' : 'bg-gray-50 dark:bg-zinc-700' }}">
                                            <div class="flex-1">
                                                <div class="flex items-center space-x-2">
                                                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $comment->user?->name ?? 'System' }}</span>
                                                    @if($comment->is_internal)
                                                        <span class="px-1.5 py-0.5 text-xs bg-yellow-200 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-200 rounded">Internal</span>
                                                    @endif
                                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $comment->created_at->diffForHumans() }}</span>
                                                </div>
                                                <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">{{ $comment->comment }}</p>
                                            </div>
                                        </div>
                                    @empty
                                        <p class="text-sm text-gray-500 dark:text-gray-400 italic">No comments yet.</p>
                                    @endforelse
                                </div>

                                <!-- Add Comment Form -->
                                @can('feedback.manage')
                                    <div class="border-t border-gray-200 dark:border-zinc-700 pt-3">
                                        <textarea wire:model="newComment" rows="2"
                                            class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white text-sm"
                                            placeholder="Add a comment..."></textarea>
                                        @error('newComment') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror

                                        <div class="flex items-center justify-between mt-2">
                                            <label class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                                <input type="checkbox" wire:model="commentIsInternal" class="rounded border-gray-300 dark:border-zinc-600 text-indigo-600 mr-2">
                                                Internal note (not visible to submitter)
                                            </label>
                                            <button wire:click="addComment"
                                                class="px-3 py-1.5 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">
                                                Add Comment
                                            </button>
                                        </div>
                                    </div>
                                @endcan
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="bg-gray-50 dark:bg-zinc-700 px-6 py-4 flex justify-between">
                        @can('feedback.manage')
                            <button wire:click="editFeedback({{ $selectedFeedback->id }})"
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                                Edit
                            </button>
                        @else
                            <div></div>
                        @endcan
                        <button wire:click="closeModals"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-zinc-600 border border-gray-300 dark:border-zinc-500 rounded-md hover:bg-gray-50 dark:hover:bg-zinc-500">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Edit Modal -->
    @if($showEditModal && $selectedFeedback)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div wire:click="closeModals" class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75 transition-opacity"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                <div class="inline-block align-bottom bg-white dark:bg-zinc-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <!-- Header -->
                    <div class="bg-blue-600 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-white">Edit Feedback</h3>
                            <button wire:click="closeModals" class="text-blue-200 hover:text-white">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <form wire:submit="updateFeedback" class="px-6 py-4 space-y-4">
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                            <strong>{{ $selectedFeedback->title }}</strong><br>
                            by {{ $selectedFeedback->user?->name ?? 'Unknown' }}
                        </div>

                        <!-- Status -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                            <select wire:model="editStatus"
                                class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                                <option value="new">New</option>
                                <option value="acknowledged">Acknowledged</option>
                                <option value="in_progress">In Progress</option>
                                <option value="resolved">Resolved</option>
                                <option value="closed">Closed</option>
                                <option value="wont_fix">Won't Fix</option>
                            </select>
                            @error('editStatus') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Priority -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Priority</label>
                            <select wire:model="editPriority"
                                class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                            @error('editPriority') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Assigned To -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Assigned To</label>
                            <select wire:model="editAssignedTo"
                                class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                                <option value="">Unassigned</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                            @error('editAssignedTo') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Admin Notes -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Admin Notes</label>
                            <textarea wire:model="editAdminNotes" rows="2"
                                class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white"
                                placeholder="Internal notes..."></textarea>
                            @error('editAdminNotes') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Resolution Notes -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Resolution Notes</label>
                            <textarea wire:model="editResolutionNotes" rows="2"
                                class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white"
                                placeholder="How was this resolved?"></textarea>
                            @error('editResolutionNotes') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </form>

                    <!-- Footer -->
                    <div class="bg-gray-50 dark:bg-zinc-700 px-6 py-4 flex justify-between">
                        @can('feedback.delete')
                            <button wire:click="deleteFeedback({{ $selectedFeedback->id }})"
                                wire:confirm="Are you sure you want to delete this feedback?"
                                class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700">
                                Delete
                            </button>
                        @else
                            <div></div>
                        @endcan
                        <div class="space-x-3">
                            <button wire:click="closeModals"
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-zinc-600 border border-gray-300 dark:border-zinc-500 rounded-md hover:bg-gray-50 dark:hover:bg-zinc-500">
                                Cancel
                            </button>
                            <button wire:click="updateFeedback"
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                                Save Changes
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
