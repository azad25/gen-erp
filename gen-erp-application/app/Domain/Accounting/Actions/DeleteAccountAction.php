<?php

namespace App\Domain\Accounting\Actions;

use App\Domain\Accounting\Events\AccountDeleted;
use App\Domain\Accounting\Models\Account;

/**
 * Action for deleting an account.
 */
class DeleteAccountAction
{
    public function execute(Account $account): void
    {
        $accountData = $account->toArray();
        
        $account->delete();

        event(new AccountDeleted($accountData));
    }
}