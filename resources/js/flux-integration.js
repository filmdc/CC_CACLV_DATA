// Flux UI Integration with Livewire
// This file ensures Flux components work properly with Livewire's DOM morphing

document.addEventListener('DOMContentLoaded', () => {
    console.log('Initializing Flux integration');
    
    // Listen for Livewire navigation events
    document.addEventListener('livewire:navigated', () => {
        console.log('Livewire navigation detected, reinitializing Flux components');
        reinitializeAllFluxComponents();
    });
    
    // Listen for custom flux reinit events
    document.addEventListener('flux:reinit', (event) => {
        const container = event.detail?.container || document.body;
        // console.log('Flux reinit requested for container:', container); // Too noisy
        reinitializeFluxInContainer(container);
    });
    
    // Monitor for Flux availability
    let fluxCheckInterval;
    let fluxCheckAttempts = 0;
    const maxFluxCheckAttempts = 50; // 5 seconds max wait
    
    function checkFluxAvailability() {
        fluxCheckAttempts++;
        
        if (window.Flux || fluxCheckAttempts >= maxFluxCheckAttempts) {
            if (window.Flux) {
                console.log('Flux UI detected and ready');
                setupFluxHooks();
            } else {
                console.warn('Flux UI not detected after timeout');
            }
            clearInterval(fluxCheckInterval);
        }
    }
    
    // Check every 100ms for Flux to be available
    fluxCheckInterval = setInterval(checkFluxAvailability, 100);
});

function setupFluxHooks() {
    // Override or extend Flux initialization if needed
    const originalInit = window.Flux?.init;
    
    if (originalInit && typeof originalInit === 'function') {
        window.Flux.init = function(container) {
            console.log('Flux init called with container:', container);
            return originalInit.call(this, container);
        };
    }
    
    // Setup mutation observer to catch dynamically added Flux components
    const observer = new MutationObserver((mutations) => {
        let shouldReinit = false;
        
        for (const mutation of mutations) {
            if (mutation.type === 'childList') {
                for (const node of mutation.addedNodes) {
                    if (node.nodeType === 1 && containsFluxComponent(node)) {
                        shouldReinit = true;
                        break;
                    }
                }
            }
        }
        
        if (shouldReinit) {
            // Debounce reinitialization
            clearTimeout(window.fluxReinitTimeout);
            window.fluxReinitTimeout = setTimeout(() => {
                reinitializeAllFluxComponents();
            }, 100);
        }
    });
    
    // Start observing the document body for changes
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
}

function containsFluxComponent(element) {
    if (!element || !element.querySelector) return false;
    
    // Check for common Flux component indicators
    const fluxSelectors = [
        '[x-data*="flux"]',
        '[data-flux]',
        '[class*="flux-"]',
        'flux-button',
        'flux-input',
        'flux-modal',
        'flux-dropdown',
        'flux-select'
    ];
    
    return fluxSelectors.some(selector => {
        try {
            return element.matches(selector) || element.querySelector(selector);
        } catch (e) {
            return false;
        }
    });
}

function reinitializeAllFluxComponents() {
    reinitializeFluxInContainer(document.body);
}

function reinitializeFluxInContainer(container) {
    if (!container || !window.Flux) return;
    
    try {
        // Call Flux's init method if available
        if (typeof window.Flux.init === 'function') {
            window.Flux.init(container);
        }
        
        // Reinitialize Alpine components within Flux components
        if (window.Alpine) {
            const alpineComponents = container.querySelectorAll('[x-data]');
            alpineComponents.forEach(el => {
                // Only reinitialize if not already initialized
                if (!el._x_dataStack) {
                    Alpine.initTree(el);
                }
            });
        }
        
        // Specific Flux component reinitialization
        reinitializeModals(container);
        reinitializeDropdowns(container);
        reinitializeSelects(container);
        reinitializeTooltips(container);
    } catch (error) {
        console.error('Error reinitializing Flux components:', error);
    }
}

function reinitializeModals(container) {
    // Find all modal triggers and ensure they're properly bound
    const modalTriggers = container.querySelectorAll('[data-flux-modal-trigger], [x-on\\:click*="$modal"]');
    modalTriggers.forEach(trigger => {
        // Flux modals are typically handled by Alpine.js
        // Ensure the click handlers are properly bound
        if (window.Alpine && trigger.hasAttribute('x-data')) {
            Alpine.initTree(trigger);
        }
    });
}

function reinitializeDropdowns(container) {
    // Similar reinitialization for dropdowns
    const dropdowns = container.querySelectorAll('[data-flux-dropdown], [x-data*="dropdown"]');
    dropdowns.forEach(dropdown => {
        if (window.Alpine && dropdown.hasAttribute('x-data')) {
            Alpine.initTree(dropdown);
        }
    });
}

function reinitializeSelects(container) {
    // Reinitialize select components
    const selects = container.querySelectorAll('[data-flux-select], flux-select');
    selects.forEach(select => {
        if (window.Alpine && select.hasAttribute('x-data')) {
            Alpine.initTree(select);
        }
    });
}

function reinitializeTooltips(container) {
    // Reinitialize tooltip components
    const tooltips = container.querySelectorAll('[data-flux-tooltip], [x-tooltip]');
    tooltips.forEach(tooltip => {
        if (window.Alpine && tooltip.hasAttribute('x-data')) {
            Alpine.initTree(tooltip);
        }
    });
}

// Export functions for external use
window.FluxIntegration = {
    reinitializeAll: reinitializeAllFluxComponents,
    reinitializeContainer: reinitializeFluxInContainer,
    checkFluxComponent: containsFluxComponent
};

console.log('Flux integration script loaded');