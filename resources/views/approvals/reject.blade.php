<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reject Approval - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 dark:bg-zinc-900">
    <div class="min-h-screen flex items-center justify-center p-6">
        <div class="max-w-2xl w-full bg-white dark:bg-zinc-800 rounded-lg shadow-xl p-8">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
                Reject Approval Request
            </h1>

            <div class="mb-6 p-4 bg-gray-50 dark:bg-zinc-700 rounded-lg">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                    Item Details
                </h2>
                <dl class="space-y-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-600 dark:text-gray-300">Type:</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">{{ $pendingApproval->item_type }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-600 dark:text-gray-300">Name:</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">{{ $pendingApproval->item_name }}</dd>
                    </div>
                    @if($pendingApproval->item_description)
                    <div>
                        <dt class="text-sm font-medium text-gray-600 dark:text-gray-300">Description:</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">{{ $pendingApproval->item_description }}</dd>
                    </div>
                    @endif
                    <div>
                        <dt class="text-sm font-medium text-gray-600 dark:text-gray-300">Submitted by:</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">{{ $pendingApproval->submittedBy->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-600 dark:text-gray-300">Submitted on:</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">{{ $pendingApproval->submitted_at->format('F j, Y g:i A') }}</dd>
                    </div>
                </dl>
            </div>

            <form method="POST" action="{{ route('approval.reject.submit', $pendingApproval->id) }}">
                @csrf

                <div class="mb-6">
                    <label for="rejection_reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Rejection Reason <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        id="rejection_reason"
                        name="rejection_reason"
                        rows="5"
                        required
                        class="w-full rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-red-500 focus:ring-red-500"
                        placeholder="Please provide a detailed reason for rejection and what needs to be corrected..."
                    >{{ old('rejection_reason') }}</textarea>
                    @error('rejection_reason')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Minimum 10 characters. Be specific about what needs to be corrected.
                    </p>
                </div>

                <div class="flex items-center justify-end space-x-3">
                    <a
                        href="{{ route('dashboard') }}"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white"
                    >
                        Cancel
                    </a>
                    <button
                        type="submit"
                        class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                    >
                        Submit Rejection
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
