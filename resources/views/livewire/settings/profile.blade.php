<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component {
    public string $name = '';
    public string $email = '';

    /**
     * Phase 12.8: per-user default view-mode for the Communications Log
     * list page. '' = no preference (use the hardcoded 'table' default).
     */
    public string $comm_log_default_view = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
        $this->comm_log_default_view = Auth::user()->comm_log_default_view ?? '';
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id)
            ],

            // Phase 12.8: empty string = no preference (null in DB).
            'comm_log_default_view' => ['nullable', 'in:,table,card'],
        ]);

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'comm_log_default_view' => $validated['comm_log_default_view'] !== '' ? $validated['comm_log_default_view'] : null,
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Profile')" :subheading="__('Update your name and email address')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name" />

            <div>
                <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

                @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail &&! auth()->user()->hasVerifiedEmail())
                    <div>
                        <flux:text class="mt-4">
                            {{ __('Your email address is unverified.') }}

                            <flux:link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                                {{ __('Click here to re-send the verification email.') }}
                            </flux:link>
                        </flux:text>

                        @if (session('status') === 'verification-link-sent')
                            <flux:text class="mt-2 font-medium !dark:text-green-400 !text-green-600">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </flux:text>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Phase 12.8: per-user default view-mode for the Communications Log list. --}}
            <div>
                <label for="comm_log_default_view" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ __('Communications Log default view') }}
                </label>
                <select wire:model="comm_log_default_view" id="comm_log_default_view"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">{{ __('No preference (use Table)') }}</option>
                    <option value="table">{{ __('Table — dense columns, scan-friendly') }}</option>
                    <option value="card">{{ __('Card — full body, fund codes, contact metadata') }}</option>
                </select>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {{ __('A ?viewMode= URL parameter overrides this for shared links.') }}
                </p>
                @error('comm_log_default_view') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full">{{ __('Save') }}</flux:button>
                </div>

                <x-action-message class="me-3" on="profile-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>

        <livewire:settings.approval-delegate />

        <livewire:settings.delete-user-form />
    </x-settings.layout>
</section>
