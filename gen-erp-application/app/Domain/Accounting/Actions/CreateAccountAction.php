<?php

namespace App\Domain\Accounting\Actions;

use App\Domain\Accounting\DTOs\CreateAccountData;
use App\Domain\Accounting\Events\AccountCreated;
use App\Domain\Accounting\Models\Account;

/**
 * Action for creating a new account.
 */
class CreateAccountAction
{
    public function execute(CreateAccountData $data): Account
    {
        $account = Account::create($data->toArray());

        event(new AccountCreated($account));

        return $account;
    }
}