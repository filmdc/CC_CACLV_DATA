<div class="w-full px-0 py-6">
    <div class="bg-white dark:bg-zinc-800 shadow-lg rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-emerald-600 text-white p-6">
            <h1 class="text-2xl font-bold">Documentation Categories</h1>
            <p class="text-emerald-100 mt-2">Manage documentation categories to organize help articles</p>
        </div>

        <!-- Success/Error Messages -->
        @if (session()->has('success'))
            <div class="p-4 bg-green-50 dark:bg-green-900/20 border-l-4 border-green-400">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-green-700 dark:text-green-300">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="p-4 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-400">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700 dark:text-red-300">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Search and Actions -->
        @if(!$showEditForm && !$showCreateForm)
        <div class="p-4 bg-gray-50 dark:bg-zinc-700 border-b dark:border-zinc-600">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex-1 max-w-md">
                    <input type="text" wire:model.live="search" placeholder="Search categories..."
                           class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.documentation.articles') }}" wire:navigate
                       class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500">
                        Manage Articles
                    </a>
                    <button wire:click="showCreateCategory"
                            class="px-4 py-2 bg-emerald-600 text-white rounded-md hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        Create Category
                    </button>
                </div>
            </div>
        </div>
        @endif

        <!-- Create Category Form -->
        @if ($showCreateForm)
            <div class="p-6 border-b dark:border-zinc-700">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Create New Category</h3>
                    <button wire:click="$set('showCreateForm', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form wire:submit="createCategory" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" wire:model="name"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500"
                                   placeholder="e.g., Getting Started">
                            @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Icon (Heroicon name)
                            </label>
                            <input type="text" wire:model="icon"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500"
                                   placeholder="e.g., book-open">
                            @error('icon') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sort Order</label>
                            <input type="number" wire:model="sort_order" min="0"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        </div>

                        <div class="flex items-center pt-6">
                            <input type="checkbox" wire:model="active" id="active" class="h-4 w-4 text-emerald-600 focus:ring-emerald-500 border-gray-300 rounded">
                            <label for="active" class="ml-2 block text-sm text-gray-900 dark:text-white">Active</label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                        <textarea wire:model="description" rows="2"
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500"
                                  placeholder="Brief description of this category..."></textarea>
                        @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button type="button" wire:click="$set('showCreateForm', false)"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 border border-gray-300 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 border border-transparent rounded-md hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            Create Category
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <!-- Edit Category Form -->
        @if ($showEditForm && $selectedCategory)
            <div class="p-6 border-b dark:border-zinc-700">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Edit Category: {{ $selectedCategory->name }}</h3>
                    <button wire:click="$set('showEditForm', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form wire:submit="updateCategory" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" wire:model="name"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Icon (Heroicon name)
                            </label>
                            <input type="text" wire:model="icon"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            @error('icon') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sort Order</label>
                            <input type="number" wire:model="sort_order" min="0"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        </div>

                        <div class="flex items-center pt-6">
                            <input type="checkbox" wire:model="active" id="edit_active" class="h-4 w-4 text-emerald-600 focus:ring-emerald-500 border-gray-300 rounded">
                            <label for="edit_active" class="ml-2 block text-sm text-gray-900 dark:text-white">Active</label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                        <textarea wire:model="description" rows="2"
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500"></textarea>
                        @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button type="button" wire:click="$set('showEditForm', false)"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 border border-gray-300 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 border border-transparent rounded-md hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            Update Category
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <!-- Categories List -->
        @if(!$showEditForm && !$showCreateForm)
        <div class="p-6">
            <div class="bg-white dark:bg-zinc-800 shadow overflow-hidden sm:rounded-md">
                <ul class="divide-y divide-gray-200 dark:divide-zinc-700">
                    @forelse ($categories as $category)
                        <li class="px-6 py-4 hover:bg-gray-50 dark:hover:bg-zinc-700/50">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3">
                                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                            {{ $category->name }}
                                        </h3>
                                        <span class="px-2 py-1 text-xs rounded-full {{ $category->active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                            {{ $category->active ? 'Active' : 'Inactive' }}
                                        </span>
                                        <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                            {{ $category->articles_count }} {{ Str::plural('article', $category->articles_count) }}
                                        </span>
                                    </div>
                                    @if($category->description)
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ Str::limit($category->description, 100) }}</p>
                                    @endif
                                    <div class="mt-2 flex items-center text-sm text-gray-500 dark:text-gray-400 space-x-4">
                                        <span>Slug: {{ $category->slug }}</span>
                                        @if($category->icon)
                                            <span>Icon: {{ $category->icon }}</span>
                                        @endif
                                        <span>Order: {{ $category->sort_order }}</span>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <button wire:click="showEditCategory({{ $category->id }})"
                                            class="px-3 py-1 text-sm bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-200 rounded hover:bg-blue-200 dark:hover:bg-blue-800">
                                        Edit
                                    </button>
                                    <button wire:click="toggleCategoryStatus({{ $category->id }})"
                                            class="px-3 py-1 text-sm {{ $category->active ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-200' : 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-200' }} rounded hover:opacity-80">
                                        {{ $category->active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                    <button wire:click="deleteCategory({{ $category->id }})"
                                            wire:confirm="Are you sure you want to delete this category? This cannot be undone."
                                            class="px-3 py-1 text-sm bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-200 rounded hover:bg-red-200 dark:hover:bg-red-800">
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="px-6 py-8 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                            </svg>
                            <p class="mt-2 text-gray-500 dark:text-gray-400">No categories found.</p>
                            <button wire:click="showCreateCategory" class="mt-3 text-emerald-600 hover:text-emerald-700 font-medium">
                                Create your first category
                            </button>
                        </li>
                    @endforelse
                </ul>
            </div>

            <div class="mt-6">
                {{ $categories->links() }}
            </div>
        </div>
        @endif
    </div>
</div>
