<?php

namespace App\Livewire;

use App\Models\DocumentationCategory;
use App\Traits\WithPermissionChecks;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class DocumentationCategoryManagement extends Component
{
    use WithPagination, WithPermissionChecks;

    public $search = '';

    public $selectedCategoryId = null;

    public $showCreateForm = false;

    public $showEditForm = false;

    // Category Form Fields
    public $name = '';

    public $description = '';

    public $icon = '';

    public $sort_order = 0;

    public $active = true;

    public function mount()
    {
        $this->authorizeAction('documentation.manage');
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function showCreateCategory()
    {
        $this->showCreateForm = true;
        $this->showEditForm = false;
        $this->selectedCategoryId = null;
        $this->resetCategoryForm();
    }

    public function showEditCategory($categoryId)
    {
        $category = DocumentationCategory::find($categoryId);
        if ($category) {
            $this->selectedCategoryId = $categoryId;
            $this->showEditForm = true;
            $this->showCreateForm = false;
            $this->fillCategoryForm($category);
        }
    }

    public function resetCategoryForm()
    {
        $this->name = '';
        $this->description = '';
        $this->icon = '';
        $this->sort_order = 0;
        $this->active = true;
        $this->selectedCategoryId = null;
    }

    public function fillCategoryForm(DocumentationCategory $category)
    {
        $this->name = $category->name;
        $this->description = $category->description;
        $this->icon = $category->icon;
        $this->sort_order = $category->sort_order;
        $this->active = $category->active;
    }

    public function getSelectedCategoryProperty(): ?DocumentationCategory
    {
        return $this->selectedCategoryId ? DocumentationCategory::find($this->selectedCategoryId) : null;
    }

    public function createCategory()
    {
        $this->validate([
            'name' => 'required|string|max:100|unique:documentation_categories,name',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:50',
            'sort_order' => 'integer|min:0',
        ]);

        try {
            DocumentationCategory::create([
                'name' => $this->name,
                'slug' => Str::slug($this->name),
                'description' => $this->description,
                'icon' => $this->icon,
                'sort_order' => $this->sort_order,
                'active' => $this->active,
            ]);

            session()->flash('success', 'Category created successfully.');
            $this->showCreateForm = false;
            $this->resetCategoryForm();
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: '.$e->getMessage());
        }
    }

    public function updateCategory()
    {
        $this->validate([
            'name' => 'required|string|max:100|unique:documentation_categories,name,'.$this->selectedCategoryId,
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:50',
            'sort_order' => 'integer|min:0',
        ]);

        try {
            $category = DocumentationCategory::find($this->selectedCategoryId);
            if (! $category) {
                session()->flash('error', 'Category not found.');

                return;
            }

            $category->update([
                'name' => $this->name,
                'slug' => Str::slug($this->name),
                'description' => $this->description,
                'icon' => $this->icon,
                'sort_order' => $this->sort_order,
                'active' => $this->active,
            ]);

            session()->flash('success', 'Category updated successfully.');
            $this->showEditForm = false;
            $this->resetCategoryForm();
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: '.$e->getMessage());
        }
    }

    public function toggleCategoryStatus($categoryId)
    {
        $category = DocumentationCategory::find($categoryId);
        if ($category) {
            $category->update(['active' => ! $category->active]);
            session()->flash('success', 'Category status updated successfully.');
        }
    }

    public function deleteCategory($categoryId)
    {
        try {
            $category = DocumentationCategory::find($categoryId);
            if ($category) {
                if ($category->articles()->count() > 0) {
                    session()->flash('error', 'Cannot delete category with articles. Move or delete articles first.');

                    return;
                }
                $category->delete();
                session()->flash('success', 'Category deleted successfully.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Cannot delete category: '.$e->getMessage());
        }
    }

    public function render()
    {
        $categories = DocumentationCategory::withCount('articles')
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('description', 'like', '%'.$this->search.'%');
            })
            ->ordered()
            ->paginate(10);

        return view('livewire.documentation-category-management', [
            'categories' => $categories,
            'selectedCategory' => $this->selectedCategory,
        ])->layout('components.layouts.app');
    }
}
