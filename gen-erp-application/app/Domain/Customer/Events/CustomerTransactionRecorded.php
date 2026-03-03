<?php

namespace App\Domain\Customer\Events;

use App\Domain\Customer\Models\CustomerTransaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a customer transaction is recorded.
 */
class CustomerTransactionRecorded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly CustomerTransaction $transaction,
    ) {}
}