<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * P-17 (Phase 3A): in-app notification bell. Renders Auth::user()'s
 * recent notifications with an unread-count badge; click to expand,
 * click an item to mark-as-read.
 *
 * Reads through Laravel's Notifiable trait — no custom storage table,
 * no separate dispatch service. Phase 3B will add 'mail' to each
 * Notification's via() to deliver email alongside the in-app surface.
 */
class NotificationBell extends Component
{
    public bool $open = false;

    public function toggle(): void
    {
        $this->open = ! $this->open;
    }

    public function markAsRead(string $id): void
    {
        $user = Auth::user();

        if ($user === null) {
            return;
        }

        $notification = $user->notifications()->where('id', $id)->first();

        if ($notification !== null && $notification->read_at === null) {
            $notification->markAsRead();
        }
    }

    public function markAllAsRead(): void
    {
        $user = Auth::user();

        if ($user === null) {
            return;
        }

        $user->unreadNotifications->markAsRead();
    }

    public function render()
    {
        $user = Auth::user();

        return view('livewire.notification-bell', [
            'unreadCount' => $user ? $user->unreadNotifications()->count() : 0,
            'recent' => $user
                ? $user->notifications()->latest()->limit(10)->get()
                : collect(),
        ]);
    }
}
