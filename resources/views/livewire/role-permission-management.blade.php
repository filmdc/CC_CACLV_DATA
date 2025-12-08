<div class="w-full px-0 py-6">
    <div class="bg-white dark:bg-zinc-800 shadow-lg rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-purple-600 text-white p-6">
            <h1 class="text-2xl font-bold">Role & Permission Management</h1>
            <p class="text-purple-100 mt-2">Manage user roles and system permissions</p>
        </div>

        <!-- Tab Navigation -->
        @if(!$showCreateRoleForm && !$showEditRoleForm && !$showCreatePermissionForm && !$showEditPermissionForm && !$showViewRoleModal && !$showViewPermissionModal)
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
        @endif

        <!-- Search and Create Button -->
        @if(!$showCreateRoleForm && !$showEditRoleForm && !$showCreatePermissionForm && !$showEditPermissionForm && !$showViewRoleModal && !$showViewPermissionModal)
        <div class="p-4 bg-gray-50 dark:bg-zinc-700 border-b border-gray-200 dark:border-zinc-600">
            <div class="flex items-center justify-between">
                <div class="flex-1 max-w-md">
                    <input wire:model.live="search" placeholder="Search {{ $activeTab }}..." class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500" />
                </div>
                @if($activeTab === 'roles')
                    @if($this->can('roles.create'))
                        <button wire:click="showCreateRole" class="ml-4 px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500">Create Role</button>
                    @endif
                @else
                    @if($this->can('permissions.create'))
                        <button wire:click="showCreatePermission" class="ml-4 px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500">Create Permission</button>
                    @endif
                @endif
            </div>
        </div>
        @endif

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
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ $showCreateRoleForm ? 'Create New Role' : 'Edit Role' }}</h3>
                    <button wire:click="closeRoleForms" class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
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
                        <button type="button" wire:click="closeRoleForms" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-zinc-700 border border-gray-300 dark:border-zinc-600 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-600 focus:outline-none focus:ring-2 focus:ring-gray-500">
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
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ $showCreatePermissionForm ? 'Create New Permission' : 'Edit Permission' }}</h3>
                    <button wire:click="closePermissionForms" class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
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
                        <button type="button" wire:click="closePermissionForms" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-zinc-700 border border-gray-300 dark:border-zinc-600 rounded-md hover:bg-gray-300 dark:hover:bg-zinc-600 focus:outline-none focus:ring-2 focus:ring-gray-500">
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
        @if($activeTab === 'roles' && !$showCreateRoleForm && !$showEditRoleForm)
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
                                        <div class="font-medium text-gray-900 dark:text-white">
                                            <button wire:click="viewRole({{ $role->id }})" class="text-purple-600 dark:text-purple-400 hover:text-purple-800 dark:hover:text-purple-300 hover:underline">
                                                {{ $role->display_name }}
                                            </button>
                                        </div>
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
                                        <button wire:click="viewRole({{ $role->id }})" class="text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300">
                                            View
                                        </button>
                                        @if($this->can('roles.edit'))
                                            <button wire:click="showEditRole({{ $role->id }})" class="text-purple-600 dark:text-purple-400 hover:text-purple-900 dark:hover:text-purple-300">
                                                Edit
                                            </button>
                                        @endif
                                        @if($this->can('roles.delete'))
                                            <button 
                                                wire:click="deleteRole({{ $role->id }})"
                                                onclick="return confirm('Are you sure you want to delete this role?')"
                                                class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"
                                            >
                                                Delete
                                            </button>
                                        @endif
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
        @if($activeTab === 'permissions' && !$showCreatePermissionForm && !$showEditPermissionForm)
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
                                        <div class="font-medium text-gray-900 dark:text-white">
                                            <button wire:click="viewPermission({{ $permission->id }})" class="text-purple-600 dark:text-purple-400 hover:text-purple-800 dark:hover:text-purple-300 hover:underline">
                                                {{ $permission->display_name }}
                                            </button>
                                        </div>
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
                                        <button wire:click="viewPermission({{ $permission->id }})" class="text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300">
                                            View
                                        </button>
                                        @if($this->can('permissions.edit'))
                                            <button wire:click="showEditPermission({{ $permission->id }})" class="text-purple-600 dark:text-purple-400 hover:text-purple-900 dark:hover:text-purple-300">
                                                Edit
                                            </button>
                                        @endif
                                        @if($this->can('permissions.delete'))
                                            <button 
                                                wire:click="deletePermission({{ $permission->id }})"
                                                onclick="return confirm('Are you sure you want to delete this permission?')"
                                                class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"
                                            >
                                                Delete
                                            </button>
                                        @endif
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

        <!-- View Role Modal -->
        @if($showViewRoleModal && $viewRole)
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50" wire:click.self="$set('showViewRoleModal', false)">
                <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-4xl w-full m-4 max-h-[90vh] overflow-y-auto">
                    <!-- Modal Header -->
                    <div class="bg-purple-600 text-white p-6 rounded-t-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-xl font-semibold">{{ $viewRole->display_name }}</h3>
                                <p class="text-purple-100 mt-1">Role Details</p>
                            </div>
                            <button wire:click="$set('showViewRoleModal', false)" class="text-white hover:text-gray-300">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Modal Content -->
                    <div class="p-6">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Role Information -->
                            <div class="space-y-6">
                                <div>
                                    <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-3">Role Information</h4>
                                    <div class="bg-gray-50 dark:bg-zinc-700 p-4 rounded-lg space-y-3">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Display Name</label>
                                            <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $viewRole->display_name }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Role Name</label>
                                            <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $viewRole->name }}</p>
                                        </div>
                                        @if($viewRole->description)
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                                                <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $viewRole->description }}</p>
                                            </div>
                                        @endif
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Created</label>
                                            <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $viewRole->created_at->format('M j, Y g:i A') }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Last Updated</label>
                                            <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $viewRole->updated_at->format('M j, Y g:i A') }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Users with this Role -->
                                <div>
                                    <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-3">Users ({{ $viewRole->users->count() }})</h4>
                                    <div class="bg-gray-50 dark:bg-zinc-700 p-4 rounded-lg">
                                        @if($viewRole->users->count() > 0)
                                            <div class="space-y-2 max-h-40 overflow-y-auto">
                                                @foreach($viewRole->users as $user)
                                                    <div class="flex items-center justify-between border-b border-gray-200 dark:border-zinc-600 pb-2 last:border-b-0">
                                                        <div>
                                                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $user->name }}</p>
                                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                                                        </div>
                                                        <span class="px-2 py-1 text-xs rounded-full {{ $user->active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                                                            {{ $user->active ? 'Active' : 'Inactive' }}
                                                        </span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="text-center py-4">
                                                <p class="text-gray-500 dark:text-gray-400">No users assigned to this role.</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Permissions -->
                            <div class="space-y-6">
                                <div>
                                    <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-3">Permissions ({{ $viewRole->permissions->count() }})</h4>
                                    <div class="bg-gray-50 dark:bg-zinc-700 p-4 rounded-lg">
                                        @if($viewRole->permissions->count() > 0)
                                            <div class="space-y-4 max-h-96 overflow-y-auto">
                                                @foreach($viewRole->permissions->groupBy('category') as $category => $permissions)
                                                    <div>
                                                        <h5 class="font-medium text-gray-900 dark:text-white mb-2">{{ $category ?: 'General' }}</h5>
                                                        <div class="space-y-2">
                                                            @foreach($permissions as $permission)
                                                                <div class="flex items-center justify-between border-b border-gray-200 dark:border-zinc-600 pb-2 last:border-b-0">
                                                                    <div>
                                                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $permission->display_name }}</p>
                                                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $permission->name }}</p>
                                                                        @if($permission->description)
                                                                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ $permission->description }}</p>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="text-center py-4">
                                                <p class="text-gray-500 dark:text-gray-400">No permissions assigned to this role.</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="flex justify-end mt-6">
                            <button wire:click="$set('showViewRoleModal', false)" 
                                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-zinc-600 border border-gray-300 dark:border-zinc-500 rounded-md hover:bg-gray-50 dark:hover:bg-zinc-500 focus:outline-none focus:ring-2 focus:ring-purple-500">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- View Permission Modal -->
        @if($showViewPermissionModal && $viewPermission)
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50" wire:click.self="$set('showViewPermissionModal', false)">
                <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-3xl w-full m-4 max-h-[90vh] overflow-y-auto">
                    <!-- Modal Header -->
                    <div class="bg-purple-600 text-white p-6 rounded-t-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-xl font-semibold">{{ $viewPermission->display_name }}</h3>
                                <p class="text-purple-100 mt-1">Permission Details</p>
                            </div>
                            <div class="flex items-center space-x-2">
                                @if($viewPermission->category)
                                    <span class="px-3 py-1 text-sm rounded-full bg-blue-100 text-blue-800">{{ $viewPermission->category }}</span>
                                @endif
                                <button wire:click="$set('showViewPermissionModal', false)" class="text-white hover:text-gray-300">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Content -->
                    <div class="p-6">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Permission Information -->
                            <div class="space-y-6">
                                <div>
                                    <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-3">Permission Information</h4>
                                    <div class="bg-gray-50 dark:bg-zinc-700 p-4 rounded-lg space-y-3">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Display Name</label>
                                            <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $viewPermission->display_name }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Permission Name</label>
                                            <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $viewPermission->name }}</p>
                                        </div>
                                        @if($viewPermission->description)
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                                                <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $viewPermission->description }}</p>
                                            </div>
                                        @endif
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Category</label>
                                            <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $viewPermission->category ?: 'General' }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Created</label>
                                            <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $viewPermission->created_at->format('M j, Y g:i A') }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Last Updated</label>
                                            <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $viewPermission->updated_at->format('M j, Y g:i A') }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Roles with this Permission -->
                            <div class="space-y-6">
                                <div>
                                    <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-3">Assigned to Roles ({{ $viewPermission->roles->count() }})</h4>
                                    <div class="bg-gray-50 dark:bg-zinc-700 p-4 rounded-lg">
                                        @if($viewPermission->roles->count() > 0)
                                            <div class="space-y-3 max-h-60 overflow-y-auto">
                                                @foreach($viewPermission->roles as $role)
                                                    <div class="border-b border-gray-200 dark:border-zinc-600 pb-3 last:border-b-0">
                                                        <div class="flex items-center justify-between">
                                                            <div>
                                                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $role->display_name }}</p>
                                                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $role->name }}</p>
                                                                @if($role->description)
                                                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ Str::limit($role->description, 60) }}</p>
                                                                @endif
                                                            </div>
                                                            <div class="text-right">
                                                                <div class="text-sm text-gray-600 dark:text-gray-400">{{ $role->users->count() }} {{ Str::plural('user', $role->users->count()) }}</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="text-center py-4">
                                                <p class="text-gray-500 dark:text-gray-400">This permission is not assigned to any roles.</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="flex justify-end mt-6">
                            <button wire:click="$set('showViewPermissionModal', false)" 
                                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-zinc-600 border border-gray-300 dark:border-zinc-500 rounded-md hover:bg-gray-50 dark:hover:bg-zinc-500 focus:outline-none focus:ring-2 focus:ring-purple-500">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>