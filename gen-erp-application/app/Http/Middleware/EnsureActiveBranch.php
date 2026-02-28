<?php

namespace App\Http\Middleware;

use App\Services\BranchAccessService;
use App\Services\BranchContext;
use App\Services\CompanyContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sets active branch context after company is resolved.
 */
class EnsureActiveBranch
{
    public function __construct(
        private readonly BranchAccessService $branchAccess,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $company = CompanyContext::getActive();

        if (! $user || ! $company) {
            return $next($request);
        }

        $accessible = $this->branchAccess->accessibleBranches($user, $company);

        // Owner/admin: default to all-branches view
        $pivot = $user->companies()->where('companies.id', $company->id)->first();
        $role = $pivot?->pivot->role ?? null;
        $isUnrestricted = in_array($role, ['owner', 'admin'], true);

        if ($isUnrestricted && ! session()->has('active_branch_id')) {
            BranchContext::clear();

            return $next($request);
        }

        // If session has active branch, validate access
        $sessionBranchId = session('active_branch_id');
        if ($sessionBranchId) {
            $branch = $accessible->firstWhere('id', $sessionBranchId);
            if ($branch) {
                BranchContext::setActive($branch);

                return $next($request);
            }
        }

        // Auto-select first accessible branch
        if ($accessible->isNotEmpty()) {
            if ($isUnrestricted) {
                BranchContext::clear();
            } else {
                BranchContext::setActive($accessible->first());
            }

            return $next($request);
        }

        // No branches exist yet — allow through without branch filter
        BranchContext::clear();

        return $next($request);
    }
}
