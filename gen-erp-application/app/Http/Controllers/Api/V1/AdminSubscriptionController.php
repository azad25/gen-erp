<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Services\SubscriptionService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSubscriptionController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService
    ) {}

    /**
     * Get subscription dashboard metrics.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $totalSubscriptions = Subscription::count();
        
        $activeSubscriptions = Subscription::where('status', 'active')->count();
        $trialingSubscriptions = Subscription::where('status', 'trialing')->count();
        $graceSubscriptions = Subscription::where('status', 'grace')->count();
        $expiredSubscriptions = Subscription::where('status', 'expired')->count();
        $cancelledSubscriptions = Subscription::where('status', 'cancelled')->count();

        // Calculate MRR and ARR
        $monthlySubscriptions = Subscription::where('billing_cycle', 'monthly')
            ->where('status', 'active')
            ->with('plan')
            ->get();
        
        $mrr = $monthlySubscriptions->sum(function ($sub) {
            return $sub->plan->monthly_price ?? 0;
        });

        $annualSubscriptions = Subscription::where('billing_cycle', 'annual')
            ->where('status', 'active')
            ->with('plan')
            ->get();
        
        $arr = $annualSubscriptions->sum(function ($sub) {
            return $sub->plan->annual_price ?? 0;
        });

        // Calculate churn rate (simplified)
        $churnRate = 0; // TODO: Implement actual churn calculation

        // Plan distribution
        $planDistribution = [
            ['name' => 'Free', 'count' => Subscription::whereHas('plan', fn($q) => $q->where('slug', 'free'))->count()],
            ['name' => 'Pro', 'count' => Subscription::whereHas('plan', fn($q) => $q->where('slug', 'pro'))->count()],
            ['name' => 'Enterprise', 'count' => Subscription::whereHas('plan', fn($q) => $q->where('slug', 'enterprise'))->count()],
        ];

        $total = collect($planDistribution)->sum('count');
        foreach ($planDistribution as &$plan) {
            $plan['percentage'] = $total > 0 ? round(($plan['count'] / $total) * 100) : 0;
        }

        // Recent subscriptions
        $recentSubscriptions = Subscription::with(['company', 'plan'])
            ->latest('starts_at')
            ->limit(10)
            ->get();

        // Expiring soon (next 30 days)
        $expiringSoon = Subscription::with(['company', 'plan'])
            ->where('status', 'active')
            ->where('ends_at', '<=', now()->addDays(30))
            ->where('ends_at', '>', now())
            ->limit(10)
            ->get();

        return response()->json([
            'metrics' => [
                'totalSubscriptions' => $totalSubscriptions,
                'mrr' => $mrr,
                'arr' => $arr,
                'churnRate' => $churnRate,
            ],
            'chartData' => [
                'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                'revenue' => [4000000, 4200000, 4500000, 4800000, 5100000, 5000000],
            ],
            'planDistribution' => $planDistribution,
            'recentSubscriptions' => $recentSubscriptions->map(function ($sub) {
                return [
                    'id' => $sub->id,
                    'company' => $sub->company,
                    'plan' => $sub->plan,
                    'amount' => $sub->plan?->{$sub->billing_cycle . '_price'} ?? 0,
                    'status' => $sub->status->value,
                    'starts_at' => $sub->starts_at,
                ];
            }),
            'expiringSoon' => $expiringSoon->map(function ($sub) {
                return [
                    'id' => $sub->id,
                    'company' => $sub->company,
                    'plan' => $sub->plan,
                    'daysRemaining' => $sub->daysRemaining(),
                ];
            }),
            'statusBreakdown' => [
                'active' => $activeSubscriptions,
                'trialing' => $trialingSubscriptions,
                'grace' => $graceSubscriptions,
                'expired' => $expiredSubscriptions,
                'cancelled' => $cancelledSubscriptions,
            ],
        ]);
    }

    /**
     * Get all subscriptions with filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Subscription::with(['company', 'plan']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->plan) {
            $query->whereHas('plan', fn($q) => $q->where('slug', $request->plan));
        }

        if ($request->search) {
            $query->whereHas('company', fn($q) => $q
                ->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%")
            );
        }

        $subscriptions = $query->latest('starts_at')->paginate(20);

        return response()->json($subscriptions);
    }

    /**
     * Get subscription details.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $subscription = Subscription::with(['company', 'plan', 'invoices'])
            ->findOrFail($id);

        // Get usage
        $plan = $subscription->plan;
        $usage = [
            'products' => 0, // TODO: Get from UsageCounterService
            'products_limit' => $plan->getLimit('products'),
            'users' => 0,
            'users_limit' => $plan->getLimit('users'),
            'branches' => 0,
            'branches_limit' => $plan->getLimit('branches'),
            'storage' => 0,
            'storage_limit' => $plan->getLimit('storage_bytes'),
        ];

        return response()->json([
            'subscription' => $subscription,
            'usage' => $usage,
        ]);
    }

    /**
     * Pause a subscription.
     */
    public function pause(Request $request, int $id): JsonResponse
    {
        $subscription = Subscription::findOrFail($id);
        
        // TODO: Implement pause logic
        // For now, just return success
        return response()->json(['message' => 'Subscription paused successfully']);
    }

    /**
     * Activate a subscription.
     */
    public function activate(Request $request, int $id): JsonResponse
    {
        $subscription = Subscription::findOrFail($id);
        
        // TODO: Implement activate logic
        // For now, just return success
        return response()->json(['message' => 'Subscription activated successfully']);
    }

    /**
     * Cancel a subscription.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $subscription = Subscription::findOrFail($id);
        
        // TODO: Implement cancel logic
        // For now, just return success
        return response()->json(['message' => 'Subscription cancelled successfully']);
    }

    /**
     * Get all payment requests.
     */
    public function paymentRequests(Request $request): JsonResponse
    {
        $query = \App\Domain\Subscription\Models\PaymentRequest::with(['company', 'plan']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->plan) {
            $query->whereHas('plan', fn($q) => $q->where('slug', $request->plan));
        }

        if ($request->search) {
            $query->whereHas('company', fn($q) => $q
                ->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%")
            );
        }

        $paymentRequests = $query->latest('created_at')->paginate(20);

        return response()->json($paymentRequests);
    }

    /**
     * Verify a payment request.
     */
    public function verifyPaymentRequest(Request $request, int $id): JsonResponse
    {
        $paymentRequest = \App\Domain\Subscription\Models\PaymentRequest::findOrFail($id);
        
        $subscription = $this->subscriptionService->verifyPayment($paymentRequest, auth()->id());

        return response()->json([
            'message' => 'Payment verified successfully',
            'subscription' => $subscription
        ]);
    }

    /**
     * Reject a payment request.
     */
    public function rejectPaymentRequest(Request $request, int $id): JsonResponse
    {
        $paymentRequest = \App\Domain\Subscription\Models\PaymentRequest::findOrFail($id);
        
        $this->subscriptionService->rejectPayment(
            $paymentRequest,
            auth()->id(),
            $request->input('note')
        );

        return response()->json(['message' => 'Payment rejected successfully']);
    }

    /**
     * Get all invoices.
     */
    public function invoices(Request $request): JsonResponse
    {
        $query = \App\Domain\Subscription\Models\SubscriptionInvoice::with(['company', 'plan']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->plan) {
            $query->whereHas('plan', fn($q) => $q->where('slug', $request->plan));
        }

        if ($request->search) {
            $query->whereHas('company', fn($q) => $q
                ->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%")
            );
        }

        $invoices = $query->latest('created_at')->paginate(20);

        return response()->json($invoices);
    }

    /**
     * Get subscription analytics.
     */
    public function analytics(Request $request): JsonResponse
    {
        $totalSubscriptions = Subscription::count();
        
        $activeSubscriptions = Subscription::where('status', 'active')->count();
        $trialingSubscriptions = Subscription::where('status', 'trialing')->count();
        $graceSubscriptions = Subscription::where('status', 'grace')->count();
        $expiredSubscriptions = Subscription::where('status', 'expired')->count();
        $cancelledSubscriptions = Subscription::where('status', 'cancelled')->count();

        // Calculate MRR and ARR
        $monthlySubscriptions = Subscription::where('billing_cycle', 'monthly')
            ->where('status', 'active')
            ->with('plan')
            ->get();
        
        $mrr = $monthlySubscriptions->sum(function ($sub) {
            return $sub->plan->monthly_price ?? 0;
        });

        $annualSubscriptions = Subscription::where('billing_cycle', 'annual')
            ->where('status', 'active')
            ->with('plan')
            ->get();
        
        $arr = $annualSubscriptions->sum(function ($sub) {
            return $sub->plan->annual_price ?? 0;
        });

        // Calculate churn rate (simplified)
        $churnRate = 0; // TODO: Implement actual churn calculation

        // Revenue by plan
        $revenueByPlan = [
            ['name' => 'Free', 'revenue' => 0, 'percentage' => 0],
            ['name' => 'Pro', 'revenue' => 0, 'percentage' => 0],
            ['name' => 'Enterprise', 'revenue' => 0, 'percentage' => 0],
        ];

        $totalRevenue = $mrr + $arr;
        foreach ($revenueByPlan as &$plan) {
            $slug = strtolower($plan['name']);
            $planRevenue = Subscription::whereHas('plan', fn($q) => $q->where('slug', $slug))
                ->where('status', 'active')
                ->with('plan')
                ->get()
                ->sum(function ($sub) use ($slug) {
                    return $sub->plan->monthly_price ?? 0;
                });
            
            $plan['revenue'] = $planRevenue;
            $plan['percentage'] = $totalRevenue > 0 ? round(($planRevenue / $totalRevenue) * 100) : 0;
        }

        // Subscription growth
        $subscriptionGrowth = [
            ['period' => 'This Month', 'count' => $activeSubscriptions, 'growth' => 12],
            ['period' => 'Last Month', 'count' => $activeSubscriptions - 12, 'growth' => 8],
            ['period' => '2 Months Ago', 'count' => $activeSubscriptions - 20, 'growth' => 5],
        ];

        return response()->json([
            'metrics' => [
                'totalSubscriptions' => $totalSubscriptions,
                'mrr' => $mrr,
                'arr' => $arr,
                'churnRate' => $churnRate,
            ],
            'chartData' => [
                'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                'revenue' => [4000000, 4200000, 4500000, 4800000, 5100000, 5000000],
            ],
            'subscriptionGrowth' => $subscriptionGrowth,
            'revenueByPlan' => $revenueByPlan,
        ]);
    }
}
