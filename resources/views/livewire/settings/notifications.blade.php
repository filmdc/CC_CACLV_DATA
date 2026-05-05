<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component
{
    public bool $assignment_email = true;

    public bool $completion_email = true;

    public bool $over_budget_email = true;

    public function mount(): void
    {
        $user = Auth::user();
        $prefs = $user->notification_preferences;

        $this->assignment_email = $prefs === null ? true : (bool) ($prefs['assignment_email'] ?? true);
        $this->completion_email = $prefs === null ? true : (bool) ($prefs['completion_email'] ?? true);
        $this->over_budget_email = $prefs === null ? true : (bool) ($prefs['over_budget_email'] ?? true);
    }

    public function save(): void
    {
        $user = Auth::user();
        $user->notification_preferences = [
            'assignment_email' => $this->assignment_email,
            'completion_email' => $this->completion_email,
            'over_budget_email' => $this->over_budget_email,
        ];
        $user->save();

        $this->dispatch('notification-preferences-updated');
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Notifications')" :subheading="__('Choose which emails you want to receive. In-app notifications always fire — these toggles only control email.')">
        <form wire:submit="save" class="my-6 w-full space-y-6">

            <div class="space-y-4">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox"
                           wire:model="assignment_email"
                           class="mt-1 rounded border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 text-indigo-600 focus:ring-indigo-500" />
                    <div class="flex-1">
                        <div class="font-medium text-gray-900 dark:text-white">
                            {{ __('Assignments and upstream uploads') }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                            {{ __('Email me when a requirement is assigned to me, and when an attachment is added to an upstream requirement that one of mine depends on.') }}
                        </div>
                    </div>
                </label>

                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox"
                           wire:model="completion_email"
                           class="mt-1 rounded border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 text-indigo-600 focus:ring-indigo-500" />
                    <div class="flex-1">
                        <div class="font-medium text-gray-900 dark:text-white">
                            {{ __('Completion of items assigned to me') }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                            {{ __('Email me when someone else completes a requirement assigned to me.') }}
                        </div>
                    </div>
                </label>

                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox"
                           wire:model="over_budget_email"
                           class="mt-1 rounded border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 text-indigo-600 focus:ring-indigo-500" />
                    <div class="flex-1">
                        <div class="font-medium text-gray-900 dark:text-white">
                            {{ __('Over-budget alerts') }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                            {{ __('Email me when a project budget category is modified — increase or decrease. Only delivered if an admin has added you to the over-budget recipient list; this toggle has no effect otherwise.') }}
                        </div>
                    </div>
                </label>
            </div>

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit">{{ __('Save preferences') }}</flux:button>

                <x-action-message class="me-3" on="notification-preferences-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>
    </x-settings.layout>
</section>
