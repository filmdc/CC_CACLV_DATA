<div class="w-full px-0 py-6">
    <div class="bg-white dark:bg-zinc-800 shadow-lg rounded-lg overflow-hidden">
        {{-- Header --}}
        <div class="bg-purple-600 text-white p-6 flex items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('users.index') }}" wire:navigate class="text-purple-100 hover:text-white text-sm">← Users</a>
                </div>
                <h1 class="text-2xl font-bold mt-1">Edit User</h1>
                <p class="text-purple-100 mt-1 text-sm">{{ $user->name }} &middot; {{ $user->email }}</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-2 shrink-0">
                <a href="{{ route('users.create', ['duplicate_from' => $user->id]) }}" wire:navigate
                   class="inline-flex items-center px-4 py-2 bg-white text-purple-700 rounded-md hover:bg-purple-50 transition font-medium">
                    Duplicate
                </a>
            </div>
        </div>

        @if (session()->has('success'))
            <div class="p-4 bg-green-50 dark:bg-green-900/20 border-l-4 border-green-400">
                <p class="text-sm text-green-700 dark:text-green-300">{{ session('success') }}</p>
            </div>
        @endif
        @if (session()->has('error'))
            <div class="p-4 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-400">
                <p class="text-sm text-red-700 dark:text-red-300">{{ session('error') }}</p>
            </div>
        @endif

        <form wire:submit.prevent="save" class="p-6 space-y-6">
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
                <label class="flex items-center gap-2">
                    <input type="checkbox" wire:model="active"
                           class="rounded border-gray-300 dark:border-zinc-600 text-purple-600 focus:ring-purple-500">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Active</span>
                </label>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 ml-6">Inactive users cannot log in.</p>
            </div>

            <div>
                <label class="flex items-center gap-2">
                    <input type="checkbox" wire:model="isHousingTeam" data-test="user-is-housing-team"
                           class="rounded border-gray-300 dark:border-zinc-600 text-purple-600 focus:ring-purple-500">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Community Action Homes team member</span>
                </label>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 ml-6">
                    When checked, this user appears in the owner / assigned-to picker on the Project Detail page.
                </p>
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
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Will also receive approval notifications sent to this user.</p>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Roles</label>
                    <div class="space-y-2 max-h-64 overflow-y-auto border border-gray-300 dark:border-zinc-600 rounded-md p-3 bg-white dark:bg-zinc-800">
                        @foreach($roles as $role)
                            <label class="flex items-start gap-2">
                                <input type="checkbox" wire:model="selectedRoles" value="{{ $role->id }}"
                                       class="mt-0.5 rounded border-gray-300 dark:border-zinc-600 text-purple-600 focus:ring-purple-500">
                                <span class="text-sm text-gray-700 dark:text-gray-300">
                                    {{ $role->display_name }}
                                    @if($role->description)
                                        <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $role->description }}</span>
                                    @endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Programs</label>
                    <div class="space-y-2 max-h-64 overflow-y-auto border border-gray-300 dark:border-zinc-600 rounded-md p-3 bg-white dark:bg-zinc-800">
                        @foreach($programs as $program)
                            <label class="flex items-start gap-2">
                                <input type="checkbox" wire:model="selectedPrograms" value="{{ $program->id }}"
                                       class="mt-0.5 rounded border-gray-300 dark:border-zinc-600 text-purple-600 focus:ring-purple-500">
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ $program->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="pt-4 border-t border-gray-200 dark:border-zinc-700 flex flex-wrap items-center gap-3 justify-between">
                <div class="flex gap-2">
                    <button type="button" wire:click="sendResetLink"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition text-sm">
                        Send Password Reset Link
                    </button>
                    <button type="button" wire:click="openPasswordPanel"
                            class="px-4 py-2 bg-gray-200 dark:bg-zinc-700 text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-600 transition text-sm">
                        Set Password Manually
                    </button>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('users.index') }}" wire:navigate
                       class="px-4 py-2 bg-gray-200 dark:bg-zinc-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-600 transition">
                        Cancel
                    </a>
                    <button type="submit"
                            class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition">
                        Save Changes
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Set password modal --}}
    @if($showPasswordPanel)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50" wire:click.self="closePasswordPanel">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-md w-full mx-4">
                <div class="bg-purple-600 text-white p-4 rounded-t-lg flex justify-between items-center">
                    <h3 class="font-bold">Set Password for {{ $user->name }}</h3>
                    <button wire:click="closePasswordPanel" class="text-purple-200 hover:text-white">✕</button>
                </div>
                <form wire:submit.prevent="setPassword" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">New Password</label>
                        <input type="password" wire:model="password"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white">
                        @error('password') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Confirm Password</label>
                        <input type="password" wire:model="password_confirmation"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white">
                    </div>
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-md p-3 text-xs text-yellow-800 dark:text-yellow-200">
                        Minimum 8 characters. Email will be marked as verified. Consider sending a reset link instead.
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" wire:click="closePasswordPanel"
                                class="px-4 py-2 bg-gray-200 dark:bg-zinc-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-300">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700">Set Password</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
