<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

/**
 * Tracks resource usage per company for plan limit enforcement.
 */
class UsageCounter extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'counter_key',
        'current_value',
        'max_value',
        'synced_at',
    ];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'current_value' => 'integer',
            'max_value' => 'integer',
            'synced_at' => 'datetime',
        ];
    }

    /** Whether the counter has reached its limit. */
    public function isAtLimit(): bool
    {
        if ($this->max_value === -1) {
            return false; // unlimited
        }

        return $this->current_value >= $this->max_value;
    }

    /** Usage as a percentage (0-100). */
    public function usagePercent(): float
    {
        if ($this->max_value <= 0) {
            return $this->max_value === -1 ? 0.0 : 100.0;
        }

        return round(($this->current_value / $this->max_value) * 100, 1);
    }

    /** Remaining capacity. */
    public function remaining(): int
    {
        if ($this->max_value === -1) {
            return PHP_INT_MAX;
        }

        return max(0, $this->max_value - $this->current_value);
    }
}
