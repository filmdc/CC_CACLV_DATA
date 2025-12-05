<div class="w-full px-0 py-6">
    <div class="bg-white dark:bg-zinc-800 shadow-lg rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-emerald-600 text-white p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Documentation</h1>
                    <p class="text-emerald-100 mt-1">Help articles and guides for using CAP TRACK</p>
                </div>
                <div class="flex items-center space-x-3">
                    <button wire:click="showMyBookmarks"
                            class="px-4 py-2 bg-emerald-500 hover:bg-emerald-400 rounded-md text-sm font-medium transition {{ $showBookmarks ? 'ring-2 ring-white' : '' }}">
                        <svg class="w-5 h-5 inline mr-1" fill="{{ $showBookmarks ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
                        </svg>
                        My Bookmarks
                    </button>
                </div>
            </div>
        </div>

        <!-- Flash Messages -->
        @if (session()->has('doc-message'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
                 class="p-3 bg-green-50 dark:bg-green-900/20 border-b border-green-200 dark:border-green-800">
                <p class="text-sm text-green-700 dark:text-green-300">{{ session('doc-message') }}</p>
            </div>
        @endif

        <!-- Search Bar -->
        <div class="p-4 bg-gray-50 dark:bg-zinc-700 border-b dark:border-zinc-600">
            <div class="max-w-2xl mx-auto">
                <div class="relative">
                    <input type="text" wire:model.live.debounce.300ms="search"
                           placeholder="Search documentation..."
                           class="w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 transform -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="flex min-h-[500px]">
            <!-- Sidebar - Categories -->
            <div class="w-64 bg-gray-50 dark:bg-zinc-700/50 border-r dark:border-zinc-600 p-4 hidden md:block">
                <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Categories</h3>
                <nav class="space-y-1">
                    <button wire:click="showAllCategories"
                            class="w-full text-left px-3 py-2 rounded-md text-sm font-medium transition
                                {{ !$selectedCategorySlug && !$showBookmarks && !$search ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-zinc-600' }}">
                        All Categories
                    </button>
                    @foreach($categories as $category)
                        <button wire:click="selectCategory('{{ $category->slug }}')"
                                class="w-full text-left px-3 py-2 rounded-md text-sm font-medium transition flex justify-between items-center
                                    {{ $selectedCategorySlug === $category->slug ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-zinc-600' }}">
                            <span>{{ $category->name }}</span>
                            <span class="text-xs bg-gray-200 dark:bg-zinc-600 text-gray-600 dark:text-gray-300 px-2 py-0.5 rounded-full">
                                {{ $category->articles_count }}
                            </span>
                        </button>
                    @endforeach
                </nav>
            </div>

            <!-- Main Content -->
            <div class="flex-1 p-6">
                <!-- Breadcrumb -->
                @if($selectedCategory || $selectedArticle)
                    <nav class="mb-4 text-sm">
                        <ol class="flex items-center space-x-2">
                            <li>
                                <button wire:click="showAllCategories" class="text-emerald-600 hover:text-emerald-700">Documentation</button>
                            </li>
                            @if($selectedCategory)
                                <li class="text-gray-400">/</li>
                                <li>
                                    @if($selectedArticle)
                                        <button wire:click="selectCategory('{{ $selectedCategory->slug }}')" class="text-emerald-600 hover:text-emerald-700">
                                            {{ $selectedCategory->name }}
                                        </button>
                                    @else
                                        <span class="text-gray-700 dark:text-gray-300">{{ $selectedCategory->name }}</span>
                                    @endif
                                </li>
                            @endif
                            @if($selectedArticle)
                                <li class="text-gray-400">/</li>
                                <li class="text-gray-700 dark:text-gray-300">{{ $selectedArticle->title }}</li>
                            @endif
                        </ol>
                    </nav>
                @endif

                <!-- Bookmarks View -->
                @if($showBookmarks)
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">My Bookmarks</h2>
                    @if($bookmarkedArticles->count() > 0)
                        <div class="space-y-3">
                            @foreach($bookmarkedArticles as $article)
                                <div class="bg-white dark:bg-zinc-800 border dark:border-zinc-700 rounded-lg p-4 hover:shadow-md transition cursor-pointer"
                                     wire:click="selectArticle('{{ $article->slug }}')">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <h3 class="font-medium text-gray-900 dark:text-white">{{ $article->title }}</h3>
                                            @if($article->summary)
                                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ Str::limit($article->summary, 100) }}</p>
                                            @endif
                                            <span class="text-xs text-gray-500 dark:text-gray-400 mt-2 inline-block">
                                                {{ $article->category?->name }}
                                            </span>
                                        </div>
                                        <button wire:click.stop="toggleBookmark({{ $article->id }})"
                                                class="text-yellow-500 hover:text-yellow-600">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
                            </svg>
                            <p class="mt-2 text-gray-500 dark:text-gray-400">No bookmarks yet. Click the bookmark icon on any article to save it here.</p>
                        </div>
                    @endif

                <!-- Article View -->
                @elseif($selectedArticle)
                    <article class="rich-text-content text-gray-800 dark:text-gray-200">
                        <div class="flex items-start justify-between mb-6">
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-0 mt-0">{{ $selectedArticle->title }}</h1>
                            <button wire:click="toggleBookmark({{ $selectedArticle->id }})"
                                    class="ml-4 p-2 rounded-full hover:bg-gray-100 dark:hover:bg-zinc-700 transition"
                                    title="{{ $selectedArticle->isBookmarkedBy() ? 'Remove bookmark' : 'Bookmark this article' }}">
                                <svg class="w-6 h-6 {{ $selectedArticle->isBookmarkedBy() ? 'text-yellow-500' : 'text-gray-400' }}"
                                     fill="{{ $selectedArticle->isBookmarkedBy() ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
                                </svg>
                            </button>
                        </div>

                        @if($selectedArticle->summary)
                            <p class="text-lg text-gray-600 dark:text-gray-400 mb-6">{{ $selectedArticle->summary }}</p>
                        @endif

                        <div class="border-t dark:border-zinc-700 pt-6">
                            {!! $selectedArticle->content !!}
                        </div>
                    </article>

                    <!-- Feedback Section -->
                    <div class="mt-8 pt-6 border-t dark:border-zinc-700">
                        <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-4">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Was this article helpful?</p>
                            @php
                                $userFeedback = $selectedArticle->getUserFeedback();
                            @endphp
                            <div class="flex items-center space-x-3">
                                <button wire:click="submitFeedback({{ $selectedArticle->id }}, true)"
                                        class="px-4 py-2 rounded-md text-sm font-medium transition
                                            {{ $userFeedback && $userFeedback->is_helpful ? 'bg-green-600 text-white' : 'bg-white dark:bg-zinc-600 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-zinc-500 hover:bg-gray-50 dark:hover:bg-zinc-500' }}">
                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"></path>
                                    </svg>
                                    Yes
                                </button>
                                <button wire:click="submitFeedback({{ $selectedArticle->id }}, false)"
                                        class="px-4 py-2 rounded-md text-sm font-medium transition
                                            {{ $userFeedback && !$userFeedback->is_helpful ? 'bg-red-600 text-white' : 'bg-white dark:bg-zinc-600 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-zinc-500 hover:bg-gray-50 dark:hover:bg-zinc-500' }}">
                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14H5.236a2 2 0 01-1.789-2.894l3.5-7A2 2 0 018.736 3h4.018a2 2 0 01.485.06l3.76.94m-7 10v5a2 2 0 002 2h.096c.5 0 .905-.405.905-.904 0-.715.211-1.413.608-2.008L17 13V4m-7 10h2m5-10h2a2 2 0 012 2v6a2 2 0 01-2 2h-2.5"></path>
                                    </svg>
                                    No
                                </button>
                            </div>
                            @php
                                $stats = $selectedArticle->getFeedbackStats();
                            @endphp
                            @if($stats['total'] > 0)
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                    {{ $stats['helpful'] }} of {{ $stats['total'] }} found this helpful ({{ $stats['percentage'] }}%)
                                </p>
                            @endif
                        </div>
                    </div>

                <!-- Search Results -->
                @elseif($search)
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                        Search results for "{{ $search }}"
                    </h2>
                    @if($articles->count() > 0)
                        <div class="space-y-3">
                            @foreach($articles as $article)
                                <div class="bg-white dark:bg-zinc-800 border dark:border-zinc-700 rounded-lg p-4 hover:shadow-md transition cursor-pointer"
                                     wire:click="selectArticle('{{ $article->slug }}')">
                                    <h3 class="font-medium text-gray-900 dark:text-white">{{ $article->title }}</h3>
                                    @if($article->summary)
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ Str::limit($article->summary, 150) }}</p>
                                    @endif
                                    <span class="text-xs text-emerald-600 dark:text-emerald-400 mt-2 inline-block">
                                        {{ $article->category?->name }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 dark:text-gray-400 text-center py-8">No articles found matching your search.</p>
                    @endif

                <!-- Category Articles List -->
                @elseif($selectedCategory)
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">{{ $selectedCategory->name }}</h2>
                    @if($selectedCategory->description)
                        <p class="text-gray-600 dark:text-gray-400 mb-6">{{ $selectedCategory->description }}</p>
                    @endif
                    @if($articles->count() > 0)
                        <div class="space-y-3">
                            @foreach($articles as $article)
                                <div class="bg-white dark:bg-zinc-800 border dark:border-zinc-700 rounded-lg p-4 hover:shadow-md transition cursor-pointer"
                                     wire:click="selectArticle('{{ $article->slug }}')">
                                    <h3 class="font-medium text-gray-900 dark:text-white">{{ $article->title }}</h3>
                                    @if($article->summary)
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ Str::limit($article->summary, 150) }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 dark:text-gray-400 text-center py-8">No articles in this category yet.</p>
                    @endif

                <!-- All Categories View (Home) -->
                @else
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Browse by Category</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($categories as $category)
                            <div wire:click="selectCategory('{{ $category->slug }}')"
                                 class="bg-white dark:bg-zinc-800 border dark:border-zinc-700 rounded-lg p-5 hover:shadow-md transition cursor-pointer group">
                                <div class="flex items-center justify-between">
                                    <h3 class="font-semibold text-gray-900 dark:text-white group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition">
                                        {{ $category->name }}
                                    </h3>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $category->articles_count }} {{ Str::plural('article', $category->articles_count) }}
                                    </span>
                                </div>
                                @if($category->description)
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">{{ Str::limit($category->description, 80) }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    @if($categories->count() === 0)
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                            </svg>
                            <p class="mt-2 text-gray-500 dark:text-gray-400">No documentation available yet.</p>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>
