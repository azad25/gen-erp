<?php

namespace App\Services;

use App\Models\Branch;

/**
 * Thread-safe branch context — stores active branch for request lifecycle.
 */
class BranchContext
{
    public static function setActive(Branch $branch): void
    {
        session(['active_branch_id' => $branch->id]);
        app()->instance('active_branch', $branch);
    }

    public static function active(): ?Branch
    {
        if (app()->has('active_branch')) {
            return app('active_branch');
        }

        $id = self::activeId();
        if ($id === null) {
            return null;
        }

        $branch = Branch::withoutGlobalScopes()->find($id);
        if ($branch) {
            app()->instance('active_branch', $branch);
        }

        return $branch;
    }

    public static function activeId(): ?int
    {
        return session('active_branch_id');
    }

    public static function hasActive(): bool
    {
        return self::activeId() !== null;
    }

    public static function isFiltered(): bool
    {
        return self::activeId() !== null;
    }

    public static function clear(): void
    {
        session()->forget('active_branch_id');
        app()->forgetInstance('active_branch');
    }
}
