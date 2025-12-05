<div class="max-w-7xl mx-auto p-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Batch Sync to Sage Intacct</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Sync gifts by date and check scan batch</p>
            </div>
            <a href="{{ route('donor-gifts.feed') }}"
               class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-zinc-700 hover:bg-gray-50 dark:hover:bg-zinc-600">
                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to Gifts Feed
            </a>
        </div>
    </div>

    <!-- Flash Messages -->
    @if (session()->has('success'))
        <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="rounded-md bg-red-50 dark:bg-red-900/30 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-red-800 dark:text-red-200">{{ session('error') }}</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Selection Card -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Select Date and Batch</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Date Selection -->
            <div>
                <label for="selectedDate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Gift Date
                </label>
                <input type="date"
                       wire:model.live="selectedDate"
                       id="selectedDate"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <!-- Batch Selection -->
            <div>
                <label for="selectedBatch" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Check Scan Batch
                </label>
                <select wire:model.live="selectedBatch"
                        id="selectedBatch"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        {{ empty($availableBatches) ? 'disabled' : '' }}>
                    <option value="">{{ empty($availableBatches) ? 'No batches found' : 'Select a batch...' }}</option>
                    @foreach($availableBatches as $batch)
                        <option value="{{ $batch }}">Batch {{ $batch }}</option>
                    @endforeach
                </select>
                @if(count($availableBatches) > 0)
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ count($availableBatches) }} batch(es) found for this date</p>
                @endif
            </div>

            <!-- Preview Button -->
            <div class="flex items-end">
                <button wire:click="loadPreview"
                        wire:loading.attr="disabled"
                        class="w-full px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        {{ empty($selectedDate) || empty($selectedBatch) ? 'disabled' : '' }}>
                    <span wire:loading.remove wire:target="loadPreview">
                        <svg class="inline-block h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        Preview Gifts
                    </span>
                    <span wire:loading wire:target="loadPreview" class="flex items-center justify-center">
                        <svg class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Loading...
                    </span>
                </button>
            </div>
        </div>
    </div>

    <!-- Preview Section -->
    @if($showPreview)
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl p-6">
            <!-- Summary Header -->
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Batch {{ $selectedBatch }} Preview
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ \Carbon\Carbon::parse($selectedDate)->format('F j, Y') }}
                    </p>
                </div>

                <div class="flex items-center space-x-4">
                    <!-- Summary Stats -->
                    <div class="text-right">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ count($previewGifts) }} gifts
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Total: ${{ number_format($this->previewTotal, 2) }}
                        </div>
                        @if($this->checkImageCount > 0)
                            <div class="text-xs text-green-600 dark:text-green-400">
                                {{ $this->checkImageCount }} with check images
                            </div>
                        @endif
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex space-x-2">
                        @if($syncCompleted && $downloadUrl)
                            <a href="{{ $downloadUrl }}"
                               class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Download CSV
                            </a>
                        @endif

                        @if(!$syncCompleted && $this->intacctConfigured && $this->canAny(['sage.sync.perform', 'intacct.sync']))
                            <button wire:click="confirmSync"
                                    wire:loading.attr="disabled"
                                    class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 disabled:opacity-50">
                                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                </svg>
                                Sync to Sage
                            </button>
                        @elseif($syncCompleted)
                            <span class="inline-flex items-center px-4 py-2 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 rounded-md">
                                <svg class="h-4 w-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                Sync Completed
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Sync Progress Indicator -->
            @if($syncInProgress)
                <div class="mb-4 p-4 bg-purple-50 dark:bg-purple-900/30 rounded-lg">
                    <div class="flex items-center">
                        <svg class="animate-spin h-5 w-5 mr-3 text-purple-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-purple-800 dark:text-purple-200 font-medium">Syncing gifts to Sage Intacct...</span>
                    </div>
                </div>
            @endif

            <!-- Gifts Table -->
            @if(count($previewGifts) > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                        <thead class="bg-gray-50 dark:bg-zinc-700">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Gift ID</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Batch ID</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Amount</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Donor</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">GL Code</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    <span class="text-gray-500 dark:text-gray-400">Campaign</span>
                                    <span class="text-purple-600 dark:text-purple-400">/ Location</span>
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    <span class="text-gray-500 dark:text-gray-400">Solicit</span>
                                    <span class="text-purple-600 dark:text-purple-400">/ Project</span>
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    <span class="text-gray-500 dark:text-gray-400">Sub-Solicit</span>
                                    <span class="text-purple-600 dark:text-purple-400">/ Service</span>
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Payment Type</th>
                                <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Check Image</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-700">
                            @foreach($previewGifts as $gift)
                                @php
                                    $campaignMapping = $this->getMappedCode($gift, 'campaign');
                                    $solicitMapping = $this->getMappedCode($gift, 'solicit_code');
                                    $subSolicitMapping = $this->getMappedCode($gift, 'sub_solicit_code');
                                    $glMapping = $this->getMappedCode($gift, 'gl_code');
                                @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-zinc-700">
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $gift['gift_id'] ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300 font-mono">
                                        {{ $gift['DP_CheckScan_Batch'] ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-white">
                                        ${{ number_format(floatval($gift['amount'] ?? 0), 2) }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ trim(($gift['first_name'] ?? '') . ' ' . ($gift['last_name'] ?? '')) ?: 'Anonymous' }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-mono">
                                        <span class="text-gray-600 dark:text-gray-300">{{ $gift['gl_code'] ?? '-' }}</span>
                                        @if($glMapping['code'])
                                            <span class="text-purple-600 dark:text-purple-400 ml-1">→ {{ $glMapping['code'] }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-mono">
                                        <span class="text-gray-600 dark:text-gray-300">{{ $gift['campaign'] ?? '-' }}</span>
                                        @if($campaignMapping['code'])
                                            <span class="text-purple-600 dark:text-purple-400 ml-1">→ {{ $campaignMapping['code'] }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-mono">
                                        <span class="text-gray-600 dark:text-gray-300">{{ $gift['solicit_code'] ?? '-' }}</span>
                                        @if($solicitMapping['code'])
                                            <span class="{{ $solicitMapping['source'] === 'conditional' ? 'text-orange-600 dark:text-orange-400' : 'text-purple-600 dark:text-purple-400' }} ml-1">→ {{ $solicitMapping['code'] }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-mono">
                                        <span class="text-gray-600 dark:text-gray-300">{{ $gift['sub_solicit_code'] ?? '-' }}</span>
                                        @if($subSolicitMapping['code'])
                                            <span class="{{ $subSolicitMapping['source'] === 'conditional' ? 'text-orange-600 dark:text-orange-400' : 'text-purple-600 dark:text-purple-400' }} ml-1">→ {{ $subSolicitMapping['code'] }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                        {{ $gift['payment_type'] ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-center">
                                        @if(!empty($gift['IMAGE_CHECK']))
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                                <svg class="h-3 w-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                </svg>
                                                Yes
                                            </span>
                                        @else
                                            <span class="text-gray-400 dark:text-gray-500">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50 dark:bg-zinc-700">
                            <tr>
                                <td colspan="2" class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-white">
                                    Total: {{ count($previewGifts) }} gifts
                                </td>
                                <td class="px-4 py-3 text-sm font-bold text-gray-900 dark:text-white">
                                    ${{ number_format($this->previewTotal, 2) }}
                                </td>
                                <td colspan="5"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No gifts found</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">No gifts found for the selected date and batch.</p>
                </div>
            @endif
        </div>
    @endif

    <!-- Confirmation Modal -->
    @if($showConfirmModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" wire:click="cancelSync"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white dark:bg-zinc-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-purple-100 dark:bg-purple-900/30 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                                Confirm Batch Sync
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Are you sure you want to sync the following to Sage Intacct?
                                </p>
                                <div class="mt-3 bg-gray-50 dark:bg-zinc-700 rounded-md p-3">
                                    <dl class="space-y-1 text-sm">
                                        <div class="flex justify-between">
                                            <dt class="text-gray-500 dark:text-gray-400">Date:</dt>
                                            <dd class="font-medium text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($selectedDate)->format('F j, Y') }}</dd>
                                        </div>
                                        <div class="flex justify-between">
                                            <dt class="text-gray-500 dark:text-gray-400">Batch:</dt>
                                            <dd class="font-medium text-gray-900 dark:text-white">{{ $selectedBatch }}</dd>
                                        </div>
                                        <div class="flex justify-between">
                                            <dt class="text-gray-500 dark:text-gray-400">Gifts:</dt>
                                            <dd class="font-medium text-gray-900 dark:text-white">{{ count($previewGifts) }}</dd>
                                        </div>
                                        <div class="flex justify-between">
                                            <dt class="text-gray-500 dark:text-gray-400">Total Amount:</dt>
                                            <dd class="font-medium text-gray-900 dark:text-white">${{ number_format($this->previewTotal, 2) }}</dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                        <button wire:click="performSync"
                                type="button"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-purple-600 text-base font-medium text-white hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Sync Now
                        </button>
                        <button wire:click="cancelSync"
                                type="button"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-zinc-600 shadow-sm px-4 py-2 bg-white dark:bg-zinc-700 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-zinc-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
