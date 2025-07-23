// Global JavaScript fixes for common issues in the CAP TRACK system

// Fix for Livewire button click handlers
document.addEventListener('livewire:init', () => {
    console.log('Applying global Livewire fixes');
    
    // Fix for buttons that don't respond to clicks
    Livewire.hook('morph.updated', (params) => {
        // Safely destructure parameters
        const { el, component } = params || {};
        
        // Safety checks
        if (!component || !el) return;
        
        // Re-enable all buttons that might have been disabled
        const buttons = el.querySelectorAll('button[wire\\:click]');
        buttons.forEach(button => {
            // Remove any stuck disabled states
            if (button.hasAttribute('wire:loading.attr') && 
                button.getAttribute('wire:loading.attr') === 'disabled' &&
                !component.loading) {
                button.removeAttribute('disabled');
            }
        });
    });
    
    // Fix for loading animations that get stuck
    Livewire.hook('request', (params) => {
        // Safely destructure parameters
        const { component } = params || {};
        
        // Safety check for component
        if (!component) return;
        
        // Track loading state
        component.loading = true;
    });
    
    Livewire.hook('response', (params) => {
        // Safely destructure parameters
        const { component } = params || {};
        
        // Safety check for component
        if (!component) return;
        
        // Clear loading state
        component.loading = false;
        
        // Force clear any stuck loading animations after a delay
        setTimeout(() => {
            if (!component || !component.id) return;
            
            const componentEl = document.querySelector(`[wire\\:id="${component.id}"]`);
            if (!componentEl) return;
            
            // Only clear loading states within this specific component
            const loadingElements = componentEl.querySelectorAll('[wire\\:loading]:not([wire\\:loading\\.remove])');
            loadingElements.forEach(el => {
                // Check if component is still not loading
                if (!component.loading && el.style.display !== 'none') {
                    el.style.display = 'none';
                }
            });
            
            // Clear loading spinners within this component
            const spinners = componentEl.querySelectorAll('.animate-spin');
            spinners.forEach(spinner => {
                const parent = spinner.closest('[wire\\:loading]');
                if (parent && !component.loading && parent.style.display !== 'none') {
                    parent.style.display = 'none';
                }
            });
        }, 1000);
    });
});

// Fix for Alpine.js components that don't initialize properly
document.addEventListener('alpine:init', () => {
    console.log('Applying Alpine.js fixes');
    
    // Add global Alpine data for common patterns
    Alpine.data('preventDoubleClick', () => ({
        processing: false,
        
        handleClick(callback) {
            if (this.processing) return;
            
            this.processing = true;
            
            // Call the callback
            Promise.resolve(callback()).finally(() => {
                // Reset after a delay to prevent double clicks
                setTimeout(() => {
                    this.processing = false;
                }, 1000);
            });
        }
    }));
});

// Fix for charts that don't load on tab switch
document.addEventListener('DOMContentLoaded', () => {
    // Listen for tab changes globally
    document.addEventListener('click', (e) => {
        const tabButton = e.target.closest('button[wire\\:click*="Tab"]');
        if (tabButton) {
            // Get the tab name from the wire:click attribute
            const wireClick = tabButton.getAttribute('wire:click');
            if (wireClick && wireClick.includes('visualizations')) {
                // Trigger chart initialization after a delay
                setTimeout(() => {
                    // Try both reporting and CSBG chart initializations
                    if (typeof window.testChartInit === 'function') {
                        window.testChartInit();
                    }
                    if (typeof initializeCharts === 'function') {
                        initializeCharts();
                    }
                }, 300);
            }
        }
    });
});

// Global error handler for better debugging
window.addEventListener('error', (e) => {
    if (e.message && e.message.includes('Chart')) {
        console.warn('Chart.js error detected, attempting recovery');
        // Don't prevent default handling, just log for debugging
    }
});

// Fix for wire:confirm dialogs that don't work
document.addEventListener('click', (e) => {
    const confirmButton = e.target.closest('[wire\\:confirm]');
    if (confirmButton && confirmButton.hasAttribute('disabled')) {
        // Remove disabled state if it's stuck
        const component = Livewire.find(confirmButton.closest('[wire\\:id]').getAttribute('wire:id'));
        if (component && !component.loading) {
            confirmButton.removeAttribute('disabled');
        }
    }
});

// Performance optimization: Debounce rapid Livewire updates
let updateTimeout;
document.addEventListener('livewire:update', () => {
    clearTimeout(updateTimeout);
    updateTimeout = setTimeout(() => {
        // Clean up any orphaned loading states
        const allLoadingElements = document.querySelectorAll('[wire\\:loading]');
        allLoadingElements.forEach(el => {
            const component = el.closest('[wire\\:id]');
            if (component) {
                const livewireComponent = Livewire.find(component.getAttribute('wire:id'));
                if (livewireComponent && !livewireComponent.loading) {
                    el.style.display = 'none';
                }
            }
        });
    }, 500);
});

console.log('Global fixes loaded');