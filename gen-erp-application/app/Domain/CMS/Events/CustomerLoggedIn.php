<?php

namespace App\Domain\CMS\Events;

use App\Domain\CMS\Models\CustomerAccount;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a customer logs in.
 */
class CustomerLoggedIn
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly CustomerAccount $customer
    ) {}
}