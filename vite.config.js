import {
    defineConfig
} from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/approvals.css',
                'resources/js/approvals.js',
                'resources/css/homes.css',
                'resources/js/homes.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        cors: true,
    },
    build: {
        rollupOptions: {
            output: {
                // Keep the shared Livewire runtime in its own stable chunk so
                // adding new entry bundles (approvals, homes) never reshapes the
                // main app's asset graph. Without this, importing livewire-helpers
                // from a new entry renames the shared chunk and churns the
                // full-page snapshot tests.
                manualChunks(id) {
                    if (id.includes('resources/js/livewire-helpers.js')) {
                        return 'livewire-helpers';
                    }
                },
            },
        },
    },
});