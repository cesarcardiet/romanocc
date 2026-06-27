<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\StorageUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StorageFileController extends Controller
{
    /**
     * GET /api/files/{encodedPath}?expires=...&signature=...
     * Sirve archivos privados (Garage/S3/R2) a través de Laravel con URL firmada.
     */
    public function show(Request $request, string $encodedPath): StreamedResponse
    {
        $path = StorageUrl::decodePath($encodedPath);

        if ($path === null || str_contains($path, '..')) {
            abort(404);
        }

        $disk = StorageUrl::uploadDisk();

        if (! Storage::disk($disk)->exists($path)) {
            abort(404);
        }

        return Storage::disk($disk)->response($path);
    }
}
