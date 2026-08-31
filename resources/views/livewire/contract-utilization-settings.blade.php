<div class="w-full px-0 py-6">
    <div class="bg-white dark:bg-zinc-800 shadow-lg rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-purple-600 to-purple-700 text-white p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Utilization Alert Settings</h1>
                    <p class="text-purple-100 mt-2">Control who gets contract overspend alerts, and test the evaluation without mailing staff</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('contract.utilization') }}" wire:navigate
                       class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                        Utilization Dashboard
                    </a>
                    <a href="{{ route('admin.panel') }}" wire:navigate
                       class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                        Back to Admin Panel
                    </a>
                </div>
            </div>
        </div>

        <div class="p-6 space-y-6">
            @if (session()->has('success'))
                <div class="rounded-md bg-green-50 dark:bg-green-900 p-4">
                    <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</p>
                </div>
            @endif

            @if (session()->has('error'))
                <div class="rounded-md bg-red-50 dark:bg-red-900 p-4">
                    <p class="text-sm font-medium text-red-800 dark:text-red-200">{{ session('error') }}</p>
                </div>
            @endif

            <!-- Current state -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="rounded-lg border p-4 {{ $notificationsEnabled ? 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20' : 'border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-900/20' }}">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Alert emails</p>
                    <p class="mt-1 text-lg font-semibold {{ $notificationsEnabled ? 'text-green-800 dark:text-green-200' : 'text-amber-800 dark:text-amber-200' }}">
                        {{ $notificationsEnabled ? 'Enabled' : 'Disabled for everyone' }}
                    </p>
                    <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                        {{ $notificationsEnabled
                            ? 'Recipients receive threshold alerts as contracts cross them.'
                            : 'Overspend is still evaluated and recorded — no email is sent.' }}
                    </p>
                </div>

                <div class="rounded-lg border p-4 {{ $testMode ? 'border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-900/20' : 'border-gray-200 bg-gray-50 dark:border-zinc-600 dark:bg-zinc-700/40' }}">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Test mode</p>
                    <p class="mt-1 text-lg font-semibold {{ $testMode ? 'text-blue-800 dark:text-blue-200' : 'text-gray-700 dark:text-gray-200' }}">
                        {{ $testMode ? 'On' : 'Off' }}
                    </p>
                    <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                        {{ $testMode
                            ? 'Every alert is redirected to ' . ($testEmail ?: 'the test address') . '.'
                            : 'Alerts go to the real recipients.' }}
                    </p>
                </div>

                <div class="rounded-lg border border-gray-200 bg-gray-50 dark:border-zinc-600 dark:bg-zinc-700/40 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Waiting in queue</p>
                    <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ $queuedAlertCount }}</p>
                    <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                        Alert jobs not yet delivered. A number that never drops means the queue worker is stopped.
                    </p>
                </div>
            </div>

            <!-- Settings -->
            <form wire:submit.prevent="save" class="space-y-6">
                <div class="border border-gray-200 dark:border-zinc-600 rounded-lg p-5 space-y-5">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Delivery</h2>

                    <!-- Master switch -->
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" wire:model.live="notificationsEnabled"
                               class="mt-1 rounded border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 text-indigo-600 focus:ring-indigo-500">
                        <span>
                            <span class="block text-sm font-medium text-gray-900 dark:text-white">Send contract utilization alerts</span>
                            <span class="block text-xs text-gray-600 dark:text-gray-300 mt-0.5">
                                Turn this off to stop overspend emails for everyone. This affects only utilization alerts —
                                contract report reminders are unaffected. Thresholds keep being evaluated and recorded while
                                off, so switching back on will not replay a backlog.
                            </span>
                        </span>
                    </label>

                    @unless ($notificationsEnabled)
                        <div class="rounded-md bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 p-3">
                            <p class="text-xs text-amber-800 dark:text-amber-200">
                                Alerts are off for all recipients. Crossings will be logged silently. If you also turn on
                                test mode below, they will still be delivered to the single test address.
                            </p>
                        </div>
                    @endunless

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2 border-t border-gray-100 dark:border-zinc-700">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" wire:model="notifyContractManagers"
                                   class="mt-1 rounded border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 text-indigo-600 focus:ring-indigo-500">
                            <span>
                                <span class="block text-sm font-medium text-gray-900 dark:text-white">Notify contract managers</span>
                                <span class="block text-xs text-gray-600 dark:text-gray-300">Managers assigned to the contract with notifications enabled.</span>
                            </span>
                        </label>

                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" wire:model="notifyDueDateEmails"
                                   class="mt-1 rounded border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 text-indigo-600 focus:ring-indigo-500">
                            <span>
                                <span class="block text-sm font-medium text-gray-900 dark:text-white">Notify report due-date recipients</span>
                                <span class="block text-xs text-gray-600 dark:text-gray-300">Anyone on the contract's report due-date email list.</span>
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Test routing -->
                <div class="border border-gray-200 dark:border-zinc-600 rounded-lg p-5 space-y-4">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Test routing</h2>

                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" wire:model.live="testMode"
                               class="mt-1 rounded border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 text-indigo-600 focus:ring-indigo-500">
                        <span>
                            <span class="block text-sm font-medium text-gray-900 dark:text-white">Redirect all utilization alerts to one address</span>
                            <span class="block text-xs text-gray-600 dark:text-gray-300 mt-0.5">
                                While on, no real recipient receives a utilization alert — every one goes to the address below,
                                subject-prefixed <span class="font-mono">[TEST]</span>. This overrides the switch above, so you can
                                keep alerts off for staff and still watch them yourself.
                            </span>
                        </span>
                    </label>

                    <div>
                        <label for="testEmail" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Test email address</label>
                        <input type="email" id="testEmail" wire:model="testEmail" placeholder="you@caclv.org"
                               class="w-full md:w-96 rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                        @error('testEmail') <span class="block text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Thresholds -->
                <div class="border border-gray-200 dark:border-zinc-600 rounded-lg p-5 space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Thresholds</h2>
                            <p class="text-xs text-gray-600 dark:text-gray-300 mt-1">
                                Percent of budget <em>remaining</em>. An alert fires the first time a contract drops to or below each
                                value. 0% is the overspend alert.
                            </p>
                        </div>
                        <button type="button" wire:click="addThreshold"
                                class="text-sm px-3 py-1.5 border border-indigo-600 text-indigo-600 dark:text-indigo-400 rounded-md hover:bg-indigo-50 dark:hover:bg-indigo-900/20">
                            Add
                        </button>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        @foreach ($thresholds as $index => $threshold)
                            <div class="flex items-center gap-1">
                                <input type="number" min="0" max="100" wire:model="thresholds.{{ $index }}"
                                       class="w-20 rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                                <span class="text-sm text-gray-500 dark:text-gray-400">%</span>
                                <button type="button" wire:click="removeThreshold({{ $index }})"
                                        class="text-gray-400 hover:text-red-600 px-1" title="Remove">&times;</button>
                            </div>
                        @endforeach
                    </div>
                    @error('thresholds') <span class="block text-red-500 text-xs">{{ $message }}</span> @enderror
                    @error('thresholds.*') <span class="block text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                            class="bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition duration-150 ease-in-out">
                        Save Settings
                    </button>
                </div>
            </form>

            <!-- Test runner -->
            <div class="border border-gray-200 dark:border-zinc-600 rounded-lg p-5 space-y-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Run the overspend evaluation now</h2>
                    <p class="text-xs text-gray-600 dark:text-gray-300 mt-1">
                        Runs the same check the scheduler runs daily at 9:00 AM. A dry run touches nothing; a test run
                        routes every resulting alert to the test address above, never to real recipients. Neither one
                        marks a threshold as handled, so a contract you rehearse on will still alert its real recipients
                        when alerts are on.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                    <div class="md:col-span-2">
                        <label for="runContractId" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Limit to one contract (optional)</label>
                        <select id="runContractId" wire:model="runContractId"
                                class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All contracts with a budget</option>
                            @foreach ($contracts as $contract)
                                <option value="{{ $contract->id }}">
                                    {{ $contract->title }}@if ($contract->contract_number) ({{ $contract->contract_number }})@endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex gap-2">
                        <button type="button" wire:click="runDryRun" wire:loading.attr="disabled"
                                class="flex-1 px-4 py-2 border border-gray-300 dark:border-zinc-600 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-50 dark:hover:bg-zinc-700 disabled:opacity-50">
                            Dry run
                        </button>
                        <button type="button" wire:click="runTestSend" wire:loading.attr="disabled"
                                class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50">
                            Send test
                        </button>
                    </div>
                </div>

                <div wire:loading wire:target="runDryRun,runTestSend" class="text-sm text-gray-500 dark:text-gray-400">
                    Running check…
                </div>

                @if ($hasRun && count($executionLog))
                    <div class="bg-gray-900 text-gray-100 rounded-md p-4 max-h-80 overflow-y-auto font-mono text-xs space-y-1">
                        @foreach ($executionLog as $entry)
                            <div><span class="text-gray-500">{{ $entry['time'] }}</span> {{ $entry['message'] }}</div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Suppressed crossings -->
            @if ($suppressedCount > 0)
                <div class="border border-gray-200 dark:border-zinc-600 rounded-lg p-5">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Crossed while alerts were off</h2>
                            <p class="text-xs text-gray-600 dark:text-gray-300 mt-1">
                                Recorded but never emailed. These will not be replayed when alerts are turned back on.
                            </p>
                        </div>
                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $suppressedCount }} total</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                            <thead>
                                <tr class="text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    <th class="py-2 pr-4">Contract</th>
                                    <th class="py-2 pr-4">Threshold</th>
                                    <th class="py-2 pr-4">Utilization</th>
                                    <th class="py-2">Recorded</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-zinc-700 text-sm">
                                @foreach ($suppressed as $row)
                                    <tr>
                                        <td class="py-2 pr-4 text-gray-900 dark:text-white">
                                            {{ $row->contract->title ?? 'Contract #'.$row->contract_id }}
                                        </td>
                                        <td class="py-2 pr-4 text-gray-600 dark:text-gray-300">{{ $row->threshold_percentage }}% remaining</td>
                                        <td class="py-2 pr-4 text-gray-600 dark:text-gray-300">{{ $row->utilization_at_notification }}%</td>
                                        <td class="py-2 text-gray-600 dark:text-gray-300">{{ $row->notified_at?->format('M j, Y g:i A') }}</td>
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
