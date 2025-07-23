// Fix for cancel buttons using incorrect $set syntax
// This is a temporary fix until all buttons are updated to use proper Livewire methods

document.addEventListener('DOMContentLoaded', () => {
    console.log('Cancel button fix loaded');
    
    // Fix existing buttons with incorrect $set syntax
    function fixSetButtons() {
        const buttons = document.querySelectorAll('[wire\\:click*="$set("]');
        
        buttons.forEach(button => {
            const wireClick = button.getAttribute('wire:click');
            if (!wireClick || !wireClick.includes('$set(')) return;
            
            // Create a new click handler
            button.removeAttribute('wire:click');
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Find the Livewire component
                const component = this.closest('[wire\\:id]');
                if (!component) return;
                
                const componentId = component.getAttribute('wire:id');
                const livewireComponent = window.Livewire.find(componentId);
                if (!livewireComponent) return;
                
                // Parse and execute the $set calls
                const setPattern = /\$set\('([^']+)',\s*([^)]+)\)/g;
                let match;
                
                while ((match = setPattern.exec(wireClick)) !== null) {
                    const property = match[1];
                    let value = match[2].trim();
                    
                    // Parse the value
                    if (value === 'true') value = true;
                    else if (value === 'false') value = false;
                    else if (value === 'null') value = null;
                    else if (value.startsWith("'") && value.endsWith("'")) {
                        value = value.slice(1, -1);
                    }
                    
                    try {
                        livewireComponent.$set(property, value);
                    } catch (error) {
                        console.error('Error setting property:', property, error);
                    }
                }
            });
        });
    }
    
    // Run the fix initially
    fixSetButtons();
    
    // Re-run after Livewire navigations
    document.addEventListener('livewire:navigated', () => {
        setTimeout(fixSetButtons, 100);
    });
    
    // Re-run after Livewire updates
    Livewire.hook('morph.updated', ({ el, component }) => {
        setTimeout(fixSetButtons, 100);
    });
});