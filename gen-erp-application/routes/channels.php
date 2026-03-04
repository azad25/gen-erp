<?php

use Illuminate\Support\Facades\Broadcast;
use App\Domain\Auth\Models\User;

// User's private channel — their personal notifications
Broadcast::channel('App.Models.User.{id}', function (User $user, int $id) {
    return (int) $user->id === (int) $id;
});

// Tenant channel — all users in the same company
Broadcast::channel('tenant.{tenantId}', function (User $user, int $tenantId) {
    return $user->companies()->where('companies.id', $tenantId)->exists();
});

// Role-based channel
Broadcast::channel('tenant.{tenantId}.role.{role}', function (User $user, int $tenantId, string $role) {
    return $user->companies()->where('companies.id', $tenantId)->exists() && $user->hasRole($role);
});
