<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Stamps deleted_by alongside Laravel's deleted_at so the admin recycle bin
 * can say who deleted a record, and clears it again on restore.
 *
 * Pair with Illuminate\Database\Eloquent\SoftDeletes on a model whose table
 * has both columns (see the 2026_08_31_110000 migration).
 */
trait TracksSoftDeleter
{
    public static function bootTracksSoftDeleter(): void
    {
        static::deleting(function ($model) {
            // forceDelete() removes the row outright — nothing to stamp.
            if (method_exists($model, 'isForceDeleting') && $model->isForceDeleting()) {
                return;
            }

            if (Auth::check() && $model->deleted_by === null) {
                // Written straight to the row: the SoftDeletes handler issues
                // its own UPDATE for deleted_at and would not carry a dirty
                // attribute set here along with it.
                $model->newQueryWithoutScopes()
                    ->where($model->getKeyName(), $model->getKey())
                    ->update(['deleted_by' => Auth::id()]);
                $model->deleted_by = Auth::id();
                $model->syncOriginalAttribute('deleted_by');
            }
        });

        static::restoring(function ($model) {
            $model->deleted_by = null;
        });
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
