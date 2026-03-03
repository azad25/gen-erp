<?php

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\DTOs\UpdateUserData;
use App\Domain\Auth\Events\UserUpdated;
use App\Domain\Auth\Models\User;

/**
 * Update an existing user account.
 */
class UpdateUserAction
{
    public function execute(User $user, UpdateUserData $data): User
    {
        $oldData = $user->toArray();
        
        $user->update($data->toArray());

        // Fire domain event
        UserUpdated::dispatch($user, $oldData);

        return $user->fresh()->load(['companies']);
    }
}