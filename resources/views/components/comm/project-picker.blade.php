@props([
    'projects' => [],          // array<['id'=>int,'job_number'=>string,'name'=>string,'meta'=>string]>
    'searchModel' => 'projectPickerSearch',  // wire:model property on parent
    'selectMethod' => 'selectProject',       // wire method to call with (id|null)
])

{{--
    Command-palette project picker per design spec §4.3.

    Activation: listens for `open-project-picker` window event. ⌘P from
    anywhere in the form dispatches it (handled by the form's keymap).

    Always includes "General intake" as the first row (id=null sentinel).

    Keyboard:
      ↑/↓  navigate
      ⏎    select highlighted
      Esc  close
      N    open "create new project" (TODO: not wired in v1)

    The parent component provides `projects` as already-fetched + sorted
    by lastEntryAt desc. Search is wire:model.debounce → re-fetches.
--}}
<div
    x-data="{
        open: false,
        highlighted: 0,
        get rows() {
            // First row is always General intake (null sentinel)
            return [{ id: null, code: '—', name: 'General intake', meta: 'Detach from project' }, ...@js($projects)];
        },
        openPicker() {
            this.open = true;
            this.highlighted = 0;
            this.$nextTick(() => this.$refs.input && this.$refs.input.focus());
        },
        close() { this.open = false; },
        moveDown() { this.highlighted = Math.min(this.highlighted + 1, this.rows.length - 1); },
        moveUp() { this.highlighted = Math.max(this.highlighted - 1, 0); },
        select(row) {
            $wire.call('{{ $selectMethod }}', row.id);
            this.close();
        },
        onKeyDown(e) {
            if (e.key === 'ArrowDown') { e.preventDefault(); this.moveDown(); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); this.moveUp(); }
            else if (e.key === 'Enter') {
                e.preventDefault();
                const row = this.rows[this.highlighted];
                if (row) this.select(row);
            } else if (e.key === 'Escape') { this.close(); }
        },
    }"
    x-on:open-project-picker.window="openPicker()"
    x-on:keydown.window.meta.p.prevent="openPicker()"
    x-on:keydown.window.ctrl.p.prevent="openPicker()"
    {{ $attributes }}
>
    <template x-if="open">
        <div class="fixed inset-0 z-50 flex items-start justify-center bg-calv-slate/40 backdrop-blur-sm pt-24"
             x-on:click.self="close()">

            <div class="w-full max-w-xl bg-white rounded-calv-md shadow-calv-lg border border-calv-slate-15"
                 x-on:keydown="onKeyDown">

                {{-- Search input --}}
                <div class="flex items-center gap-2 px-4 py-3 border-b border-calv-slate-15">
                    <x-lucide name="search" :size="16" class="text-calv-slate-65" />
                    <input
                        x-ref="input"
                        type="text"
                        wire:model.live.debounce.150ms="{{ $searchModel }}"
                        placeholder="Search project name, code, lead…"
                        class="flex-1 border-0 p-0 text-[14px] focus:outline-none focus:ring-0 bg-transparent text-calv-slate placeholder:text-calv-slate-65"
                        x-on:input="highlighted = 0"
                    />
                    <span class="text-[11px] text-calv-slate-65"><x-comm.kbd>Esc</x-comm.kbd> close</span>
                </div>

                {{-- Rows --}}
                <div class="max-h-96 overflow-y-auto py-1">
                    <template x-for="(row, idx) in rows" :key="row.id ?? 'general-intake'">
                        <button
                            type="button"
                            x-on:click="select(row)"
                            x-on:mouseenter="highlighted = idx"
                            :class="{ 'bg-calv-sand-15': highlighted === idx, 'border-b border-calv-slate-15': row.id === null }"
                            class="w-full flex items-center gap-3 px-4 py-2.5 text-left hover:bg-calv-sand-15">

                            <span class="inline-flex items-center px-2 py-0.5 rounded-calv-sm bg-calv-teal-15 text-calv-teal font-mono text-[11px] min-w-[64px] justify-center"
                                  x-text="row.code"></span>

                            <div class="min-w-0 flex-1">
                                <div class="text-[13.5px] text-calv-slate truncate" x-text="row.name"></div>
                                <div class="text-[11px] text-calv-slate-65 truncate" x-text="row.meta"></div>
                            </div>

                            <template x-if="highlighted === idx">
                                <span class="text-[11px] text-calv-slate-65"><x-comm.kbd>⏎</x-comm.kbd></span>
                            </template>
                        </button>
                    </template>
                </div>

                {{-- Footer --}}
                <div class="px-4 py-2 border-t border-calv-slate-15 bg-calv-sand-15 text-[11px] text-calv-slate-65 flex items-center justify-between rounded-b-calv-md">
                    <span><x-comm.kbd>↑</x-comm.kbd> <x-comm.kbd>↓</x-comm.kbd> navigate</span>
                    <span><x-comm.kbd>⏎</x-comm.kbd> select</span>
                </div>
            </div>
        </div>
    </template>
</div>
