// Livewire Hooks Configuration
// This file sets up all Livewire lifecycle hooks for better control and debugging

document.addEventListener('livewire:init', () => {
    console.log('Initializing Livewire hooks');
    
    // Wrap all hooks in try-catch to prevent breaking Livewire
    try {
        setupLivewireHooks();
    } catch (e) {
        console.error('Error setting up Livewire hooks:', e);
    }
});

function setupLivewireHooks() {
    // Component initialization hook
    Livewire.hook('component.init', ({ component, cleanup }) => {
        if (!component) return;
        
        // Get component identifier (handle Volt components that may not have a name)
        const componentId = getComponentIdentifier(component);
        console.log(`Component initialized: ${componentId}`);
        
        // Track component state
        component._customState = {
            initialized: true,
            requestCount: 0,
            lastUpdate: Date.now(),
            lastRequestTime: null,
            identifier: componentId
        };
        
        // Cleanup on component destruction
        cleanup(() => {
            if (!component) return;
            
            const cleanupId = component._customState?.identifier || 'unknown';
            console.log(`Component cleanup: ${cleanupId}`);
            
            // Destroy any charts if this is a chart component
            if (componentId && (componentId.includes('reporting') || componentId.includes('csbg'))) {
                if (window.ChartManager) {
                    window.ChartManager.destroyAll();
                }
            }
        });
    });
    
    // Before morphing DOM
    Livewire.hook('morph.updating', ({ el, component, toEl, skip, childrenOnly }) => {
        // Preserve chart containers during morph
        if (el.id === 'chartContainer' || el.querySelector('canvas')) {
            skip();
        }
    });
    
    // After morphing DOM
    Livewire.hook('morph.updated', ({ el, component }) => {
        if (!el || !component) return;
        
        // Re-initialize any Alpine components that were morphed
        if (el.hasAttribute('x-data') && window.Alpine) {
            Alpine.initTree(el);
        }
        
        // Fix stuck buttons
        fixStuckButtons(el);
        
        // Re-initialize tooltips if any
        initializeTooltips(el);
        
        // Re-initialize Flux UI components if present
        reinitializeFluxComponents(el);
    });
    
    // Before request
    Livewire.hook('request', (params) => {
        try {
            const { component, options } = params || {};
            
            // Safety check - component might be undefined
            if (!component) {
                console.warn('Request hook called with undefined component');
                return;
            }
            
            // Ensure _customState exists
            if (!component._customState) {
                component._customState = {
                    initialized: true,
                    requestCount: 0,
                    lastUpdate: Date.now(),
                    lastRequestTime: null,
                    identifier: getComponentIdentifier(component)
                };
            }
            
            component._customState.requestCount++;
            component._customState.lastRequestTime = Date.now();
            
            const componentId = component._customState?.identifier || getComponentIdentifier(component);
            console.log(`Request started: ${componentId} (${options?.method || 'unknown'})`);
            
            // Add timeout protection
            const timeoutId = setTimeout(() => {
                if (!component || !component._customState) return;
                const timeoutName = component._customState?.identifier || 'unknown';
                console.warn(`Request timeout: ${timeoutName}`);
                // Force clear loading states
                clearComponentLoadingStates(component);
            }, 30000); // 30 second timeout
            
            if (component) {
                component._timeoutId = timeoutId;
            }
        } catch (e) {
            console.error('Error in request hook:', e);
        }
    });
    
    // After successful response
    Livewire.hook('response', ({ component, response }) => {
        // Clear timeout
        if (component._timeoutId) {
            clearTimeout(component._timeoutId);
        }
        
        const componentId = component._customState?.identifier || getComponentIdentifier(component);
        console.log(`Response received: ${componentId}`);
        
        // Update state if _customState exists
        if (component._customState) {
            component._customState.lastUpdate = Date.now();
        }
        
        // Clear any stuck loading states
        setTimeout(() => {
            clearComponentLoadingStates(component);
        }, 100);
    });
    
    // Handle errors
    Livewire.hook('response.error', ({ component, response }) => {
        if (!component) return;
        
        const componentId = component._customState?.identifier || getComponentIdentifier(component);
        console.error(`Error in component: ${componentId}`, response);
        
        // Clear timeout if exists
        if (component._timeoutId) {
            clearTimeout(component._timeoutId);
            component._timeoutId = null;
        }
        
        // Clear loading states on error
        clearComponentLoadingStates(component);
        
        // Show user-friendly error message
        showErrorNotification('An error occurred. Please try again.');
    });
    
    // Element initialization
    Livewire.hook('element.init', ({ el, component }) => {
        if (!el || !component) return;
        
        // Add data attributes for debugging
        const componentId = getComponentIdentifier(component);
        if (componentId && componentId !== 'unknown') {
            el.setAttribute('data-livewire-component', componentId);
        }
        if (component.id) {
            el.setAttribute('data-livewire-id', component.id);
        }
    });
    
    // Element removal
    Livewire.hook('element.remove', ({ el, component }) => {
        // Clean up any event listeners or resources
        cleanupElement(el);
    });
}

// Helper functions

function fixStuckButtons(container) {
    if (!container) return;
    
    // Find all buttons with wire:loading.attr="disabled"
    const buttons = container.querySelectorAll('button[wire\\:loading\\.attr="disabled"]');
    buttons.forEach(button => {
        try {
            // Check if button should be enabled
            const wireIdEl = button.closest('[wire\\:id]');
            if (!wireIdEl) return;
            
            const wireId = wireIdEl.getAttribute('wire:id');
            if (!wireId) return;
            
            const component = Livewire.find(wireId);
            if (component && !isComponentLoading(component)) {
                button.removeAttribute('disabled');
                button.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        } catch (e) {
            console.warn('Error fixing button state:', e);
        }
    });
    
    // Fix stuck loading spinners
    const spinners = container.querySelectorAll('[wire\\:loading]:not([wire\\:loading\\.remove])');
    spinners.forEach(spinner => {
        try {
            const wireIdEl = spinner.closest('[wire\\:id]');
            if (!wireIdEl) return;
            
            const wireId = wireIdEl.getAttribute('wire:id');
            if (!wireId) return;
            
            const component = Livewire.find(wireId);
            if (component && !isComponentLoading(component)) {
                spinner.style.display = 'none';
            }
        } catch (e) {
            console.warn('Error fixing spinner state:', e);
        }
    });
}

function clearComponentLoadingStates(component) {
    if (!component || !component.id) return;
    
    const componentEl = document.querySelector(`[wire\\:id="${component.id}"]`);
    if (!componentEl) return;
    
    try {
        // Clear disabled states
        componentEl.querySelectorAll('[disabled]').forEach(el => {
            if (el.hasAttribute('wire:loading.attr')) {
                el.removeAttribute('disabled');
            }
        });
        
        // Hide loading elements
        componentEl.querySelectorAll('[wire\\:loading]').forEach(el => {
            if (!el.hasAttribute('wire:loading.remove')) {
                el.style.display = 'none';
            }
        });
        
        // Show elements that should be visible
        componentEl.querySelectorAll('[wire\\:loading\\.remove]').forEach(el => {
            el.style.display = '';
        });
    } catch (e) {
        console.warn('Error clearing loading states:', e);
    }
}

function isComponentLoading(component) {
    if (!component) return false;
    
    // Check multiple indicators of loading state
    try {
        // Primary check - Livewire's built-in loading state
        if (component.loading || component.uploading) {
            return true;
        }
        
        // Secondary check - our custom state (if available)
        if (component._customState && 
            component._customState.lastRequestTime && 
            Date.now() - component._customState.lastRequestTime < 100) {
            return true;
        }
        
        return false;
    } catch (e) {
        // If any error, assume not loading to prevent stuck states
        return false;
    }
}

function initializeTooltips(container) {
    // Initialize any tooltip libraries if used
    const tooltips = container.querySelectorAll('[data-tooltip]');
    tooltips.forEach(el => {
        // Initialize tooltip (example implementation)
        el.addEventListener('mouseenter', showTooltip);
        el.addEventListener('mouseleave', hideTooltip);
    });
}

function cleanupElement(el) {
    // Remove any event listeners
    const tooltips = el.querySelectorAll('[data-tooltip]');
    tooltips.forEach(tooltip => {
        tooltip.removeEventListener('mouseenter', showTooltip);
        tooltip.removeEventListener('mouseleave', hideTooltip);
    });
    
    // Destroy any chart instances in this element
    const canvases = el.querySelectorAll('canvas');
    canvases.forEach(canvas => {
        if (window.ChartManager) {
            const chartId = canvas.id;
            if (chartId) {
                window.ChartManager.destroyAll();
            }
        }
    });
}

function showTooltip(e) {
    // Tooltip implementation
}

function hideTooltip(e) {
    // Tooltip implementation
}

function showErrorNotification(message) {
    // Create a temporary error notification
    const notification = document.createElement('div');
    notification.className = 'fixed top-4 right-4 bg-red-500 text-white px-4 py-2 rounded-md shadow-lg z-50';
    notification.textContent = message;
    document.body.appendChild(notification);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        notification.remove();
    }, 5000);
}

// Export for debugging
window.LivewireDebug = {
    getComponentState(componentId) {
        try {
            const component = Livewire.find(componentId);
            return component ? component._customState : null;
        } catch (e) {
            console.error('Error getting component state:', e);
            return null;
        }
    },
    
    clearAllLoadingStates() {
        try {
            Livewire.all().forEach(component => {
                if (component) {
                    clearComponentLoadingStates(component);
                }
            });
        } catch (e) {
            console.error('Error clearing all loading states:', e);
        }
    },
    
    listComponents() {
        try {
            return Livewire.all().map(c => ({
                id: c ? c.id : 'unknown',
                name: c ? getComponentIdentifier(c) : 'unknown',
                state: c ? c._customState : null
            }));
        } catch (e) {
            console.error('Error listing components:', e);
            return [];
        }
    }
};

// Helper function to get component identifier
function getComponentIdentifier(component) {
    if (!component) return 'unknown';
    
    // Try to get the name first (standard Livewire components)
    if (component.name) return component.name;
    
    // For Volt components, try to get the component path from the element
    try {
        const el = document.querySelector(`[wire\\:id="${component.id}"]`);
        if (el) {
            // Check for data attributes that might contain component info
            const dataComponent = el.getAttribute('data-component');
            if (dataComponent) return dataComponent;
            
            // Try to extract from wire:initial-data
            const initialData = el.getAttribute('wire:initial-data');
            if (initialData) {
                const data = JSON.parse(initialData);
                if (data.fingerprint?.name) return data.fingerprint.name;
            }
            
            // Check parent elements for component identification
            const parentWithRoute = el.closest('[data-route]');
            if (parentWithRoute) {
                const route = parentWithRoute.getAttribute('data-route');
                if (route) return `volt:${route}`;
            }
            
            // Extract from URL if it's a page component
            const path = window.location.pathname;
            if (path && path !== '/') {
                return `volt:${path.substring(1).replace(/\//g, '.')}`;
            }
        }
    } catch (e) {
        console.warn('Error getting component identifier:', e);
    }
    
    // Final fallback - use component ID
    return component.id ? `component-${component.id}` : 'unknown';
}

// Function to reinitialize Flux UI components after DOM updates
function reinitializeFluxComponents(container) {
    if (!container) return;
    
    try {
        // Check if Flux is available
        if (window.Flux && typeof window.Flux.init === 'function') {
            // Reinitialize Flux components in the container
            window.Flux.init(container);
        }
        
        // Dispatch custom event for any additional Flux initialization
        container.dispatchEvent(new CustomEvent('flux:reinit', { 
            bubbles: true,
            detail: { container }
        }));
        
        // Handle specific Flux components that might need reinitialization
        // Modals
        const modals = container.querySelectorAll('[data-flux-modal]');
        modals.forEach(modal => {
            if (window.Flux && window.Flux.Modal) {
                window.Flux.Modal.init(modal);
            }
        });
        
        // Dropdowns
        const dropdowns = container.querySelectorAll('[data-flux-dropdown]');
        dropdowns.forEach(dropdown => {
            if (window.Flux && window.Flux.Dropdown) {
                window.Flux.Dropdown.init(dropdown);
            }
        });
        
        // Selects
        const selects = container.querySelectorAll('[data-flux-select]');
        selects.forEach(select => {
            if (window.Flux && window.Flux.Select) {
                window.Flux.Select.init(select);
            }
        });
    } catch (e) {
        console.warn('Error reinitializing Flux components:', e);
    }
}

console.log('Livewire hooks configured with Volt and Flux support');