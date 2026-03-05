<?php

namespace App\Domain\Customer\Listeners;

use App\Domain\Customer\Events\CustomerTransactionRecorded;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Update customer balance and check credit limits when transaction is recorded.
 */
class UpdateCustomerBalance implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(CustomerTransactionRecorded $event): void
    {
        $transaction = $event->transaction;
        $customer = $transaction->customer;
        
        // Skip if customer not found (e.g., for supplier transactions)
        if (!$customer) {
            return;
        }

        // Log the transaction
        Log::info('Customer transaction recorded', [
            'transaction_id' => $transaction->id,
            'customer_id' => $customer->id,
            'type' => $transaction->type,
            'amount' => $transaction->amount,
            'balance_after' => $transaction->balance_after,
        ]);

        // Check if customer is over credit limit
        if ($customer->isOverCreditLimit()) {
            Log::warning('Customer over credit limit', [
                'customer_id' => $customer->id,
                'current_balance' => $customer->currentBalance(),
                'credit_limit' => $customer->credit_limit,
            ]);

            // TODO: Implement credit limit actions
            // - Send alert to sales team
            // - Block new orders if configured
            // - Send notification to customer
        }

        // TODO: Implement other balance-related actions
        // - Update customer risk score
        // - Trigger payment reminders if overdue
        // - Update customer analytics
    }
}