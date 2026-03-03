<?php

namespace App\Domain\Shared\Cache;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Centralized caching service with domain-aware cache keys.
 */
class CacheService
{
    private const DEFAULT_TTL = 3600; // 1 hour
    private const CACHE_PREFIX = 'erp';

    /**
     * Get cached data or execute callback and cache result.
     */
    public function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $cacheKey = $this->buildKey($key);
        $ttl = $ttl ?? self::DEFAULT_TTL;

        try {
            return Cache::remember($cacheKey, $ttl, $callback);
        } catch (\Exception $e) {
            Log::warning('Cache remember failed', [
                'key' => $cacheKey,
                'error' => $e->getMessage(),
            ]);
            
            // Fallback to direct execution if cache fails
            return $callback();
        }
    }

    /**
     * Store data in cache.
     */
    public function put(string $key, mixed $value, ?int $ttl = null): bool
    {
        $cacheKey = $this->buildKey($key);
        $ttl = $ttl ?? self::DEFAULT_TTL;

        try {
            return Cache::put($cacheKey, $value, $ttl);
        } catch (\Exception $e) {
            Log::warning('Cache put failed', [
                'key' => $cacheKey,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get data from cache.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $cacheKey = $this->buildKey($key);

        try {
            return Cache::get($cacheKey, $default);
        } catch (\Exception $e) {
            Log::warning('Cache get failed', [
                'key' => $cacheKey,
                'error' => $e->getMessage(),
            ]);
            return $default;
        }
    }

    /**
     * Remove data from cache.
     */
    public function forget(string $key): bool
    {
        $cacheKey = $this->buildKey($key);

        try {
            return Cache::forget($cacheKey);
        } catch (\Exception $e) {
            Log::warning('Cache forget failed', [
                'key' => $cacheKey,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Clear cache by pattern (tags-based).
     */
    public function forgetByTags(array $tags): bool
    {
        try {
            Cache::tags($tags)->flush();
            return true;
        } catch (\Exception $e) {
            Log::warning('Cache flush by tags failed', [
                'tags' => $tags,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Cache with tags for easier invalidation.
     */
    public function rememberWithTags(array $tags, string $key, callable $callback, ?int $ttl = null): mixed
    {
        $cacheKey = $this->buildKey($key);
        $ttl = $ttl ?? self::DEFAULT_TTL;

        try {
            return Cache::tags($tags)->remember($cacheKey, $ttl, $callback);
        } catch (\Exception $e) {
            Log::warning('Cache remember with tags failed', [
                'key' => $cacheKey,
                'tags' => $tags,
                'error' => $e->getMessage(),
            ]);
            
            return $callback();
        }
    }

    /**
     * Build cache key with prefix and company context.
     */
    private function buildKey(string $key): string
    {
        $companyId = auth()->user()?->company_id ?? 'global';
        return self::CACHE_PREFIX . ':' . $companyId . ':' . $key;
    }

    /**
     * Get cache key for invoice-related data.
     */
    public static function invoiceKey(int $invoiceId): string
    {
        return "invoice:{$invoiceId}";
    }

    /**
     * Get cache key for invoice list.
     */
    public static function invoiceListKey(array $filters = []): string
    {
        $filterHash = md5(serialize($filters));
        return "invoices:list:{$filterHash}";
    }

    /**
     * Get cache key for customer data.
     */
    public static function customerKey(int $customerId): string
    {
        return "customer:{$customerId}";
    }

    /**
     * Get cache key for product data.
     */
    public static function productKey(int $productId): string
    {
        return "product:{$productId}";
    }
}