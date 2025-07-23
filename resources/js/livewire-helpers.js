// Livewire JavaScript Helpers and Optimizations
// This file provides utilities and fixes for common Livewire/Alpine.js issues

// Chart Management - Prevents memory leaks and initialization issues
window.ChartManager = {
    instances: {},
    
    // Destroy all chart instances
    destroyAll() {
        Object.values(this.instances).forEach(chart => {
            if (chart && typeof chart.destroy === 'function') {
                chart.destroy();
            }
        });
        this.instances = {};
    },
    
    // Register a chart instance
    register(id, chart) {
        // Destroy existing instance if present
        if (this.instances[id]) {
            this.instances[id].destroy();
        }
        this.instances[id] = chart;
    },
    
    // Get a chart instance
    get(id) {
        return this.instances[id];
    },
    
    // Check if Chart.js is loaded
    isChartJsLoaded() {
        return typeof window.Chart !== 'undefined';
    }
};

// Livewire Event Handler - Prevents duplicate event listeners
window.LivewireEventManager = {
    listeners: new Map(),
    
    // Add event listener with automatic cleanup
    on(event, callback, componentId = null) {
        const key = componentId ? `${event}-${componentId}` : event;
        
        // Remove existing listener if present
        this.off(event, componentId);
        
        // Store the callback
        this.listeners.set(key, callback);
        
        // Add the listener
        Livewire.on(event, callback);
    },
    
    // Remove event listener
    off(event, componentId = null) {
        const key = componentId ? `${event}-${componentId}` : event;
        const callback = this.listeners.get(key);
        
        if (callback) {
            Livewire.off(event, callback);
            this.listeners.delete(key);
        }
    },
    
    // Clear all listeners
    clear() {
        this.listeners.forEach((callback, key) => {
            const event = key.split('-')[0];
            Livewire.off(event, callback);
        });
        this.listeners.clear();
    }
};

// DOM Ready Handler - Ensures proper initialization timing
window.DOMReady = {
    // Wait for DOM and Livewire to be ready
    async ready(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                this.waitForLivewire(callback);
            });
        } else {
            this.waitForLivewire(callback);
        }
    },
    
    // Wait for Livewire to initialize
    async waitForLivewire(callback) {
        if (window.Livewire) {
            callback();
        } else {
            // Poll for Livewire availability
            let attempts = 0;
            const checkLivewire = setInterval(() => {
                attempts++;
                if (window.Livewire || attempts > 50) {
                    clearInterval(checkLivewire);
                    if (window.Livewire) {
                        callback();
                    } else {
                        console.error('Livewire not found after 5 seconds');
                    }
                }
            }, 100);
        }
    }
};

// Animation Control - Prevents stuck loading animations
window.AnimationControl = {
    // Track active animations
    activeAnimations: new Set(),
    
    // Start animation with timeout protection
    startLoading(elementId, timeout = 30000) {
        this.activeAnimations.add(elementId);
        
        // Auto-stop after timeout to prevent stuck animations
        setTimeout(() => {
            this.stopLoading(elementId);
        }, timeout);
    },
    
    // Stop animation
    stopLoading(elementId) {
        this.activeAnimations.delete(elementId);
    },
    
    // Check if animation is active
    isLoading(elementId) {
        return this.activeAnimations.has(elementId);
    },
    
    // Clear all animations
    clearAll() {
        this.activeAnimations.clear();
    }
};

// Scroll Control - Prevents unwanted automatic scrolling
window.ScrollControl = {
    scrollExecuted: new Set(),
    
    // Execute scroll only once per element
    scrollOnce(element, options = {}) {
        const elementId = element.id || element.className;
        
        if (!this.scrollExecuted.has(elementId)) {
            element.scrollIntoView({
                behavior: 'smooth',
                block: 'start',
                ...options
            });
            this.scrollExecuted.add(elementId);
        }
    },
    
    // Reset scroll tracking for an element
    resetScroll(elementId) {
        this.scrollExecuted.delete(elementId);
    },
    
    // Clear all scroll tracking
    clearAll() {
        this.scrollExecuted.clear();
    }
};

// Alpine.js Component Registry - Prevents duplicate components
window.AlpineComponents = {
    // Register reusable Alpine components
    register() {
        // Loading button component
        Alpine.data('loadingButton', () => ({
            loading: false,
            
            async handleClick(callback) {
                if (this.loading) return;
                
                this.loading = true;
                try {
                    await callback();
                } finally {
                    // Ensure loading state is cleared
                    setTimeout(() => {
                        this.loading = false;
                    }, 100);
                }
            }
        }));
        
        // Auto-dismiss message component
        Alpine.data('autoDismiss', (duration = 3000) => ({
            show: true,
            
            init() {
                setTimeout(() => {
                    this.show = false;
                }, duration);
            }
        }));
        
        // Debounced input component
        Alpine.data('debouncedInput', (delay = 300) => ({
            value: '',
            timeout: null,
            
            updateValue(newValue, callback) {
                this.value = newValue;
                clearTimeout(this.timeout);
                this.timeout = setTimeout(() => {
                    callback(this.value);
                }, delay);
            }
        }));
    }
};

// Initialize helpers when DOM is ready
DOMReady.ready(() => {
    console.log('Livewire helpers initialized');
    
    // Register Alpine components if Alpine is available
    if (window.Alpine) {
        AlpineComponents.register();
    }
    
    // Clean up on page unload
    window.addEventListener('beforeunload', () => {
        ChartManager.destroyAll();
        LivewireEventManager.clear();
        AnimationControl.clearAll();
        ScrollControl.clearAll();
    });
});

// Export for use in other modules
export {
    ChartManager,
    LivewireEventManager,
    DOMReady,
    AnimationControl,
    ScrollControl,
    AlpineComponents
};