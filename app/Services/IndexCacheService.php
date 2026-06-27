<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class IndexCacheService
{
    /**
     * Clave base para el cache del índice
     */
    const CACHE_PREFIX = 'law_index_v2';
    
    /**
     * Duración del cache en minutos (24 horas)
     */
    const CACHE_DURATION = 1440; // 24 horas en minutos

    /**
     * Tag para agrupar todas las claves de cache del índice
     */
    const CACHE_TAG = 'law_index';

    /**
     * Obtener clave de cache para el índice
     */
    public static function getCacheKey(string $type): string
    {
        return self::CACHE_PREFIX . ':' . $type;
    }

    /**
     * Verificar si el driver de cache soporta tags
     */
    private static function supportsTags(): bool
    {
        $driver = config('cache.default');
        return in_array($driver, ['redis', 'memcached']);
    }

    /**
     * Obtener datos del cache o ejecutar callback y guardar
     */
    public static function remember(string $type, callable $callback)
    {
        $cacheKey = self::getCacheKey($type);
        
        if (self::supportsTags()) {
            return Cache::tags([self::CACHE_TAG])->remember(
                $cacheKey,
                now()->addMinutes(self::CACHE_DURATION),
                $callback
            );
        }
        
        // Fallback para drivers que no soportan tags
        return Cache::remember(
            $cacheKey,
            now()->addMinutes(self::CACHE_DURATION),
            $callback
        );
    }

    /**
     * Invalidar todo el cache del índice
     */
    public static function clear(): void
    {
        if (self::supportsTags()) {
            Cache::tags([self::CACHE_TAG])->flush();
        } else {
            // Sin tags, invalidar manualmente cada tipo
            self::clearAll();
        }
    }

    /**
     * Invalidar cache específico por tipo
     */
    public static function clearByType(string $type): void
    {
        $cacheKey = self::getCacheKey($type);
        
        if (self::supportsTags()) {
            Cache::tags([self::CACHE_TAG])->forget($cacheKey);
        } else {
            Cache::forget($cacheKey);
        }
    }

    /**
     * Invalidar todos los tipos de cache del índice
     */
    public static function clearAll(): void
    {
        // Invalidar cache para cada tipo posible
        self::clearByType('ley');
        self::clearByType('reglamento');
        self::clearByType('ambos');
    }
}
