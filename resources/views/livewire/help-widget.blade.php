<div>
    <!-- Floating Help Button -->
    <button
        wire:click="openModal"
        class="fixed bottom-20 right-6 z-40 bg-emerald-600 hover:bg-emerald-700 text-white rounded-full p-4 shadow-lg transition-all duration-200 hover:scale-110 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
        title="Help on this page"
    >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
    </button>

    <!-- Help Modal -->
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div wire:click="closeModal" class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75 transition-opacity" aria-hidden="true"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <!-- Modal panel -->
                <div class="inline-block align-bottom bg-white dark:bg-zinc-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <!-- Header -->
                    <div class="bg-emerald-600 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-white" id="modal-title">
                                @if($selectedArticle)
                                    {{ $selectedArticle->title }}
                                @else
                                    Help for this Page
                                @endif
                            </h3>
                            <button wire:click="closeModal" class="text-emerald-200 hover:text-white focus:outline-none">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="px-6 py-4 max-h-96 overflow-y-auto">
                        @if($selectedArticle)
                            <!-- Single Article View -->
                            @if($selectedArticle->summary)
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 italic">{{ $selectedArticle->summary }}</p>
                            @endif
                            <div class="prose dark:prose-invert prose-sm max-w-none">
                                {!! $selectedArticle->content !!}
                            </div>
                            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-zinc-700">
                                <button wire:click="backToList" class="text-emerald-600 hover:text-emerald-700 text-sm font-medium">
                                    &larr; Back to help topics
                                </button>
                            </div>
                        @else
                            <!-- Article List -->
                            @if($contextualArticles && count($contextualArticles) > 0)
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                    Help articles for this page:
                                </p>
                                <ul class="space-y-3">
                                    @foreach($contextualArticles as $article)
                                        <li>
                                            <button wire:click="viewArticle({{ $article->id }})"
                                                    class="w-full text-left p-3 rounded-lg bg-gray-50 dark:bg-zinc-700 hover:bg-gray-100 dark:hover:bg-zinc-600 transition">
                                                <h4 class="font-medium text-gray-900 dark:text-white">{{ $article->title }}</h4>
                                                @if($article->summary)
                                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ Str::limit($article->summary, 80) }}</p>
                                                @endif
                                                @if($article->category)
                                                    <span class="text-xs text-emerald-600 dark:text-emerald-400 mt-1 inline-block">{{ $article->category->name }}</span>
                                                @endif
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <div class="text-center py-6">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                    </svg>
                                    <p class="mt-2 text-gray-600 dark:text-gray-400">No specific help available for this page.</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">Browse our full documentation for more help.</p>
                                </div>
                            @endif
                        @endif
                    </div>

                    <!-- Footer -->
                    <div class="bg-gray-50 dark:bg-zinc-700 px-6 py-4 flex justify-between items-center">
                        <a href="{{ route('documentation.index') }}" wire:navigate class="text-sm text-emerald-600 hover:text-emerald-700 font-medium">
                            Browse all documentation
                        </a>
                        <button type="button" wire:click="closeModal"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-zinc-600 border border-gray-300 dark:border-zinc-500 rounded-md hover:bg-gray-50 dark:hover:bg-zinc-500 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
