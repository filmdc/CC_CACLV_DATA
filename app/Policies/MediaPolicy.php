<?php

namespace App\Policies;

use App\Contracts\HasMediaAuthorization;
use App\Models\User;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * P-39a: per-record authorization for Spatie media downloads.
 *
 * Order of checks:
 *   1. Admin short-circuit — admins see everything.
 *   2. Orphaned media (parent deleted, `$media->model` is null) — default deny.
 *   3. Parent does not implement HasMediaAuthorization — default deny.
 *      Any media-bearing model that forgets to opt into the contract
 *      is silently inaccessible, which is the intended fail-closed posture.
 *   4. Delegate to the parent's canViewMedia() rule.
 */
class MediaPolicy
{
    public function view(User $user, Media $media): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $parent = $media->model;

        if ($parent === null) {
            return false;
        }

        if (! $parent instanceof HasMediaAuthorization) {
            return false;
        }

        return $parent->canViewMedia($user, $media);
    }
}
