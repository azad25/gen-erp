<?php

namespace App\Domain\System\Services;

use App\Models\Plan;
use App\Domain\Subscription\Models\UsageCounter;
use Illuminate\Support\Facades\Log;

/**
 * Tracks and enforces plan-based resource usage limits.
 */
class UsageCounterService
{
    /** Counter keys that map to plan limits. */
    public const COUNTER_KEYS = [
        'products',
        'users',
        'branches',
        'storage_bytes',
        'integrations',
        'custom_fields',
    ];

    /**
     * Initialize usage counters for a company based on their plan limits.
     */
    public function initializeForPlan(int $companyId, Plan $plan): void
    {
        foreach (self::COUNTER_KEYS as $key) {
            UsageCounter::withoutGlobalScopes()->updateOrCreate(
                ['company_id' => $companyId, 'counter_key' => $key],
                ['max_value' => $plan->getLimit($key)],
            );
        }
    }

    /**
     * Initialize a specific counter.
     */
    public function initializeCounter(int $companyId, string $key, int $limit): void
    {
        UsageCounter::withoutGlobalScopes()->updateOrCreate(
            ['company_id' => $companyId, 'counter_key' => $key],
            ['current_value' => 0, 'max_value' => $limit],
        );
    }

    /**
     * Get current usage for a counter.
     */
    public function getCurrentUsage(int $companyId, string $key): int
    {
        $counter = UsageCounter::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('counter_key', $key)
            ->first();
        
        return $counter ? $counter->current_value : 0;
    }

    /**
     * Get limit for a counter.
     */
    public function getLimit(int $companyId, string $key): int
    {
        $counter = UsageCounter::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('counter_key', $key)
            ->first();
        
        return $counter ? $counter->max_value : 0;
    }

    /**
     * Get remaining usage for a counter.
     */
    public function getRemainingUsage(int $companyId, string $key): int
    {
        $counter = $this->getOrCreate($companyId, $key);
        if ($counter->max_value === -1) {
            return -1; // Unlimited
        }
        return max(0, $counter->max_value - $counter->current_value);
    }

    /**
     * Increment usage for a counter.
     */
    public function incrementUsage(int $companyId, string $key, int $amount = 1): void
    {
        $counter = $this->getOrCreate($companyId, $key);
        $counter->increment('current_value', $amount);
    }

    /**
     * Decrement usage for a counter.
     */
    public function decrementUsage(int $companyId, string $key, int $amount = 1): void
    {
        $counter = $this->getOrCreate($companyId, $key);
        $newValue = max(0, $counter->current_value - $amount);
        $counter->update(['current_value' => $newValue]);
    }

    /**
     * Check if limit is exceeded.
     */
    public function isLimitExceeded(int $companyId, string $key): bool
    {
        $counter = $this->getOrCreate($companyId, $key);
        if ($counter->max_value === -1) {
            return false; // Unlimited
        }
        return $counter->current_value > $counter->max_value;
    }

    /**
     * Reset usage for a counter.
     */
    public function resetUsage(int $companyId, string $key): void
    {
        $counter = $this->getOrCreate($companyId, $key);
        $counter->update(['current_value' => 0]);
    }

    /**
     * Update limit for a counter.
     */
    public function updateLimit(int $companyId, string $key, int $newLimit): void
    {
        $counter = $this->getOrCreate($companyId, $key);
        $counter->update(['max_value' => $newLimit]);
    }

    /**
     * Increment a counter. Throws if limit would be exceeded.
     *
     * @throws \App\Exceptions\PlanLimitExceededException
     */
    public function increment(int $companyId, string $key, int $amount = 1): UsageCounter
    {
        $counter = $this->getOrCreate($companyId, $key);

        if ($counter->max_value !== -1 && ($counter->current_value + $amount) > $counter->max_value) {
            throw new \App\Exceptions\PlanLimitExceededException($key, $counter->max_value);
        }

        $counter->increment('current_value', $amount);
        $counter->refresh();

        return $counter;
    }

    /**
     * Decrement a counter (e.g., when deleting a product).
     */
    public function decrement(int $companyId, string $key, int $amount = 1): UsageCounter
    {
        $counter = $this->getOrCreate($companyId, $key);
        $newValue = max(0, $counter->current_value - $amount);
        $counter->update(['current_value' => $newValue]);

        return $counter;
    }

    /**
     * Check if adding `amount` would exceed the limit.
     */
    public function wouldExceed(int $companyId, string $key, int $amount = 1): bool
    {
        $counter = $this->getOrCreate($companyId, $key);

        if ($counter->max_value === -1) {
            return false;
        }

        return ($counter->current_value + $amount) > $counter->max_value;
    }

    /**
     * Get usage data for all counters of a company.
     *
     * @return array<string, array{current: int, max: int, percent: float}>
     */
    public function getUsageSummary(int $companyId): array
    {
        $counters = UsageCounter::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->get();

        $summary = [];
        foreach ($counters as $counter) {
            $summary[$counter->counter_key] = [
                'current' => $counter->current_value,
                'max' => $counter->max_value,
                'percent' => $counter->usagePercent(),
            ];
        }

        return $summary;
    }

    /**
     * Sync the actual counts from database (recount). Used for data integrity.
     */
    public function recountAll(int $companyId): void
    {
        $counts = [
            'products' => \App\Models\Product::withoutGlobalScopes()->where('company_id', $companyId)->count(),
            'users' => \App\Models\CompanyUser::where('company_id', $companyId)->count(),
            'branches' => \App\Models\Branch::withoutGlobalScopes()->where('company_id', $companyId)->count(),
            'storage_bytes' => (int) \App\Models\Document::withoutGlobalScopes()->where('company_id', $companyId)->sum('size_bytes'),
        ];

        foreach ($counts as $key => $value) {
            UsageCounter::withoutGlobalScopes()->updateOrCreate(
                ['company_id' => $companyId, 'counter_key' => $key],
                ['current_value' => $value, 'synced_at' => now()],
            );
        }

        Log::info('Usage counters recounted', ['company_id' => $companyId, 'counts' => $counts]);
    }

    /**
     * Get or create a usage counter.
     */
    private function getOrCreate(int $companyId, string $key): UsageCounter
    {
        return UsageCounter::withoutGlobalScopes()->firstOrCreate(
            ['company_id' => $companyId, 'counter_key' => $key],
            ['current_value' => 0, 'max_value' => -1],
        );
    }
}
