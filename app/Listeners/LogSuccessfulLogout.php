<?php

namespace App\Listeners;

use App\Models\UserLoginAudit;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Log;

class LogSuccessfulLogout
{
    /**
     * Handle the event.
     */
    public function handle(Logout $event): void
    {
        try {
            if ($event->user) {
                UserLoginAudit::logLogout($event->user);

                Log::info('User logged out', [
                    'user_id' => $event->user->id,
                    'email' => $event->user->email,
                    'ip' => request()->ip(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to log logout', [
                'error' => $e->getMessage(),
                'user_id' => $event->user->id ?? null,
            ]);
        }
    }
}
