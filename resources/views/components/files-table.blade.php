@props([
    'rows' => [],
    'empty' => 'No files have been uploaded yet.',
])

{{--
    Central files table. Renders a normalized list of uploaded documents
    aggregated from a record's requirements (and, on the project side, its
    milestone documents). Each $row is an associative array:
      name, mime, size, source, uploaded (Carbon|null),
      preview_url, download_url, previewable (bool),
      remove_call (string "method(args)"|null — null hides the Remove control)

    Presentational only: View/Download are plain links to the media routes;
    Remove fires the supplied Livewire method-call string. The host component
    owns authorization (it only sets remove_call when the user may remove).
--}}
<div class="overflow-x-auto">
    @if(count($rows) === 0)
        <div class="text-center py-10 text-sm text-gray-500 dark:text-gray-400">{{ $empty }}</div>
    @else
        <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
            <thead>
                <tr class="text-left text-[11px] uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    <th class="px-4 py-2 font-semibold">File</th>
                    <th class="px-4 py-2 font-semibold">Requirement / Source</th>
                    <th class="px-4 py-2 font-semibold whitespace-nowrap">Uploaded</th>
                    <th class="px-4 py-2 font-semibold text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                @foreach($rows as $row)
                    <tr class="text-sm text-gray-800 dark:text-gray-200">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="text-gray-400 shrink-0">📎</span>
                                <span class="truncate max-w-[260px]" title="{{ $row['name'] }}">{{ $row['name'] }}</span>
                            </div>
                            <div class="text-xs text-gray-400 mt-0.5">{{ $row['size'] }} · {{ $row['mime'] }}</div>
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $row['source'] }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-gray-600 dark:text-gray-300">
                            {{ $row['uploaded'] ? $row['uploaded']->format('M d, Y') : '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-3">
                                @if($row['previewable'])
                                    <a href="{{ $row['preview_url'] }}" target="_blank" rel="noopener"
                                       class="text-teal-600 hover:underline font-medium">View</a>
                                @endif
                                <a href="{{ $row['download_url'] }}"
                                   class="text-teal-600 hover:underline font-medium">Download</a>
                                @if(!empty($row['remove_call']))
                                    <button type="button"
                                            wire:click="{{ $row['remove_call'] }}"
                                            wire:confirm="Remove this attachment?"
                                            class="text-red-600 hover:underline font-medium">Remove</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
