<div class="max-w-7xl mx-auto p-6">
    <div class="bg-white dark:bg-zinc-800 shadow-lg rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-purple-600 to-purple-700 text-white p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Contract Notification Test Dashboard</h1>
                    <p class="text-purple-100 mt-2">Test and preview contract report reminder notifications</p>
                </div>
                <a href="{{ route('admin.panel') }}" wire:navigate
                   class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                    Back to Admin Panel
                </a>
            </div>
        </div>

        <!-- Test Mode Warning -->
        @if($isTestMode)
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700 dark:text-yellow-300">
                            <strong>Test Mode is Active:</strong> All notifications will be sent to the configured test email address instead of assigned users.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Success/Error Messages -->
        @if (session()->has('success'))
            <div class="p-4 bg-green-50 dark:bg-green-900/20 border-l-4 border-green-400">
                <div class="flex">
                    <div class="ml-3">
                        <p class="text-sm text-green-700 dark:text-green-300">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="p-4 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-400">
                <div class="flex">
                    <div class="ml-3">
                        <p class="text-sm text-red-700 dark:text-red-300">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
        @endif

        <div class="p-6">
            <!-- Test Configuration -->
            <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Test Configuration</h2>
                
                <!-- Debug info (remove in production) -->
                <div class="mb-4 text-xs text-gray-500 dark:text-gray-400">
                    <p>Debug: Contract ID: {{ $selectedContractId ?: 'none' }} | Due Date ID: {{ $selectedDueDateId ?: 'none' }} | Day Interval: {{ $selectedDayInterval !== '' ? $selectedDayInterval : 'none' }} | Email: {{ $testEmailRecipient ?: 'none' }}</p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Contract Selection -->
                    <div>
                        <label for="contract" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Select Contract</label>
                        <select wire:model.live="selectedContractId" id="contract"
                                class="mt-1 block w-full border-gray-300 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                            <option value="">Select a contract...</option>
                            @foreach($contracts as $contract)
                                <option wire:key="contract-{{ $contract->id }}" value="{{ $contract->id }}">
                                    {{ $contract->title }} ({{ $contract->reportDueDates->count() }} active due dates)
                                </option>
                            @endforeach
                        </select>
                        @error('selectedContractId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <!-- Due Date Selection -->
                    <div>
                        <label for="dueDate" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Select Due Date</label>
                        <select wire:model.live="selectedDueDateId" id="dueDate"
                                class="mt-1 block w-full border-gray-300 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500"
                                @if(!$selectedContractId) disabled @endif>
                            <option value="">Select a due date...</option>
                            @foreach($dueDates as $dueDate)
                                <option wire:key="duedate-{{ $dueDate->id }}" value="{{ $dueDate->id }}">
                                    {{ strip_tags($dueDate->report_name ?? ucfirst($dueDate->report_type) . ' Report') }} - 
                                    Due: {{ $dueDate->due_date->format('M j, Y') }}
                                    ({{ $dueDate->emails->count() }} assigned)
                                </option>
                            @endforeach
                        </select>
                        @error('selectedDueDateId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <!-- Day Interval Selection -->
                    <div>
                        <label for="dayInterval" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Test Reminder Interval</label>
                        <select wire:model.live="selectedDayInterval" id="dayInterval"
                                class="mt-1 block w-full border-gray-300 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                            <option value="">Select interval...</option>
                            @foreach($intervalOptions as $days => $label)
                                <option value="{{ $days }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('selectedDayInterval') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <!-- Test Email Recipient -->
                    <div>
                        <label for="testEmail" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Test Email Recipient</label>
                        <input type="email" wire:model="testEmailRecipient" id="testEmail"
                               class="mt-1 block w-full border-gray-300 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500"
                               placeholder="test@example.com">
                        @error('testEmailRecipient') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="mt-6 flex space-x-3">
                    <button wire:click="previewEmail"
                            wire:loading.attr="disabled"
                            @if(!$selectedContractId || !$selectedDueDateId || $selectedDayInterval === null || $selectedDayInterval === '' || !$testEmailRecipient) 
                                disabled 
                                class="px-4 py-2 bg-gray-400 text-gray-200 rounded-md cursor-not-allowed"
                            @else
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            @endif>
                        <span wire:loading.remove wire:target="previewEmail">Preview Email</span>
                        <span wire:loading wire:target="previewEmail">Loading...</span>
                    </button>
                    <button wire:click="sendTestNotification"
                            wire:loading.attr="disabled"
                            @if(!$selectedContractId || !$selectedDueDateId || $selectedDayInterval === null || $selectedDayInterval === '' || !$testEmailRecipient) 
                                disabled 
                                class="px-4 py-2 bg-gray-400 text-gray-200 rounded-md cursor-not-allowed"
                            @else
                                class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            @endif>
                        <span wire:loading.remove wire:target="sendTestNotification">Send Test Notification</span>
                        <span wire:loading wire:target="sendTestNotification">Sending...</span>
                    </button>
                </div>
            </div>

            <!-- Email Preview -->
            @if($showPreview && !empty($emailPreview))
                <div class="bg-white dark:bg-zinc-700 rounded-lg shadow-md p-6 mb-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Email Preview</h2>
                    
                    <div class="space-y-4">
                        <!-- Recipient Info -->
                        <div>
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">To:</p>
                            <p class="text-sm text-gray-900 dark:text-white">{{ $emailPreview['recipient'] }}</p>
                        </div>

                        <!-- Subject -->
                        <div>
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Subject:</p>
                            <p class="text-sm text-gray-900 dark:text-white font-mono">{{ $emailPreview['subject'] }}</p>
                        </div>

                        <!-- Body -->
                        <div>
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Body:</p>
                            <div class="border border-gray-300 dark:border-zinc-600 rounded-md p-4 bg-gray-50 dark:bg-zinc-800">
                                <div class="prose dark:prose-invert max-w-none text-sm">
                                    {!! nl2br(e($emailPreview['body'])) !!}
                                </div>
                            </div>
                        </div>

                        <!-- Assigned Users -->
                        @if(isset($emailPreview['assigned_users']) && count($emailPreview['assigned_users']) > 0)
                            <div>
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Normally would be sent to ({{ count($emailPreview['assigned_users']) }} users):
                                </p>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                    @foreach($emailPreview['assigned_users'] as $user)
                                        <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                            <span>{{ $user['name'] }} ({{ $user['email'] }})</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Execution Log -->
            @if(!empty($executionLog))
                <div class="bg-gray-900 text-gray-100 rounded-lg p-6 mb-6">
                    <h2 class="text-lg font-semibold mb-4">Execution Log</h2>
                    <div class="font-mono text-sm space-y-1 overflow-x-auto">
                        @foreach($executionLog as $log)
                            <div class="flex">
                                <span class="text-gray-500 mr-3">[{{ $log['time'] }}]</span>
                                <span>{{ $log['message'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Reminder History -->
            @if($selectedDueDateId && $reminderHistory->count() > 0)
                <div class="bg-white dark:bg-zinc-700 rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Reminder History</h2>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-600">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">User</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Email</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Reminder Type</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Sent At</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-zinc-600">
                                @foreach($reminderHistory as $history)
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $history['user'] }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $history['email'] }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                            @if($history['day_interval'] == 0)
                                                On Due Date
                                            @elseif($history['day_interval'] == 1)
                                                1 Day Before
                                            @else
                                                {{ $history['day_interval'] }} Days Before
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                            {{ $history['sent_at'] ? $history['sent_at']->format('M j, Y g:i A') : 'N/A' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>