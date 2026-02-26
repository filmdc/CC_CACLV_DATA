<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public ?int $approvalDelegateId = null;

    public function mount(): void
    {
        $this->approvalDelegateId = Auth::user()->approval_delegate_id;
    }

    public function saveDelegate(): void
    {
        $user = Auth::user();

        $this->validate([
            'approvalDelegateId' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        if ($this->approvalDelegateId) {
            // Prevent self-delegation
            if ((int) $this->approvalDelegateId === $user->id) {
                $this->addError('approvalDelegateId', 'You cannot delegate to yourself.');
                return;
            }

            // Prevent circular delegation (A→B while B→A)
            $delegate = User::find($this->approvalDelegateId);
            if ($delegate && (int) $delegate->approval_delegate_id === $user->id) {
                $this->addError('approvalDelegateId', 'Circular delegation is not allowed. This user already delegates to you.');
                return;
            }
        }

        $user->update([
            'approval_delegate_id' => $this->approvalDelegateId ?: null,
        ]);

        $this->dispatch('delegate-updated');
    }

    public function clearDelegate(): void
    {
        $this->approvalDelegateId = null;
        $this->saveDelegate();
    }

    public function getEligibleDelegatesProperty()
    {
        return User::active()
            ->where('id', '!=', Auth::id())
            ->whereHas('roles.permissions', function ($query) {
                $query->where('name', 'approvals.approve_reject');
            })
            ->orderBy('name')
            ->get();
    }
}; ?>

@if(auth()->user()->hasPermission('approvals.approve_reject'))
<section class="w-full mt-6">
    <x-settings.layout :heading="__('Approval Delegate')" :subheading="__('Choose someone to receive and act on your approval notifications')">
        <form wire:submit="saveDelegate" class="my-6 w-full space-y-6">
            <div>
                <label for="approvalDelegateId" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Delegate
                </label>
                <div class="flex items-center gap-2">
                    <select
                        wire:model="approvalDelegateId"
                        id="approvalDelegateId"
                        class="flex-1 rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">None</option>
                        @foreach($this->eligibleDelegates as $delegate)
                            <option value="{{ $delegate->id }}">{{ $delegate->name }}</option>
                        @endforeach
                    </select>
                    @if($approvalDelegateId)
                        <button type="button" wire:click="clearDelegate"
                                class="px-3 py-2 text-sm text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 border border-red-300 dark:border-red-600 rounded-md hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                            Clear
                        </button>
                    @endif
                </div>
                @error('approvalDelegateId') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit" class="w-full">{{ __('Save') }}</flux:button>

                <x-action-message class="me-3" on="delegate-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>
    </x-settings.layout>
</section>
@endif
