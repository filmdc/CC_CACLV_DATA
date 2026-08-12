<div class="w-full max-w-sm mx-auto page-enter">
    <div class="text-center mb-7">
        <div class="calv-h1" style="font-size: 30px;">CACLV Housing</div>
        <div class="calv-subhead" style="font-size: 10px; letter-spacing: .14em; color: var(--calv-amber); margin-top: 4px;">Fighting for good</div>
    </div>

    <div class="calv-card p-7">
        <form wire:submit="login" class="space-y-4">
            <div>
                <label for="email" class="calv-label">Email</label>
                <input
                    wire:model="email"
                    id="email"
                    type="email"
                    autocomplete="email"
                    autofocus
                    required
                    class="calv-input"
                    placeholder="you@caclv.org"
                >
                @error('email')
                    <p class="mt-1.5 text-sm" style="color: var(--calv-red);">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="calv-label">Password</label>
                <input
                    wire:model="password"
                    id="password"
                    type="password"
                    autocomplete="current-password"
                    required
                    class="calv-input"
                    placeholder="Enter your password"
                >
                @error('password')
                    <p class="mt-1.5 text-sm" style="color: var(--calv-red);">{{ $message }}</p>
                @enderror
            </div>

            <label class="flex items-center gap-2.5 py-1">
                <input wire:model="remember" type="checkbox" class="h-4 w-4 rounded" style="accent-color: var(--calv-red);">
                <span class="text-sm" style="color: var(--calv-slate-65);">Remember me</span>
            </label>

            <button
                type="submit"
                class="calv-btn calv-btn--primary calv-btn--lg w-full"
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
        </form>
    </div>

    <div class="mt-6 text-center space-y-2">
        <p class="text-sm" style="color: var(--calv-slate-65);">Need an account? Contact your administrator.</p>
        <a href="{{ env('MAIN_APP_URL', 'https://caclv-data.caclv.org') }}" class="calv-link inline-flex items-center gap-1 text-sm">
            Go to main application
        </a>
    </div>
</div>
