<?php

namespace App\Domain\Customer\Actions;

use App\Domain\Customer\Models\Customer;
use App\Domain\Customer\Events\CustomerTransactionRecorded;
use App\Domain\Customer\Models\CustomerTransaction;
use Illuminate\Database\Eloquent\Model;

/**
 * Record a customer transaction and update balance.
 */
class RecordCustomerTransactionAction
{
    public function execute(
        Customer $customer,
        string $type,
        int $amount,
        string $description,
        ?Model $reference = null,
    ): CustomerTransaction {
        $balanceAfter = $customer->currentBalance() + $amount;

        $transaction = CustomerTransaction::withoutGlobalScopes()->create([
            'company_id' => $customer->company_id,
            'customer_id' => $customer->id,
            'type' => $type,
            'reference_type' => $reference ? get_class($reference) : null,
            'reference_id' => $reference?->getKey(),
            'amount' => $amount,
            'balance_after' => $balanceAfter,
            'description' => $description,
            'transaction_date' => now()->toDateString(),
            'created_by' => auth()->id(),
            'created_at' => now(),
        ]);

        // Fire domain event
        CustomerTransactionRecorded::dispatch($transaction);

        return $transaction;
    }
}