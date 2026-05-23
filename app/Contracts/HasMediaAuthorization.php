<?php

namespace App\Contracts;

use App\Models\User;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * P-39a: per-record access rule for Spatie media downloads.
 *
 * Every model that implements Spatie's HasMedia must also implement
 * this contract. MediaPolicy::view consults canViewMedia() to decide
 * whether the authenticated user may preview or download a given media
 * file. The admin short-circuit and the orphan-media default-deny live
 * in MediaPolicy; this method should encode the per-record rule only.
 *
 * The single seam is intentional: any new media-bearing model that
 * forgets to implement this contract default-denies through MediaPolicy.
 */
interface HasMediaAuthorization
{
    public function canViewMedia(User $user, Media $media): bool;
}
