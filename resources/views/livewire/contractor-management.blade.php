<div class="max-w-7xl mx-auto p-6">
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl">
        <!-- Header -->
        <div class="bg-emerald-600 text-white p-6">
            <h1 class="text-2xl font-bold">Contractor Management</h1>
            <p class="text-emerald-100 mt-2">Manage contractors for housing rehabilitation projects</p>
        </div>

        <!-- Filters and Search -->
        <div class="p-4 bg-gray-50 dark:bg-zinc-700 border-b border-gray-200 dark:border-zinc-600">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Search -->
                <div class="md:col-span-2">
                    <input type="text" wire:model.live="search" 
                           placeholder="Search by company, contact, email, phone, or license..." 
                           class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white focus:border-emerald-500 focus:ring-emerald-500">
                </div>
                
                <!-- Status Filter -->
                <div>
                    <select wire:model.live="filterStatus" 
                            class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>
                
                <!-- Actions -->
                <div class="flex items-center justify-end space-x-2">
                    @can('contractors.create')
                        <button wire:click="showContractorCreateForm" 
                                class="px-4 py-2 bg-emerald-600 text-white rounded-md hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            <svg class="inline-block w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Add Contractor
                        </button>
                    @endcan
                </div>
            </div>
            
            <!-- Additional Filters -->
            <div class="mt-4 flex items-center space-x-4">
                <label class="flex items-center">
                    <input type="checkbox" wire:model.live="filterExpiredLicense" 
                           class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Expired License</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" wire:model.live="filterExpiredInsurance" 
                           class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Expired Insurance</span>
                </label>
            </div>
        </div>

        <!-- Success/Error Messages -->
        @if (session()->has('message'))
            <div class="p-4 bg-green-50 dark:bg-green-900 border-l-4 border-green-400">
                <div class="flex">
                    <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="ml-3 text-sm text-green-700 dark:text-green-200">{{ session('message') }}</p>
                </div>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="p-4 bg-red-50 dark:bg-red-900 border-l-4 border-red-400">
                <div class="flex">
                    <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="ml-3 text-sm text-red-700 dark:text-red-200">{{ session('error') }}</p>
                </div>
            </div>
        @endif

        <!-- Contractors Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                <thead class="bg-gray-50 dark:bg-zinc-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Company</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Contact</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">License/Insurance</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Jobs</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-700">
                    @forelse($contractors as $contractor)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $contractor->company_name }}
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $contractor->city }}, {{ $contractor->state }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">
                                    {{ $contractor->contact_name ?? 'N/A' }}
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $contractor->phone }}
                                </div>
                                @if($contractor->email)
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $contractor->email }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="space-y-1">
                                    @if($contractor->license_number)
                                        <div class="flex items-center text-sm">
                                            <span class="text-gray-600 dark:text-gray-400">License:</span>
                                            <span class="ml-2 text-gray-900 dark:text-white">{{ $contractor->license_number }}</span>
                                            @if($contractor->isLicenseExpired())
                                                <span class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                                    Expired
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                    @if($contractor->insurance_policy_number)
                                        <div class="flex items-center text-sm">
                                            <span class="text-gray-600 dark:text-gray-400">Insurance:</span>
                                            <span class="ml-2 text-gray-900 dark:text-white">{{ $contractor->insurance_provider }}</span>
                                            @if($contractor->isInsuranceExpired())
                                                <span class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                                    Expired
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $contractor->jobs->count() }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    @if($contractor->status === 'active') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                    @elseif($contractor->status === 'inactive') bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200
                                    @else bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                    @endif">
                                    {{ ucfirst($contractor->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                <button wire:click="viewDetails({{ $contractor->id }})" 
                                        class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-900 dark:hover:text-emerald-300 mr-3">
                                    View
                                </button>
                                @can('contractors.update')
                                    <button wire:click="showEditForm({{ $contractor->id }})" 
                                            class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 mr-3">
                                        Edit
                                    </button>
                                @endcan
                                @can('contractors.delete')
                                    <button wire:click="delete({{ $contractor->id }})" 
                                            wire:confirm="Are you sure you want to delete this contractor?"
                                            class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">
                                        Delete
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                No contractors found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="p-4 border-t border-gray-200 dark:border-zinc-700">
            {{ $contractors->links() }}
        </div>
    </div>

    <!-- Create/Edit Form Modal -->
    @if($showCreateForm || $showEditForm)
        <div class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-zinc-800 rounded-lg max-w-6xl w-full max-h-[90vh] overflow-y-auto shadow-2xl">
                <div class="sticky top-0 bg-white dark:bg-zinc-800 border-b border-gray-200 dark:border-zinc-700 px-6 py-4 rounded-t-lg">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                            <svg class="inline-block w-6 h-6 mr-2 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                            {{ $showCreateForm ? 'Add New Contractor' : 'Edit Contractor' }}
                        </h3>
                        <button type="button" wire:click="resetForm" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div class="p-6">
                    <form wire:submit="{{ $showCreateForm ? 'save' : 'update' }}">
                        <!-- Company Information Section -->
                        <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-lg p-4 mb-6">
                            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-3 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                                Company Information
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Company Name -->
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                            </svg>
                                            Company Name <span class="text-red-500">*</span>
                                        </span>
                                    </label>
                                    <input type="text" wire:model="company_name" placeholder="ABC Construction Company"
                                           class="w-full rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-emerald-500 focus:ring-emerald-500 transition-colors">
                                    @error('company_name') <span class="text-red-500 text-xs flex items-center mt-1"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
                                </div>

                                <!-- Contact Information -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                            Contact First Name <span class="text-gray-400">(Optional)</span>
                                        </span>
                                    </label>
                                    <input type="text" wire:model="contact_first_name" placeholder="John"
                                           class="w-full rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-emerald-500 focus:ring-emerald-500 transition-colors">
                                    @error('contact_first_name') <span class="text-red-500 text-xs flex items-center mt-1"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                            Contact Last Name <span class="text-gray-400">(Optional)</span>
                                        </span>
                                    </label>
                                    <input type="text" wire:model="contact_last_name" placeholder="Smith"
                                           class="w-full rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-emerald-500 focus:ring-emerald-500 transition-colors">
                                    @error('contact_last_name') <span class="text-red-500 text-xs flex items-center mt-1"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
                                </div>

                                <!-- Contact Details -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 7.89a2 2 0 002.83 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                            </svg>
                                            Email Address <span class="text-gray-400">(Optional)</span>
                                        </span>
                                    </label>
                                    <input type="email" wire:model="email" placeholder="john@abcconstruction.com"
                                           class="w-full rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-emerald-500 focus:ring-emerald-500 transition-colors">
                                    @error('email') <span class="text-red-500 text-xs flex items-center mt-1"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                            </svg>
                                            Phone Number <span class="text-red-500">*</span>
                                        </span>
                                    </label>
                                    <input type="tel" wire:model="phone" placeholder="(702) 555-0123"
                                           class="w-full rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-emerald-500 focus:ring-emerald-500 transition-colors">
                                    @error('phone') <span class="text-red-500 text-xs flex items-center mt-1"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2m-9 0h10m-10 0a2 2 0 00-2 2v14a2 2 0 002 2h10a2 2 0 002-2V6a2 2 0 00-2-2"></path>
                                            </svg>
                                            Fax Number <span class="text-gray-400">(Optional)</span>
                                        </span>
                                    </label>
                                    <input type="tel" wire:model="fax" placeholder="(702) 555-0124"
                                           class="w-full rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-emerald-500 focus:ring-emerald-500 transition-colors">
                                    @error('fax') <span class="text-red-500 text-xs flex items-center mt-1"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            EIN/Tax ID <span class="text-gray-400">(Optional)</span>
                                        </span>
                                    </label>
                                    <input type="text" wire:model="ein" placeholder="12-3456789"
                                           class="w-full rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-emerald-500 focus:ring-emerald-500 transition-colors">
                                    @error('ein') <span class="text-red-500 text-xs flex items-center mt-1"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Address Section -->
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 mb-6">
                            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-3 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                Business Address
                            </h4>
                            <div class="grid grid-cols-1 gap-4">
                                <!-- Address Lines -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Street Address <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" wire:model="address_line_1" placeholder="123 Construction Blvd"
                                           class="w-full rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-emerald-500 focus:ring-emerald-500 transition-colors">
                                    @error('address_line_1') <span class="text-red-500 text-xs flex items-center mt-1"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Address Line 2 <span class="text-gray-400">(Optional)</span>
                                    </label>
                                    <input type="text" wire:model="address_line_2" placeholder="Suite 100"
                                           class="w-full rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-emerald-500 focus:ring-emerald-500 transition-colors">
                                    @error('address_line_2') <span class="text-red-500 text-xs flex items-center mt-1"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            City <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" wire:model="city" placeholder="Las Vegas"
                                               class="w-full rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-emerald-500 focus:ring-emerald-500 transition-colors">
                                        @error('city') <span class="text-red-500 text-xs flex items-center mt-1"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            State <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" wire:model="state" maxlength="2" placeholder="NV"
                                               class="w-full rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-emerald-500 focus:ring-emerald-500 transition-colors uppercase">
                                        @error('state') <span class="text-red-500 text-xs flex items-center mt-1"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Zip Code <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" wire:model="zip_code" placeholder="89101"
                                               class="w-full rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-emerald-500 focus:ring-emerald-500 transition-colors">
                                        @error('zip_code') <span class="text-red-500 text-xs flex items-center mt-1"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- License & Insurance Section -->
                        <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4 mb-6">
                            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-3 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                </svg>
                                License & Insurance Information
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- License Information -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            License Number <span class="text-gray-400">(Optional)</span>
                                        </span>
                                    </label>
                                    <input type="text" wire:model="license_number" placeholder="LIC123456"
                                           class="w-full rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-emerald-500 focus:ring-emerald-500 transition-colors">
                                    @error('license_number') <span class="text-red-500 text-xs flex items-center mt-1"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        License Expiration Date
                                    </label>
                                    <input type="date" wire:model="license_expiration"
                                           class="w-full rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-emerald-500 focus:ring-emerald-500 transition-colors">
                                    @error('license_expiration') <span class="text-red-500 text-xs flex items-center mt-1"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
                                </div>

                                <!-- Insurance Information -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Insurance Provider
                                    </label>
                                    <input type="text" wire:model="insurance_provider" placeholder="State Farm"
                                           class="w-full rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-emerald-500 focus:ring-emerald-500 transition-colors">
                                    @error('insurance_provider') <span class="text-red-500 text-xs flex items-center mt-1"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Policy Number
                                    </label>
                                    <input type="text" wire:model="insurance_policy_number" placeholder="POL123456789"
                                           class="w-full rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-emerald-500 focus:ring-emerald-500 transition-colors">
                                    @error('insurance_policy_number') <span class="text-red-500 text-xs flex items-center mt-1"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Insurance Expiration Date
                                    </label>
                                    <input type="date" wire:model="insurance_expiration"
                                           class="w-full rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-emerald-500 focus:ring-emerald-500 transition-colors">
                                    @error('insurance_expiration') <span class="text-red-500 text-xs flex items-center mt-1"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Liability Coverage Amount
                                    </label>
                                    <input type="number" wire:model="liability_coverage_amount" step="0.01" placeholder="1000000"
                                           class="w-full rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-emerald-500 focus:ring-emerald-500 transition-colors">
                                    @error('liability_coverage_amount') <span class="text-red-500 text-xs flex items-center mt-1"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Services Section -->
                        <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-4 mb-6">
                            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-3 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Services Offered
                            </h4>
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                                @foreach($availableServices as $service)
                                    <label class="flex items-center space-x-2 p-2 rounded border border-gray-200 dark:border-zinc-600 hover:bg-gray-50 dark:hover:bg-zinc-700 cursor-pointer transition-colors">
                                        <input type="checkbox" 
                                               wire:click="toggleService({{ $service->id }})"
                                               @if(in_array($service->id, $selectedServices)) checked @endif
                                               class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $service->label }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @if(count($selectedServices) > 0)
                                <div class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                                    {{ count($selectedServices) }} service(s) selected
                                </div>
                            @endif
                        </div>

                        <!-- Status and Notes Section -->
                        <div class="bg-gray-50 dark:bg-zinc-700/30 rounded-lg p-4 mb-6">
                            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-3 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Status & Additional Information
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Status -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            Status <span class="text-red-500">*</span>
                                        </span>
                                    </label>
                                    <select wire:model="status" 
                                            class="w-full rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-emerald-500 focus:ring-emerald-500 transition-colors">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                        <option value="suspended">Suspended</option>
                                    </select>
                                    @error('status') <span class="text-red-500 text-xs flex items-center mt-1"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
                                </div>

                                <!-- Notes -->
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
                                            </svg>
                                            Notes <span class="text-gray-400">(Optional)</span>
                                        </span>
                                    </label>
                                    <x-wysiwyg-editor model="notes" :rows="3" placeholder="Enter any additional notes about this contractor..." id="contractor_notes" />
                                    @error('notes') <span class="text-red-500 text-xs flex items-center mt-1"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="bg-gray-50 dark:bg-zinc-700/50 rounded-lg p-4 flex justify-between items-center">
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                <span class="text-red-500">*</span> Required fields
                            </div>
                            <div class="flex space-x-3">
                                <button type="button" wire:click="resetForm" 
                                        class="px-6 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-zinc-600 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-all duration-200 flex items-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                    Cancel
                                </button>
                                <button type="submit" 
                                        class="px-6 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-all duration-200 flex items-center font-medium shadow-lg">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        @if($showCreateForm)
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        @endif
                                    </svg>
                                    {{ $showCreateForm ? 'Create Contractor' : 'Update Contractor' }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Contractor Details Modal -->
    @if($showDetails && $selectedContractor)
        <div class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-zinc-800 rounded-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex justify-between items-start mb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Contractor Details</h3>
                        <button wire:click="$set('showDetails', false)" 
                                class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Company Information -->
                        <div>
                            <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">Company Information</h4>
                            <dl class="space-y-2">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Company Name</dt>
                                    <dd class="text-sm text-gray-900 dark:text-white">{{ $selectedContractor->company_name }}</dd>
                                </div>
                                @if($selectedContractor->contact_name)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Contact</dt>
                                        <dd class="text-sm text-gray-900 dark:text-white">{{ $selectedContractor->contact_name }}</dd>
                                    </div>
                                @endif
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Phone</dt>
                                    <dd class="text-sm text-gray-900 dark:text-white">{{ $selectedContractor->phone }}</dd>
                                </div>
                                @if($selectedContractor->fax)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Fax</dt>
                                        <dd class="text-sm text-gray-900 dark:text-white">{{ $selectedContractor->fax }}</dd>
                                    </div>
                                @endif
                                @if($selectedContractor->email)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</dt>
                                        <dd class="text-sm text-gray-900 dark:text-white">{{ $selectedContractor->email }}</dd>
                                    </div>
                                @endif
                                @if($selectedContractor->ein)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">EIN</dt>
                                        <dd class="text-sm text-gray-900 dark:text-white">{{ $selectedContractor->ein }}</dd>
                                    </div>
                                @endif
                            </dl>
                        </div>

                        <!-- Address -->
                        <div>
                            <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">Address</h4>
                            <dl class="space-y-2">
                                <div>
                                    <dd class="text-sm text-gray-900 dark:text-white">
                                        {{ $selectedContractor->address_line_1 }}<br>
                                        @if($selectedContractor->address_line_2)
                                            {{ $selectedContractor->address_line_2 }}<br>
                                        @endif
                                        {{ $selectedContractor->city }}, {{ $selectedContractor->state }} {{ $selectedContractor->zip_code }}
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <!-- License & Insurance -->
                        <div>
                            <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">License & Insurance</h4>
                            <dl class="space-y-2">
                                @if($selectedContractor->license_number)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">License Number</dt>
                                        <dd class="text-sm text-gray-900 dark:text-white">
                                            {{ $selectedContractor->license_number }}
                                            @if($selectedContractor->license_expiration)
                                                <span class="text-gray-500 dark:text-gray-400">
                                                    (Expires: {{ $selectedContractor->license_expiration->format('M d, Y') }})
                                                </span>
                                            @endif
                                            @if($selectedContractor->isLicenseExpired())
                                                <span class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                                    Expired
                                                </span>
                                            @endif
                                        </dd>
                                    </div>
                                @endif
                                @if($selectedContractor->insurance_provider)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Insurance Provider</dt>
                                        <dd class="text-sm text-gray-900 dark:text-white">{{ $selectedContractor->insurance_provider }}</dd>
                                    </div>
                                @endif
                                @if($selectedContractor->insurance_policy_number)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Policy Number</dt>
                                        <dd class="text-sm text-gray-900 dark:text-white">
                                            {{ $selectedContractor->insurance_policy_number }}
                                            @if($selectedContractor->insurance_expiration)
                                                <span class="text-gray-500 dark:text-gray-400">
                                                    (Expires: {{ $selectedContractor->insurance_expiration->format('M d, Y') }})
                                                </span>
                                            @endif
                                            @if($selectedContractor->isInsuranceExpired())
                                                <span class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                                    Expired
                                                </span>
                                            @endif
                                        </dd>
                                    </div>
                                @endif
                                @if($selectedContractor->liability_coverage_amount)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Liability Coverage</dt>
                                        <dd class="text-sm text-gray-900 dark:text-white">${{ number_format($selectedContractor->liability_coverage_amount, 2) }}</dd>
                                    </div>
                                @endif
                            </dl>
                        </div>

                        <!-- Status -->
                        <div>
                            <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">Status</h4>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                @if($selectedContractor->status === 'active') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                @elseif($selectedContractor->status === 'inactive') bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200
                                @else bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                @endif">
                                {{ ucfirst($selectedContractor->status) }}
                            </span>
                        </div>
                    </div>

                    <!-- Services Offered -->
                    @if($selectedContractor->services_offered && count($selectedContractor->services_offered) > 0)
                        <div class="mt-6">
                            <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">Services Offered</h4>
                            <div class="flex flex-wrap gap-2">
                                @foreach($selectedContractor->services_offered as $serviceId)
                                    @php
                                        $service = $availableServices->firstWhere('id', $serviceId);
                                    @endphp
                                    @if($service)
                                        <span class="px-3 py-1 bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200 rounded-full text-sm">
                                            {{ $service->name }}
                                        </span>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Notes -->
                    @if($selectedContractor->notes)
                        <div class="mt-6">
                            <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">Notes</h4>
                            <div class="text-sm text-gray-700 dark:text-gray-300 rich-text-content">{!! $selectedContractor->notes !!}</div>
                        </div>
                    @endif

                    <!-- Jobs -->
                    <div class="mt-6">
                        <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">Housing Jobs ({{ $selectedContractor->jobs->count() }})</h4>
                        @if($selectedContractor->jobs->count() > 0)
                            <div class="space-y-2">
                                @foreach($selectedContractor->jobs->take(5) as $job)
                                    <div class="bg-gray-50 dark:bg-zinc-700 p-3 rounded-md">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                    Job #{{ $job->job_number }}
                                                </p>
                                                @if($job->property)
                                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                                        {{ $job->property->address_line_1 }}, {{ $job->property->city }}
                                                    </p>
                                                @endif
                                            </div>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-zinc-600 dark:text-gray-200">
                                                {{ ucfirst($job->status) }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                                @if($selectedContractor->jobs->count() > 5)
                                    <p class="text-sm text-gray-500 dark:text-gray-400 text-center">
                                        ... and {{ $selectedContractor->jobs->count() - 5 }} more
                                    </p>
                                @endif
                            </div>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400">No jobs associated with this contractor.</p>
                        @endif
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button wire:click="$set('showDetails', false)" 
                                class="px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-zinc-700">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>