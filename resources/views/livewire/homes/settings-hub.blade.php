<div class="px-6 lg:px-9 py-7 max-w-4xl mx-auto" style="min-height: 100vh;">

    <div class="mb-6">
        <h1 class="calv-h1" style="font-size: 30px;">Settings</h1>
        <div class="text-[13.5px] mt-1.5" style="color: var(--calv-slate-65);">
            Setup you touch a few times a year — kept out of the daily path.
        </div>
    </div>

    @if($surfaces->isEmpty())
        <div class="calv-card p-6 text-sm" style="color: var(--calv-slate-65);">
            You don't have access to any setup areas. Contact your administrator if you need one.
        </div>
    @else
        <div class="grid gap-3" style="grid-template-columns: 1fr 1fr;">
            @foreach($surfaces as $s)
                <a href="{{ route($s['route']) }}" wire:navigate
                   class="calv-card flex items-start gap-3.5" style="text-decoration: none; padding: 16px 18px;">
                    <span class="flex-1 min-w-0">
                        <span class="block text-[14.5px] font-semibold" style="color: var(--calv-slate);">{{ $s['label'] }}</span>
                        <span class="block text-[12px] mt-1 leading-snug" style="color: var(--calv-slate-65);">{{ $s['sub'] }}</span>
                    </span>
                    <span class="shrink-0 mt-0.5" style="color: var(--calv-teal);">→</span>
                </a>
            @endforeach
        </div>
    @endif
</div>
