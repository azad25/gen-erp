<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Subscription\Services\SubscriptionService;
use App\Http\Controllers\Controller;
use App\Services\CompanyContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService
    ) {}

    /**
     * Get current subscription for the authenticated company.
     */
    public function current(Request $request): JsonResponse
    {
        $companyId = CompanyContext::activeId();
        
        $subscription = $this->subscriptionService->getActive($companyId);
        $plan = $this->subscriptionService->getActivePlan($companyId);

        return response()->json([
            'subscription' => $subscription,
            'plan' => $plan,
        ]);
    }

    /**
     * Get usage statistics for the current subscription.
     */
    public function usage(Request $request): JsonResponse
    {
        $companyId = CompanyContext::activeId();
        $plan = $this->subscriptionService->getActivePlan($companyId);

        // TODO: Get actual usage from UsageCounterService
        $usage = [
            'products' => 3,
            'products_limit' => $plan->getLimit('products'),
            'users' => 2,
            'users_limit' => $plan->getLimit('users'),
            'branches' => 1,
            'branches_limit' => $plan->getLimit('branches'),
            'storage' => 31457280,
            'storage_limit' => $plan->getLimit('storage_bytes'),
        ];

        return response()->json(['usage' => $usage]);
    }

    /**
     * Get all available plans.
     */
    public function plans(Request $request): JsonResponse
    {
        $plans = \App\Domain\Subscription\Models\Plan::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return response()->json(['plans' => $plans]);
    }

    /**
     * Get plan details.
     */
    public function plan(Request $request, int $id): JsonResponse
    {
        $plan = \App\Domain\Subscription\Models\Plan::findOrFail($id);

        return response()->json(['plan' => $plan]);
    }
}
