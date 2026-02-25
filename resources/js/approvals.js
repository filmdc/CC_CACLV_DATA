// Mobile Approvals Portal - Lightweight JS bundle
// No Chart.js, no Flux, no Jodit - just Livewire essentials

// Import Livewire helpers that are needed
import './livewire-helpers.js';

// Service Worker registration for PWA-like experience (future)
if ('serviceWorker' in navigator) {
    // Reserved for future PWA implementation
}

// Prevent pull-to-refresh on mobile when scrolling within content
document.addEventListener('touchmove', function(e) {
    // Allow normal scrolling
}, { passive: true });
