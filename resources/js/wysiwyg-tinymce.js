// TinyMCE integration for WYSIWYG editors
document.addEventListener('DOMContentLoaded', function() {
    console.log('WYSIWYG TinyMCE script loaded');
    
    // Load TinyMCE from CDN first
    if (!window.tinymce) {
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js';
        script.onload = function() {
            console.log('TinyMCE library loaded');
            initializeTinyMCEEditors();
        };
        document.head.appendChild(script);
    } else {
        initializeTinyMCEEditors();
    }
});

// Reinitialize on Livewire events
document.addEventListener('livewire:navigated', function() {
    setTimeout(initializeTinyMCEEditors, 200);
});

document.addEventListener('livewire:updated', function() {
    setTimeout(initializeTinyMCEEditors, 200);
});

function initializeTinyMCEEditors() {
    const editors = document.querySelectorAll('.tinymce-editor:not([data-tinymce-initialized])');
    
    console.log('Found', editors.length, 'TinyMCE editors to initialize');
    
    editors.forEach(function(textarea) {
        const editorId = textarea.id;
        const wireModel = textarea.getAttribute('wire:model');
        
        console.log('Initializing TinyMCE for:', editorId, 'model:', wireModel);
        
        if (!editorId || !wireModel) {
            console.error('Missing ID or wire:model for TinyMCE editor');
            return;
        }
        
        // Mark as being initialized to prevent duplicates
        textarea.setAttribute('data-tinymce-initialized', 'true');
        
        tinymce.init({
            target: textarea,
            height: 200,
            menubar: false,
            plugins: 'lists link code',
            toolbar: 'undo redo | formatselect | bold italic | bullist numlist | link | removeformat',
            branding: false,
            content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; font-size: 14px; line-height: 1.6; }',
            setup: function(editor) {
                editor.on('change keyup', function() {
                    const content = editor.getContent();
                    
                    // Find the Livewire component
                    const livewireEl = textarea.closest('[wire\\:id]');
                    if (livewireEl) {
                        const componentId = livewireEl.getAttribute('wire:id');
                        const component = window.Livewire?.find(componentId);
                        
                        if (component) {
                            component.set(wireModel, content);
                        }
                    }
                });
            },
            init_instance_callback: function(editor) {
                console.log('TinyMCE editor initialized successfully:', editorId);
                
                // Set initial content from Livewire
                const livewireEl = textarea.closest('[wire\\:id]');
                if (livewireEl) {
                    const componentId = livewireEl.getAttribute('wire:id');
                    const component = window.Livewire?.find(componentId);
                    
                    if (component) {
                        const initialContent = component.get(wireModel) || '';
                        if (initialContent) {
                            editor.setContent(initialContent);
                        }
                    }
                }
            }
        });
    });
}

// Export for global access if needed
window.initializeTinyMCEEditors = initializeTinyMCEEditors;