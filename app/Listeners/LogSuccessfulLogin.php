<?php

namespace App\Listeners;

use App\Models\UserLoginAudit;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Log;

class LogSuccessfulLogin
{
    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        try {
            UserLoginAudit::logLogin($event->user);

            Log::info('User logged in', [
                'user_id' => $event->user->id,
                'email' => $event->user->email,
                'ip' => request()->ip(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log successful login', [
                'error' => $e->getMessage(),
                'user_id' => $event->user->id ?? null,
            ]);
        }
    }
}
