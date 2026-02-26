<div class="w-full px-0 py-6">
    <div class="bg-white dark:bg-zinc-800 shadow-lg rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-purple-600 text-white p-6">
            <h1 class="text-2xl font-bold">User Management</h1>
            <p class="text-purple-100 mt-2">Manage users and their role assignments</p>
        </div>

        <!-- Search and Bulk Actions -->
        @if(!$showEditForm && !$showPasswordForm)
            <div class="p-4 bg-gray-50 dark:bg-zinc-700 border-b border-gray-200 dark:border-zinc-600">
                <div class="flex items-center justify-between">
                    <div class="flex-1 max-w-md">
                        <input type="text" wire:model.live="search" placeholder="Search users..." 
                               class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                    </div>
                    @if(count($selectedUsers) > 0)
                        <div class="flex space-x-2 ml-4">
                            @if($this->can('users.edit'))
                                <button wire:click="showBulkAssign" 
                                        class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition">
                                    Bulk Assign ({{ count($selectedUsers) }} selected)
                                </button>
                            @endif
                            @if($this->can('users.delete'))
                                <button wire:click="showBulkDelete" 
                                        class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition">
                                    Bulk Delete ({{ count($selectedUsers) }} selected)
                                </button>
                            @endif
                        </div>
                    @endif
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

        <!-- Edit Form -->
        @if($showEditForm && $selectedUser)
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Edit User: {{ $selectedUser->name }}</h3>
                    <button wire:click="resetForm" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form wire:submit="updateUser" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="name" wire:model="name" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md shadow-sm bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-purple-500 focus:border-purple-500">
                            @error('name') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Email <span class="text-red-500">*</span>
                            </label>
                            <input type="email" id="email" wire:model="email" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md shadow-sm bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-purple-500 focus:border-purple-500">
                            @error('email') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="flex items-center space-x-2">
                            <input type="checkbox" wire:model="active"
                                   class="rounded border-gray-300 dark:border-zinc-600 text-purple-600 focus:ring-purple-500">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Active User
                            </span>
                        </label>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Inactive users cannot log in to the system</p>
                    </div>

                    @if($eligibleDelegates->isNotEmpty())
                    <div>
                        <label for="approvalDelegateId" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Approval Delegate
                        </label>
                        <select wire:model="approvalDelegateId" id="approvalDelegateId"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md shadow-sm bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-purple-500 focus:border-purple-500">
                            <option value="">None</option>
                            @foreach($eligibleDelegates as $delegate)
                                <option value="{{ $delegate->id }}">{{ $delegate->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">This person will also receive approval notifications sent to this user</p>
                        @error('approvalDelegateId') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Assigned Roles
                            </label>
                            <div class="space-y-2 max-h-48 overflow-y-auto border border-gray-300 dark:border-zinc-600 rounded-md p-3 bg-white dark:bg-zinc-800">
                                @foreach($roles as $role)
                                    <label class="flex items-center space-x-2">
                                        <input type="checkbox" wire:model="selectedRoles" value="{{ $role->id }}"
                                               class="rounded border-gray-300 dark:border-zinc-600 text-purple-600 focus:ring-purple-500">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">
                                            {{ $role->display_name }}
                                            @if($role->description)
                                                <span class="text-xs text-gray-500 dark:text-gray-400">- {{ $role->description }}</span>
                                            @endif
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            @error('selectedRoles') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Assigned Programs
                            </label>
                            <div class="space-y-2 max-h-48 overflow-y-auto border border-gray-300 dark:border-zinc-600 rounded-md p-3 bg-white dark:bg-zinc-800">
                                @foreach($programs as $program)
                                    <label class="flex items-center space-x-2">
                                        <input type="checkbox" wire:model="selectedPrograms" value="{{ $program->id }}"
                                               class="rounded border-gray-300 dark:border-zinc-600 text-purple-600 focus:ring-purple-500">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">
                                            {{ $program->name }}
                                            @if($program->description)
                                                <span class="text-xs text-gray-500 dark:text-gray-400">- {{ Str::limit($program->description, 50) }}</span>
                                            @endif
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            @error('selectedPrograms') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="flex justify-end space-x-2">
                        <button type="button" wire:click="resetForm" 
                                class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition">
                            Update User
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <!-- Password Form -->
        @if($showPasswordForm && $selectedUser)
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Set Password for: {{ $selectedUser->name }}</h3>
                    <button wire:click="resetPasswordForm" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form wire:submit.prevent="setUserPassword" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                New Password <span class="text-red-500">*</span>
                            </label>
                            <input type="password" id="password" wire:model="password" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md shadow-sm bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            @error('password') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Confirm Password <span class="text-red-500">*</span>
                            </label>
                            <input type="password" id="password_confirmation" wire:model="password_confirmation" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md shadow-sm bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            @error('password_confirmation') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-md p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                                    Password Requirements
                                </h3>
                                <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                                    <ul class="list-disc list-inside space-y-1">
                                        <li>Minimum 8 characters</li>
                                        <li>Password will be set immediately</li>
                                        <li>User's email will be marked as verified</li>
                                        <li>Consider sending a password reset link instead for security</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-2">
                        <button type="button" wire:click="resetPasswordForm" 
                                class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                            Set Password
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <!-- View User Modal -->
        @if($showViewModal && $selectedUser)
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50" wire:click.self="closeViewModal">
                <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                    <!-- Modal Header -->
                    <div class="bg-purple-600 text-white p-6 rounded-t-lg">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xl font-bold">User Details</h3>
                            <button wire:click="closeViewModal" class="text-purple-200 hover:text-white transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Modal Content -->
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Basic Information -->
                            <div class="space-y-4">
                                <h4 class="text-lg font-semibold text-gray-900 dark:text-white border-b border-gray-200 dark:border-zinc-600 pb-2">Basic Information</h4>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                                    <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedUser->name }}</p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                                    <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedUser->email }}</p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                                    <p class="mt-1">
                                        @if($selectedUser->active)
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">
                                                Active
                                            </span>
                                        @else
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200">
                                                Inactive
                                            </span>
                                        @endif
                                    </p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email Verified</label>
                                    <p class="mt-1 text-sm text-gray-900 dark:text-white">
                                        {{ $selectedUser->email_verified_at ? $selectedUser->email_verified_at->format('M d, Y g:i A') : 'Not verified' }}
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Roles & Permissions -->
                            <div class="space-y-4">
                                <h4 class="text-lg font-semibold text-gray-900 dark:text-white border-b border-gray-200 dark:border-zinc-600 pb-2">Roles & Access</h4>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Assigned Roles</label>
                                    <div class="space-y-2">
                                        @forelse($selectedUser->roles as $role)
                                            <div class="flex items-center justify-between p-2 bg-purple-50 dark:bg-purple-900/20 rounded-md">
                                                <div>
                                                    <span class="text-sm font-medium text-purple-800 dark:text-purple-200">{{ $role->display_name }}</span>
                                                    @if($role->description)
                                                        <p class="text-xs text-purple-600 dark:text-purple-300">{{ $role->description }}</p>
                                                    @endif
                                                </div>
                                            </div>
                                        @empty
                                            <p class="text-sm text-gray-500 dark:text-gray-400">No roles assigned</p>
                                        @endforelse
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Assigned Programs</label>
                                    <div class="space-y-2">
                                        @forelse($selectedUser->programs as $program)
                                            <div class="flex items-center justify-between p-2 bg-blue-50 dark:bg-blue-900/20 rounded-md">
                                                <div>
                                                    <span class="text-sm font-medium text-blue-800 dark:text-blue-200">{{ $program->name }}</span>
                                                    @if($program->description)
                                                        <p class="text-xs text-blue-600 dark:text-blue-300">{{ Str::limit($program->description, 50) }}</p>
                                                    @endif
                                                </div>
                                            </div>
                                        @empty
                                            <p class="text-sm text-gray-500 dark:text-gray-400">No programs assigned</p>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Account Information -->
                        <div class="mt-6 pt-6 border-t border-gray-200 dark:border-zinc-600">
                            <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Account Information</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Created</label>
                                    <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedUser->created_at->format('M d, Y g:i A') }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Last Updated</label>
                                    <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedUser->updated_at->format('M d, Y g:i A') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Modal Footer -->
                    <div class="bg-gray-50 dark:bg-zinc-700 px-6 py-4 rounded-b-lg">
                        <div class="flex justify-end space-x-2">
                            <button wire:click="closeViewModal" 
                                    class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-300 dark:hover:bg-gray-500 transition">
                                Close
                            </button>
                            @if($this->can('users.edit'))
                                <button wire:click="showEditUser({{ $selectedUser->id }})" 
                                        class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition">
                                    Edit User
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Users Table -->
        @if(!$showEditForm && !$showPasswordForm)
            <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                <thead class="bg-gray-50 dark:bg-zinc-700">
                    <tr>
                        @if($this->can('users.edit'))
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            <input type="checkbox" wire:model="selectedUsers" 
                                   class="rounded border-gray-300 dark:border-zinc-600 text-purple-600 focus:ring-purple-500">
                        </th>
                        @endif
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Roles</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Programs</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-700">
                    @forelse($users as $user)
                        <tr class="{{ !$user->active ? 'bg-gray-50 dark:bg-zinc-700/50' : '' }}">
                            @if($this->can('users.edit'))
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input type="checkbox" wire:model="selectedUsers" value="{{ $user->id }}"
                                       class="rounded border-gray-300 dark:border-zinc-600 text-purple-600 focus:ring-purple-500">
                            </td>
                            @endif
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium">
                                    <button wire:click="viewUser({{ $user->id }})" 
                                            class="text-gray-900 dark:text-white hover:text-purple-600 dark:hover:text-purple-400 hover:underline cursor-pointer transition-colors duration-200">
                                        {{ $user->name }}
                                    </button>
                                    @if($user->id === auth()->id())
                                        <span class="ml-2 inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">You</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $user->email }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($user->active)
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200">
                                        Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1">
                                    @forelse($user->roles as $role)
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200">
                                            {{ $role->display_name }}
                                        </span>
                                    @empty
                                        <span class="text-sm text-gray-500 dark:text-gray-400">No roles</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1">
                                    @forelse($user->programs as $program)
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">
                                            {{ $program->name }}
                                        </span>
                                    @empty
                                        <span class="text-sm text-gray-500 dark:text-gray-400">No programs</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex flex-wrap gap-2">
                                    @if($this->can('users.edit'))
                                        <button wire:click="showEditUser({{ $user->id }})" 
                                                class="text-purple-600 dark:text-purple-400 hover:text-purple-900 dark:hover:text-purple-300">Edit</button>
                                        
                                        <button wire:click="showPasswordModal({{ $user->id }})" 
                                                class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300">Set Password</button>
                                        
                                        <button wire:click="sendPasswordReset({{ $user->id }})" 
                                                class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">Reset Password</button>
                                        
                                        @if($user->id !== auth()->id())
                                            <button wire:click="toggleUserStatus({{ $user->id }})" 
                                                    class="{{ $user->active ? 'text-orange-600 dark:text-orange-400 hover:text-orange-900 dark:hover:text-orange-300' : 'text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300' }}">
                                                {{ $user->active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        @endif
                                    @endif
                                    
                                    @if($this->can('users.delete') && $user->id !== auth()->id())
                                        <button wire:click="deleteUser({{ $user->id }})" 
                                                onclick="return confirm('Are you sure you want to delete this user?')"
                                                class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">Delete</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $this->can('users.edit') ? '7' : '6' }}" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                No users found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>

            <!-- Pagination -->
            @if($users->hasPages())
                <div class="px-6 py-4 bg-gray-50 dark:bg-zinc-700 border-t border-gray-200 dark:border-zinc-600">
                    {{ $users->links() }}
                </div>
            @endif
        @endif
    </div>

    <!-- Bulk Assignment Modal -->
    @if($showBulkAssignModal)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50" wire:click.self="resetBulkAssignment">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                <!-- Modal Header -->
                <div class="bg-purple-600 text-white p-6 rounded-t-lg">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-bold">Bulk Role & Program Assignment</h3>
                        <button wire:click="resetBulkAssignment" class="text-purple-200 hover:text-white transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <!-- Modal Content -->
                <div class="p-6">
                    <div class="mb-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Assigning to <strong>{{ count($selectedUsers) }} users</strong>
                        </p>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Assignment Action
                            </label>
                            <div class="space-x-4">
                                <label class="inline-flex items-center">
                                    <input type="radio" wire:model="bulkAction" value="add" 
                                           class="text-purple-600 focus:ring-purple-500">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Add to existing</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="radio" wire:model="bulkAction" value="replace" 
                                           class="text-purple-600 focus:ring-purple-500">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Replace existing</span>
                                </label>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Roles to Assign
                                </label>
                                <div class="space-y-2 max-h-48 overflow-y-auto border border-gray-300 dark:border-zinc-600 rounded-md p-3 bg-white dark:bg-zinc-800">
                                    @foreach($roles as $role)
                                        <label class="flex items-center space-x-2">
                                            <input type="checkbox" wire:model="bulkRoles" value="{{ $role->id }}"
                                                   class="rounded border-gray-300 dark:border-zinc-600 text-purple-600 focus:ring-purple-500">
                                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                                {{ $role->display_name }}
                                                @if($role->description)
                                                    <span class="text-xs text-gray-500 dark:text-gray-400">- {{ $role->description }}</span>
                                                @endif
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Programs to Assign
                                </label>
                                <div class="space-y-2 max-h-48 overflow-y-auto border border-gray-300 dark:border-zinc-600 rounded-md p-3 bg-white dark:bg-zinc-800">
                                    @foreach($programs as $program)
                                        <label class="flex items-center space-x-2">
                                            <input type="checkbox" wire:model="bulkPrograms" value="{{ $program->id }}"
                                                   class="rounded border-gray-300 dark:border-zinc-600 text-purple-600 focus:ring-purple-500">
                                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                                {{ $program->name }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Modal Footer -->
                <div class="bg-gray-50 dark:bg-zinc-700 px-6 py-4 rounded-b-lg">
                    <div class="flex justify-end space-x-2">
                        <button wire:click="resetBulkAssignment" 
                                class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-300 dark:hover:bg-gray-500 transition">
                            Cancel
                        </button>
                        <button wire:click="processBulkAssignment" 
                                class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition">
                            Apply Changes
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Bulk Delete Confirmation Modal -->
    @if($showBulkDeleteModal)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50" wire:click.self="resetBulkDelete">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-md w-full mx-4">
                <!-- Modal Header -->
                <div class="bg-red-600 text-white p-6 rounded-t-lg">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-bold">Confirm Bulk Delete</h3>
                        <button wire:click="resetBulkDelete" class="text-red-200 hover:text-white transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <!-- Modal Content -->
                <div class="p-6">
                    <div class="flex items-center mb-4">
                        <div class="flex-shrink-0">
                            <svg class="h-12 w-12 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.996-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                Delete Selected Users
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Are you sure you want to delete <strong>{{ count($selectedUsers) }} users</strong>? This action cannot be undone.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-md p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800 dark:text-red-200">
                                    Warning
                                </h3>
                                <div class="mt-2 text-sm text-red-700 dark:text-red-300">
                                    <ul class="list-disc list-inside space-y-1">
                                        <li>All user data will be permanently deleted</li>
                                        <li>This action cannot be undone</li>
                                        <li>User sessions will be terminated</li>
                                        <li>Your own account is protected and cannot be deleted</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Modal Footer -->
                <div class="bg-gray-50 dark:bg-zinc-700 px-6 py-4 rounded-b-lg">
                    <div class="flex justify-end space-x-2">
                        <button wire:click="resetBulkDelete" 
                                class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-300 dark:hover:bg-gray-500 transition">
                            Cancel
                        </button>
                        <button wire:click="processBulkDelete" 
                                class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition">
                            Delete {{ count($selectedUsers) }} Users
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>