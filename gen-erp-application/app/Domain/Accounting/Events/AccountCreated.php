<?php

namespace App\Domain\Accounting\Events;

use App\Domain\Accounting\Models\Account;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when an account is created.
 */
class AccountCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Account $account
    ) {}
}