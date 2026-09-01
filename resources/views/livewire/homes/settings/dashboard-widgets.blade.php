<div class="px-6 lg:px-9 py-7 max-w-3xl mx-auto" style="min-height: 100vh;">

    @if (session('homes_message'))
        <div class="mb-4 rounded-md px-4 py-3 text-sm" style="background: var(--calv-sage-15); color: #2F5A41; border: 1px solid var(--calv-sage-35);">
            {{ session('homes_message') }}
        </div>
    @endif

    <a href="{{ route('homes.settings') }}" wire:navigate class="calv-link inline-block mb-3" style="font-size: 12.5px;">← Settings</a>

    <div class="mb-5">
        <h1 class="calv-h1" style="font-size: 30px;">Dashboard widgets</h1>
        <div class="text-[13.5px] mt-1.5" style="color: var(--calv-slate-65);">
            Choose which cards the Today home shows. This is site-wide — it changes the dashboard for everyone using the portal.
        </div>
    </div>

    <div class="calv-card overflow-hidden">
        @foreach($registry as $key => $widget)
            @php $on = (bool) ($widgets[$key] ?? false); @endphp
            <label class="flex items-start gap-3.5 px-5 py-4"
                   style="border-bottom: 1px solid var(--calv-slate-15); cursor: pointer;">
                <input
                    type="checkbox"
                    wire:model="widgets.{{ $key }}"
                    class="mt-0.5 rounded"
                    style="accent-color: var(--calv-teal); width: 16px; height: 16px;">
                <span class="flex-1 min-w-0">
                    <span class="flex items-center gap-2 flex-wrap">
                        <span class="text-[14.5px] font-semibold" style="color: var(--calv-slate);">{{ $widget['label'] }}</span>
                        @if(! $defaults[$key])
                            <span class="calv-badge" style="font-size: 9.5px;">Off by default</span>
                        @endif
                    </span>
                    <span class="block text-[12.5px] mt-1 leading-snug" style="color: var(--calv-slate-65);">{{ $widget['sub'] }}</span>
                </span>
                <span class="shrink-0 calv-subhead" style="font-size: 10px; color: {{ $on ? 'var(--calv-teal)' : 'var(--calv-slate-65)' }};">
                    {{ $on ? 'Showing' : 'Hidden' }}
                </span>
            </label>
        @endforeach

        <div class="flex items-center gap-2 px-5 py-4" style="background: var(--calv-sand-15);">
            <span class="flex-1 text-[12px]" style="color: var(--calv-slate-65);">
                Hiding every card leaves Today with just the greeting.
            </span>
            <button type="button" wire:click="resetToDefaults" class="calv-btn calv-btn--quiet">Reset to defaults</button>
            <button type="button" wire:click="save" class="calv-btn calv-btn--primary">Save changes</button>
        </div>
    </div>
</div>
