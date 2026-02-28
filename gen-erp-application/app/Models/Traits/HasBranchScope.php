<?php

namespace App\Models\Traits;

use App\Models\Scopes\BranchScope;

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
