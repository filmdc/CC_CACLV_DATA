<div class="w-full max-w-sm lg:max-w-md mx-auto page-enter">
    <div class="text-center mb-8">
        <div class="flex justify-center mb-3">
            <x-app-logo-icon class="size-10 lg:size-12 fill-current text-indigo-400" />
        </div>
        <h1 class="text-xl lg:text-2xl font-semibold text-white">CAP TRACK</h1>
        <p class="text-sm text-zinc-400 mt-1">Approvals Portal</p>
    </div>

    <div class="bg-zinc-900 rounded-2xl border border-zinc-800 p-6 lg:p-8">
        <form wire:submit="login">
            <div class="space-y-4">
                <div>
                    <label for="email" class="block text-sm font-medium text-zinc-300 mb-1.5">Email</label>
                    <input
                        wire:model="email"
                        id="email"
                        type="email"
                        autocomplete="email"
                        autofocus
                        required
                        class="w-full h-12 lg:h-10 px-4 text-base lg:text-sm rounded-lg bg-zinc-800 border border-zinc-700 text-white placeholder-zinc-500 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 focus:outline-none"
                        placeholder="you@caclv.org"
                    >
                    @error('email')
                        <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-zinc-300 mb-1.5">Password</label>
                    <input
                        wire:model="password"
                        id="password"
                        type="password"
                        autocomplete="current-password"
                        required
                        class="w-full h-12 lg:h-10 px-4 text-base lg:text-sm rounded-lg bg-zinc-800 border border-zinc-700 text-white placeholder-zinc-500 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 focus:outline-none"
                        placeholder="Enter your password"
                    >
                    @error('password')
                        <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <label class="flex items-center gap-2.5 py-1">
                    <input
                        wire:model="remember"
                        type="checkbox"
                        class="h-5 w-5 lg:h-4 lg:w-4 rounded border-zinc-600 bg-zinc-800 text-indigo-500 focus:ring-indigo-500 focus:ring-offset-zinc-900"
                    >
                    <span class="text-sm text-zinc-400">Remember me</span>
                </label>

                <button
                    type="submit"
                    class="w-full h-12 lg:h-10 bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 text-white font-semibold rounded-lg transition-colors flex items-center justify-center"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-75 cursor-wait"
                >
                    <span wire:loading.remove>Sign In</span>
                    <span wire:loading class="flex items-center gap-2">
                        <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Signing in...
                    </span>
                </button>
            </div>
        </form>
    </div>

    <div class="mt-6 text-center space-y-2">
        <p class="text-sm text-zinc-500">Need an account? Contact your administrator.</p>
        <a
            href="{{ config('app.main_app_url') }}"
            class="inline-flex items-center gap-1 text-sm text-indigo-400 hover:text-indigo-300"
        >
            Go to main application
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
            </svg>
        </a>
    </div>
</div>
