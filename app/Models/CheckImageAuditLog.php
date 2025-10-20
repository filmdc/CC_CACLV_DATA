<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class CheckImageAuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'filename',
        'gift_id',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public const UPDATED_AT = null; // Only track creation time

    /**
     * Get the user who performed the action
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Log a check image access event
     *
     * @param string $action Action type: 'downloaded', 'accessed', 'decrypted', 'cached', 'deleted'
     * @param string $filename The filename being accessed
     * @param string|null $giftId Optional gift ID associated with the file
     * @param array $metadata Optional additional metadata
     * @return void
     */
    public static function logAccess(string $action, string $filename, ?string $giftId = null, array $metadata = []): void
    {
        try {
            $request = request();

            self::create([
                'user_id' => auth()->id(),
                'action' => $action,
                'filename' => $filename,
                'gift_id' => $giftId,
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
                'metadata' => $metadata,
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to create audit log entry', [
                'action' => $action,
                'filename' => $filename,
                'error' => $e->getMessage()
            ]);
        }
    }
}
