<div class="w-full px-0 py-6">
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl p-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-6">Participants & Households</h1>
        
        <!-- Management Tools as Buttons -->
        <div class="flex flex-wrap gap-3 mb-8">
            @if($this->can('participants.view'))
            <a href="{{ route('participant.management') }}" wire:navigate
               class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition-colors font-medium">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                All Participants
            </a>
            @endif
            
            @if($this->can('households.view'))
            <a href="{{ route('household.management') }}" wire:navigate
               class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition-colors font-medium">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
                All Households
            </a>
            @endif
            
            @if($this->canAny(['participants.export', 'participants.import']))
            <a href="{{ route('participant.import-export') }}" wire:navigate
               class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition-colors font-medium">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                </svg>
                Import/Export Data
            </a>
            @endif

            @if($this->can('program-manager-uploads.manage'))
            <a href="{{ route('program-manager-uploads.review') }}" wire:navigate
               class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition-colors font-medium">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 16.5V19a2 2 0 002 2h14a2 2 0 002-2v-2.5m-18-5l5.857 4.399a4 4 0 004.286 0L21 11.5m-18-7l6.238 4.706a4 4 0 004.524 0L21 4.5"/>
                </svg>
                Program Uploads
            </a>
            @endif
        </div>
        
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

    </div>
</div>
