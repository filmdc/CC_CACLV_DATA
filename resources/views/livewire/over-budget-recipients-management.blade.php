<div class="max-w-3xl mx-auto p-6">
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl p-6">
        <div class="mb-6">
            <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Over-budget email recipients</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                Users on this list receive a database notification and (unless they've opted out in
                <a href="{{ route('settings.notifications') }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">Settings → Notifications</a>)
                an email any time a project budget category is modified — increase or decrease.
            </p>
        </div>

        @if (session('success'))
            <div class="mb-4 rounded-md bg-green-50 dark:bg-green-900/30 border border-green-300 dark:border-green-700 p-3 text-sm text-green-700 dark:text-green-300">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="mb-4 rounded-md bg-red-50 dark:bg-red-900/30 border border-red-300 dark:border-red-700 p-3 text-sm text-red-700 dark:text-red-300">
                {{ session('error') }}
            </div>
        @endif

        <div class="mb-6 flex items-end gap-3" data-add-recipient-form>
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Add a recipient</label>
                <select wire:model="userIdToAdd"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md text-sm">
                    <option value="">Select an active user…</option>
                    @foreach($candidateUsers as $u)
                        <option value="{{ $u->id }}">{{ $u->name }} &lt;{{ $u->email }}&gt;</option>
                    @endforeach
                </select>
                @error('userIdToAdd') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
            </div>
            <button wire:click="addRecipient"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 text-sm font-medium">
                Add
            </button>
        </div>

        <div class="border border-gray-200 dark:border-zinc-700 rounded-md overflow-hidden" data-recipient-list>
            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                <thead class="bg-gray-50 dark:bg-zinc-900">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Name</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Email</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Added by</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-700">
                    @forelse($recipients as $r)
                        <tr data-recipient-row="{{ $r->id }}">
                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">{{ $r->user?->name ?? '—' }}</td>
                            <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300">{{ $r->user?->email ?? '—' }}</td>
                            <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">{{ $r->createdBy?->name ?? '—' }}</td>
                            <td class="px-4 py-2 text-sm text-right">
                                <button wire:click="removeRecipient({{ $r->id }})"
                                        wire:confirm="Remove {{ $r->user?->name ?? 'this recipient' }}?"
                                        class="text-red-600 dark:text-red-400 hover:text-red-800">
                                    Remove
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400 italic">
                                No recipients configured. Budget modifications still record to the audit ledger; just no emails will go out.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
