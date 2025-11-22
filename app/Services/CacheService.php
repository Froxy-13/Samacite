<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Centralized cache management service
 * Handles cache operations with TTL
 */
class CacheService
{
    // Cache TTLs (in seconds)
    const CACHE_TTL_CATEGORIES = 3600;      // 1 hour
    const CACHE_TTL_REPORTS = 60;           // 1 minute
    const CACHE_TTL_STATS = 3600;           // 1 hour

    /**
     * Get cached data or return null
     */
    public function get(string $key)
    {
        return Cache::get($key);
    }

    /**
     * Put data in cache with TTL
     */
    public function put(string $key, $data, int $ttl): void
    {
        Cache::put($key, $data, $ttl);
    }

    /**
     * Remember cached data - if not exists, execute callback and cache result
     */
    public function remember(string $key, int $ttl, callable $callback)
    {
        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Flush specific cache key
     */
    public function forget(string $key): void
    {
        Cache::forget($key);
    }

    /**
     * Cache categories with 1 hour TTL
     */
    public function cacheCategories($categories): void
    {
        $this->put('cache:categories', $categories, self::CACHE_TTL_CATEGORIES);
    }

    /**
     * Get cached categories
     */
    public function getCachedCategories()
    {
        return $this->get('cache:categories');
    }

    /**
     * Invalidate category cache
     */
    public function invalidateCategories(): void
    {
        $this->forget('cache:categories');
    }

    /**
     * Cache reports with 1 minute TTL
     */
    public function cacheReports($reports): void
    {
        $this->put('cache:reports', $reports, self::CACHE_TTL_REPORTS);
    }

    /**
     * Get cached reports
     */
    public function getCachedReports()
    {
        return $this->get('cache:reports');
    }

    /**
     * Invalidate reports cache
     */
    public function invalidateReports(): void
    {
        $this->forget('cache:reports');
    }

    /**
     * Cache statistics with 1 hour TTL
     */
    public function cacheStats($key, $stats): void
    {
        $this->put('cache:stats:' . $key, $stats, self::CACHE_TTL_STATS);
    }

    /**
     * Get cached statistics
     */
    public function getCachedStats(string $key)
    {
        return $this->get('cache:stats:' . $key);
    }

    /**
     * Invalidate all stats cache
     */
    public function invalidateStats(): void
    {
        $this->forget('cache:stats:global_stats');
    }

    /**
     * Invalidate all application caches
     */
    public function invalidateAll(): void
    {
        Cache::flush();
    }
}
