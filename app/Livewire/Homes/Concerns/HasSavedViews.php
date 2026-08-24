<?php

namespace App\Livewire\Homes\Concerns;

use App\Models\HomesSavedView;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

/**
 * Per-user configurable list views for the Housing portal.
 *
 * The consuming component declares what a "view" means on its screen —
 * the column registry, the filter pills, and the built-in defaults —
 * and this trait supplies the shared lifecycle: applying a saved view,
 * the create/edit modal, defaults, and delete.
 *
 * A component using this trait must define:
 *   - screenKey(): string                  'calls' | 'projects'
 *   - columnOptions(): array               ordered key => label registry
 *   - lockedColumns(): array               keys that cannot be hidden
 *   - pillOptions(): array                 key => label of filter pills
 *   - builtInDefaults(): array             ['columns' => [...], 'filters' => [...]]
 *   - applyViewState(array $columns, array $filters): void
 *   - currentFilters(): array              the screen's live filter state
 */
trait HasSavedViews
{
    /** Visible column keys, in registry order. */
    public array $columns = [];

    /** The saved view currently applied (null = standard/ad-hoc). */
    public ?int $activeViewId = null;

    // ── View editor modal state ─────────────────────────────────────
    public bool $showViewModal = false;

    public ?int $editingViewId = null;

    public string $viewName = '';

    public array $viewColumns = [];

    public array $viewFilters = [];

    public bool $viewMakeDefault = false;

    /**
     * Resolve initial view state. Call from mount() AFTER authorization.
     * Explicit URL state (e.g. ?pill=needs) wins over the saved default,
     * so shared links keep meaning what they say.
     */
    protected function initSavedViews(): void
    {
        $defaults = $this->builtInDefaults();
        $this->columns = $defaults['columns'];

        $default = HomesSavedView::forUserScreen(auth()->id(), $this->screenKey())
            ->where('is_default', true)
            ->first();

        $urlHasFilterState = collect(array_keys($defaults['filters']))
            ->contains(fn ($key) => request()->query($key) !== null);

        if ($default) {
            $this->activeViewId = $default->id;
            $this->columns = $this->sanitizeColumns($default->columns);
            if (! $urlHasFilterState) {
                $this->applyViewState($this->columns, $this->sanitizeFilters($default->filters));
            }
        } elseif (! $urlHasFilterState) {
            $this->applyViewState($this->columns, $defaults['filters']);
        }
    }

    #[Computed]
    public function savedViews(): Collection
    {
        return HomesSavedView::forUserScreen(auth()->id(), $this->screenKey())
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();
    }

    public function applyView(int $viewId): void
    {
        $view = $this->findOwnView($viewId);
        if (! $view) {
            return;
        }

        $this->activeViewId = $view->id;
        $this->columns = $this->sanitizeColumns($view->columns);
        $this->applyViewState($this->columns, $this->sanitizeFilters($view->filters));
        $this->afterViewApplied();
    }

    /** Back to the built-in standard view. */
    public function applyStandardView(): void
    {
        $defaults = $this->builtInDefaults();
        $this->activeViewId = null;
        $this->columns = $defaults['columns'];
        $this->applyViewState($this->columns, $defaults['filters']);
        $this->afterViewApplied();
    }

    public function openCreateView(): void
    {
        $this->editingViewId = null;
        $this->viewName = '';
        $this->viewColumns = $this->columns;
        $this->viewFilters = $this->currentFilters();
        $this->viewMakeDefault = false;
        $this->resetErrorBag();
        $this->showViewModal = true;
    }

    public function openEditView(int $viewId): void
    {
        $view = $this->findOwnView($viewId);
        if (! $view) {
            return;
        }

        $this->editingViewId = $view->id;
        $this->viewName = $view->name;
        $this->viewColumns = $this->sanitizeColumns($view->columns);
        $this->viewFilters = $this->sanitizeFilters($view->filters);
        $this->viewMakeDefault = $view->is_default;
        $this->resetErrorBag();
        $this->showViewModal = true;
    }

    public function closeViewModal(): void
    {
        $this->showViewModal = false;
        $this->editingViewId = null;
    }

    public function toggleViewColumn(string $key): void
    {
        if (! array_key_exists($key, $this->columnOptions()) || in_array($key, $this->lockedColumns(), true)) {
            return;
        }

        $this->viewColumns = in_array($key, $this->viewColumns, true)
            ? array_values(array_diff($this->viewColumns, [$key]))
            : $this->sanitizeColumns([...$this->viewColumns, $key]);
    }

    public function saveView(): void
    {
        $this->viewName = trim($this->viewName);
        $this->viewColumns = $this->sanitizeColumns($this->viewColumns);
        $this->viewFilters = $this->sanitizeFilters($this->viewFilters);

        $this->validate(
            ['viewName' => 'required|string|max:60'],
            [],
            ['viewName' => 'view name'],
        );

        $duplicate = HomesSavedView::forUserScreen(auth()->id(), $this->screenKey())
            ->where('name', $this->viewName)
            ->when($this->editingViewId, fn ($q) => $q->where('id', '!=', $this->editingViewId))
            ->exists();
        if ($duplicate) {
            $this->addError('viewName', 'You already have a view with that name.');

            return;
        }

        $view = $this->editingViewId
            ? $this->findOwnView($this->editingViewId)
            : new HomesSavedView(['user_id' => auth()->id(), 'screen' => $this->screenKey()]);
        if (! $view) {
            return;
        }

        $view->fill([
            'name' => $this->viewName,
            'columns' => $this->viewColumns,
            'filters' => $this->viewFilters,
        ]);
        $view->is_default = $this->viewMakeDefault;
        $view->save();

        if ($this->viewMakeDefault) {
            HomesSavedView::forUserScreen(auth()->id(), $this->screenKey())
                ->where('id', '!=', $view->id)
                ->update(['is_default' => false]);
        }

        unset($this->savedViews);
        $this->closeViewModal();
        $this->applyView($view->id);
    }

    public function makeDefaultView(int $viewId): void
    {
        $view = $this->findOwnView($viewId);
        if (! $view) {
            return;
        }

        HomesSavedView::forUserScreen(auth()->id(), $this->screenKey())
            ->update(['is_default' => false]);
        $view->update(['is_default' => true]);
        unset($this->savedViews);
    }

    /** Clear any default so the built-in standard view greets the user again. */
    public function clearDefaultView(): void
    {
        HomesSavedView::forUserScreen(auth()->id(), $this->screenKey())
            ->update(['is_default' => false]);
        unset($this->savedViews);
    }

    public function deleteView(int $viewId): void
    {
        $view = $this->findOwnView($viewId);
        if (! $view) {
            return;
        }

        $view->delete();
        unset($this->savedViews);

        if ($this->activeViewId === $viewId) {
            $this->applyStandardView();
        }
        if ($this->editingViewId === $viewId) {
            $this->closeViewModal();
        }
    }

    public function showsColumn(string $key): bool
    {
        return in_array($key, $this->columns, true);
    }

    /** Hook for the consuming component (e.g. resetPage()). */
    protected function afterViewApplied(): void {}

    protected function findOwnView(int $viewId): ?HomesSavedView
    {
        return HomesSavedView::forUserScreen(auth()->id(), $this->screenKey())
            ->find($viewId);
    }

    /** Keep only known keys, force locked ones on, restore registry order. */
    protected function sanitizeColumns(array $columns): array
    {
        $selected = array_unique([...$this->lockedColumns(), ...$columns]);

        return array_values(array_intersect(array_keys($this->columnOptions()), $selected));
    }

    /** Keep only known filter keys and coerce them to legal values. */
    protected function sanitizeFilters(array $filters): array
    {
        $defaults = $this->builtInDefaults()['filters'];
        $clean = [];

        foreach ($defaults as $key => $fallback) {
            $value = $filters[$key] ?? $fallback;
            if ($key === 'pill') {
                $value = array_key_exists($value, $this->pillOptions()) ? $value : $fallback;
            } elseif (is_bool($fallback)) {
                $value = (bool) $value;
            }
            $clean[$key] = $value;
        }

        return $clean;
    }
}
