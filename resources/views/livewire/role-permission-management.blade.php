<div class="max-w-7xl mx-auto p-6">
    <div class="bg-white dark:bg-zinc-800 shadow-lg rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-purple-600 text-white p-6">
            <h1 class="text-2xl font-bold">Role & Permission Management</h1>
            <p class="text-purple-100 mt-2">Manage user roles and system permissions</p>
        </div>

        <!-- Tab Navigation -->
        <div class="p-4 bg-gray-50 dark:bg-zinc-700 border-b border-gray-200 dark:border-zinc-600">
            <div class="flex border-b border-gray-200 dark:border-zinc-600">
                <button 
                    wire:click="setActiveTab('roles')"
                    class="px-4 py-2 font-medium text-sm {{ $activeTab === 'roles' ? 'text-purple-600 dark:text-purple-400 border-b-2 border-purple-600 dark:border-purple-400' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300' }}"
                >
                    Roles
                </button>
                <button 
                    wire:click="setActiveTab('permissions')"
                    class="px-4 py-2 font-medium text-sm {{ $activeTab === 'permissions' ? 'text-purple-600 dark:text-purple-400 border-b-2 border-purple-600 dark:border-purple-400' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300' }}"
                >
                    Permissions
                </button>
            </div>
        </div>

        <!-- Search and Create Button -->
        <div class="p-4 bg-gray-50 dark:bg-zinc-700 border-b border-gray-200 dark:border-zinc-600">
            <div class="flex items-center justify-between">
                <div class="flex-1 max-w-md">
                    <input wire:model.live="search" placeholder="Search {{ $activeTab }}..." class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500" />
                </div>
                @if($activeTab === 'roles')
                    <button wire:click="showCreateRole" class="ml-4 px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500">Create Role</button>
                @else
                    <button wire:click="showCreatePermission" class="ml-4 px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500">Create Permission</button>
                @endif
            </div>
        </div>

        <!-- Flash Messages -->
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

        <!-- Role Create/Edit Form -->
        @if($activeTab === 'roles' && ($showCreateRoleForm || $showEditRoleForm))
            <div class="p-6 bg-purple-50 dark:bg-purple-900/20 border-b border-gray-200 dark:border-zinc-600">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">{{ $showCreateRoleForm ? 'Create New Role' : 'Edit Role' }}</h3>
                <form wire:submit="{{ $showCreateRoleForm ? 'createRole' : 'updateRole' }}" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Role Name *</label>
                            <input wire:model="roleName" placeholder="e.g., admin, manager" class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500" />
                            @error('roleName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Display Name *</label>
                            <input wire:model="roleDisplayName" placeholder="e.g., Administrator, Program Manager" class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500" />
                            @error('roleDisplayName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                        <textarea wire:model="roleDescription" rows="2" placeholder="Role description..." class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500"></textarea>
                        @error('roleDescription') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Permissions</label>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 max-h-48 overflow-y-auto border border-gray-300 dark:border-zinc-600 rounded p-3 bg-white dark:bg-zinc-800">
                            @foreach($permissions->groupBy('category') as $category => $categoryPermissions)
                                <div class="col-span-full">
                                    <h4 class="font-medium text-gray-900 dark:text-white mt-2">{{ $category ?: 'General' }}</h4>
                                </div>
                                @foreach($categoryPermissions as $permission)
                                    <div class="flex items-center">
                                        <input 
                                            type="checkbox" 
                                            wire:model="selectedPermissions" 
                                            value="{{ $permission->id }}"
                                            id="permission_{{ $permission->id }}"
                                            class="mr-2"
                                        >
                                        <label for="permission_{{ $permission->id }}" class="text-sm text-gray-700 dark:text-gray-300">
                                            {{ $permission->display_name }}
                                        </label>
                                    </div>
                                @endforeach
                            @endforeach
                        </div>
                        @error('selectedPermissions') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end gap-2 mt-6">
                        <button type="button" wire:click="$set('showCreateRoleForm', false); $set('showEditRoleForm', false)" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-zinc-700 border border-gray-300 dark:border-zinc-600 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-600 focus:outline-none focus:ring-2 focus:ring-gray-500">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-purple-600 border border-transparent rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500">
                            {{ $showCreateRoleForm ? 'Create Role' : 'Update Role' }}
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <!-- Permission Create/Edit Form -->
        @if($activeTab === 'permissions' && ($showCreatePermissionForm || $showEditPermissionForm))
            <div class="p-6 bg-purple-50 dark:bg-purple-900/20 border-b border-gray-200 dark:border-zinc-600">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">{{ $showCreatePermissionForm ? 'Create New Permission' : 'Edit Permission' }}</h3>
                <form wire:submit="{{ $showCreatePermissionForm ? 'createPermission' : 'updatePermission' }}" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Permission Name *</label>
                            <input wire:model="permissionName" placeholder="e.g., edit-participants" class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500" />
                            @error('permissionName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Display Name *</label>
                            <input wire:model="permissionDisplayName" placeholder="e.g., Edit Participants" class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500" />
                            @error('permissionDisplayName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category</label>
                            <input wire:model="permissionCategory" placeholder="e.g., Participants, Programs" class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500" />
                            @error('permissionCategory') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                        <textarea wire:model="permissionDescription" rows="2" placeholder="Permission description..." class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500"></textarea>
                        @error('permissionDescription') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end gap-2 mt-6">
                        <button type="button" wire:click="$set('showCreatePermissionForm', false); $set('showEditPermissionForm', false)" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-zinc-700 border border-gray-300 dark:border-zinc-600 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-600 focus:outline-none focus:ring-2 focus:ring-gray-500">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-purple-600 border border-transparent rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500">
                            {{ $showCreatePermissionForm ? 'Create Permission' : 'Update Permission' }}
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <!-- Roles Table -->
        @if($activeTab === 'roles')
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-600">
                    <thead class="bg-gray-50 dark:bg-zinc-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Permissions</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Users</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-600">
                        @forelse($roles as $role)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div class="font-medium text-gray-900 dark:text-white">{{ $role->display_name }}</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $role->name }}</div>
                                        @if($role->description)
                                            <div class="text-sm text-gray-400 dark:text-gray-500">{{ Str::limit($role->description, 50) }}</div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm">
                                        {{ $role->permissions->count() }} {{ Str::plural('permission', $role->permissions->count()) }}
                                        @if($role->permissions->count() > 0)
                                            <div class="text-gray-500 dark:text-gray-400">
                                                {{ $role->permissions->take(3)->pluck('display_name')->implode(', ') }}
                                                @if($role->permissions->count() > 3)
                                                    <span class="text-gray-400 dark:text-gray-500">+{{ $role->permissions->count() - 3 }} more</span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $role->users->count() }} {{ Str::plural('user', $role->users->count()) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <div class="flex gap-2">
                                        <button wire:click="showEditRole({{ $role->id }})" class="text-purple-600 dark:text-purple-400 hover:text-purple-900 dark:hover:text-purple-300">
                                            Edit
                                        </button>
                                        <button 
                                            wire:click="deleteRole({{ $role->id }})"
                                            onclick="return confirm('Are you sure you want to delete this role?')"
                                            class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                    No roles found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        @if($roles->hasPages())
            <div class="px-6 py-3 border-t border-gray-200 dark:border-zinc-600">
                {{ $roles->links() }}
            </div>
        @endif
        @endif

        <!-- Permissions Table -->
        @if($activeTab === 'permissions')
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-600">
                    <thead class="bg-gray-50 dark:bg-zinc-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Permission</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Roles</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-600">
                        @forelse($permissions as $permission)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div class="font-medium text-gray-900 dark:text-white">{{ $permission->display_name }}</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $permission->name }}</div>
                                        @if($permission->description)
                                            <div class="text-sm text-gray-400 dark:text-gray-500">{{ Str::limit($permission->description, 50) }}</div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($permission->category)
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">{{ $permission->category }}</span>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500">General</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $permission->roles->count() }} {{ Str::plural('role', $permission->roles->count()) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <div class="flex gap-2">
                                        <button wire:click="showEditPermission({{ $permission->id }})" class="text-purple-600 dark:text-purple-400 hover:text-purple-900 dark:hover:text-purple-300">
                                            Edit
                                        </button>
                                        <button 
                                            wire:click="deletePermission({{ $permission->id }})"
                                            onclick="return confirm('Are you sure you want to delete this permission?')"
                                            class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                    No permissions found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        @if($permissions->hasPages())
            <div class="px-6 py-3 border-t border-gray-200 dark:border-zinc-600">
                {{ $permissions->links() }}
            </div>
        @endif
        @endif
    </div>
</div>