<div class="px-6 py-8 space-y-6">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">Program Manager Uploads</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Review uploads submitted via the public portal and send them through the import pipeline.</p>
        </div>
        <a href="{{ route('program-manager-upload.create') }}" target="_blank" class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-blue-700">Open Public Portal</a>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Status</label>
            <select wire:model.live="status" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                <option value="all">All</option>
                <option value="pending">Pending</option>
                <option value="processing">Processing</option>
                <option value="completed">Completed</option>
                <option value="failed">Failed</option>
                <option value="rejected">Rejected</option>
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Upload Type</label>
            <select wire:model.live="type" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                <option value="all">All</option>
                <option value="general">General Upload</option>
                <option value="hmis">HMIS Upload</option>
                <option value="rx_office">RX Office Upload</option>
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Search</label>
            <input type="search" wire:model.debounce.400ms="search" placeholder="Name, email, organization" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 dark:border-slate-600 dark:bg-slate-800 dark:text-white" />
        </div>
        <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Items per page</label>
            <select wire:model.live="perPage" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                <option value="10">10</option>
                <option value="15">15</option>
                <option value="25">25</option>
                <option value="50">50</option>
            </select>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
            <thead class="bg-slate-50 dark:bg-slate-800">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Details</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Submitted</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 text-sm dark:divide-slate-700">
                @forelse ($uploads as $upload)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/60">
                        <td class="px-4 py-3 align-top">
                            <div class="font-semibold text-slate-900 dark:text-white">{{ $upload->program_manager_name }}</div>
                            <div class="text-xs text-slate-500">{{ $upload->program_manager_email }}</div>
                            @if ($upload->organization)
                                <div class="text-xs text-slate-500">{{ $upload->organization }}</div>
                            @endif
                            <div class="mt-2 flex flex-wrap gap-2 text-xs">
                                <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 font-medium text-blue-700">{{ strtoupper($upload->type) }}</span>
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 font-medium text-slate-700">{{ $upload->original_filename }}</span>
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 font-medium text-slate-700">{{ number_format($upload->file_size / 1024, 1) }} KB</span>
                            </div>
                            @if ($upload->review_notes)
                                <div class="mt-2 rounded border border-amber-200 bg-amber-50 px-2 py-1 text-xs text-amber-600">
                                    Notes: {{ $upload->review_notes }}
                                </div>
                            @endif
                            @if (!empty($upload->processing_result['warnings']))
                                <div class="mt-2 rounded border border-yellow-200 bg-yellow-50 px-2 py-1 text-xs text-yellow-700">
                                    Warnings: {{ implode(', ', (array) $upload->processing_result['warnings']) }}
                                </div>
                            @endif
                            @if (!empty($upload->processing_result['errors']) && !$upload->processing_result['success'])
                                <div class="mt-2 rounded border border-rose-200 bg-rose-50 px-2 py-1 text-xs text-rose-600">
                                    Errors: {{ implode(', ', (array) $upload->processing_result['errors']) }}
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-3 align-top text-xs text-slate-500">
                            <div>{{ optional($upload->submitted_at)->format('M d, Y g:i a') }}</div>
                            <div>IP: {{ $upload->ip_address }}</div>
                        </td>
                        <td class="px-4 py-3 align-top">
                            @php
                                $statusClasses = [
                                    'pending' => 'bg-slate-100 text-slate-700',
                                    'processing' => 'bg-blue-100 text-blue-700',
                                    'completed' => 'bg-emerald-100 text-emerald-700',
                                    'failed' => 'bg-rose-100 text-rose-700',
                                    'rejected' => 'bg-amber-100 text-amber-700',
                                ];
                                $label = ucfirst($upload->status);
                            @endphp
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusClasses[$upload->status] ?? 'bg-slate-100 text-slate-700' }}">{{ $label }}</span>
                            @if ($upload->reviewed_at)
                                <div class="mt-1 text-xs text-slate-500">Reviewed {{ $upload->reviewed_at->diffForHumans() }}</div>
                            @endif
                            @if (!empty($upload->processing_result['success']) && $upload->processing_result['success'])
                                <div class="mt-2 text-xs text-slate-500">
                                    Processed Participants: {{ $upload->processing_result['processed_participants'] ?? '—' }}<br>
                                    Processed Households: {{ $upload->processing_result['processed_households'] ?? '—' }}
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-3 align-top">
                            <div class="flex flex-col gap-2">
                                <a href="{{ route('program-manager-upload.download', $upload->id) }}" class="inline-flex items-center justify-center rounded-md border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-100">Download</a>
                                @if (in_array($upload->status, [App\Models\ProgramManagerUpload::STATUS_PENDING, App\Models\ProgramManagerUpload::STATUS_FAILED]))
                                    <button wire:click="processUpload({{ $upload->id }})" class="inline-flex items-center justify-center rounded-md bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700">Process Import</button>
                                    <button wire:click="openRejectModal({{ $upload->id }})" class="inline-flex items-center justify-center rounded-md border border-amber-400 px-3 py-1.5 text-xs font-medium text-amber-700 hover:bg-amber-50">Reject</button>
                                @endif
                                @if ($upload->status === App\Models\ProgramManagerUpload::STATUS_COMPLETED)
                                    <button wire:click="openRejectModal({{ $upload->id }})" class="inline-flex items-center justify-center rounded-md border border-amber-400 px-3 py-1.5 text-xs font-medium text-amber-700 hover:bg-amber-50">Add Notes</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-sm text-slate-500">No uploads found for the selected filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $uploads->links() }}
    </div>

    @if ($showRejectModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
            <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-900">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Add Review Notes</h2>
                <p class="mt-2 text-sm text-slate-500">Provide optional notes or reasons for rejection. Notes are visible to other staff with access.</p>
                <textarea wire:model.defer="rejectNotes" rows="4" class="mt-4 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 dark:border-slate-600 dark:bg-slate-800 dark:text-white"></textarea>
                <div class="mt-6 flex justify-end gap-3">
                    <button wire:click="cancelRejection" type="button" class="inline-flex items-center rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Cancel</button>
                    <button wire:click="rejectUpload" type="button" class="inline-flex items-center rounded-md bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">Save Notes</button>
                </div>
            </div>
        </div>
    @endif
</div>
