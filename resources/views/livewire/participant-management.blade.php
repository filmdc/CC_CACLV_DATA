<div class="max-w-7xl mx-auto p-6">
    <div class="bg-white dark:bg-zinc-800 shadow-lg rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-blue-600 text-white p-6">
            <h1 class="text-2xl font-bold">Participant Management</h1>
            <p class="text-blue-100 mt-2">Manage participant records and demographics</p>
        </div>

        <!-- Search -->
        <div class="p-4 bg-gray-50 dark:bg-zinc-700 border-b border-gray-200 dark:border-zinc-600">
            <div class="flex items-center justify-between">
                <div class="flex-1 max-w-md">
                    <input type="text" wire:model.live="search" placeholder="Search participants..." 
                           class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
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

        <!-- Edit Form -->
        @if($showEditForm && $selectedParticipant)
            <div class="p-6 bg-blue-50 dark:bg-blue-900/20 border-b border-gray-200 dark:border-zinc-600">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Edit Participant: {{ $selectedParticipant->full_name }}</h3>

                <form wire:submit="updateParticipant" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="first_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                First Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" wire:model="first_name" id="first_name" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('first_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="middle_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Middle Name</label>
                            <input type="text" wire:model="middle_name" id="middle_name" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('middle_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="last_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Last Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" wire:model="last_name" id="last_name" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('last_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="suffix" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Suffix</label>
                            <input type="text" wire:model="suffix" id="suffix" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('suffix') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="date_of_birth" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Date of Birth <span class="text-red-500">*</span>
                            </label>
                            <input type="date" wire:model="date_of_birth" id="date_of_birth" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('date_of_birth') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="gender" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Gender <span class="text-red-500">*</span>
                            </label>
                            <select wire:model="gender" id="gender" 
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Non-binary">Non-binary</option>
                                <option value="Other">Other</option>
                                <option value="Prefer not to say">Prefer not to say</option>
                            </select>
                            @error('gender') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="telephone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Phone Number</label>
                            <input type="tel" wire:model="telephone" id="telephone" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('telephone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email Address</label>
                            <input type="email" wire:model="email" id="email" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="education_level" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Education Level</label>
                            <select wire:model="education_level" id="education_level" 
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Education Level</option>
                                <option value="Less than high school">Less than high school</option>
                                <option value="High school graduate">High school graduate</option>
                                <option value="Some college">Some college</option>
                                <option value="Bachelor's degree">Bachelor's degree</option>
                                <option value="Graduate degree">Graduate degree</option>
                            </select>
                            @error('education_level') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="ethnicity" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ethnicity</label>
                            <select wire:model="ethnicity" id="ethnicity" 
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Ethnicity</option>
                                <option value="Hispanic or Latino">Hispanic or Latino</option>
                                <option value="Not Hispanic or Latino">Not Hispanic or Latino</option>
                                <option value="Unknown/not reported">Unknown/not reported</option>
                            </select>
                            @error('ethnicity') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="race" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Race</label>
                            <select wire:model="race" id="race" 
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Race</option>
                                <option value="American Indian or Alaska Native">American Indian or Alaska Native</option>
                                <option value="Asian">Asian</option>
                                <option value="Black or African American">Black or African American</option>
                                <option value="Native Hawaiian or Other Pacific Islander">Native Hawaiian or Other Pacific Islander</option>
                                <option value="White">White</option>
                                <option value="More than one race">More than one race</option>
                                <option value="Unknown/not reported">Unknown/not reported</option>
                            </select>
                            @error('race') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="military_status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Military Status</label>
                            <select wire:model="military_status" id="military_status" 
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Status</option>
                                <option value="Never served">Never served</option>
                                <option value="Currently serving">Currently serving</option>
                                <option value="Veteran">Veteran</option>
                                <option value="Unknown/not reported">Unknown/not reported</option>
                            </select>
                            @error('military_status') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" wire:click="$set('showEditForm', false)" 
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-zinc-700 border border-gray-300 dark:border-zinc-600 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-600 focus:outline-none focus:ring-2 focus:ring-gray-500">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            Update Participant
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <!-- Participants Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-600">
                <thead class="bg-gray-50 dark:bg-zinc-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Household</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Age</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Gender</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Contact</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-600">
                    @forelse($participants as $participant)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $participant->full_name }}</div>
                                    @if($participant->email)
                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $participant->email }}</div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $participant->household->household_code ?? 'N/A' }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $participant->household->household_type ?? 'N/A' }}</div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $participant->age ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $participant->gender ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm">
                                    @if($participant->telephone)
                                        <div>{{ $participant->telephone }}</div>
                                    @endif
                                    @if($participant->email)
                                        <div class="text-gray-500 dark:text-gray-400">{{ $participant->email }}</div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($participant->is_head_of_household)
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">Head of Household</span>
                                @else
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">Family Member</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <button wire:click="showEditParticipant({{ $participant->id }})" 
                                            class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300">Edit</button>
                                    @if(!$participant->is_head_of_household)
                                        <button wire:click="deleteParticipant({{ $participant->id }})" 
                                                onclick="return confirm('Are you sure you want to delete this participant?')"
                                                class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">Delete</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                No participants found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($participants->hasPages())
            <div class="px-6 py-3 border-t border-gray-200 dark:border-zinc-600">
                {{ $participants->links() }}
            </div>
        @endif
    </div>
</div>