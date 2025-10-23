<div wire:ignore>
    <textarea
        wire:model="{{ $model }}"
        id="{{ $id }}"
        rows="{{ $rows }}"
        placeholder="{{ $placeholder }}"
        class="jodit-textarea w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
        data-model="{{ $model }}"
        data-editor-id="{{ $id }}"
    ></textarea>
</div>