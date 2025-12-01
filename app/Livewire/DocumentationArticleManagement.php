<?php

namespace App\Livewire;

use App\Models\DocumentationArticle;
use App\Models\DocumentationArticleVersion;
use App\Models\DocumentationCategory;
use App\Models\DocumentationRouteMapping;
use App\Traits\WithPermissionChecks;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class DocumentationArticleManagement extends Component
{
    use WithPagination, WithPermissionChecks;

    public $search = '';

    public $filterCategoryId = '';

    public $filterStatus = '';

    public $selectedArticleId = null;

    public $showCreateForm = false;

    public $showEditForm = false;

    public $showVersionHistory = false;

    public $versions = [];

    protected $queryString = [
        'filterCategoryId' => ['as' => 'category'],
        'filterStatus' => ['as' => 'status'],
    ];

    // Article Form Fields
    public $title = '';

    public $summary = '';

    public $content = '';

    public $category_id = '';

    public $status = 'draft';

    public $sort_order = 0;

    public $is_internal = false;

    public $show_in_help_widget = false;

    public $related_routes = [];

    public function mount()
    {
        $this->authorizeAction('documentation.manage');
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilterCategoryId()
    {
        $this->resetPage();
    }

    public function updatedFilterStatus()
    {
        $this->resetPage();
    }

    public function showCreateArticle()
    {
        $this->showCreateForm = true;
        $this->showEditForm = false;
        $this->showVersionHistory = false;
        $this->selectedArticleId = null;
        $this->resetArticleForm();
    }

    public function showEditArticle($articleId)
    {
        $article = DocumentationArticle::with('routeMappings')->find($articleId);
        if ($article) {
            $this->selectedArticleId = $articleId;
            $this->showEditForm = true;
            $this->showCreateForm = false;
            $this->showVersionHistory = false;
            $this->fillArticleForm($article);
        }
    }

    public function showArticleVersions($articleId)
    {
        $article = DocumentationArticle::find($articleId);
        if ($article) {
            $this->selectedArticleId = $articleId;
            $this->versions = $article->versions()->with('createdBy')->get();
            $this->showVersionHistory = true;
            $this->showEditForm = false;
            $this->showCreateForm = false;
        }
    }

    public function restoreVersion($versionId)
    {
        $version = DocumentationArticleVersion::find($versionId);
        if ($version) {
            $version->restore();
            session()->flash('success', 'Article restored to version '.$version->version_number.'.');
            $this->showVersionHistory = false;
        }
    }

    public function resetArticleForm()
    {
        $this->title = '';
        $this->summary = '';
        $this->content = '';
        $this->category_id = '';
        $this->status = 'draft';
        $this->sort_order = 0;
        $this->is_internal = false;
        $this->show_in_help_widget = false;
        $this->related_routes = [];
        $this->selectedArticleId = null;
        $this->versions = [];
    }

    public function fillArticleForm(DocumentationArticle $article)
    {
        $this->title = $article->title;
        $this->summary = $article->summary;
        $this->content = $article->content;
        $this->category_id = $article->category_id;
        $this->status = $article->status;
        $this->sort_order = $article->sort_order;
        $this->is_internal = $article->is_internal;
        $this->show_in_help_widget = $article->show_in_help_widget;
        $this->related_routes = $article->routeMappings->pluck('route_name')->toArray();
    }

    public function getSelectedArticleProperty(): ?DocumentationArticle
    {
        return $this->selectedArticleId ? DocumentationArticle::with(['category', 'versions'])->find($this->selectedArticleId) : null;
    }

    public function createArticle()
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'summary' => 'nullable|string',
            'content' => 'required|string',
            'category_id' => 'required|exists:documentation_categories,id',
            'status' => 'required|in:draft,published,archived',
            'sort_order' => 'integer|min:0',
        ]);

        try {
            $article = DocumentationArticle::create([
                'title' => $this->title,
                'slug' => Str::slug($this->title),
                'summary' => $this->summary,
                'content' => $this->content,
                'category_id' => $this->category_id,
                'status' => $this->status,
                'sort_order' => $this->sort_order,
                'is_internal' => $this->is_internal,
                'show_in_help_widget' => $this->show_in_help_widget,
                'published_at' => $this->status === 'published' ? now() : null,
            ]);

            $this->saveRouteMappings($article);

            session()->flash('success', 'Article created successfully.');
            $this->showCreateForm = false;
            $this->resetArticleForm();
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: '.$e->getMessage());
        }
    }

    public function updateArticle()
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'summary' => 'nullable|string',
            'content' => 'required|string',
            'category_id' => 'required|exists:documentation_categories,id',
            'status' => 'required|in:draft,published,archived',
            'sort_order' => 'integer|min:0',
        ]);

        try {
            $article = DocumentationArticle::find($this->selectedArticleId);
            if (! $article) {
                session()->flash('error', 'Article not found.');

                return;
            }

            $wasPublished = $article->status === 'published';

            $article->update([
                'title' => $this->title,
                'summary' => $this->summary,
                'content' => $this->content,
                'category_id' => $this->category_id,
                'status' => $this->status,
                'sort_order' => $this->sort_order,
                'is_internal' => $this->is_internal,
                'show_in_help_widget' => $this->show_in_help_widget,
                'published_at' => (! $wasPublished && $this->status === 'published') ? now() : $article->published_at,
            ]);

            $this->saveRouteMappings($article);

            session()->flash('success', 'Article updated successfully.');
            $this->showEditForm = false;
            $this->resetArticleForm();
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: '.$e->getMessage());
        }
    }

    protected function saveRouteMappings(DocumentationArticle $article)
    {
        $article->routeMappings()->delete();

        foreach ($this->related_routes as $routeName) {
            if (! empty($routeName)) {
                DocumentationRouteMapping::create([
                    'route_name' => $routeName,
                    'route_label' => $this->getAvailableRoutes()[$routeName] ?? $routeName,
                    'article_id' => $article->id,
                ]);
            }
        }
    }

    public function publishArticle($articleId)
    {
        $article = DocumentationArticle::find($articleId);
        if ($article) {
            $article->publish();
            session()->flash('success', 'Article published successfully.');
        }
    }

    public function archiveArticle($articleId)
    {
        $article = DocumentationArticle::find($articleId);
        if ($article) {
            $article->archive();
            session()->flash('success', 'Article archived successfully.');
        }
    }

    public function deleteArticle($articleId)
    {
        try {
            $article = DocumentationArticle::find($articleId);
            if ($article) {
                $article->delete();
                session()->flash('success', 'Article deleted successfully.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Cannot delete article: '.$e->getMessage());
        }
    }

    public function getAvailableRoutes(): array
    {
        return [
            'dashboard' => 'Dashboard',
            'participant.household.hub' => 'Participants & Households Hub',
            'participant.management' => 'Participant Management',
            'household.management' => 'Household Management',
            'program.service.hub' => 'Programs & Services Hub',
            'program.management' => 'Program Management',
            'service.management' => 'Service Management',
            'contract.management' => 'Contract Management',
            'projects.management' => 'Projects Management',
            'property.management' => 'Property Management',
            'contractor.management' => 'Contractor Management',
            'housing-job.management' => 'Housing Job Management',
            'communications-log.management' => 'Communications Log',
            'reports' => 'Reports Dashboard',
            'donor-gifts.feed' => 'DonorPerfect Gifts',
            'sage-intacct.dashboard' => 'Sage Intacct',
            'admin.panel' => 'Admin Panel',
            'user.management' => 'User Management',
            'role-permission.management' => 'Roles & Permissions',
            'funder.management' => 'Funder Management',
        ];
    }

    public function render()
    {
        $articles = DocumentationArticle::with(['category', 'createdBy', 'routeMappings'])
            ->when($this->filterCategoryId, fn ($q) => $q->where('category_id', $this->filterCategoryId))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->search, fn ($q) => $q->search($this->search))
            ->orderBy('updated_at', 'desc')
            ->paginate(15);

        $categories = DocumentationCategory::active()->ordered()->get();

        return view('livewire.documentation-article-management', [
            'articles' => $articles,
            'categories' => $categories,
            'availableRoutes' => $this->getAvailableRoutes(),
            'selectedArticle' => $this->selectedArticle,
        ])->layout('components.layouts.app');
    }
}
