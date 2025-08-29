// Quill Editor Integration for Livewire
window.QuillEditors = window.QuillEditors || {};

function initializeQuillEditor(editorId, wireModel, placeholder) {
    // Prevent double initialization
    if (window.QuillEditors[editorId]) {
        return;
    }

    const editorElement = document.getElementById(editorId);
    if (!editorElement) {
        console.warn(`Quill editor element not found: ${editorId}`);
        return;
    }

    // Load Quill CSS and JS if not already loaded
    loadQuillAssets().then(() => {
        try {
            const quill = new Quill('#' + editorId, {
                theme: 'snow',
                placeholder: placeholder || '',
                modules: {
                    toolbar: [
                        [{ 'header': [1, 2, 3, false] }],
                        ['bold', 'italic', 'underline'],
                        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                        ['link'],
                        ['clean']
                    ]
                }
            });

            // Store reference
            window.QuillEditors[editorId] = quill;

            // Try multiple approaches to find and integrate with Livewire
            let component = null;
            let initialContent = '';
            
            // Method 1: Try to find Livewire component directly
            const livewireElement = editorElement.closest('[wire\\:id]');
            if (livewireElement && window.Livewire) {
                try {
                    const wireId = livewireElement.getAttribute('wire:id');
                    component = window.Livewire.find(wireId);
                    console.log(`Found Livewire component for ${editorId}:`, wireId);
                } catch (error) {
                    console.warn('Method 1 failed - Could not find Livewire component:', error);
                }
            }
            
            // Method 2: Try to find the closest Livewire component using @this
            if (!component && window.Livewire) {
                try {
                    // Try to access @this from the element context
                    const livewireComponent = editorElement.closest('[x-data]');
                    if (livewireComponent && livewireComponent.__livewire) {
                        component = livewireComponent.__livewire;
                        console.log(`Found Livewire component via __livewire for ${editorId}`);
                    }
                } catch (error) {
                    console.warn('Method 2 failed:', error);
                }
            }

            if (component) {
                // Livewire integration
                try {
                    initialContent = component.get(wireModel) || '';
                    console.log(`Initial content for ${editorId}:`, initialContent.length + ' chars');
                    
                    if (initialContent) {
                        quill.root.innerHTML = initialContent;
                    }

                    // Listen for content changes and update Livewire
                    quill.on('text-change', function(delta, oldDelta, source) {
                        if (source === 'user') {
                            const content = quill.root.innerHTML;
                            try {
                                component.set(wireModel, content);
                                console.log(`Updated Livewire model ${wireModel} with ${content.length} chars`);
                            } catch (error) {
                                console.warn('Could not update Livewire model:', error);
                            }
                        }
                    });
                } catch (error) {
                    console.warn('Error setting up Livewire integration:', error);
                    component = null; // Fall back to textarea method
                }
            }
            
            if (!component) {
                console.log(`No Livewire component found for ${editorId}, trying textarea fallback`);
                // Fallback: try to sync with a hidden textarea if it exists
                const textarea = document.querySelector(`textarea[wire\\:model="${wireModel}"]`);
                if (textarea) {
                    console.log(`Found fallback textarea for ${editorId}`);
                    // Set initial content
                    if (textarea.value) {
                        quill.root.innerHTML = textarea.value;
                        console.log(`Set initial content from textarea: ${textarea.value.length} chars`);
                    }
                    
                    // Update textarea on changes
                    quill.on('text-change', function() {
                        textarea.value = quill.root.innerHTML;
                        // Trigger Livewire update
                        textarea.dispatchEvent(new Event('input', { bubbles: true }));
                    });
                } else {
                    console.warn(`No textarea fallback found for ${editorId} with model ${wireModel}`);
                }
            }

            console.log(`Quill editor initialized: ${editorId}`);
        } catch (error) {
            console.error('Failed to initialize Quill editor:', error);
        }
    });
}

function loadQuillAssets() {
    return new Promise((resolve) => {
        // Check if Quill is already loaded
        if (window.Quill) {
            resolve();
            return;
        }

        // Load CSS
        if (!document.querySelector('link[href*="quill.snow.css"]')) {
            const css = document.createElement('link');
            css.rel = 'stylesheet';
            css.href = 'https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css';
            document.head.appendChild(css);
        }

        // Load JS
        if (!document.querySelector('script[src*="quill.js"]')) {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js';
            script.onload = resolve;
            document.head.appendChild(script);
        } else {
            resolve();
        }
    });
}

// Initialize all editors
function initializeAllEditors() {
    document.querySelectorAll('[data-quill-editor]').forEach(function(element) {
        const editorId = element.getAttribute('data-quill-editor');
        const wireModel = element.getAttribute('data-wire-model');
        const placeholder = element.getAttribute('data-placeholder');
        
        if (!window.QuillEditors[editorId]) {
            initializeQuillEditor(editorId, wireModel, placeholder);
        }
    });
}

// More aggressive initialization - use MutationObserver to watch for new editors
function setupEditorObserver() {
    const observer = new MutationObserver(function(mutations) {
        let hasNewEditors = false;
        
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType === 1) { // Element node
                    // Check if the added node itself is an editor
                    if (node.hasAttribute && node.hasAttribute('data-quill-editor')) {
                        hasNewEditors = true;
                    }
                    // Check if the added node contains editors
                    else if (node.querySelectorAll) {
                        const editors = node.querySelectorAll('[data-quill-editor]');
                        if (editors.length > 0) {
                            hasNewEditors = true;
                        }
                    }
                }
            });
        });
        
        if (hasNewEditors) {
            console.log('New editors detected, initializing...');
            setTimeout(initializeAllEditors, 50);
        }
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
    
    return observer;
}

// Initialize editors immediately when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM ready, initializing editors...');
    initializeAllEditors();
    setupEditorObserver();
});

// Also try when window loads (after all resources)
window.addEventListener('load', function() {
    console.log('Window loaded, checking for missed editors...');
    setTimeout(initializeAllEditors, 100);
});

// Livewire events
document.addEventListener('livewire:navigated', function() {
    console.log('Livewire navigated, reinitializing editors...');
    setTimeout(initializeAllEditors, 50);
});

// Try to hook into various Livewire events
function setupLivewireHooks() {
    if (window.Livewire) {
        console.log('Setting up Livewire hooks...');
        
        // Livewire v3 events
        document.addEventListener('livewire:init', function() {
            console.log('Livewire init event');
            setTimeout(initializeAllEditors, 100);
        });
        
        document.addEventListener('livewire:navigated', function() {
            console.log('Livewire navigated event');
            setTimeout(initializeAllEditors, 50);
        });
        
        // Try older events too
        document.addEventListener('livewire:load', function() {
            console.log('Livewire load event');
            setTimeout(initializeAllEditors, 100);
        });
        
        document.addEventListener('livewire:updated', function() {
            console.log('Livewire updated event');
            setTimeout(initializeAllEditors, 50);
        });
    }
}

// Set up Livewire hooks immediately if available, or wait for it
if (window.Livewire) {
    setupLivewireHooks();
} else {
    // Wait for Livewire to be available
    let livewireCheckCount = 0;
    const livewireCheckInterval = setInterval(() => {
        livewireCheckCount++;
        if (window.Livewire) {
            setupLivewireHooks();
            clearInterval(livewireCheckInterval);
        } else if (livewireCheckCount > 20) { // Stop checking after 10 seconds
            clearInterval(livewireCheckInterval);
            console.warn('Livewire not found after 10 seconds');
        }
    }, 500);
}

// Aggressive fallback: check every second for the first 30 seconds
let fallbackAttempts = 0;
const maxFallbackAttempts = 30;
const fallbackInterval = setInterval(() => {
    fallbackAttempts++;
    const uninitializedEditors = document.querySelectorAll('[data-quill-editor]:not(.ql-container)');
    
    if (uninitializedEditors.length > 0) {
        console.log(`Fallback initialization attempt ${fallbackAttempts} - found ${uninitializedEditors.length} uninitialized editors`);
        initializeAllEditors();
    }
    
    if (fallbackAttempts >= maxFallbackAttempts) {
        clearInterval(fallbackInterval);
        console.log('Fallback initialization attempts completed');
    }
}, 1000);

// Clean up editors when component is destroyed
document.addEventListener('livewire:load', function() {
    Livewire.hook('component.destroyed', (component) => {
        // Clean up any Quill editors belonging to this component
        Object.keys(window.QuillEditors).forEach(editorId => {
            const editor = document.getElementById(editorId);
            if (!editor || !document.contains(editor)) {
                delete window.QuillEditors[editorId];
            }
        });
    });
});

// Make functions globally available for debugging
window.initializeQuillEditor = initializeQuillEditor;
window.initializeAllEditors = initializeAllEditors;

// Debug function to check editor status
window.debugQuillEditors = function() {
    const allEditors = document.querySelectorAll('[data-quill-editor]');
    const initializedEditors = Object.keys(window.QuillEditors);
    
    console.log('=== Quill Editor Debug Info ===');
    console.log('Total editor elements found:', allEditors.length);
    console.log('Initialized editors:', initializedEditors.length);
    console.log('Initialized editor IDs:', initializedEditors);
    
    allEditors.forEach(element => {
        const editorId = element.getAttribute('data-quill-editor');
        const wireModel = element.getAttribute('data-wire-model');
        const isInitialized = window.QuillEditors[editorId] ? 'YES' : 'NO';
        const hasQuillClass = element.classList.contains('ql-container') ? 'YES' : 'NO';
        
        console.log(`Editor ${editorId}: initialized=${isInitialized}, hasQuillClass=${hasQuillClass}, model=${wireModel}`);
    });
    
    // Try to force initialize any missing editors
    console.log('Attempting to initialize missing editors...');
    initializeAllEditors();
};