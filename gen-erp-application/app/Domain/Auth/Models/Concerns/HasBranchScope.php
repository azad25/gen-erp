<?php

namespace App\Domain\Auth\Models\Concerns;

use App\Models\Concerns\BranchScope;

/**
 * Apply to models with a branch_id column for automatic branch filtering.
 */
trait HasBranchScope
{
    public static function bootHasBranchScope(): void
    {
        static::addGlobalScope(new BranchScope);
    }
}
