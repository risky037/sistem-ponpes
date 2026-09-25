<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StorageFallbackController extends Controller
{
    /**
     * Safely serve public storage assets when symlink is unavailable (e.g. shared hosting).
     */
    public function show(string $path): BinaryFileResponse
    {
        // Sanitize path to prevent directory traversal
        $normalized = str_replace(['../', '..\\'], '', $path);
        $fullPath = storage_path('app/public/'.$normalized);

        if (! file_exists($fullPath) || is_dir($fullPath)) {
            abort(404);
        }

        $publicStorage = realpath(storage_path('app/public'));
        $realFile = realpath($fullPath);

        if ($realFile === false || ! str_starts_with($realFile, $publicStorage)) {
            abort(403);
        }

        return response()->file($realFile, [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
