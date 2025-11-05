<div class="w-full px-0 py-6">
    <div class="bg-white dark:bg-zinc-800 shadow-lg rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-indigo-600 text-white p-6">
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center space-x-3 mb-2">
                        <a href="{{ route('pdf-templates.index') }}" wire:navigate
                           class="text-indigo-100 hover:text-white">
                            ← Back to Templates
                        </a>
                    </div>
                    <h1 class="text-2xl font-bold">{{ $template->name }}</h1>
                    <p class="text-indigo-100 mt-2">
                        {{ $template->field_count }} form fields •
                        {{ $template->mapped_field_count }} mapped ({{ $template->mapping_completion }}%)
                    </p>
                </div>
            </div>
        </div>

        <!-- Coming Soon Notice -->
        <div class="p-12 text-center">
            <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <h2 class="text-2xl font-semibold text-gray-700 dark:text-gray-300 mb-2">Field Mapping Interface - Coming in Phase 2</h2>
            <p class="text-gray-600 dark:text-gray-400 mb-4">
                This page will allow you to map PDF form fields to database fields from your system.
            </p>

            <div class="max-w-2xl mx-auto text-left bg-gray-50 dark:bg-zinc-700 rounded-lg p-6 mt-6">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-3">Detected Fields in This Template:</h3>
                <div class="space-y-2">
                    @foreach($template->fieldMappings as $field)
                        <div class="flex items-center justify-between p-2 bg-white dark:bg-zinc-800 rounded">
                            <div class="flex items-center space-x-2">
                                <span class="text-lg">{{ $field->field_type_icon }}</span>
                                <div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $field->field_name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $field->field_type }}</div>
                                </div>
                            </div>
                            <span class="text-xs px-2 py-1 rounded {{ $field->isMapped() ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300' }}">
                                {{ $field->isMapped() ? 'Mapped' : 'Not Mapped' }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mt-6">
                <a href="{{ route('pdf-templates.index') }}" wire:navigate
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                    ← Back to Templates
                </a>
            </div>
        </div>
    </div>
</div>
