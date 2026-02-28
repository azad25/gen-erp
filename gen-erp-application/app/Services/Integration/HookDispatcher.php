<?php

namespace App\Services\Integration;

use App\Models\IntegrationHook;
use App\Jobs\RunHookHandlerJob;
use App\Services\CompanyContext;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Central hook dispatcher — fires action and filter hooks for the integration platform.
 * Actions run async via queued jobs. Filters run sync for data modification.
 */
class HookDispatcher
{
    /**
     * Fire an action hook — handlers run asynchronously via queue.
     * A failing handler never blocks the core operation.
     */
    public static function action(string $hook, mixed ...$args): void
    {
        if (! CompanyContext::hasActive()) {
            return;
        }

        $companyId = CompanyContext::activeId();

        $handlers = self::getHandlers($companyId, $hook);

        foreach ($handlers as $hookRecord) {
            try {
                RunHookHandlerJob::dispatch($hookRecord->id, serialize($args))
                    ->onQueue('integrations');
            } catch (Throwable $e) {
                Log::error("HookDispatcher: failed to dispatch handler for {$hook}", [
                    'hook_id' => $hookRecord->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Fire a filter hook — handlers run synchronously and can modify the value.
     * Only for simple, fast operations — no external HTTP calls in filters.
     */
    public static function filter(string $hook, mixed $value, mixed ...$args): mixed
    {
        if (! CompanyContext::hasActive()) {
            return $value;
        }

        $companyId = CompanyContext::activeId();

        $handlers = self::getHandlers($companyId, $hook);

        foreach ($handlers as $hookRecord) {
            try {
                $handler = app($hookRecord->handler_class);
                $value = $handler->filter($hook, $value, ...$args);
            } catch (Throwable $e) {
                Log::error("HookDispatcher: filter failed for {$hook}", [
                    'hook_id' => $hookRecord->id,
                    'error' => $e->getMessage(),
                ]);
                // On filter failure, return original value — safe fallback
            }
        }

        return $value;
    }

    /**
     * Get cached handlers for a hook in a company.
     *
     * @return \Illuminate\Database\Eloquent\Collection<IntegrationHook>
     */
    private static function getHandlers(int $companyId, string $hook)
    {
        return Cache::remember(
            "hooks:{$companyId}:{$hook}",
            300,
            fn () => IntegrationHook::forHook($hook, $companyId)->get()
        );
    }

    /** Clear all cached hooks for a company (call when hooks are updated). */
    public static function clearCache(int $companyId): void
    {
        // Clear all known hook caches for this company
        Cache::forget("hooks:{$companyId}:*");
    }

    /** Clear cache for a specific hook in a company. */
    public static function clearHookCache(int $companyId, string $hook): void
    {
        Cache::forget("hooks:{$companyId}:{$hook}");
    }
}
