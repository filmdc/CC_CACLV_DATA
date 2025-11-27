<?php

namespace App\Listeners;

use App\Models\UserLoginAudit;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Log;

class LogLockout
{
    /**
     * Handle the event.
     */
    public function handle(Lockout $event): void
    {
        try {
            $email = $event->request->input('email', 'unknown');

            UserLoginAudit::logFailedLogin($email, 'rate_limited');

            Log::warning('Login rate limit exceeded', [
                'email' => $email,
                'ip' => $event->request->ip(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log lockout', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
