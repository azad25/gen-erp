<?php

namespace App\Domain\CMS\Events;

use App\Domain\CMS\Models\CustomerAccount;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a new customer registers.
 */
class CustomerRegistered
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly CustomerAccount $customer
    ) {}
}