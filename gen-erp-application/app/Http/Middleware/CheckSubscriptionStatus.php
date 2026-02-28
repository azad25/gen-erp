<?php

namespace App\Http\Middleware;

use App\Services\SubscriptionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks write operations when a company's subscription has expired (read-only mode).
 */
class CheckSubscriptionStatus
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        // Only check authenticated users with an active company
        if (! $request->user() || ! \App\Services\CompanyContext::hasActive()) {
            return $next($request);
        }

        $companyId = \App\Services\CompanyContext::activeId();

        if ($this->subscriptionService->isReadOnly($companyId)) {
            // Allow read operations (GET, HEAD, OPTIONS)
            if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
                return $next($request);
            }

            // Allow access to subscription/payment pages (so they can upgrade)
            if ($request->is('*/subscriptions*', '*/payment-requests*', '*/plans*', 'app/switch-company/*', 'logout')) {
                return $next($request);
            }

            // Block write operations
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('Your subscription has expired. Please renew to continue making changes.'),
                ], 403);
            }

            // For Filament / web requests
            session()->flash('warning', __('Your subscription has expired. You are in read-only mode. Please renew your plan to make changes.'));

            return redirect()->back();
        }

        return $next($request);
    }
}
