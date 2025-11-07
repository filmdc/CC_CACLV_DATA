<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * UserSageIntacctToken Model
 *
 * Stores OAuth 2.0 tokens for user connections to Sage Intacct REST API.
 * Each user can have one active connection.
 *
 * @property int $id
 * @property int $user_id
 * @property string $access_token Encrypted OAuth access token
 * @property string|null $refresh_token Encrypted OAuth refresh token
 * @property \Carbon\Carbon $expires_at Token expiration timestamp
 * @property string|null $company_id Sage Intacct company ID
 * @property string $token_type Token type (Bearer)
 * @property array|null $scopes OAuth scopes granted
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class UserSageIntacctToken extends Model
{
    protected $table = 'user_sage_intacct_tokens';

    protected $fillable = [
        'user_id',
        'access_token',
        'refresh_token',
        'expires_at',
        'company_id',
        'token_type',
        'scopes',
    ];

    protected $casts = [
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'expires_at' => 'datetime',
        'scopes' => 'array',
    ];

    /**
     * Get the user that owns this token
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the access token is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if the token needs refresh (expires in < 5 minutes)
     */
    public function needsRefresh(): bool
    {
        return $this->expires_at->subMinutes(5)->isPast();
    }

    /**
     * Check if the user has a valid, non-expired token
     */
    public function hasValidToken(): bool
    {
        return ! $this->isExpired();
    }

    /**
     * Update the access token and expiration
     */
    public function updateToken(
        string $accessToken,
        int $expiresIn,
        ?string $refreshToken = null
    ): bool {
        $data = [
            'access_token' => $accessToken,
            'expires_at' => now()->addSeconds($expiresIn),
        ];

        if ($refreshToken !== null) {
            $data['refresh_token'] = $refreshToken;
        }

        return $this->update($data);
    }

    /**
     * Check if connected to sandbox environment
     */
    public function isSandbox(): bool
    {
        return strtolower($this->company_id ?? '') === 'caclv-sandbox';
    }

    /**
     * Get a summary of the token status
     */
    public function getStatusSummary(): array
    {
        return [
            'connected' => true,
            'company_id' => $this->company_id,
            'is_sandbox' => $this->isSandbox(),
            'expires_at' => $this->expires_at,
            'expires_in_minutes' => now()->diffInMinutes($this->expires_at, false),
            'needs_refresh' => $this->needsRefresh(),
            'is_expired' => $this->isExpired(),
        ];
    }
}
