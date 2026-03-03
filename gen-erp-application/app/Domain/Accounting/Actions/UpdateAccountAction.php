<?php

namespace App\Domain\Accounting\Actions;

use App\Domain\Accounting\DTOs\UpdateAccountData;
use App\Domain\Accounting\Events\AccountUpdated;
use App\Domain\Accounting\Models\Account;

/**
 * Action for updating an account.
 */
class UpdateAccountAction
{
    public function execute(Account $account, UpdateAccountData $data): Account
    {
        $account->update($data->toArray());

        event(new AccountUpdated($account));

        return $account->fresh();
    }
}