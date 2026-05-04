<div class="relative" wire:key="notification-bell-{{ auth()->id() ?? 'guest' }}">
    {{-- Bell trigger with unread count badge --}}
    <button type="button"
            wire:click="toggle"
            class="relative inline-flex h-10 w-10 items-center justify-center rounded-md text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-white"
            aria-label="Notifications"
            aria-expanded="{{ $open ? 'true' : 'false' }}"
            data-test="notification-bell-trigger">
        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>

        @if($unreadCount > 0)
            <span class="absolute top-1 right-1 inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-semibold leading-none text-white"
                  data-test="notification-bell-count">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        @endif
    </button>

    {{-- Dropdown panel --}}
    @if($open)
        <div class="absolute right-0 top-full z-50 mt-1 w-80 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 dark:bg-zinc-800 dark:ring-zinc-700"
             data-test="notification-bell-panel"
             wire:click.outside="$set('open', false)">
            <div class="flex items-center justify-between border-b border-zinc-200 px-4 py-2 dark:border-zinc-700">
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Notifications</h3>
                @if($unreadCount > 0)
                    <button type="button"
                            wire:click="markAllAsRead"
                            class="text-xs text-indigo-600 hover:underline dark:text-indigo-400"
                            data-test="notification-bell-mark-all">
                        Mark all read
                    </button>
                @endif
            </div>

            @if($recent->isEmpty())
                <div class="px-4 py-6 text-center text-sm text-zinc-500 dark:text-zinc-400" data-test="notification-bell-empty">
                    No notifications yet.
                </div>
            @else
                <ul class="max-h-80 divide-y divide-zinc-200 overflow-y-auto dark:divide-zinc-700">
                    @foreach($recent as $notification)
                        @php
                            $data = $notification->data;
                            $unread = $notification->read_at === null;
                            $kind = $data['kind'] ?? 'notice';
                            $title = $data['title'] ?? 'Notification';
                            $jobTitle = $data['job_title'] ?? null;
                            $jobNumber = $data['job_number'] ?? null;
                            $url = $data['url'] ?? null;
                            $completerName = $data['completer_name'] ?? null;
                        @endphp
                        <li wire:key="notification-{{ $notification->id }}"
                            class="px-4 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-700/40 {{ $unread ? 'bg-indigo-50/40 dark:bg-indigo-900/10' : '' }}">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm text-zinc-900 dark:text-white">
                                        @if($kind === 'assigned')
                                            <span class="font-medium">Assigned:</span> {{ $title }}
                                        @elseif($kind === 'completed')
                                            <span class="font-medium">Completed:</span> {{ $title }}
                                            @if($completerName) <span class="text-xs text-zinc-500 dark:text-zinc-400">by {{ $completerName }}</span> @endif
                                        @else
                                            {{ $title }}
                                        @endif
                                    </p>
                                    @if($jobTitle)
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400 truncate">
                                            {{ $jobTitle }}@if($jobNumber) <span class="font-mono">({{ $jobNumber }})</span>@endif
                                        </p>
                                    @endif
                                    <p class="text-xs text-zinc-400 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                                </div>
                                <div class="flex flex-col items-end gap-1 shrink-0">
                                    @if($unread)
                                        <span class="inline-block h-2 w-2 rounded-full bg-indigo-500" aria-label="unread"></span>
                                    @endif
                                    @if($url)
                                        <a href="{{ $url }}"
                                           wire:click="markAsRead('{{ $notification->id }}')"
                                           class="text-xs text-indigo-600 hover:underline dark:text-indigo-400">
                                            View
                                        </a>
                                    @endif
                                    @if($unread)
                                        <button type="button"
                                                wire:click="markAsRead('{{ $notification->id }}')"
                                                class="text-xs text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">
                                            Mark read
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif
</div>
