<?php

namespace App\Http\Middleware;

use App\Services\SubscriptionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks access to routes requiring specific feature flags not included in the company's plan.
 *
 * Usage: Route::middleware('feature:api_access') or Route::middleware('feature:plugin_sdk')
 */
class EnforceModuleAccess
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService,
    ) {}

    public function handle(Request $request, Closure $next, string $feature): Response
    {
        if (! $request->user() || ! \App\Services\CompanyContext::hasActive()) {
            return $next($request);
        }

        $companyId = \App\Services\CompanyContext::activeId();

        if (! $this->subscriptionService->isFeatureEnabled($companyId, $feature)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('This feature requires a higher plan. Please upgrade to access :feature.', [
                        'feature' => ucfirst(str_replace('_', ' ', $feature)),
                    ]),
                ], 403);
            }

            abort(403, __('This feature requires a plan upgrade.'));
        }

        return $next($request);
    }
}
