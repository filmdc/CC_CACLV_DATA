<div class="w-full px-0 py-6">
    <div class="bg-white dark:bg-zinc-800 shadow-lg rounded-lg overflow-hidden">
        {{-- Header --}}
        <div class="bg-purple-600 text-white p-6">
            <div class="flex items-center gap-3">
                <a href="{{ route('users.index') }}" wire:navigate class="text-purple-100 hover:text-white text-sm">← Users</a>
            </div>
            <h1 class="text-2xl font-bold mt-1">New User</h1>
            @if($sourceUser)
                <p class="text-purple-100 mt-1 text-sm">
                    Duplicating roles, programs, and the housing-team flag from
                    <strong>{{ $sourceUser->name }}</strong>.
                    Name, email, password, and approval delegate are NOT copied.
                </p>
            @else
                <p class="text-purple-100 mt-1 text-sm">Create a new account. To start from an existing user's settings, click <em>Duplicate</em> on a row in the user list.</p>
            @endif
        </div>

        @if (session()->has('error'))
            <div class="p-4 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-400">
                <p class="text-sm text-red-700 dark:text-red-300">{{ session('error') }}</p>
            </div>
        @endif

        <form wire:submit.prevent="create" class="p-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="name" wire:model="name" autofocus
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
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 ml-6">Uncheck to create the account in a disabled state.</p>
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

            {{-- Password method --}}
            <div class="border border-gray-200 dark:border-zinc-700 rounded-md p-4 space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Password Setup
                    </label>
                    <div class="space-y-2">
                        <label class="flex items-start gap-2">
                            <input type="radio" wire:model.live="passwordMethod" value="reset_link"
                                   class="mt-1 text-purple-600 focus:ring-purple-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                <strong>Email a password setup link</strong>
                                <span class="block text-xs text-gray-500 dark:text-gray-400">User will receive an email and set their own password. Recommended for security.</span>
                            </span>
                        </label>
                        <label class="flex items-start gap-2">
                            <input type="radio" wire:model.live="passwordMethod" value="admin_set"
                                   class="mt-1 text-purple-600 focus:ring-purple-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                <strong>Set an initial password now</strong>
                                <span class="block text-xs text-gray-500 dark:text-gray-400">You enter a password and share it out-of-band. Email will be marked as verified.</span>
                            </span>
                        </label>
                    </div>
                </div>

                @if($passwordMethod === 'admin_set')
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-3 border-t border-gray-200 dark:border-zinc-700">
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Initial Password <span class="text-red-500">*</span>
                            </label>
                            <input type="password" id="password" wire:model="password"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white">
                            @error('password') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Confirm Password <span class="text-red-500">*</span>
                            </label>
                            <input type="password" id="password_confirmation" wire:model="password_confirmation"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white">
                        </div>
                    </div>
                @endif
            </div>

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

            <div class="pt-4 border-t border-gray-200 dark:border-zinc-700 flex justify-end gap-2">
                <a href="{{ route('users.index') }}" wire:navigate
                   class="px-4 py-2 bg-gray-200 dark:bg-zinc-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-600 transition">
                    Cancel
                </a>
                <button type="submit"
                        class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition">
                    Create User
                </button>
            </div>
        </form>
    </div>
</div>
