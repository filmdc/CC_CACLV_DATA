<div wire:ignore class="wysiwyg-wrapper {{ $class }}">
    <div 
        id="{{ $id }}" 
        data-quill-editor="{{ $id }}"
        data-wire-model="{{ $model }}"
        data-placeholder="{{ $placeholder }}"
        style="min-height: {{ $rows * 20 + 60 }}px;" 
        class="bg-white dark:bg-zinc-700 rounded-md">
    </div>
</div>

@once
@push('styles')
<style>
    /* Dark mode styles for Quill editor */
    .dark .ql-toolbar {
        background-color: rgb(63, 63, 70) !important;
        border-color: rgb(82, 82, 91) !important;
    }
    
    .dark .ql-toolbar .ql-formats button {
        color: rgb(244, 244, 245) !important;
    }
    
    .dark .ql-toolbar .ql-formats button:hover {
        background-color: rgb(82, 82, 91) !important;
    }
    
    .dark .ql-toolbar .ql-formats button.ql-active {
        background-color: rgb(99, 102, 241) !important;
    }
    
    .dark .ql-editor {
        background-color: rgb(63, 63, 70) !important;
        color: rgb(244, 244, 245) !important;
        border-color: rgb(82, 82, 91) !important;
    }
    
    .dark .ql-container {
        border-color: rgb(82, 82, 91) !important;
    }
    
    /* Custom toolbar styling */
    .ql-toolbar.ql-snow {
        border-top: 1px solid #cbd5e1;
        border-left: 1px solid #cbd5e1;
        border-right: 1px solid #cbd5e1;
        border-radius: 0.375rem 0.375rem 0 0;
    }
    
    .dark .ql-toolbar.ql-snow {
        border-color: rgb(82, 82, 91);
    }
    
    .ql-container.ql-snow {
        border-bottom: 1px solid #cbd5e1;
        border-left: 1px solid #cbd5e1;
        border-right: 1px solid #cbd5e1;
        border-radius: 0 0 0.375rem 0.375rem;
    }
    
    .dark .ql-container.ql-snow {
        border-color: rgb(82, 82, 91);
    }
    
    /* Ensure proper sizing */
    .ql-container {
        font-size: 14px;
    }
    
    .ql-editor {
        padding: 12px;
    }
    
    /* Fix for dark mode text visibility */
    .dark .ql-editor.ql-blank::before {
        color: rgb(161, 161, 170) !important;
    }
</style>
@endpush
@endonce