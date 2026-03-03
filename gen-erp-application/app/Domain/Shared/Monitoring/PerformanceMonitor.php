<?php

namespace App\Domain\Shared\Monitoring;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Performance monitoring service for tracking application metrics.
 */
class PerformanceMonitor
{
    private array $timers = [];
    private array $counters = [];
    private array $metrics = [];

    /**
     * Start a performance timer.
     */
    public function startTimer(string $name): void
    {
        $this->timers[$name] = [
            'start' => microtime(true),
            'memory_start' => memory_get_usage(true),
        ];
    }

    /**
     * Stop a performance timer and log the result.
     */
    public function stopTimer(string $name): float
    {
        if (!isset($this->timers[$name])) {
            Log::warning('Timer not found', ['timer' => $name]);
            return 0.0;
        }

        $timer = $this->timers[$name];
        $duration = microtime(true) - $timer['start'];
        $memoryUsed = memory_get_usage(true) - $timer['memory_start'];

        $this->metrics[$name] = [
            'duration' => $duration,
            'memory_used' => $memoryUsed,
            'timestamp' => now(),
        ];

        // Log slow operations
        if ($duration > 1.0) { // More than 1 second
            Log::warning('Slow operation detected', [
                'operation' => $name,
                'duration' => $duration,
                'memory_used' => $memoryUsed,
            ]);
        }

        // Store metrics for analysis
        $this->storeMetric($name, $duration, $memoryUsed);

        unset($this->timers[$name]);

        return $duration;
    }

    /**
     * Increment a counter.
     */
    public function incrementCounter(string $name, int $value = 1): void
    {
        $this->counters[$name] = ($this->counters[$name] ?? 0) + $value;
        
        // Store counter in cache for aggregation
        $cacheKey = "metrics:counter:{$name}:" . now()->format('Y-m-d-H');
        Cache::increment($cacheKey, $value);
        Cache::expire($cacheKey, 86400); // 24 hours
    }

    /**
     * Record a custom metric.
     */
    public function recordMetric(string $name, float $value, array $tags = []): void
    {
        $this->metrics[$name] = [
            'value' => $value,
            'tags' => $tags,
            'timestamp' => now(),
        ];

        Log::info('Custom metric recorded', [
            'metric' => $name,
            'value' => $value,
            'tags' => $tags,
        ]);
    }

    /**
     * Get current metrics.
     */
    public function getMetrics(): array
    {
        return [
            'timers' => $this->timers,
            'counters' => $this->counters,
            'metrics' => $this->metrics,
        ];
    }

    /**
     * Monitor database query performance.
     */
    public function monitorQuery(string $sql, float $time): void
    {
        $this->incrementCounter('database.queries');
        
        if ($time > 0.1) { // Slow query threshold: 100ms
            $this->incrementCounter('database.slow_queries');
            
            Log::warning('Slow database query', [
                'sql' => $sql,
                'time' => $time,
            ]);
        }

        // Store query time for analysis
        $this->recordMetric('database.query_time', $time, ['sql' => substr($sql, 0, 100)]);
    }

    /**
     * Monitor cache performance.
     */
    public function monitorCache(string $operation, string $key, bool $hit = null): void
    {
        $this->incrementCounter("cache.{$operation}");
        
        if ($hit !== null) {
            $this->incrementCounter($hit ? 'cache.hits' : 'cache.misses');
        }

        Log::debug('Cache operation', [
            'operation' => $operation,
            'key' => $key,
            'hit' => $hit,
        ]);
    }

    /**
     * Monitor API endpoint performance.
     */
    public function monitorApiEndpoint(string $method, string $endpoint, int $statusCode, float $responseTime): void
    {
        $this->incrementCounter('api.requests');
        $this->incrementCounter("api.{$method}");
        $this->incrementCounter("api.status.{$statusCode}");
        
        $this->recordMetric('api.response_time', $responseTime, [
            'method' => $method,
            'endpoint' => $endpoint,
            'status' => $statusCode,
        ]);

        // Monitor slow API responses
        if ($responseTime > 2.0) { // 2 seconds threshold
            $this->incrementCounter('api.slow_responses');
            
            Log::warning('Slow API response', [
                'method' => $method,
                'endpoint' => $endpoint,
                'status' => $statusCode,
                'response_time' => $responseTime,
            ]);
        }
    }

    /**
     * Store metric for long-term analysis.
     */
    private function storeMetric(string $name, float $duration, int $memoryUsed): void
    {
        $hour = now()->format('Y-m-d-H');
        $cacheKey = "metrics:performance:{$name}:{$hour}";
        
        $existing = Cache::get($cacheKey, [
            'count' => 0,
            'total_duration' => 0,
            'total_memory' => 0,
            'max_duration' => 0,
            'min_duration' => PHP_FLOAT_MAX,
        ]);

        $existing['count']++;
        $existing['total_duration'] += $duration;
        $existing['total_memory'] += $memoryUsed;
        $existing['max_duration'] = max($existing['max_duration'], $duration);
        $existing['min_duration'] = min($existing['min_duration'], $duration);

        Cache::put($cacheKey, $existing, 86400); // 24 hours
    }

    /**
     * Get performance summary for a time period.
     */
    public function getPerformanceSummary(string $period = 'hour'): array
    {
        $pattern = match($period) {
            'hour' => now()->format('Y-m-d-H'),
            'day' => now()->format('Y-m-d'),
            default => now()->format('Y-m-d-H'),
        };

        // This would typically query a time-series database
        // For now, we'll return cached metrics
        $keys = Cache::get("metrics:keys:{$pattern}", []);
        $summary = [];

        foreach ($keys as $key) {
            $data = Cache::get($key);
            if ($data) {
                $summary[str_replace("metrics:performance:", "", $key)] = $data;
            }
        }

        return $summary;
    }
}