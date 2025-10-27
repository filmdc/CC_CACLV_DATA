import Chart from 'chart.js/auto';
import './livewire-helpers.js';
import './livewire-hooks.js';
import './global-fixes.js';
import './flux-integration.js';
import './cancel-button-fix.js';

// Make Chart.js available globally
window.Chart = Chart;

// Note: Jodit Editor initialization is now handled entirely by JoditEditorManager
// in sidebar.blade.php to prevent conflicts and focus issues.