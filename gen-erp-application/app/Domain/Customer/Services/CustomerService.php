<?php

namespace App\Domain\Customer\Services;

use App\Domain\Customer\Models\Customer;

class CustomerService
{
    /**
     * Find active customer or fail.
     */
    public function findActiveOrFail(int $customerId): Customer
    {
        return Customer::where('id', $customerId)
            ->where('is_active', true)
            ->firstOrFail();
    }

    /**
     * Record customer transaction.
     */
    public function recordTransaction(
        int $customerId,
        string $type,
        int $amount,
        string $description,
        $relatedModel = null
    ): void {
        // This would create a customer transaction record
        // For now, we'll implement a basic version
        \App\Domain\Customer\Models\CustomerTransaction::create([
            'customer_id' => $customerId,
            'type' => $type,
            'amount' => $amount,
            'description' => $description,
            'related_type' => $relatedModel ? get_class($relatedModel) : null,
            'related_id' => $relatedModel?->id,
            'transaction_date' => now(),
        ]);
    }
}