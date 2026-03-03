<?php

namespace App\Domain\Accounting\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when an account is deleted.
 */
class AccountDeleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly array $accountData
    ) {}
}