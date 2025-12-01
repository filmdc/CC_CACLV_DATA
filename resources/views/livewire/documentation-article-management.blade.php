<div class="w-full px-0 py-6">
    <div class="bg-white dark:bg-zinc-800 shadow-lg rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-emerald-600 text-white p-6">
            <h1 class="text-2xl font-bold">Documentation Articles</h1>
            <p class="text-emerald-100 mt-2">Create and manage help documentation articles</p>
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
        @if(!$showEditForm && !$showCreateForm && !$showVersionHistory)
        <div class="p-4 bg-gray-50 dark:bg-zinc-700 border-b dark:border-zinc-600">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex-1 max-w-md">
                    <input type="text" wire:model.live="search" placeholder="Search articles..."
                           class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div class="flex items-center gap-3">
                    <select wire:model.live="filterCategoryId" class="px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <select wire:model.live="filterStatus" class="px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md">
                        <option value="">All Statuses</option>
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                        <option value="archived">Archived</option>
                    </select>
                    <a href="{{ route('admin.documentation.categories') }}" wire:navigate
                       class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500">
                        Categories
                    </a>
                    <button wire:click="showCreateArticle"
                            class="px-4 py-2 bg-emerald-600 text-white rounded-md hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        Create Article
                    </button>
                </div>
            </div>
        </div>
        @endif

        <!-- Version History View -->
        @if ($showVersionHistory && $selectedArticle)
            <div class="p-6 border-b dark:border-zinc-700">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                        Version History: {{ $selectedArticle->title }}
                        <span class="text-sm text-gray-500 dark:text-gray-400">(Current: v{{ $selectedArticle->current_version }})</span>
                    </h3>
                    <button wire:click="$set('showVersionHistory', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                @if(count($versions) > 0)
                    <div class="space-y-3">
                        @foreach($versions as $version)
                            <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <span class="font-medium text-gray-900 dark:text-white">Version {{ $version->version_number }}</span>
                                        <span class="text-sm text-gray-500 dark:text-gray-400 ml-2">
                                            {{ $version->created_at->format('M d, Y g:i A') }}
                                        </span>
                                        @if($version->createdBy)
                                            <span class="text-sm text-gray-500 dark:text-gray-400 ml-2">
                                                by {{ $version->createdBy->name }}
                                            </span>
                                        @endif
                                    </div>
                                    <button wire:click="restoreVersion({{ $version->id }})"
                                            wire:confirm="Are you sure you want to restore this version? Current content will be saved as a new version."
                                            class="px-3 py-1 text-sm bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-200 rounded hover:bg-blue-200">
                                        Restore
                                    </button>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">Title: {{ $version->title }}</p>
                                @if($version->change_summary)
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $version->change_summary }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 dark:text-gray-400 text-center py-4">No previous versions available.</p>
                @endif
            </div>
        @endif

        <!-- Create Article Form -->
        @if ($showCreateForm)
            <div class="p-6 border-b dark:border-zinc-700">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Create New Article</h3>
                    <button wire:click="$set('showCreateForm', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form wire:submit="createArticle" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Title <span class="text-red-500">*</span>
                            </label>
                            <input type="text" wire:model="title"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            @error('title') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Category <span class="text-red-500">*</span>
                            </label>
                            <select wire:model="category_id"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                <option value="">Select category...</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                            @error('category_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                            <select wire:model="status"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                <option value="draft">Draft</option>
                                <option value="published">Published</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sort Order</label>
                            <input type="number" wire:model="sort_order" min="0"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-4">
                        <label class="flex items-center">
                            <input type="checkbox" wire:model="show_in_help_widget" class="h-4 w-4 text-emerald-600 focus:ring-emerald-500 border-gray-300 rounded">
                            <span class="ml-2 text-sm text-gray-900 dark:text-white">Show in Help Widget</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" wire:model="is_internal" class="h-4 w-4 text-emerald-600 focus:ring-emerald-500 border-gray-300 rounded">
                            <span class="ml-2 text-sm text-gray-900 dark:text-white">Internal Only (Admin)</span>
                        </label>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Summary</label>
                        <textarea wire:model="summary" rows="2" placeholder="Brief summary for search results..."
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Content <span class="text-red-500">*</span>
                        </label>
                        <x-wysiwyg-editor model="content" :rows="12" placeholder="Write your article content here..." id="create_article_content" />
                        @error('content') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Related Pages (for contextual help)
                        </label>
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2 p-3 bg-gray-50 dark:bg-zinc-700 rounded-md max-h-48 overflow-y-auto">
                            @foreach($availableRoutes as $routeName => $routeLabel)
                                <label class="flex items-center text-sm">
                                    <input type="checkbox" wire:model="related_routes" value="{{ $routeName }}"
                                           class="h-4 w-4 text-emerald-600 focus:ring-emerald-500 border-gray-300 rounded">
                                    <span class="ml-2 text-gray-700 dark:text-gray-300">{{ $routeLabel }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button type="button" wire:click="$set('showCreateForm', false)"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 border border-gray-300 rounded-md hover:bg-gray-300">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 border border-transparent rounded-md hover:bg-emerald-700">
                            Create Article
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <!-- Edit Article Form -->
        @if ($showEditForm && $selectedArticle)
            <div class="p-6 border-b dark:border-zinc-700">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                        Edit Article: {{ $selectedArticle->title }}
                        <span class="text-sm text-gray-500 dark:text-gray-400">(v{{ $selectedArticle->current_version }})</span>
                    </h3>
                    <div class="flex items-center space-x-2">
                        <button wire:click="showArticleVersions({{ $selectedArticle->id }})"
                                class="px-3 py-1 text-sm bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-200 rounded hover:bg-purple-200">
                            Version History
                        </button>
                        <button wire:click="$set('showEditForm', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <form wire:submit="updateArticle" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Title <span class="text-red-500">*</span>
                            </label>
                            <input type="text" wire:model="title"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            @error('title') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Category <span class="text-red-500">*</span>
                            </label>
                            <select wire:model="category_id"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                <option value="">Select category...</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                            @error('category_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                            <select wire:model="status"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                <option value="draft">Draft</option>
                                <option value="published">Published</option>
                                <option value="archived">Archived</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sort Order</label>
                            <input type="number" wire:model="sort_order" min="0"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-4">
                        <label class="flex items-center">
                            <input type="checkbox" wire:model="show_in_help_widget" class="h-4 w-4 text-emerald-600 focus:ring-emerald-500 border-gray-300 rounded">
                            <span class="ml-2 text-sm text-gray-900 dark:text-white">Show in Help Widget</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" wire:model="is_internal" class="h-4 w-4 text-emerald-600 focus:ring-emerald-500 border-gray-300 rounded">
                            <span class="ml-2 text-sm text-gray-900 dark:text-white">Internal Only (Admin)</span>
                        </label>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Summary</label>
                        <textarea wire:model="summary" rows="2"
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Content <span class="text-red-500">*</span>
                        </label>
                        <x-wysiwyg-editor model="content" :rows="12" placeholder="Write your article content here..." id="edit_article_content" />
                        @error('content') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Related Pages (for contextual help)
                        </label>
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2 p-3 bg-gray-50 dark:bg-zinc-700 rounded-md max-h-48 overflow-y-auto">
                            @foreach($availableRoutes as $routeName => $routeLabel)
                                <label class="flex items-center text-sm">
                                    <input type="checkbox" wire:model="related_routes" value="{{ $routeName }}"
                                           class="h-4 w-4 text-emerald-600 focus:ring-emerald-500 border-gray-300 rounded">
                                    <span class="ml-2 text-gray-700 dark:text-gray-300">{{ $routeLabel }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button type="button" wire:click="$set('showEditForm', false)"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 border border-gray-300 rounded-md hover:bg-gray-300">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 border border-transparent rounded-md hover:bg-emerald-700">
                            Update Article
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <!-- Articles List -->
        @if(!$showEditForm && !$showCreateForm && !$showVersionHistory)
        <div class="p-6">
            <div class="bg-white dark:bg-zinc-800 shadow overflow-hidden sm:rounded-md">
                <ul class="divide-y divide-gray-200 dark:divide-zinc-700">
                    @forelse ($articles as $article)
                        <li class="px-6 py-4 hover:bg-gray-50 dark:hover:bg-zinc-700/50">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center flex-wrap gap-2">
                                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                            {{ $article->title }}
                                        </h3>
                                        <span class="px-2 py-1 text-xs rounded-full
                                            @if($article->status === 'published') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                            @elseif($article->status === 'draft') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                            @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                                            @endif">
                                            {{ $article->status_label }}
                                        </span>
                                        @if($article->is_internal)
                                            <span class="px-2 py-1 text-xs rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                                Internal
                                            </span>
                                        @endif
                                        @if($article->show_in_help_widget)
                                            <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                Help Widget
                                            </span>
                                        @endif
                                    </div>
                                    @if($article->summary)
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ Str::limit($article->summary, 100) }}</p>
                                    @endif
                                    <div class="mt-2 flex items-center flex-wrap text-sm text-gray-500 dark:text-gray-400 gap-x-4 gap-y-1">
                                        <span>Category: {{ $article->category?->name ?? 'None' }}</span>
                                        <span>Views: {{ $article->view_count }}</span>
                                        <span>Version: {{ $article->current_version }}</span>
                                        <span>Updated: {{ $article->updated_at->diffForHumans() }}</span>
                                        @if($article->routeMappings->count() > 0)
                                            <span>Pages: {{ $article->routeMappings->count() }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2 ml-4">
                                    <button wire:click="showEditArticle({{ $article->id }})"
                                            class="px-3 py-1 text-sm bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-200 rounded hover:bg-blue-200 dark:hover:bg-blue-800">
                                        Edit
                                    </button>
                                    <button wire:click="showArticleVersions({{ $article->id }})"
                                            class="px-3 py-1 text-sm bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-200 rounded hover:bg-purple-200 dark:hover:bg-purple-800">
                                        History
                                    </button>
                                    @if($article->status === 'draft')
                                        <button wire:click="publishArticle({{ $article->id }})"
                                                class="px-3 py-1 text-sm bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-200 rounded hover:bg-green-200 dark:hover:bg-green-800">
                                            Publish
                                        </button>
                                    @elseif($article->status === 'published')
                                        <button wire:click="archiveArticle({{ $article->id }})"
                                                class="px-3 py-1 text-sm bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 rounded hover:bg-gray-200 dark:hover:bg-gray-600">
                                            Archive
                                        </button>
                                    @endif
                                    <button wire:click="deleteArticle({{ $article->id }})"
                                            wire:confirm="Are you sure you want to delete this article? This cannot be undone."
                                            class="px-3 py-1 text-sm bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-200 rounded hover:bg-red-200 dark:hover:bg-red-800">
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="px-6 py-8 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <p class="mt-2 text-gray-500 dark:text-gray-400">No articles found.</p>
                            <button wire:click="showCreateArticle" class="mt-3 text-emerald-600 hover:text-emerald-700 font-medium">
                                Create your first article
                            </button>
                        </li>
                    @endforelse
                </ul>
            </div>

            <div class="mt-6">
                {{ $articles->links() }}
            </div>
        </div>
        @endif
    </div>
</div>
