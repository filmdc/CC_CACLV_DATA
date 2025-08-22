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
                        <button wire:click="editHousehold" 
                                class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                            Edit Household
                        </button>
                    @endif
                    <a href="{{ $backUrl }}" 
                       class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                        Back to List
                    </a>
                </div>
            </div>
        </div>

        <div class="p-6">
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
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Household Type</label>
                        <p class="text-gray-900 dark:text-white">{{ ucfirst($household->household_type) }}</p>
                    </div>
                    
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
                            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Area</label>
                            <p class="text-gray-900 dark:text-white">{{ $household->currentAddress->area ?: 'Not specified' }}</p>
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
                                            {{ ucfirst($participant->relationship_to_head) ?: 'Not specified' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                            {{ $participant->date_of_birth ? \Carbon\Carbon::parse($participant->date_of_birth)->format('M j, Y') : 'N/A' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                            {{ ucfirst($participant->gender) ?: 'Not specified' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            @if($participant->active)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                                    Active
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100">
                                                    Inactive
                                                </span>
                                            @endif
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
</div>