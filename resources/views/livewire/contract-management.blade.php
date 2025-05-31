<div class="max-w-7xl mx-auto p-6">
    <div class="bg-white dark:bg-zinc-800 shadow-lg rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-green-600 text-white p-6">
            <h1 class="text-2xl font-bold">Contract Management</h1>
            <p class="text-green-100 mt-2">Manage funding contracts and agreements</p>
        </div>

        <!-- Search and Actions -->
        <div class="p-4 bg-gray-50 dark:bg-zinc-700 border-b border-gray-200 dark:border-zinc-600">
            <div class="flex items-center justify-between">
                <div class="flex-1 max-w-md">
                    <input type="text" wire:model.live="search" placeholder="Search contracts..." 
                           class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
                <button wire:click="showCreateContract" 
                        class="ml-4 px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                    Create Contract
                </button>
            </div>
        </div>

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

        <!-- Create/Edit Form -->
        @if($showCreateForm || $showEditForm)
            <div class="p-6 bg-green-50 dark:bg-green-900/20 border-b border-gray-200 dark:border-zinc-600">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">{{ $showCreateForm ? 'Create New Contract' : 'Edit Contract' }}</h3>

                <form wire:submit="{{ $showCreateForm ? 'createContract' : 'updateContract' }}" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Title <span class="text-red-500">*</span>
                            </label>
                            <input type="text" wire:model="title" id="title" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500">
                            @error('title') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="contract_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Contract Number</label>
                            <input type="text" wire:model="contract_number" id="contract_number" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500">
                            @error('contract_number') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="cfda_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">CFDA Number</label>
                            <input type="text" wire:model="cfda_number" id="cfda_number" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500">
                            @error('cfda_number') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="program_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Program <span class="text-red-500">*</span>
                            </label>
                            <select wire:model="program_id" id="program_id" 
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500">
                                <option value="">Select Program</option>
                                @foreach($programs as $program)
                                    <option value="{{ $program->id }}">{{ $program->name }}</option>
                                @endforeach
                            </select>
                            @error('program_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="funder" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Funder <span class="text-red-500">*</span>
                            </label>
                            <input type="text" wire:model="funder" id="funder" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500">
                            @error('funder') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Amount <span class="text-red-500">*</span>
                            </label>
                            <input type="number" step="0.01" wire:model="amount" id="amount" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500">
                            @error('amount') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Start Date <span class="text-red-500">*</span>
                            </label>
                            <input type="date" wire:model="start_date" id="start_date" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500">
                            @error('start_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">End Date</label>
                            <input type="date" wire:model="end_date" id="end_date" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500">
                            @error('end_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="fund_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fund Code</label>
                            <input type="text" wire:model="fund_code" id="fund_code" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500">
                            @error('fund_code') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="purpose" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Purpose</label>
                        <textarea wire:model="purpose" id="purpose" rows="3" 
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
                        @error('purpose') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                        <textarea wire:model="notes" id="notes" rows="3" 
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
                        @error('notes') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" wire:click="$set('showCreateForm', false); $set('showEditForm', false)" 
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-zinc-700 border border-gray-300 dark:border-zinc-600 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-600 focus:outline-none focus:ring-2 focus:ring-gray-500">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                            {{ $showCreateForm ? 'Create Contract' : 'Update Contract' }}
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <!-- Contracts Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-600">
                <thead class="bg-gray-50 dark:bg-zinc-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Contract Number</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Program</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Funder</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Start Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">End Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-600">
                    @forelse($contracts as $contract)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ $contract->title }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $contract->contract_number ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $contract->program->name ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $contract->funder }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${{ number_format($contract->amount, 2) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $contract->start_date?->format('M j, Y') ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $contract->end_date?->format('M j, Y') ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <button wire:click="showEditContract({{ $contract->id }})" 
                                            class="text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300">Edit</button>
                                    <button wire:click="deleteContract({{ $contract->id }})" 
                                            onclick="return confirm('Are you sure you want to delete this contract?')"
                                            class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">Delete</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                No contracts found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($contracts->hasPages())
            <div class="px-6 py-3 border-t border-gray-200 dark:border-zinc-600">
                {{ $contracts->links() }}
            </div>
        @endif
    </div>
</div>