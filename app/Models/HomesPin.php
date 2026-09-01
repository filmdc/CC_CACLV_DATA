<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A Housing-portal pin: one record the user wants kept in front of her.
 *
 * pin_type:    'call' (CommunicationLog) | 'project' (HousingJob)
 * pinnable_id: the id of that record
 *
 * Pins are personal — there is no shared/global pin, and no new permission:
 * a pin is only ever readable by the user who made it, and the screens that
 * surface pins already gate on communications_log.view / projects.view.
 */
class HomesPin extends Model
{
    public const TYPE_CALL = 'call';

    public const TYPE_PROJECT = 'project';

    protected $fillable = [
        'user_id',
        'pin_type',
        'pinnable_id',
    ];

    protected $casts = [
        'pinnable_id' => 'integer',
    ];

    /** The permission a user must hold to pin/see records of a given type. */
    public static function permissionFor(string $type): ?string
    {
        return match ($type) {
            self::TYPE_CALL => 'communications_log.view',
            self::TYPE_PROJECT => 'projects.view',
            default => null,
        };
    }

    public static function isValidType(string $type): bool
    {
        return in_array($type, [self::TYPE_CALL, self::TYPE_PROJECT], true);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('pin_type', $type);
    }

    /** Pinned ids for one user+type, in the order they were pinned. */
    public static function idsFor(int $userId, string $type): array
    {
        return static::query()
            ->forUser($userId)
            ->ofType($type)
            ->orderBy('id')
            ->pluck('pinnable_id')
            ->all();
    }

    /** Flip a pin on/off. Returns the resulting pinned state. */
    public static function toggle(int $userId, string $type, int $id): bool
    {
        $existing = static::query()
            ->forUser($userId)
            ->ofType($type)
            ->where('pinnable_id', $id)
            ->first();

        if ($existing) {
            $existing->delete();

            return false;
        }

        static::create([
            'user_id' => $userId,
            'pin_type' => $type,
            'pinnable_id' => $id,
        ]);

        return true;
    }
}
