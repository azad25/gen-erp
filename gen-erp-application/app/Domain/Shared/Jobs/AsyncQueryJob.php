<?php

namespace App\Domain\Shared\Jobs;

use App\Domain\Shared\Queries\QueryInterface;
use App\Domain\Shared\Bus\QueryBus;
use App\Domain\Shared\Cache\CacheService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job for executing expensive queries asynchronously and caching results.
 */
class AsyncQueryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 600; // 10 minutes for complex queries
    public array $backoff = [60, 180];

    public function __construct(
        private readonly QueryInterface $query,
        private readonly string $cacheKey,
        private readonly int $cacheTtl = 3600
    ) {
        $this->onQueue('queries');
    }

    public function handle(QueryBus $queryBus, CacheService $cache): void
    {
        try {
            Log::info('Executing async query', [
                'query_id' => $this->query->getQueryId(),
                'query_type' => get_class($this->query),
                'cache_key' => $this->cacheKey,
            ]);

            $result = $queryBus->execute($this->query);

            // Cache the result
            $cache->put($this->cacheKey, $result, $this->cacheTtl);

            Log::info('Async query completed and cached', [
                'query_id' => $this->query->getQueryId(),
                'query_type' => get_class($this->query),
                'cache_key' => $this->cacheKey,
            ]);

        } catch (\Exception $e) {
            Log::error('Async query failed', [
                'query_id' => $this->query->getQueryId(),
                'query_type' => get_class($this->query),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Async query permanently failed', [
            'query_id' => $this->query->getQueryId(),
            'query_type' => get_class($this->query),
            'cache_key' => $this->cacheKey,
            'error' => $exception->getMessage(),
        ]);
    }
}