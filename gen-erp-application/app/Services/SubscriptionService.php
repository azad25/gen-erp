<?php

namespace App\Services;

use App\Enums\PaymentRequestStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Company;
use App\Models\PaymentRequest;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Manages subscription lifecycle — plan access, upgrades, renewals, and expiry.
 */
class SubscriptionService
{
    /**
     * Get the active subscription for a company.
     */
    public function getActive(int $companyId): ?Subscription
    {
        return Subscription::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereIn('status', [
                SubscriptionStatus::ACTIVE,
                SubscriptionStatus::TRIALING,
                SubscriptionStatus::GRACE,
            ])
            ->latest('starts_at')
            ->first();
    }

    /**
     * Get the active plan for a company. Falls back to free plan.
     */
    public function getActivePlan(int $companyId): Plan
    {
        $subscription = $this->getActive($companyId);

        if ($subscription) {
            return $subscription->plan;
        }

        return Plan::bySlug('free') ?? new Plan([
            'slug' => 'free',
            'limits' => ['products' => 5, 'users' => 2, 'branches' => 1, 'storage_bytes' => 52428800],
            'feature_flags' => ['api_access' => false, 'integrations' => 0, 'plugin_sdk' => false],
        ]);
    }

    /**
     * Get a specific plan limit for a company.
     * Returns -1 if unlimited.
     */
    public function getLimit(int $companyId, string $key): int
    {
        return $this->getActivePlan($companyId)->getLimit($key);
    }

    /**
     * Check if a feature flag is enabled for a company.
     */
    public function isFeatureEnabled(int $companyId, string $flag): bool
    {
        return $this->getActivePlan($companyId)->hasFeature($flag);
    }

    /**
     * Check if the company's subscription is currently accessible (active/trial/grace).
     */
    public function isAccessible(int $companyId): bool
    {
        $subscription = Subscription::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->latest('starts_at')
            ->first();

        // Companies without a subscription are on free tier (always accessible)
        if (! $subscription) {
            return true;
        }

        return $subscription->isAccessible();
    }

    /**
     * Check if company is in read-only mode (expired subscription).
     */
    public function isReadOnly(int $companyId): bool
    {
        $subscription = Subscription::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->latest('starts_at')
            ->first();

        if (! $subscription) {
            return false; // Free tier, not read-only
        }

        return $subscription->status === SubscriptionStatus::EXPIRED;
    }

    /**
     * Create a new subscription when payment is verified.
     */
    public function activate(int $companyId, int $planId, string $billingCycle = 'monthly'): Subscription
    {
        $plan = Plan::findOrFail($planId);
        $now = now();

        $endsAt = $billingCycle === 'annual'
            ? $now->copy()->addYear()
            : $now->copy()->addMonth();

        return DB::transaction(function () use ($companyId, $plan, $billingCycle, $now, $endsAt) {
            // Expire any existing active subscription
            Subscription::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->whereIn('status', [
                    SubscriptionStatus::ACTIVE->value,
                    SubscriptionStatus::TRIALING->value,
                    SubscriptionStatus::GRACE->value,
                ])
                ->update(['status' => SubscriptionStatus::CANCELLED->value, 'cancelled_at' => $now]);

            // Create new subscription
            $subscription = Subscription::withoutGlobalScopes()->create([
                'company_id' => $companyId,
                'plan_id' => $plan->id,
                'status' => SubscriptionStatus::ACTIVE,
                'billing_cycle' => $billingCycle,
                'starts_at' => $now,
                'ends_at' => $endsAt,
                'grace_ends_at' => $endsAt->copy()->addDays(7),
            ]);

            // Update company plan field
            Company::withoutGlobalScopes()
                ->where('id', $companyId)
                ->update([
                    'plan' => $plan->slug,
                    'plan_expires_at' => $endsAt,
                ]);

            // Initialize usage counters
            app(UsageCounterService::class)->initializeForPlan($companyId, $plan);

            Log::info('Subscription activated', [
                'company_id' => $companyId,
                'plan' => $plan->slug,
                'billing_cycle' => $billingCycle,
            ]);

            return $subscription;
        });
    }

    /**
     * Verify a payment request and activate subscription.
     */
    public function verifyPayment(PaymentRequest $request, int $verifiedBy): Subscription
    {
        $request->update([
            'status' => PaymentRequestStatus::VERIFIED,
            'verified_by' => $verifiedBy,
            'verified_at' => now(),
        ]);

        $subscription = $this->activate(
            $request->company_id,
            $request->plan_id,
            $request->billing_cycle,
        );

        // Create invoice
        SubscriptionInvoice::withoutGlobalScopes()->create([
            'company_id' => $request->company_id,
            'subscription_id' => $subscription->id,
            'payment_request_id' => $request->id,
            'invoice_number' => 'INV-' . str_pad((string) $request->id, 6, '0', STR_PAD_LEFT),
            'amount' => $request->amount,
            'billing_cycle' => $request->billing_cycle,
            'period_start' => $subscription->starts_at->toDateString(),
            'period_end' => $subscription->ends_at->toDateString(),
            'paid_at' => now(),
        ]);

        return $subscription;
    }

    /**
     * Reject a payment request.
     */
    public function rejectPayment(PaymentRequest $request, int $rejectedBy, ?string $note = null): void
    {
        $request->update([
            'status' => PaymentRequestStatus::REJECTED,
            'verified_by' => $rejectedBy,
            'verified_at' => now(),
            'admin_note' => $note,
        ]);
    }

    /**
     * Process subscription expiry transitions (called daily by command).
     *
     * Active + past ends_at → Grace
     * Grace + past grace_ends_at → Expired (read-only)
     */
    public function processExpiries(): array
    {
        $now = now();
        $stats = ['to_grace' => 0, 'to_expired' => 0];

        // Active → Grace
        $toGrace = Subscription::withoutGlobalScopes()
            ->where('status', SubscriptionStatus::ACTIVE)
            ->where('ends_at', '<', $now)
            ->get();

        foreach ($toGrace as $sub) {
            $sub->update(['status' => SubscriptionStatus::GRACE]);
            $stats['to_grace']++;
        }

        // Grace → Expired
        $toExpired = Subscription::withoutGlobalScopes()
            ->where('status', SubscriptionStatus::GRACE)
            ->where('grace_ends_at', '<', $now)
            ->get();

        foreach ($toExpired as $sub) {
            $sub->update(['status' => SubscriptionStatus::EXPIRED]);

            // Downgrade company to free
            Company::withoutGlobalScopes()
                ->where('id', $sub->company_id)
                ->update(['plan' => 'free']);

            $stats['to_expired']++;
        }

        Log::info('Subscription expiry processed', $stats);

        return $stats;
    }
}
