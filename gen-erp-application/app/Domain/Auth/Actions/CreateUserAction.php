<?php

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\DTOs\CreateUserData;
use App\Domain\Auth\Events\UserCreated;
use App\Domain\Auth\Models\User;

/**
 * Create a new user account.
 */
class CreateUserAction
{
    public function execute(CreateUserData $data): User
    {
        $user = User::create($data->toArray());

        // Fire domain event
        UserCreated::dispatch($user);

        return $user->load(['companies']);
    }
}