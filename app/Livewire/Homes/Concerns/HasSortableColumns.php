<?php

namespace App\Livewire\Homes\Concerns;

use Livewire\Attributes\Url;

/**
 * Column sorting for the Housing-portal list screens.
 *
 * The trait owns the shared state and the click/toggle behaviour; each screen
 * declares which columns can be sorted (sortOptions()) and supplies the
 * ordering itself, because the two lists are shaped differently: Calls &
 * Intakes is paginated, so it must sort in SQL (sorting one page would be a
 * lie); Projects already loads its whole active set, so it sorts the rows it
 * has in memory.
 *
 * An empty $sort means "the screen's own default ordering" — that is the
 * state every list starts in, so sorting is purely additive to what the
 * screens did before. Sort state rides in the URL and can be captured in a
 * saved view (see HasSavedViews), so a sorted list is shareable and stickable.
 *
 * A component using this trait must define:
 *   - sortOptions(): array                 ordered key => label registry
 */
trait HasSortableColumns
{
    /** Active sort column; '' = the screen's default ordering. */
    #[Url]
    public string $sort = '';

    /** asc | desc */
    #[Url]
    public string $sortDir = 'asc';

    /**
     * Click a column header: a new column starts in its natural direction,
     * clicking the active column again flips it, and a third click drops
     * back to the screen's default ordering.
     */
    public function sortBy(string $key): void
    {
        if (! $this->isSortable($key)) {
            return;
        }

        if ($this->sort !== $key) {
            $this->sort = $key;
            $this->sortDir = $this->naturalSortDir($key);
        } elseif ($this->sortDir === $this->naturalSortDir($key)) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->clearSort();

            return;
        }

        $this->afterSortChanged();
    }

    /**
     * The toolbar's sort dropdown writes $sort directly (wire:model.live),
     * so picking a column there lands in that column's natural direction —
     * the same place a first header click would land.
     */
    public function updatedSort(): void
    {
        if (! $this->isSortable($this->sort)) {
            $this->clearSort();

            return;
        }

        $this->sortDir = $this->naturalSortDir($this->sort);
        $this->afterSortChanged();
    }

    public function updatedSortDir(): void
    {
        $this->sortDir = $this->sortDir === 'desc' ? 'desc' : 'asc';
        $this->afterSortChanged();
    }

    /** Flip the active sort. No-op while the default ordering is in force. */
    public function toggleSortDir(): void
    {
        if ($this->sort === '') {
            return;
        }

        $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        $this->afterSortChanged();
    }

    public function clearSort(): void
    {
        $this->sort = '';
        $this->sortDir = 'asc';
        $this->afterSortChanged();
    }

    public function isSortable(string $key): bool
    {
        return array_key_exists($key, $this->sortOptions());
    }

    public function isSortedBy(string $key): bool
    {
        return $this->sort !== '' && $this->sort === $key;
    }

    /** Header indicator for a column: ▲ / ▼ when active, null otherwise. */
    public function sortArrow(string $key): ?string
    {
        if (! $this->isSortedBy($key)) {
            return null;
        }

        return $this->sortDir === 'asc' ? '▲' : '▼';
    }

    /**
     * What this screen's default ordering is called in the UI. Screens with
     * a meaningful default ordering (and both of them have one) override it.
     */
    public function defaultSortLabel(): string
    {
        return 'Usual order';
    }

    /** Plain-language label for the direction toggle's tooltip. */
    public function sortDirLabel(): string
    {
        if ($this->sort === '') {
            return 'Sorted the way this page usually sorts: '.$this->defaultSortLabel();
        }

        $label = $this->sortOptions()[$this->sort] ?? $this->sort;

        return $this->sortDir === 'asc'
            ? "{$label}: first to last — click to reverse"
            : "{$label}: last to first — click to reverse";
    }

    /**
     * Dates, amounts and progress read best biggest-first; text reads A→Z.
     * Screens override the list, not the rule.
     */
    protected function naturalSortDir(string $key): string
    {
        return in_array($key, $this->descFirstSortKeys(), true) ? 'desc' : 'asc';
    }

    protected function descFirstSortKeys(): array
    {
        return [];
    }

    /**
     * Sort state, coerced to something safe to hand a query or a sorter.
     * Returns ['' , 'asc'] when no legal explicit sort is set.
     */
    protected function activeSort(): array
    {
        $key = $this->isSortable($this->sort) ? $this->sort : '';
        $dir = $this->sortDir === 'desc' ? 'desc' : 'asc';

        return [$key, $key === '' ? 'asc' : $dir];
    }

    /** Hook for the consuming component (e.g. resetPage()). */
    protected function afterSortChanged(): void {}

    /** key => label of the columns this screen can sort on, in display order. */
    abstract public function sortOptions(): array;
}
