<div class="max-w-7xl mx-auto p-6">
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl p-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-8">Participants & Households</h1>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @if($this->canViewIntake())
            <a href="{{ route('participant.intake') }}" wire:navigate
               class="block p-6 bg-white dark:bg-zinc-700 border border-gray-200 dark:border-zinc-600 rounded-lg shadow hover:shadow-lg transition-shadow">
                <div class="flex items-center mb-4">
                    <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                    </svg>
                    <h2 class="ml-3 text-xl font-semibold text-gray-900 dark:text-white">Manual Intake</h2>
                </div>
                <p class="text-gray-600 dark:text-gray-300">Add new participants to the system through the manual intake process.</p>
            </a>
            @endif

            @if($this->canViewDuplicates())
            <a href="{{ route('duplicate.resolution') }}" wire:navigate
               class="block p-6 bg-white dark:bg-zinc-700 border border-gray-200 dark:border-zinc-600 rounded-lg shadow hover:shadow-lg transition-shadow">
                <div class="flex items-center mb-4">
                    <svg class="w-8 h-8 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <h2 class="ml-3 text-xl font-semibold text-gray-900 dark:text-white">Duplicate Management</h2>
                </div>
                <p class="text-gray-600 dark:text-gray-300">Review and resolve potential duplicate participant records.</p>
            </a>
            @endif

            @if($this->canViewSearch())
            <livewire:general-search />
            @endif
        </div>

        @if($this->canViewEnrollments())
        <div class="mt-8 pt-8 border-t border-gray-200 dark:border-zinc-600">
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">Program Enrollments</h2>
            <a href="{{ route('enrollment.management') }}" wire:navigate
               class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-md transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                </svg>
                Manage Enrollments
            </a>
            <p class="mt-2 text-gray-600 dark:text-gray-300">View and manage participant enrollments in programs and services.</p>
        </div>
        @endif

        <div class="mt-8 pt-8 border-t border-gray-200 dark:border-zinc-600">
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">Quick Links</h2>
            <div class="flex flex-wrap gap-4">
                @if($this->can('participants.view'))
                <a href="{{ route('participant.management') }}" wire:navigate
                   class="text-indigo-600 dark:text-indigo-400 hover:underline">
                    All Participants →
                </a>
                @endif
                
                @if($this->can('households.view'))
                <a href="{{ route('household.management') }}" wire:navigate
                   class="text-indigo-600 dark:text-indigo-400 hover:underline">
                    All Households →
                </a>
                @endif
                
                @if($this->canAny(['participants.export', 'participants.import']))
                <a href="{{ route('participant.import-export') }}" wire:navigate
                   class="text-indigo-600 dark:text-indigo-400 hover:underline">
                    Import/Export Data →
                </a>
                @endif
            </div>
        </div>
    </div>
</div>