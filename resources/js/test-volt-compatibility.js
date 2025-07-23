// Test script to verify Volt component compatibility
// This can be run in the browser console to test the fixes

console.log('=== Testing Volt Component Compatibility ===');

// Test 1: Check if Livewire is available
if (typeof Livewire !== 'undefined') {
    console.log('✓ Livewire is available');
    
    // Test 2: List all components
    try {
        const components = Livewire.all();
        console.log(`✓ Found ${components.length} Livewire components`);
        
        // Test 3: Check each component
        components.forEach((component, index) => {
            const hasName = !!component.name;
            const identifier = window.getComponentIdentifier ? 
                window.getComponentIdentifier(component) : 
                (component.name || 'unknown');
            
            console.log(`  Component ${index + 1}:`, {
                id: component.id,
                hasName: hasName,
                identifier: identifier,
                isVolt: !hasName && identifier.includes('volt:')
            });
        });
    } catch (error) {
        console.error('✗ Error listing components:', error);
    }
} else {
    console.error('✗ Livewire is not available');
}

// Test 4: Check if Flux is available
if (typeof window.Flux !== 'undefined') {
    console.log('✓ Flux UI is available');
} else {
    console.warn('⚠ Flux UI is not detected');
}

// Test 5: Check if our debug tools are available
if (typeof window.LivewireDebug !== 'undefined') {
    console.log('✓ LivewireDebug tools are available');
    
    // Try listing components with our debug tool
    try {
        const debugList = window.LivewireDebug.listComponents();
        console.log('✓ Debug component list:', debugList);
    } catch (error) {
        console.error('✗ Error using LivewireDebug:', error);
    }
} else {
    console.error('✗ LivewireDebug tools are not available');
}

// Test 6: Check for error-prone hooks
try {
    // Simulate a component without name
    const mockComponent = {
        id: 'test-volt-component',
        // name is intentionally missing to simulate Volt component
    };
    
    if (window.getComponentIdentifier) {
        const identifier = window.getComponentIdentifier(mockComponent);
        console.log('✓ Component identifier function handles nameless components:', identifier);
    } else {
        console.warn('⚠ getComponentIdentifier function not found in global scope');
    }
} catch (error) {
    console.error('✗ Error testing component identifier:', error);
}

console.log('=== Test Complete ===');