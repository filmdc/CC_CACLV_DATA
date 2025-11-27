<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;

class UserLoginAudit extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'email',
        'action',
        'ip_address',
        'user_agent',
        'browser',
        'platform',
        'device_type',
        'failure_reason',
        'session_id',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function logLogin(User $user, ?Request $request = null): self
    {
        $request = $request ?? request();

        return self::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'action' => 'login_success',
            'ip_address' => $request->ip() ?? 'unknown',
            'user_agent' => $request->userAgent(),
            'browser' => self::parseBrowser($request->userAgent()),
            'platform' => self::parsePlatform($request->userAgent()),
            'device_type' => self::parseDeviceType($request->userAgent()),
            'session_id' => session()->getId(),
            'created_at' => now(),
        ]);
    }

    public static function logLogout(?User $user = null, ?Request $request = null): ?self
    {
        $user = $user ?? auth()->user();
        if (! $user) {
            return null;
        }

        $request = $request ?? request();

        return self::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'action' => 'logout',
            'ip_address' => $request->ip() ?? 'unknown',
            'user_agent' => $request->userAgent(),
            'browser' => self::parseBrowser($request->userAgent()),
            'platform' => self::parsePlatform($request->userAgent()),
            'device_type' => self::parseDeviceType($request->userAgent()),
            'session_id' => session()->getId(),
            'created_at' => now(),
        ]);
    }

    public static function logFailedLogin(string $email, ?string $reason = null, ?Request $request = null): self
    {
        $request = $request ?? request();

        $user = User::where('email', $email)->first();

        return self::create([
            'user_id' => $user?->id,
            'email' => $email,
            'action' => 'login_failed',
            'ip_address' => $request->ip() ?? 'unknown',
            'user_agent' => $request->userAgent(),
            'browser' => self::parseBrowser($request->userAgent()),
            'platform' => self::parsePlatform($request->userAgent()),
            'device_type' => self::parseDeviceType($request->userAgent()),
            'failure_reason' => $reason ?? 'invalid_credentials',
            'created_at' => now(),
        ]);
    }

    public static function logPasswordResetRequested(string $email, ?Request $request = null): self
    {
        $request = $request ?? request();

        $user = User::where('email', $email)->first();

        return self::create([
            'user_id' => $user?->id,
            'email' => $email,
            'action' => 'password_reset_requested',
            'ip_address' => $request->ip() ?? 'unknown',
            'user_agent' => $request->userAgent(),
            'browser' => self::parseBrowser($request->userAgent()),
            'platform' => self::parsePlatform($request->userAgent()),
            'device_type' => self::parseDeviceType($request->userAgent()),
            'created_at' => now(),
        ]);
    }

    public static function logPasswordResetCompleted(User $user, ?Request $request = null): self
    {
        $request = $request ?? request();

        return self::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'action' => 'password_reset_completed',
            'ip_address' => $request->ip() ?? 'unknown',
            'user_agent' => $request->userAgent(),
            'browser' => self::parseBrowser($request->userAgent()),
            'platform' => self::parsePlatform($request->userAgent()),
            'device_type' => self::parseDeviceType($request->userAgent()),
            'created_at' => now(),
        ]);
    }

    protected static function parseBrowser(?string $userAgent): ?string
    {
        if (! $userAgent) {
            return null;
        }

        $browsers = [
            'Edge' => '/Edg[e\/]?/i',
            'Opera' => '/Opera|OPR/i',
            'Chrome' => '/Chrome/i',
            'Safari' => '/Safari/i',
            'Firefox' => '/Firefox/i',
            'IE' => '/MSIE|Trident/i',
        ];

        foreach ($browsers as $browser => $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return $browser;
            }
        }

        return 'Other';
    }

    protected static function parsePlatform(?string $userAgent): ?string
    {
        if (! $userAgent) {
            return null;
        }

        $platforms = [
            'Windows' => '/Windows/i',
            'macOS' => '/Macintosh|Mac OS/i',
            'Linux' => '/Linux/i',
            'iOS' => '/iPhone|iPad|iPod/i',
            'Android' => '/Android/i',
            'Chrome OS' => '/CrOS/i',
        ];

        foreach ($platforms as $platform => $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return $platform;
            }
        }

        return 'Other';
    }

    protected static function parseDeviceType(?string $userAgent): ?string
    {
        if (! $userAgent) {
            return null;
        }

        if (preg_match('/Mobile|Android.*Mobile|iPhone|iPod/i', $userAgent)) {
            return 'Mobile';
        }

        if (preg_match('/iPad|Android(?!.*Mobile)|Tablet/i', $userAgent)) {
            return 'Tablet';
        }

        return 'Desktop';
    }

    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'login_success' => 'Login',
            'login_failed' => 'Failed Login',
            'logout' => 'Logout',
            'session_expired' => 'Session Expired',
            'password_reset_requested' => 'Password Reset Requested',
            'password_reset_completed' => 'Password Reset Completed',
            default => ucfirst(str_replace('_', ' ', $this->action)),
        };
    }

    public function getActionColorAttribute(): string
    {
        return match ($this->action) {
            'login_success' => 'green',
            'login_failed' => 'red',
            'logout' => 'gray',
            'session_expired' => 'yellow',
            'password_reset_requested' => 'blue',
            'password_reset_completed' => 'indigo',
            default => 'gray',
        };
    }

    public function scopeSuccessfulLogins($query)
    {
        return $query->where('action', 'login_success');
    }

    public function scopeFailedLogins($query)
    {
        return $query->where('action', 'login_failed');
    }

    public function scopeLogouts($query)
    {
        return $query->where('action', 'logout');
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByEmail($query, string $email)
    {
        return $query->where('email', $email);
    }

    public function scopeByIp($query, string $ip)
    {
        return $query->where('ip_address', $ip);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('email', 'like', "%{$search}%")
                ->orWhere('ip_address', 'like', "%{$search}%")
                ->orWhereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%");
                });
        });
    }
}
