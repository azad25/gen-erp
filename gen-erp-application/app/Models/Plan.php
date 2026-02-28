<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A subscription plan (Free, Pro, Enterprise).
 */
class Plan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'monthly_price',
        'annual_price',
        'limits',
        'feature_flags',
        'description',
        'is_active',
        'sort_order',
    ];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'monthly_price' => 'integer',
            'annual_price' => 'integer',
            'limits' => 'array',
            'feature_flags' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<Subscription, $this> */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get a specific limit value. Returns -1 if unlimited.
     */
    public function getLimit(string $key): int
    {
        return (int) ($this->limits[$key] ?? -1);
    }

    /**
     * Check if this plan has a given feature flag enabled.
     */
    public function hasFeature(string $flag): bool
    {
        $value = $this->feature_flags[$flag] ?? false;

        // Flags can be bool OR int (e.g. integrations: 0/5/unlimited)
        if (is_int($value)) {
            return $value > 0 || $value === -1;
        }

        return (bool) $value;
    }

    /**
     * Monthly price formatted in BDT.
     */
    public function formattedMonthlyPrice(): string
    {
        if ($this->monthly_price === 0) {
            return 'Free';
        }

        return '৳' . number_format($this->monthly_price / 100, 2);
    }

    /**
     * Annual price formatted in BDT.
     */
    public function formattedAnnualPrice(): string
    {
        if ($this->annual_price === 0) {
            return 'Free';
        }

        return '৳' . number_format($this->annual_price / 100, 2);
    }

    /** Find plan by slug. */
    public static function bySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->where('is_active', true)->first();
    }
}
