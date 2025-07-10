<div class="max-w-7xl mx-auto p-6">
    <div class="bg-white dark:bg-zinc-800 shadow-lg rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">{{ $program->name }}</h1>
                    <p class="text-blue-100 mt-2">Program Details</p>
                </div>
                <div class="flex space-x-3">
                    @if ($this->can('programs.update'))
                        <button wire:click="editProgram" 
                                class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                            Edit Program
                        </button>
                    @endif
                    <a href="{{ route('program.management') }}" 
                       class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                        Back to List
                    </a>
                </div>
            </div>
        </div>

        <div class="p-6">
            <!-- Basic Information -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-zinc-700 pb-2">
                    Basic Information
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Program Name</label>
                        <p class="text-gray-900 dark:text-white">{{ $program->name }}</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Status</label>
                        <p>
                            @if($program->active)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                    Active
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100">
                                    Inactive
                                </span>
                            @endif
                        </p>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Description</label>
                        <p class="text-gray-900 dark:text-white">{{ $program->description ?: 'No description provided' }}</p>
                    </div>
                </div>
            </div>

            <!-- Services -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-zinc-700 pb-2">
                    Services ({{ $program->services->count() }})
                </h2>
                @if($program->services->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($program->services as $service)
                            <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-4">
                                <h3 class="font-medium text-gray-900 dark:text-white">{{ $service->name }}</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $service->description ?: 'No description' }}</p>
                                <div class="mt-2">
                                    @if($service->is_active)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100">
                                            Inactive
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 dark:text-gray-400">No services configured for this program.</p>
                @endif
            </div>

            <!-- Contracts -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-zinc-700 pb-2">
                    Contracts ({{ $program->contracts->count() }})
                </h2>
                @if($program->contracts->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Contract Name
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Funder
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Start Date
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        End Date
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Status
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                                @foreach($program->contracts as $contract)
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                            {{ $contract->name }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                            {{ $contract->funder->name ?? 'N/A' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                            {{ $contract->start_date ? \Carbon\Carbon::parse($contract->start_date)->format('M j, Y') : 'N/A' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                            {{ $contract->end_date ? \Carbon\Carbon::parse($contract->end_date)->format('M j, Y') : 'N/A' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            @if($contract->status === 'active')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                                    Active
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100">
                                                    {{ ucfirst($contract->status) }}
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-gray-500 dark:text-gray-400">No contracts associated with this program.</p>
                @endif
            </div>

            <!-- Recent Enrollments -->
            <div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-zinc-700 pb-2">
                    Recent Enrollments
                </h2>
                @if($program->programEnrollments->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Participant
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Enrollment Date
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Exit Date
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                                @foreach($program->programEnrollments as $enrollment)
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                            <a href="{{ route('participant.show', $enrollment->participant_id) }}" 
                                               class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                                {{ $enrollment->participant->full_name ?? 'Unknown' }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                            {{ $enrollment->enrollment_date ? \Carbon\Carbon::parse($enrollment->enrollment_date)->format('M j, Y') : 'N/A' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            @if($enrollment->status === 'active')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                                    Active
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100">
                                                    {{ ucfirst($enrollment->status) }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                            {{ $enrollment->exit_date ? \Carbon\Carbon::parse($enrollment->exit_date)->format('M j, Y') : '-' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                        Showing last 10 enrollments. View enrollment management for complete list.
                    </p>
                @else
                    <p class="text-gray-500 dark:text-gray-400">No enrollments recorded for this program.</p>
                @endif
            </div>
        </div>
    </div>
</div>