<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaController extends Controller
{
    /**
     * Preview a media file
     */
    public function preview(Media $media)
    {
        // Check if user has access (you can add authorization here)
        if (!auth()->check()) {
            abort(403);
        }

        $path = $media->getPath();

        if (!file_exists($path)) {
            abort(404, 'File not found');
        }

        return response()->file($path, [
            'Content-Type' => $media->mime_type,
            'Content-Disposition' => 'inline; filename="' . $media->file_name . '"',
        ]);
    }

    /**
     * Download a media file
     */
    public function download(Media $media)
    {
        // Check if user has access
        if (!auth()->check()) {
            abort(403);
        }

        $path = $media->getPath();

        if (!file_exists($path)) {
            abort(404, 'File not found');
        }

        return response()->download($path, $media->file_name);
    }
}
