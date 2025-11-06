<?php

namespace App\Livewire;

use Livewire\Component;

class SearchableSelect extends Component
{
    public $items = [];
    public $selected = '';
    public $name = 'item';
    public $placeholder = 'Search...';
    public $displayField = 'display';
    public $secondaryField = 'secondary';
    public $required = false;
    public $emptyMessage = 'No items available';

    public $search = '';
    public $isOpen = false;
    public $highlightedIndex = 0;

    protected $listeners = ['closeAllDropdowns'];

    public function mount(
        $items = [],
        $selected = '',
        $name = 'item',
        $placeholder = 'Search...',
        $displayField = 'display',
        $secondaryField = 'secondary',
        $required = false,
        $emptyMessage = 'No items available'
    ) {
        $this->items = $items;
        $this->selected = $selected;
        $this->name = $name;
        $this->placeholder = $placeholder;
        $this->displayField = $displayField;
        $this->secondaryField = $secondaryField;
        $this->required = $required;
        $this->emptyMessage = $emptyMessage;
    }

    public function updatedSearch()
    {
        $this->highlightedIndex = 0;
    }

    public function toggleDropdown()
    {
        if ($this->isOpen) {
            $this->closeDropdown();
        } else {
            $this->openDropdown();
        }
    }

    public function openDropdown()
    {
        // Close all other dropdowns first
        $this->dispatch('closeAllDropdowns');
        $this->isOpen = true;
        $this->search = '';
        $this->highlightedIndex = 0;
    }

    public function closeDropdown()
    {
        $this->isOpen = false;
        $this->search = '';
    }

    public function closeAllDropdowns()
    {
        $this->isOpen = false;
    }

    public function selectItem($itemId)
    {
        $this->selected = $itemId;
        $this->isOpen = false;
        $this->search = '';

        // Emit event to parent component
        $this->dispatch('item-selected-' . $this->name, $itemId);

        // Also log for debugging
        \Log::info('SearchableSelect item selected', [
            'name' => $this->name,
            'item_id' => $itemId,
            'event_name' => 'item-selected-' . $this->name,
        ]);
    }

    public function clearSelection()
    {
        $this->selected = '';
        $this->dispatch('item-selected-' . $this->name, '');
    }

    public function getFilteredItemsProperty()
    {
        if (empty($this->search)) {
            return $this->items;
        }

        $search = strtolower($this->search);

        return array_filter($this->items, function($item) use ($search) {
            $display = strtolower($item[$this->displayField] ?? '');
            $secondary = strtolower($item[$this->secondaryField] ?? '');

            return str_contains($display, $search) || str_contains($secondary, $search);
        });
    }

    public function getSelectedItemProperty()
    {
        if (empty($this->selected)) {
            return null;
        }

        foreach ($this->items as $item) {
            if ($item['id'] == $this->selected) {
                return $item;
            }
        }

        return null;
    }

    public function render()
    {
        return view('livewire.searchable-select', [
            'filteredItems' => $this->filteredItems,
            'selectedItem' => $this->selectedItem,
        ]);
    }
}
