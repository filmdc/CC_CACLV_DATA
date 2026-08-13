<div class="px-6 lg:px-9 py-7 max-w-4xl mx-auto" style="min-height: 100vh;">

    <div class="mb-6">
        <h1 class="calv-h1" style="font-size: 30px;">Settings</h1>
        <div class="text-[13.5px] mt-1.5" style="color: var(--calv-slate-65);">
            Setup you touch a few times a year — kept out of the daily path. Each opens in the main app, functionally unchanged.
        </div>
    </div>

    <div class="grid gap-3" style="grid-template-columns: 1fr 1fr;">
        @foreach($surfaces as $s)
            <a href="{{ $s['url'] }}" target="_blank" rel="noopener"
               class="calv-card px-4.5 py-4 flex items-start gap-3.5" style="text-decoration: none; padding: 16px 18px;">
                <span class="flex-1 min-w-0">
                    <span class="block text-[14.5px] font-semibold" style="color: var(--calv-slate);">{{ $s['label'] }}</span>
                    <span class="block text-[12px] mt-1 leading-snug" style="color: var(--calv-slate-65);">{{ $s['sub'] }}</span>
                </span>
                <span class="shrink-0 mt-0.5" style="color: var(--calv-teal);">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                    </svg>
                </span>
            </a>
        @endforeach
    </div>

    <div class="mt-5 px-4 py-3.5 text-[12.5px] leading-relaxed" style="background: #fff; border: 1px dashed var(--calv-slate-35); border-radius: 8px; color: var(--calv-slate-65);">
        These pages open in the main CAP TRACK app. Your login carries over — no second sign-in needed.
    </div>
</div>
