<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class StorageUrl
{
    /**
     * Disco donde Filament guarda archivos finales (adjuntos, PDFs, etc.).
     */
    public static function uploadDisk(): string
    {
        return (string) config(
            'filesystems.filament_upload_disk',
            config('filesystems.default', 'public')
        );
    }

    /**
     * URL pública absoluta para un path guardado en BD.
     */
    public static function forPath(?string $path, ?string $disk = null): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $disk = $disk ?? self::uploadDisk();
        $normalizedPath = self::normalizePath($path);

        if (in_array($disk, ['public', 'local'], true)) {
            $base = rtrim((string) config('app.url'), '/');
            if (str_starts_with($normalizedPath, '/storage/') || str_starts_with($normalizedPath, 'storage/')) {
                return $base . (str_starts_with($normalizedPath, '/') ? $normalizedPath : '/' . $normalizedPath);
            }

            return $base . '/storage/' . ltrim($normalizedPath, '/');
        }

        return self::signedProxyUrl($normalizedPath);
    }

    /**
     * URL firmada temporal que Laravel usa para leer el archivo del bucket privado.
     */
    public static function signedProxyUrl(string $path, ?int $ttlMinutes = null): string
    {
        $ttlMinutes = $ttlMinutes ?? (int) config('filesystems.signed_url_ttl', 1440);

        return URL::temporarySignedRoute(
            'api.files.download',
            now()->addMinutes($ttlMinutes),
            ['encodedPath' => self::encodePath($path)]
        );
    }

    public static function encodePath(string $path): string
    {
        return rtrim(strtr(base64_encode(self::normalizePath($path)), '+/', '-_'), '=');
    }

    public static function decodePath(string $encodedPath): ?string
    {
        $padded = strtr($encodedPath, '-_', '+/');
        $padding = strlen($padded) % 4;
        if ($padding > 0) {
            $padded .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode($padded, true);

        return $decoded === false ? null : self::normalizePath($decoded);
    }

    public static function normalizePath(string $path): string
    {
        return ltrim(str_replace('\\', '/', $path), '/');
    }

    /**
     * @deprecated Usar forPath(); se mantiene por compatibilidad interna.
     */
    public static function directDiskUrl(string $path, string $disk): string
    {
        return Storage::disk($disk)->url($path);
    }
}
