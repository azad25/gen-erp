<?php

namespace App\Domain\Integration\Policies;

use App\Domain\Auth\Models\User;
use App\Domain\Integration\Models\Integration;

class IntegrationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view-integrations');
    }

    public function view(User $user, Integration $integration): bool
    {
        return $user->hasPermission('view-integrations');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('create-integrations');
    }

    public function update(User $user, Integration $integration): bool
    {
        return $user->hasPermission('update-integrations');
    }

    public function delete(User $user, Integration $integration): bool
    {
        return $user->hasPermission('delete-integrations');
    }

    public function install(User $user): bool
    {
        return $user->hasPermission('install-integrations');
    }
}
