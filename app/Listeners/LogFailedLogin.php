<?php

namespace App\Listeners;

use App\Models\User;
use App\Models\UserLoginAudit;
use Illuminate\Auth\Events\Failed;
use Illuminate\Support\Facades\Log;

class LogFailedLogin
{
    /**
     * Handle the event.
     */
    public function handle(Failed $event): void
    {
        try {
            $email = $event->credentials['email'] ?? 'unknown';

            $user = User::where('email', $email)->first();
            $reason = 'invalid_credentials';

            if ($user && ! $user->active) {
                $reason = 'account_inactive';
            }

            UserLoginAudit::logFailedLogin($email, $reason);

            Log::warning('Failed login attempt', [
                'email' => $email,
                'ip' => request()->ip(),
                'reason' => $reason,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log failed login', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
