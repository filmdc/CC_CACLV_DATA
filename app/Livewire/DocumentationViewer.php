<?php

namespace App\Livewire;

use App\Models\DocumentationArticle;
use App\Models\DocumentationArticleFeedback;
use App\Models\DocumentationCategory;
use App\Models\DocumentationUserBookmark;
use App\Traits\WithPermissionChecks;
use Livewire\Component;

class DocumentationViewer extends Component
{
    use WithPermissionChecks;

    public $search = '';

    public $selectedCategorySlug = null;

    public $selectedArticleSlug = null;

    public $showBookmarks = false;

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    public function mount($category = null, $article = null)
    {
        $this->selectedCategorySlug = $category;
        $this->selectedArticleSlug = $article;

        if ($article) {
            $articleModel = DocumentationArticle::where('slug', $article)
                ->published()
                ->visibleToUser()
                ->first();
            if ($articleModel) {
                $articleModel->incrementViewCount();
            }
        }
    }

    public function selectCategory($slug)
    {
        $this->selectedCategorySlug = $slug;
        $this->selectedArticleSlug = null;
        $this->showBookmarks = false;
    }

    public function selectArticle($slug)
    {
        $this->selectedArticleSlug = $slug;
        $this->showBookmarks = false;

        $article = DocumentationArticle::where('slug', $slug)
            ->published()
            ->visibleToUser()
            ->first();
        if ($article) {
            $article->incrementViewCount();
        }
    }

    public function showAllCategories()
    {
        $this->selectedCategorySlug = null;
        $this->selectedArticleSlug = null;
        $this->showBookmarks = false;
        $this->search = '';
    }

    public function showMyBookmarks()
    {
        $this->showBookmarks = true;
        $this->selectedCategorySlug = null;
        $this->selectedArticleSlug = null;
    }

    public function toggleBookmark($articleId)
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        $bookmark = DocumentationUserBookmark::where('user_id', $user->id)
            ->where('article_id', $articleId)
            ->first();

        if ($bookmark) {
            $bookmark->delete();
            session()->flash('doc-message', 'Bookmark removed.');
        } else {
            DocumentationUserBookmark::create([
                'user_id' => $user->id,
                'article_id' => $articleId,
            ]);
            session()->flash('doc-message', 'Article bookmarked.');
        }
    }

    public function submitFeedback($articleId, $isHelpful)
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        $feedback = DocumentationArticleFeedback::updateOrCreate(
            [
                'article_id' => $articleId,
                'user_id' => $user->id,
            ],
            [
                'is_helpful' => $isHelpful,
            ]
        );

        session()->flash('doc-message', 'Thank you for your feedback!');
    }

    public function getSelectedArticleProperty(): ?DocumentationArticle
    {
        if (! $this->selectedArticleSlug) {
            return null;
        }

        return DocumentationArticle::where('slug', $this->selectedArticleSlug)
            ->published()
            ->visibleToUser()
            ->with('category')
            ->first();
    }

    public function getSelectedCategoryProperty(): ?DocumentationCategory
    {
        if (! $this->selectedCategorySlug) {
            return null;
        }

        return DocumentationCategory::where('slug', $this->selectedCategorySlug)
            ->active()
            ->first();
    }

    public function render()
    {
        $categories = DocumentationCategory::active()
            ->withCount(['articles' => fn ($q) => $q->published()->visibleToUser()])
            ->ordered()
            ->get();

        $articles = collect();
        $bookmarkedArticles = collect();

        if ($this->showBookmarks) {
            $bookmarkedArticles = DocumentationArticle::published()
                ->visibleToUser()
                ->whereHas('bookmarkedByUsers', fn ($q) => $q->where('user_id', auth()->id()))
                ->with('category')
                ->ordered()
                ->get();
        } elseif ($this->search) {
            $articles = DocumentationArticle::published()
                ->visibleToUser()
                ->search($this->search)
                ->with('category')
                ->ordered()
                ->limit(20)
                ->get();
        } elseif ($this->selectedCategorySlug && ! $this->selectedArticleSlug) {
            $articles = DocumentationArticle::published()
                ->visibleToUser()
                ->whereHas('category', fn ($q) => $q->where('slug', $this->selectedCategorySlug))
                ->with('category')
                ->ordered()
                ->get();
        }

        return view('livewire.documentation-viewer', [
            'categories' => $categories,
            'articles' => $articles,
            'bookmarkedArticles' => $bookmarkedArticles,
            'selectedArticle' => $this->selectedArticle,
            'selectedCategory' => $this->selectedCategory,
        ])->layout('components.layouts.app');
    }
}
