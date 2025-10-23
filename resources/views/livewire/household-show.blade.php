<div class="max-w-7xl mx-auto p-6">
    <div class="bg-white dark:bg-zinc-800 shadow-lg rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">{{ $household->household_code }}</h1>
                    <p class="text-blue-100 mt-2">Household Details</p>
                </div>
                <div class="flex space-x-3">
                    @if ($this->can('households.edit'))
                        @if($canViewSensitiveData)
                            <button wire:click="editHousehold"
                                    class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                                Edit Household
                            </button>
                        @else
                            <button disabled
                                    class="bg-white/10 text-white/50 px-4 py-2 rounded-lg font-medium cursor-not-allowed"
                                    title="You need program access to edit this household">
                                Edit Household
                            </button>
                        @endif
                    @endif
                    <a href="{{ route('household.management') }}"
                       class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                        Back to List
                    </a>
                </div>
            </div>
        </div>

        <div class="p-6">
            <!-- Access Level Indicator -->
            @if(!$canViewSensitiveData)
            <div class="mb-6 bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400 p-4 rounded-r-lg">
                <div class="flex items-start">
                    <svg class="h-5 w-5 text-yellow-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">Limited Access View</h3>
                        <p class="mt-1 text-sm text-yellow-700 dark:text-yellow-300">
                            You are viewing basic household information only. Income, housing status, and other details require program access to household members.
                        </p>
                    </div>
                </div>
            </div>
            @else
            <div class="mb-6 bg-green-50 dark:bg-green-900/20 border-l-4 border-green-400 p-4 rounded-r-lg">
                <div class="flex items-start">
                    <svg class="h-5 w-5 text-green-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-green-800 dark:text-green-200">Full Access</h3>
                        <p class="mt-1 text-sm text-green-700 dark:text-green-300">
                            You have full access to this household's information.
                        </p>
                    </div>
                </div>
            </div>
            @endif

            <!-- Basic Information -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-zinc-700 pb-2">
                    Basic Information
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Household Code</label>
                        <p class="text-gray-900 dark:text-white font-mono">{{ $household->household_code }}</p>
                    </div>

                    @if($canViewSensitiveData)
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Household Type</label>
                        <p class="text-gray-900 dark:text-white">{{ $household->household_type ? ucfirst($household->household_type) : 'Not specified' }}</p>
                    </div>
                    @endif
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Primary Phone</label>
                        <p class="text-gray-900 dark:text-white">{{ $household->primary_phone ?: 'Not specified' }}</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Member Count</label>
                        <p class="text-gray-900 dark:text-white">{{ $household->member_count }}</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Head of Household</label>
                        <p class="text-gray-900 dark:text-white">
                            @if($household->headOfHousehold)
                                <a href="{{ route('participant.show', $household->headOfHousehold->id) }}" 
                                   class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                    {{ $household->headOfHousehold->first_name }} {{ $household->headOfHousehold->last_name }}
                                </a>
                            @else
                                No head of household assigned
                            @endif
                        </p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Status</label>
                        <p>
                            @if($household->active)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                    Active
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100">
                                    Inactive
                                </span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            <!-- Address Information -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-zinc-700 pb-2">
                    Address Information
                </h2>
                @if($household->currentAddress)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Current Address</label>
                            <p class="text-gray-900 dark:text-white">
                                {{ $household->currentAddress->address ?: 'No street address' }}<br>
                                @if($household->currentAddress->city || $household->currentAddress->state || $household->currentAddress->zip_code)
                                    {{ $household->currentAddress->city }}{{ $household->currentAddress->state ? ', ' . $household->currentAddress->state : '' }} {{ $household->currentAddress->zip_code }}
                                @endif
                            </p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">County</label>
                            <p class="text-gray-900 dark:text-white">{{ $household->currentAddress->county ?: 'Not specified' }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Primary Phone</label>
                            <p class="text-gray-900 dark:text-white">{{ $household->currentAddress->primary_phone ?: 'Not specified' }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Effective Since</label>
                            <p class="text-gray-900 dark:text-white">{{ $household->currentAddress->effective_date->format('M j, Y') }}</p>
                        </div>
                    </div>
                @else
                    <p class="text-gray-500 dark:text-gray-400">No address on file</p>
                @endif
            </div>
            
            <!-- Address History -->
            @if($household->addresses->count() > 1)
                <div class="mb-8">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-zinc-700 pb-2">
                        Address History
                    </h2>
                    <div class="space-y-3">
                        @foreach($household->addresses as $address)
                            @if(!$address->is_current)
                                <div class="bg-gray-50 dark:bg-zinc-700 p-4 rounded-lg">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <p class="text-sm text-gray-900 dark:text-white">
                                                {{ $address->address ?: 'No street address' }}
                                                @if($address->city || $address->state || $address->zip_code)
                                                    <br>{{ $address->city }}{{ $address->state ? ', ' . $address->state : '' }} {{ $address->zip_code }}
                                                @endif
                                            </p>
                                            @if($address->reason_for_change)
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Reason: {{ $address->reason_for_change }}</p>
                                            @endif
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 text-right">
                                            <p>{{ $address->effective_date->format('M j, Y') }} - {{ $address->end_date ? $address->end_date->format('M j, Y') : 'Present' }}</p>
                                            @if($address->creator)
                                                <p>By: {{ $address->creator->name }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Members -->
            <div>
                <div class="flex items-center justify-between mb-4 border-b border-gray-200 dark:border-zinc-700 pb-2">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                        Household Members ({{ $household->participants->count() }})
                    </h2>
                    <div class="flex space-x-2">
                        @if ($this->can('households.edit'))
                            <button wire:click="editHousehold" 
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                                Edit Info
                            </button>
                        @endif
                        @if ($this->can('participants.create'))
                            <button wire:click="addMember" 
                                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                                Add Member
                            </button>
                        @endif
                    </div>
                </div>
                @if($household->participants->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Name
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Relationship
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Date of Birth
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Gender
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                                @foreach($household->participants as $participant)
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                            <a href="{{ route('participant.show', $participant->id) }}" 
                                               class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                                {{ $participant->first_name }} {{ $participant->last_name }}
                                                @if($participant->is_head_of_household)
                                                    <span class="ml-2 text-xs bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100 px-2 py-0.5 rounded">
                                                        Head
                                                    </span>
                                                @endif
                                            </a>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                            @if($participant->is_head_of_household)
                                                Head of Household
                                            @else
                                                {{ $participant->relationship_to_head ? ucfirst(str_replace('_', ' ', $participant->relationship_to_head)) : 'Not specified' }}
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                            {{ $participant->date_of_birth ? \Carbon\Carbon::parse($participant->date_of_birth)->format('M j, Y') : 'N/A' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                            {{ $participant->gender ? ucfirst($participant->gender) : 'Not specified' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            @if($participant->programEnrollments->where('status', 'active')->count() > 0)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                                    Active
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100">
                                                    Inactive
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm font-medium">
                                            <div class="flex space-x-2">
                                                @if ($this->can('participants.edit'))
                                                    <button wire:click="editMember({{ $participant->id }})" 
                                                            class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-xs font-medium">
                                                        Edit
                                                    </button>
                                                @endif
                                                @if ($this->can('participants.delete') && !$participant->is_head_of_household)
                                                    <button wire:click="removeMember({{ $participant->id }})" 
                                                            onclick="return confirm('Are you sure you want to remove this household member?')"
                                                            class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 text-xs font-medium">
                                                        Remove
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-gray-500 dark:text-gray-400">No members in this household.</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Edit Household Modal -->
    @if($showEditHouseholdModal)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-zinc-600">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Edit Household Information</h3>
                        <button wire:click="cancelHouseholdEdit" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div class="px-6 py-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="editHouseholdType" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Household Type <span class="text-red-500">*</span>
                            </label>
                            <select wire:model="editHouseholdType" id="editHouseholdType" 
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select household type...</option>
                                @foreach($householdTypes as $option)
                                    <option value="{{ $option->value }}">{{ $option->label }}</option>
                                @endforeach
                            </select>
                            @error('editHouseholdType') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="editHouseholdSize" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Household Size <span class="text-red-500">*</span>
                            </label>
                            <input type="number" wire:model="editHouseholdSize" id="editHouseholdSize" min="1" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('editHouseholdSize') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="editHousingStatus" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Housing Status <span class="text-red-500">*</span>
                            </label>
                            <select wire:model="editHousingStatus" id="editHousingStatus" 
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select housing status...</option>
                                @foreach($housingStatuses as $option)
                                    <option value="{{ $option->value }}">{{ $option->label }}</option>
                                @endforeach
                            </select>
                            @error('editHousingStatus') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="editMonthlyIncome" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Monthly Household Income
                            </label>
                            <input type="number" wire:model="editMonthlyIncome" id="editMonthlyIncome" min="0" step="0.01" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('editMonthlyIncome') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="space-y-4 mt-6">
                        <h4 class="text-lg font-medium text-gray-900 dark:text-white">Address Information</h4>
                        
                        <div>
                            <label for="editStreetAddress" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Street Address
                            </label>
                            <input type="text" wire:model="editStreetAddress" id="editStreetAddress" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('editStreetAddress') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="editCity" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">City</label>
                                <input type="text" wire:model="editCity" id="editCity" 
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('editCity') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label for="editState" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">State</label>
                                <input type="text" wire:model="editState" id="editState" maxlength="2" 
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('editState') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label for="editZipCode" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ZIP Code</label>
                                <input type="text" wire:model="editZipCode" id="editZipCode" 
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('editZipCode') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label for="editCounty" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">County</label>
                                <select wire:model="editCounty" id="editCounty" 
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Select county...</option>
                                    @foreach($countyOptions as $option)
                                        <option value="{{ $option->value }}">{{ $option->label }}</option>
                                    @endforeach
                                </select>
                                @error('editCounty') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div>
                            <label for="editNotes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Notes</label>
                            <x-wysiwyg-editor model="editNotes" :rows="3" placeholder="Enter household notes..." id="editNotes" />
                        </div>
                    </div>
                </div>
                
                <div class="px-6 py-4 border-t border-gray-200 dark:border-zinc-600 bg-gray-50 dark:bg-zinc-700 flex justify-end space-x-3">
                    <button wire:click="cancelHouseholdEdit" 
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-zinc-600 border border-gray-300 dark:border-zinc-500 rounded-md hover:bg-gray-50 dark:hover:bg-zinc-500">
                        Cancel
                    </button>
                    <button wire:click="saveHouseholdEdit" 
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700">
                        Save Changes
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Add Member Modal -->
    @if($showAddMemberModal)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-zinc-600">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Add Household Member</h3>
                        <button wire:click="cancelMemberEdit" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div class="px-6 py-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="memberFirstName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                First Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" wire:model="memberFirstName" id="memberFirstName" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('memberFirstName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="memberMiddleName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Middle Name</label>
                            <input type="text" wire:model="memberMiddleName" id="memberMiddleName" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('memberMiddleName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="memberLastName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Last Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" wire:model="memberLastName" id="memberLastName" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('memberLastName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="memberSuffix" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Suffix</label>
                            <input type="text" wire:model="memberSuffix" id="memberSuffix" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('memberSuffix') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="memberDateOfBirth" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Date of Birth <span class="text-red-500">*</span>
                            </label>
                            <input type="date" wire:model="memberDateOfBirth" id="memberDateOfBirth" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('memberDateOfBirth') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="memberGender" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Gender <span class="text-red-500">*</span>
                            </label>
                            <select wire:model="memberGender" id="memberGender" 
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select gender...</option>
                                @foreach($genderOptions as $option)
                                    <option value="{{ $option->value }}">{{ $option->label }}</option>
                                @endforeach
                            </select>
                            @error('memberGender') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="memberTelephone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Phone Number</label>
                            <input type="tel" wire:model="memberTelephone" id="memberTelephone" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('memberTelephone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="memberEmail" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email Address</label>
                            <input type="email" wire:model="memberEmail" id="memberEmail" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('memberEmail') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label for="memberRelationship" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Relationship to Head of Household <span class="text-red-500">*</span>
                            </label>
                            <select wire:model="memberRelationship" id="memberRelationship" 
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select relationship...</option>
                                <option value="self">Self (Head of Household)</option>
                                <option value="spouse">Spouse</option>
                                <option value="child">Child</option>
                                <option value="parent">Parent</option>
                                <option value="sibling">Sibling</option>
                                <option value="grandparent">Grandparent</option>
                                <option value="grandchild">Grandchild</option>
                                <option value="other_relative">Other Relative</option>
                                <option value="unrelated_individual">Unrelated Individual</option>
                            </select>
                            @error('memberRelationship') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
                
                <div class="px-6 py-4 border-t border-gray-200 dark:border-zinc-600 bg-gray-50 dark:bg-zinc-700 flex justify-end space-x-3">
                    <button wire:click="cancelMemberEdit" 
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-zinc-600 border border-gray-300 dark:border-zinc-500 rounded-md hover:bg-gray-50 dark:hover:bg-zinc-500">
                        Cancel
                    </button>
                    <button wire:click="saveMember" 
                            class="px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700">
                        Add Member
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Edit Member Modal -->
    @if($showEditMemberModal)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-zinc-600">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Edit Household Member</h3>
                        <button wire:click="cancelMemberEdit" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div class="px-6 py-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="memberFirstName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                First Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" wire:model="memberFirstName" id="memberFirstName" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('memberFirstName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="memberMiddleName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Middle Name</label>
                            <input type="text" wire:model="memberMiddleName" id="memberMiddleName" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('memberMiddleName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="memberLastName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Last Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" wire:model="memberLastName" id="memberLastName" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('memberLastName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="memberSuffix" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Suffix</label>
                            <input type="text" wire:model="memberSuffix" id="memberSuffix" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('memberSuffix') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="memberDateOfBirth" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Date of Birth <span class="text-red-500">*</span>
                            </label>
                            <input type="date" wire:model="memberDateOfBirth" id="memberDateOfBirth" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('memberDateOfBirth') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="memberGender" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Gender <span class="text-red-500">*</span>
                            </label>
                            <select wire:model="memberGender" id="memberGender" 
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select gender...</option>
                                @foreach($genderOptions as $option)
                                    <option value="{{ $option->value }}">{{ $option->label }}</option>
                                @endforeach
                            </select>
                            @error('memberGender') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="memberTelephone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Phone Number</label>
                            <input type="tel" wire:model="memberTelephone" id="memberTelephone" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('memberTelephone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="memberEmail" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email Address</label>
                            <input type="email" wire:model="memberEmail" id="memberEmail" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('memberEmail') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="md:col-span-2">
                            @if($isEditingHeadOfHousehold)
                                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm text-blue-800 dark:text-blue-200 font-medium">This is the Head of Household</p>
                                            <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">Relationship field is automatically set for head of household members.</p>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" wire:model="memberRelationship" value="self">
                            @else
                                <label for="memberRelationship" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Relationship to Head of Household <span class="text-red-500">*</span>
                                </label>
                                <select wire:model="memberRelationship" id="memberRelationship" 
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Select relationship...</option>
                                    <option value="spouse">Spouse</option>
                                    <option value="child">Child</option>
                                    <option value="parent">Parent</option>
                                    <option value="sibling">Sibling</option>
                                    <option value="grandparent">Grandparent</option>
                                    <option value="grandchild">Grandchild</option>
                                    <option value="other_relative">Other Relative</option>
                                    <option value="unrelated_individual">Unrelated Individual</option>
                                </select>
                                @error('memberRelationship') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            @endif
                        </div>
                    </div>
                </div>
                
                <div class="px-6 py-4 border-t border-gray-200 dark:border-zinc-600 bg-gray-50 dark:bg-zinc-700 flex justify-end space-x-3">
                    <button wire:click="cancelMemberEdit" 
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-zinc-600 border border-gray-300 dark:border-zinc-500 rounded-md hover:bg-gray-50 dark:hover:bg-zinc-500">
                        Cancel
                    </button>
                    <button wire:click="updateMember" 
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-50 cursor-not-allowed"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span wire:loading.remove>Update Member</span>
                        <span wire:loading>Updating...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>