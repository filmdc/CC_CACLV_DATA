import Chart from 'chart.js/auto';
import './livewire-helpers.js';
import './livewire-hooks.js';
import './global-fixes.js';
import './flux-integration.js';
import './cancel-button-fix.js';

// Make Chart.js available globally
window.Chart = Chart;

// Jodit Editor Initialization - Direct approach
document.addEventListener('DOMContentLoaded', () => {
    console.log('App.js - DOM ready, checking for Jodit...');
    
    // Wait for Jodit to load
    const waitForJodit = () => {
        if (window.Jodit) {
            console.log('App.js - Jodit is available');
            initializeJoditEditors();
        } else {
            setTimeout(waitForJodit, 100);
        }
    };
    
    setTimeout(waitForJodit, 500);
});

function initializeJoditEditors() {
    const textareas = document.querySelectorAll('.jodit-textarea');
    console.log(`App.js - Found ${textareas.length} jodit textareas`);
    
    textareas.forEach((textarea, index) => {
        const editorId = textarea.id;
        console.log(`App.js - Initializing editor: ${editorId}`);
        
        if (editorId && window.Jodit) {
            const editor = Jodit.make(`#${editorId}`, {
                "autofocus": false,
                "toolbarSticky": false,
                "toolbarButtonSize": "medium",
                "showCharsCounter": false,
                "showWordsCounter": false,
                "showXPathInStatusbar": false,
                "buttons": [
                    'bold', 'italic', 'underline', '|',
                    'ul', 'ol', '|',
                    'link', '|',
                    'undo', 'redo'
                ],
                "height": 150
            });
            
            // Sync with Livewire
            editor.events.on('change', (value) => {
                const model = textarea.getAttribute('data-model');
                const component = Livewire.find(textarea.closest('[wire\\:id]').getAttribute('wire:id'));
                if (component && model) {
                    component.set(model, value);
                }
            });
            
            console.log(`App.js - Editor initialized successfully: ${editorId}`);
        }
    });
}