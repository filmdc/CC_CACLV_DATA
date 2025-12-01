<?php

namespace App\Livewire;

use App\Models\DocumentationArticle;
use Livewire\Component;

class HelpWidget extends Component
{
    public bool $showModal = false;

    public string $currentUrl = '';

    public string $currentRouteName = '';

    public $contextualArticles = [];

    public $selectedArticle = null;

    public function mount()
    {
        $this->currentUrl = request()->url();
        $this->currentRouteName = request()->route()?->getName() ?? '';
    }

    public function openModal()
    {
        $this->currentUrl = request()->header('referer', request()->url());
        $this->loadContextualArticles();
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->selectedArticle = null;
    }

    public function loadContextualArticles()
    {
        if (empty($this->currentRouteName)) {
            $this->contextualArticles = collect();

            return;
        }

        $this->contextualArticles = DocumentationArticle::published()
            ->visibleToUser()
            ->forHelpWidget()
            ->forRoute($this->currentRouteName)
            ->with('category')
            ->orderBy('sort_order')
            ->limit(5)
            ->get();
    }

    public function viewArticle($articleId)
    {
        $this->selectedArticle = DocumentationArticle::published()
            ->visibleToUser()
            ->with('category')
            ->find($articleId);

        if ($this->selectedArticle) {
            $this->selectedArticle->incrementViewCount();
        }
    }

    public function backToList()
    {
        $this->selectedArticle = null;
    }

    public function render()
    {
        return view('livewire.help-widget');
    }
}
