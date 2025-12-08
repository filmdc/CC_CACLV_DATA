<div class="w-full px-0 py-6">
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl">
        <!-- Header -->
        <div class="bg-indigo-600 text-white p-6">
            <h1 class="text-2xl font-bold">Property Management</h1>
            <p class="text-indigo-100 mt-2">Manage properties for housing rehabilitation programs</p>
        </div>

        <!-- Filters and Search -->
        <div class="p-4 bg-gray-50 dark:bg-zinc-700 border-b border-gray-200 dark:border-zinc-600">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Search -->
                <div class="md:col-span-2">
                    <input type="text" wire:model.live="search" 
                           placeholder="Search by address, participant, or household code..." 
                           class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                
                <!-- Status Filter -->
                <div>
                    <select wire:model.live="filterStatus" 
                            class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="sold">Sold</option>
                        <option value="demolished">Demolished</option>
                    </select>
                </div>
                
                <!-- Type Filter -->
                <div>
                    <select wire:model.live="filterType" 
                            class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Types</option>
                        @foreach($propertyTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            
            <!-- Program Filter and Actions -->
            <div class="mt-4 flex items-center justify-between">
                <div class="w-64">
                    <select wire:model.live="filterProgram" 
                            class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Programs</option>
                        @foreach($housingPrograms as $program)
                            <option value="{{ $program->id }}">{{ $program->name }}</option>
                        @endforeach
                    </select>
                </div>
                
                @can('properties.create')
                    <button wire:click="showPropertyCreateForm" 
                            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <svg class="inline-block w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Add Property
                    </button>
                @endcan
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

        <!-- Properties Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                <thead class="bg-gray-50 dark:bg-zinc-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Property</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Owner</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Jobs</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-700">
                    @forelse($properties as $property)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $property->address_line_1 }}
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $property->city }}, {{ $property->state }} {{ $property->zip_code }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($property->participant)
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        {{ $property->participant->first_name }} {{ $property->participant->last_name }}
                                    </div>
                                @endif
                                @if($property->household)
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        HH: {{ $property->household->household_code }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 dark:bg-zinc-700 text-gray-800 dark:text-gray-200">
                                    {{ $property->propertyType->label ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $property->jobs->count() }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    @if($property->status === 'active') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                    @elseif($property->status === 'inactive') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                    @elseif($property->status === 'sold') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                    @else bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                    @endif">
                                    {{ ucfirst($property->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                <div class="flex items-center justify-center space-x-2">
                                    <button wire:click="viewDetails({{ $property->id }})"
                                            class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300"
                                            title="View Details">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </button>
                                    @can('properties.edit')
                                        <button wire:click="showEditForm({{ $property->id }})"
                                                class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300"
                                                title="Edit Property">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>
                                        <button wire:click="toggleStatus({{ $property->id }})"
                                                class="{{ $property->status === 'active' ? 'text-yellow-600 dark:text-yellow-400 hover:text-yellow-900' : 'text-green-600 dark:text-green-400 hover:text-green-900' }}"
                                                title="{{ $property->status === 'active' ? 'Deactivate' : 'Activate' }}">
                                            @if($property->status === 'active')
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                                                </svg>
                                            @else
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            @endif
                                        </button>
                                    @endcan
                                    @can('properties.delete')
                                        <button wire:click="delete({{ $property->id }})"
                                                wire:confirm="Are you sure you want to delete this property? This action cannot be undone."
                                                class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"
                                                title="Delete Property">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                No properties found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="p-4 border-t border-gray-200 dark:border-zinc-700">
            {{ $properties->links() }}
        </div>
    </div>

    <!-- Create/Edit Form Modal -->
    @if($showCreateForm || $showEditForm)
        <div class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75 flex items-center justify-center p-4 z-50" wire:click.self="resetForm">
            <div class="bg-white dark:bg-zinc-800 rounded-lg max-w-5xl w-full max-h-[90vh] overflow-y-auto shadow-2xl">
                <div class="sticky top-0 bg-white dark:bg-zinc-800 border-b border-gray-200 dark:border-zinc-700 px-6 py-4 rounded-t-lg">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                            <svg class="inline-block w-6 h-6 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4"></path>
                            </svg>
                            {{ $showCreateForm ? 'Add New Property' : 'Edit Property' }}
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
                        <!-- Household Selection Section -->
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 mb-6">
                            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-3 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                                Household & Property Owner
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Household -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                            </svg>
                                            Household <span class="text-red-500">*</span>
                                        </span>
                                    </label>
                                    <select wire:model="household_id" 
                                            class="w-full rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 transition-colors">
                                        <option value="">Choose a household...</option>
                                        @foreach($households as $household)
                                            <option value="{{ $household->id }}">
                                                {{ $household->household_code }} - 
                                                @if($household->participants->count() > 0)
                                                    {{ $household->participants->first()->first_name }} 
                                                    {{ $household->participants->first()->last_name }}
                                                @endif
                                                ({{ $household->participants->count() }} members)
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('household_id') <span class="text-red-500 text-xs flex items-center mt-1"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
                                </div>

                                <!-- Primary Participant -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                            Primary Participant <span class="text-gray-400">(Optional)</span>
                                        </span>
                                    </label>
                                    <select wire:model="participant_id" 
                                            class="w-full rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 transition-colors">
                                        <option value="">Select primary participant...</option>
                                        @foreach($participants as $participant)
                                            <option value="{{ $participant->id }}">
                                                {{ $participant->first_name }} {{ $participant->last_name }}
                                                @if($participant->household)
                                                    (HH: {{ $participant->household->household_code }})
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('participant_id') <span class="text-red-500 text-xs flex items-center mt-1"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Property Details Section -->
                        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 mb-6">
                            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-3 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                                Property Information
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Property Type -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                            </svg>
                                            Property Type <span class="text-red-500">*</span>
                                        </span>
                                    </label>
                                    <select wire:model="property_type_id" 
                                            class="w-full rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 transition-colors">
                                        <option value="">Select property type...</option>
                                        @foreach($propertyTypes as $type)
                                            <option value="{{ $type->id }}">{{ $type->label }}</option>
                                        @endforeach
                                    </select>
                                    @error('property_type_id') <span class="text-red-500 text-xs flex items-center mt-1"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
                                </div>

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
                                            class="w-full rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 transition-colors">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                        <option value="sold">Sold</option>
                                        <option value="demolished">Demolished</option>
                                    </select>
                                    @error('status') <span class="text-red-500 text-xs flex items-center mt-1"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Address Section -->
                        <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4 mb-6">
                            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-3 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                Property Address
                            </h4>
                            <div class="grid grid-cols-1 gap-4">
                                <!-- Address Line 1 -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                            </svg>
                                            Street Address <span class="text-red-500">*</span>
                                        </span>
                                    </label>
                                    <input type="text" wire:model="address_line_1" placeholder="123 Main Street" 
                                           class="w-full rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 transition-colors">
                                    @error('address_line_1') <span class="text-red-500 text-xs flex items-center mt-1"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
                                </div>

                                <!-- Address Line 2 -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Address Line 2 <span class="text-gray-400">(Optional)</span>
                                    </label>
                                    <input type="text" wire:model="address_line_2" placeholder="Apartment, suite, etc." 
                                           class="w-full rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 transition-colors">
                                    @error('address_line_2') <span class="text-red-500 text-xs flex items-center mt-1"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                    <!-- City -->
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            City <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" wire:model="city" placeholder="Las Vegas" 
                                               class="w-full rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 transition-colors">
                                        @error('city') <span class="text-red-500 text-xs flex items-center mt-1"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
                                    </div>

                                    <!-- State -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            State <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" wire:model="state" maxlength="2" placeholder="NV" 
                                               class="w-full rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 transition-colors uppercase">
                                        @error('state') <span class="text-red-500 text-xs flex items-center mt-1"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
                                    </div>

                                    <!-- Zip Code -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Zip Code <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" wire:model="zip_code" placeholder="89101" 
                                               class="w-full rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 transition-colors">
                                        @error('zip_code') <span class="text-red-500 text-xs flex items-center mt-1"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <!-- County -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        County <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" wire:model="county" placeholder="Clark County" 
                                           class="w-full rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 transition-colors">
                                    @error('county') <span class="text-red-500 text-xs flex items-center mt-1"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Property Details Section -->
                        <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-4 mb-6">
                            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-3 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                                Property Details
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <!-- Year Built -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 0h8m-6 0V5a2 2 0 012-2h2a2 2 0 012 2v2m-6 0V3a2 2 0 012-2h2a2 2 0 012 2v2m-6 0V1a2 2 0 012-2h2a2 2 0 012 2v2"></path>
                                            </svg>
                                            Year Built
                                        </span>
                                    </label>
                                    <input type="number" wire:model="year_built" min="1800" max="{{ date('Y') + 1 }}" placeholder="{{ date('Y') }}"
                                           class="w-full rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 transition-colors">
                                    @error('year_built') <span class="text-red-500 text-xs flex items-center mt-1"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
                                </div>

                                <!-- Bedrooms -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 21l0-12m8 12l0-12"></path>
                                            </svg>
                                            Bedrooms
                                        </span>
                                    </label>
                                    <input type="number" wire:model="bedrooms" min="0" max="20" placeholder="3"
                                           class="w-full rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 transition-colors">
                                    @error('bedrooms') <span class="text-red-500 text-xs flex items-center mt-1"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
                                </div>

                                <!-- Bathrooms -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10v11M20 10v11"></path>
                                            </svg>
                                            Bathrooms
                                        </span>
                                    </label>
                                    <input type="number" wire:model="bathrooms" step="0.5" min="0" max="20" placeholder="2.5"
                                           class="w-full rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 transition-colors">
                                    @error('bathrooms') <span class="text-red-500 text-xs flex items-center mt-1"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
                                </div>

                                <!-- Square Footage -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5v-4m0 4h-4m4 0l-5-5"></path>
                                            </svg>
                                            Square Footage
                                        </span>
                                    </label>
                                    <input type="number" wire:model="square_footage" min="0" placeholder="1,200"
                                           class="w-full rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 transition-colors">
                                    @error('square_footage') <span class="text-red-500 text-xs flex items-center mt-1"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <!-- Description -->
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
                                        </svg>
                                        Property Description <span class="text-gray-400">(Optional)</span>
                                    </span>
                                </label>
                                <x-wysiwyg-editor model="property_description" :rows="3" placeholder="Describe any unique features, condition, or important details about this property..." id="property_description" />
                                @error('property_description') <span class="text-red-500 text-xs flex items-center mt-1"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
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
                                        class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all duration-200 flex items-center font-medium shadow-lg">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        @if($showCreateForm)
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        @endif
                                    </svg>
                                    {{ $showCreateForm ? 'Create Property' : 'Update Property' }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Property Details Modal -->
    @if($showDetails && $selectedProperty)
        <div class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75 flex items-center justify-center p-4 z-50" wire:click.self="$set('showDetails', false)">
            <div class="bg-white dark:bg-zinc-800 rounded-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex justify-between items-start mb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Property Details</h3>
                        <button wire:click="$set('showDetails', false)" 
                                class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Property Information -->
                        <div>
                            <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">Property Information</h4>
                            <dl class="space-y-2">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Address</dt>
                                    <dd class="text-sm text-gray-900 dark:text-white">
                                        {{ $selectedProperty->address_line_1 }}<br>
                                        @if($selectedProperty->address_line_2)
                                            {{ $selectedProperty->address_line_2 }}<br>
                                        @endif
                                        {{ $selectedProperty->city }}, {{ $selectedProperty->state }} {{ $selectedProperty->zip_code }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">County</dt>
                                    <dd class="text-sm text-gray-900 dark:text-white">{{ $selectedProperty->county }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Property Type</dt>
                                    <dd class="text-sm text-gray-900 dark:text-white">{{ $selectedProperty->propertyType->label ?? 'N/A' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                                    <dd class="text-sm">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            @if($selectedProperty->status === 'active') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                            @elseif($selectedProperty->status === 'inactive') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                            @elseif($selectedProperty->status === 'sold') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                            @else bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                            @endif">
                                            {{ ucfirst($selectedProperty->status) }}
                                        </span>
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Property Details -->
                        <div>
                            <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">Property Details</h4>
                            <dl class="space-y-2">
                                @if($selectedProperty->year_built)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Year Built</dt>
                                        <dd class="text-sm text-gray-900 dark:text-white">{{ $selectedProperty->year_built }}</dd>
                                    </div>
                                @endif
                                @if($selectedProperty->bedrooms)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Bedrooms</dt>
                                        <dd class="text-sm text-gray-900 dark:text-white">{{ $selectedProperty->bedrooms }}</dd>
                                    </div>
                                @endif
                                @if($selectedProperty->bathrooms)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Bathrooms</dt>
                                        <dd class="text-sm text-gray-900 dark:text-white">{{ $selectedProperty->bathrooms }}</dd>
                                    </div>
                                @endif
                                @if($selectedProperty->square_footage)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Square Footage</dt>
                                        <dd class="text-sm text-gray-900 dark:text-white">{{ number_format($selectedProperty->square_footage) }} sq ft</dd>
                                    </div>
                                @endif
                            </dl>
                        </div>

                        <!-- Owner Information -->
                        <div>
                            <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">Owner Information</h4>
                            <dl class="space-y-2">
                                @if($selectedProperty->participant)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Participant</dt>
                                        <dd class="text-sm text-gray-900 dark:text-white">
                                            {{ $selectedProperty->participant->first_name }} {{ $selectedProperty->participant->last_name }}
                                        </dd>
                                    </div>
                                @endif
                                @if($selectedProperty->household)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Household</dt>
                                        <dd class="text-sm text-gray-900 dark:text-white">
                                            Code: {{ $selectedProperty->household->household_code }}<br>
                                            Members: {{ $selectedProperty->household->participants->count() }}
                                        </dd>
                                    </div>
                                @endif
                            </dl>
                        </div>

                        <!-- Housing Jobs -->
                        <div>
                            <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">Housing Jobs</h4>
                            @if($selectedProperty->jobs->count() > 0)
                                <ul class="space-y-1">
                                    @foreach($selectedProperty->jobs as $job)
                                        <li class="text-sm text-gray-900 dark:text-white">
                                            #{{ $job->job_number }} - {{ $job->status }}
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="text-sm text-gray-500 dark:text-gray-400">No jobs associated with this property.</p>
                            @endif
                        </div>
                    </div>

                    @if($selectedProperty->property_description)
                        <div class="mt-6">
                            <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">Description</h4>
                            <p class="text-sm text-gray-700 dark:text-gray-300">{{ $selectedProperty->property_description }}</p>
                        </div>
                    @endif

                    <!-- Documents Section -->
                    <div class="mt-6">
                        <div class="flex justify-between items-center mb-3">
                            <h4 class="text-md font-medium text-gray-900 dark:text-white">Documents</h4>
                            @can('properties.edit')
                                <button class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">
                                    Upload Document
                                </button>
                            @endcan
                        </div>

                        @if($selectedProperty->propertyDocuments && $selectedProperty->propertyDocuments->count() > 0)
                            <div class="space-y-3">
                                @foreach($selectedProperty->propertyDocuments as $doc)
                                    <div class="p-4 bg-gray-50 dark:bg-zinc-700 rounded-lg border border-gray-200 dark:border-zinc-600">
                                        <div class="flex items-start justify-between">
                                            <div class="flex items-start flex-1">
                                                <!-- Document Icon -->
                                                <div class="flex-shrink-0 mr-3 mt-0.5">
                                                    @if($doc->approval_status === 'approved')
                                                        <svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                        </svg>
                                                    @elseif($doc->approval_status === 'rejected')
                                                        <svg class="h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                                        </svg>
                                                    @elseif($doc->requires_approval && $doc->approval_status === 'pending')
                                                        <svg class="h-5 w-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                                        </svg>
                                                    @else
                                                        <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                                                        </svg>
                                                    @endif
                                                </div>

                                                <!-- Document Info -->
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center gap-2 mb-1">
                                                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                            {{ $doc->document_type ?? 'Document' }}
                                                        </p>

                                                        <!-- Requires Approval Badge -->
                                                        @if($doc->requires_approval)
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200">
                                                                <svg class="h-3 w-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                                </svg>
                                                                Needs Approval
                                                            </span>
                                                        @endif
                                                    </div>

                                                    <!-- Status and Details -->
                                                    <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1">
                                                        @if($doc->requires_approval)
                                                            <div>
                                                                Status:
                                                                <span class="font-medium">{{ ucfirst($doc->approval_status ?? 'pending') }}</span>
                                                            </div>
                                                        @endif

                                                        @if($doc->uploaded_by)
                                                            <div>
                                                                Uploaded: {{ $doc->created_at->format('M d, Y') }}
                                                            </div>
                                                        @endif

                                                        <!-- Approved Info -->
                                                        @if($doc->approval_status === 'approved' && $doc->approvedBy)
                                                            <div class="flex items-center text-green-600 dark:text-green-400">
                                                                <svg class="h-3 w-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                                </svg>
                                                                Approved by {{ $doc->approvedBy->name }} on {{ $doc->approved_at?->format('M d, Y') }}
                                                            </div>
                                                        @endif

                                                        <!-- Rejected Info -->
                                                        @if($doc->approval_status === 'rejected' && $doc->rejection_reason)
                                                            <div class="mt-2 p-2 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded">
                                                                <p class="text-xs font-semibold text-red-800 dark:text-red-200">Rejected</p>
                                                                <p class="text-xs text-red-700 dark:text-red-300 mt-1">{{ $doc->rejection_reason }}</p>
                                                            </div>
                                                        @endif
                                                    </div>

                                                    <!-- Requires Approval Checkbox (PM Only) -->
                                                    @if(auth()->user()->hasRole('project_manager') || auth()->user()->hasRole('admin'))
                                                        <div class="mt-2">
                                                            <label class="flex items-center space-x-2 cursor-pointer">
                                                                <input type="checkbox"
                                                                       wire:click="toggleRequiresApproval({{ $doc->id }})"
                                                                       {{ $doc->requires_approval ? 'checked' : '' }}
                                                                       class="rounded border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 text-indigo-600 focus:ring-indigo-500">
                                                                <span class="text-xs text-gray-600 dark:text-gray-400">Requires Approval</span>
                                                            </label>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>

                                            <!-- Action Buttons -->
                                            @if($doc->requires_approval && $doc->approval_status === 'pending' && auth()->user()->can('properties.edit'))
                                                <div class="flex gap-2 ml-3">
                                                    <button wire:click="approveDocument({{ $doc->id }})"
                                                            class="px-3 py-1.5 bg-green-600 text-white text-xs rounded hover:bg-green-700 transition">
                                                        Approve
                                                    </button>
                                                    <button wire:click="showRejectModal({{ $doc->id }})"
                                                            class="px-3 py-1.5 bg-red-600 text-white text-xs rounded hover:bg-red-700 transition">
                                                        Reject
                                                    </button>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400">No documents uploaded for this property.</p>
                        @endif
                    </div>

                    <!-- Notes Section -->
                    <div class="mt-6">
                        <div class="flex justify-between items-center mb-3">
                            <h4 class="text-md font-medium text-gray-900 dark:text-white">Notes</h4>
                            @can('properties.edit')
                                <button wire:click="$set('showNotesForm', true)"
                                        class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">
                                    Add Note
                                </button>
                            @endcan
                        </div>

                        @if($showNotesForm)
                            <div class="mb-4">
                                <x-wysiwyg-editor model="noteContent" :rows="3" placeholder="Enter note..." id="property_note_content" />
                                <div class="mt-2 flex justify-end space-x-2">
                                    <button wire:click="$set('showNotesForm', false)" 
                                            class="px-3 py-1 text-sm border border-gray-300 dark:border-zinc-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-zinc-700">
                                        Cancel
                                    </button>
                                    <button wire:click="addNote" 
                                            class="px-3 py-1 text-sm bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                                        Add Note
                                    </button>
                                </div>
                            </div>
                        @endif

                        @if($selectedProperty->notes->count() > 0)
                            <div class="space-y-2">
                                @foreach($selectedProperty->notes->sortByDesc('created_at') as $note)
                                    <div class="bg-gray-50 dark:bg-zinc-700 p-3 rounded-md">
                                        <p class="text-sm text-gray-700 dark:text-gray-300">{{ $note->note }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            {{ $note->createdBy->name }} - {{ $note->created_at->format('M d, Y g:i A') }}
                                        </p>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400">No notes for this property.</p>
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

    <!-- Rejection Modal -->
    @if($showApprovalModal)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50" wire:click.self="resetApprovalModal">
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-md w-full mx-4 shadow-xl">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    Reject Document
                </h3>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Rejection Reason <span class="text-red-500">*</span>
                    </label>
                    <textarea wire:model="rejectionReason"
                              class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                              rows="4"
                              placeholder="Please provide a detailed reason for rejection (minimum 10 characters)"></textarea>
                    @error('rejectionReason')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex gap-3">
                    <button wire:click="resetApprovalModal"
                            class="flex-1 px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-zinc-700 transition">
                        Cancel
                    </button>
                    <button wire:click="rejectDocument"
                            class="flex-1 px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition"
                            wire:loading.attr="disabled">
                        <span wire:loading.remove>Confirm Rejection</span>
                        <span wire:loading>Rejecting...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
