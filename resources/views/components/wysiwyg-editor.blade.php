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

@once
@push('scripts')
<script>
// Global Jodit Editor Manager - handles multiple editors safely
window.JoditEditorManager = {
    editors: {},
    
    initializeEditor(elementId, model, component) {
        // Destroy existing editor if present
        if (this.editors[elementId]) {
            this.editors[elementId].destruct();
            delete this.editors[elementId];
        }
        
        const element = document.getElementById(elementId);
        if (!element) return;
        
        // Create editor instance
        const editor = Jodit.make(element, {
            "autofocus": false,
            "toolbarSticky": false,
            "uploader": {
                "insertImageAsBase64URI": true
            },
            "toolbarButtonSize": "medium",
            "showCharsCounter": false,
            "showWordsCounter": false,
            "showXPathInStatusbar": false,
            "defaultActionOnPaste": "insert_clear_html",
            "buttons": [
                'bold', 'italic', 'underline', '|',
                'ul', 'ol', '|',
                'left', 'center', 'right', '|',
                'link', 'image', '|',
                'undo', 'redo'
            ],
            "height": 200
        });
        
        // Store editor reference
        this.editors[elementId] = editor;
        
        // Set up change event with debouncing
        let timeout;
        editor.events.on('change', (value) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                component.set(model, value);
            }, 300);
        });
        
        console.log(`Jodit editor initialized for: ${elementId}`);
        return editor;
    },
    
    initializeAllEditors() {
        const textareas = document.querySelectorAll('.jodit-textarea');
        textareas.forEach(textarea => {
            const elementId = textarea.getAttribute('data-editor-id');
            const model = textarea.getAttribute('data-model');
            
            if (elementId && model && window.Livewire) {
                const component = Livewire.find(textarea.closest('[wire\\:id]').getAttribute('wire:id'));
                if (component) {
                    this.initializeEditor(elementId, model, component);
                }
            }
        });
    },
    
    destroyAll() {
        Object.values(this.editors).forEach(editor => {
            if (editor && editor.destruct) {
                editor.destruct();
            }
        });
        this.editors = {};
    }
};

// Initialize when Livewire is ready
document.addEventListener('livewire:initialized', () => {
    console.log('Initializing Jodit editors...');
    console.log('Available textareas:', document.querySelectorAll('.jodit-textarea').length);
    setTimeout(() => {
        JoditEditorManager.initializeAllEditors();
    }, 100);
});

// Also try initializing when DOM is ready (fallback)
document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM ready - checking for Jodit textareas...');
    setTimeout(() => {
        if (window.Jodit && document.querySelectorAll('.jodit-textarea').length > 0) {
            console.log('Found textareas, initializing editors...');
            JoditEditorManager.initializeAllEditors();
        }
    }, 500);
});

// Reinitialize on Livewire updates
document.addEventListener('livewire:navigated', () => {
    setTimeout(() => {
        JoditEditorManager.initializeAllEditors();
    }, 100);
});

// Clean up on page unload
window.addEventListener('beforeunload', () => {
    JoditEditorManager.destroyAll();
});
</script>
@endpush
@endonce