<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MediaController extends Controller
{
    use AuthorizesRequests;

    /**
     * Preview a media file
     */
    public function preview(Media $media): BinaryFileResponse
    {
        $this->authorize('view', $media);
        $path = $this->ensureAccessibleMedia($media);

        return response()->file($path, [
            'Content-Type' => $media->mime_type,
            'Content-Disposition' => 'inline; filename="'.$media->file_name.'"',
        ]);
    }

    /**
     * Download a media file
     */
    public function download(Media $media): BinaryFileResponse
    {
        $this->authorize('view', $media);
        $path = $this->ensureAccessibleMedia($media);

        return response()->download($path, $media->file_name);
    }

    /**
     * Ensure the authenticated user can access the requested media record.
     */
    private function ensureAccessibleMedia(Media $media): string
    {
        abort_unless(Auth::check(), 403, 'Unauthorized');

        $path = $media->getPath();
        if (! is_string($path) || ! file_exists($path)) {
            abort(404, 'File not found');
        }

        return $path;
    }
}
