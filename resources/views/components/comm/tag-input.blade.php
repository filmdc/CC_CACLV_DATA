@props([
    'modelName' => 'tags',             // wire:model target on the parent
    'placeholder' => 'Add tags…',
    'existingTags' => [],              // array of strings — for autocomplete
    'selectedTags' => [],              // current selection (array of strings)
])

{{--
    Alpine combobox for free-form tag search + create.
    Emits Livewire events:
      - tags-changed (selected: string[])  on every add/remove
--}}
<div
    x-data="{
        open: false,
        query: '',
        selected: @js($selectedTags),
        existing: @js($existingTags),
        highlightedIndex: -1,
        get filtered() {
            const q = this.query.trim().toLowerCase();
            if (!q) return this.existing.filter(t => !this.selected.includes(t)).slice(0, 10);
            return this.existing
                .filter(t => t.toLowerCase().includes(q) && !this.selected.includes(t))
                .slice(0, 10);
        },
        get canCreate() {
            const q = this.query.trim();
            return q.length > 0
                && !this.existing.some(t => t.toLowerCase() === q.toLowerCase())
                && !this.selected.includes(q);
        },
        add(tag) {
            if (!tag || this.selected.includes(tag)) return;
            this.selected = [...this.selected, tag];
            this.query = '';
            this.highlightedIndex = -1;
            $dispatch('tags-changed', { selected: this.selected });
            $wire.set('{{ $modelName }}', this.selected);
        },
        remove(tag) {
            this.selected = this.selected.filter(t => t !== tag);
            $dispatch('tags-changed', { selected: this.selected });
            $wire.set('{{ $modelName }}', this.selected);
        },
        onKeyDown(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (this.highlightedIndex >= 0 && this.filtered[this.highlightedIndex]) {
                    this.add(this.filtered[this.highlightedIndex]);
                } else if (this.canCreate) {
                    this.add(this.query.trim());
                }
            } else if (e.key === 'ArrowDown') {
                e.preventDefault();
                this.highlightedIndex = Math.min(this.highlightedIndex + 1, this.filtered.length - 1);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                this.highlightedIndex = Math.max(this.highlightedIndex - 1, -1);
            } else if (e.key === 'Backspace' && !this.query && this.selected.length) {
                this.selected = this.selected.slice(0, -1);
                $wire.set('{{ $modelName }}', this.selected);
            } else if (e.key === 'Escape') {
                this.open = false;
            }
        },
    }"
    {{ $attributes->merge(['class' => 'relative']) }}
>
    {{-- selected chips + input --}}
    <div class="flex flex-wrap items-center gap-1.5 min-h-[42px] px-2 py-1.5 border border-calv-slate-35 rounded-calv-sm bg-white focus-within:border-calv-red focus-within:shadow-calv-focus"
         x-on:click="$refs.input.focus()">
        <template x-for="tag in selected" :key="tag">
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-calv-pill bg-calv-teal-15 text-calv-teal text-[11px] font-medium">
                <span x-text="tag"></span>
                <button type="button" x-on:click.stop="remove(tag)" class="hover:text-calv-red">
                    <x-lucide name="x" :size="12" />
                </button>
            </span>
        </template>
        <input
            x-ref="input"
            type="text"
            x-model="query"
            x-on:focus="open = true"
            x-on:blur="setTimeout(() => open = false, 150)"
            x-on:keydown="onKeyDown"
            placeholder="{{ $placeholder }}"
            class="flex-1 min-w-[120px] border-0 p-0 text-[13.5px] focus:outline-none focus:ring-0 bg-transparent text-calv-slate placeholder:text-calv-slate-65" />
    </div>

    {{-- dropdown --}}
    <div x-show="open && (filtered.length || canCreate)" x-cloak
         class="absolute z-30 mt-1 w-full bg-white border border-calv-slate-35 rounded-calv-sm shadow-calv-md max-h-64 overflow-y-auto">
        <template x-for="(tag, idx) in filtered" :key="tag">
            <button type="button"
                    x-on:mousedown.prevent="add(tag)"
                    x-on:mouseenter="highlightedIndex = idx"
                    :class="{ 'bg-calv-sand-15': highlightedIndex === idx }"
                    class="w-full flex items-center gap-2 px-3 py-1.5 text-left text-[13px] text-calv-slate hover:bg-calv-sand-15">
                <x-lucide name="tag" :size="12" />
                <span x-text="tag"></span>
            </button>
        </template>
        <template x-if="canCreate">
            <button type="button"
                    x-on:mousedown.prevent="add(query.trim())"
                    class="w-full flex items-center gap-2 px-3 py-1.5 text-left text-[13px] text-calv-teal hover:bg-calv-teal-15 border-t border-calv-slate-15">
                <x-lucide name="plus" :size="12" />
                <span>Create "<span x-text="query.trim()"></span>"</span>
            </button>
        </template>
    </div>
</div>
