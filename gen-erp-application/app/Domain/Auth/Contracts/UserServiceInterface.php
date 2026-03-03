<?php

namespace App\Domain\Auth\Contracts;

use App\Domain\Auth\DTOs\CreateUserData;
use App\Domain\Auth\DTOs\UpdateUserData;
use App\Domain\Auth\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Contract for User service operations.
 */
interface UserServiceInterface
{
    /**
     * Get paginated users with search functionality.
     */
    public function paginateUsers(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Create a new user.
     */
    public function createUser(CreateUserData $data): User;

    /**
     * Update an existing user.
     */
    public function updateUser(User $user, UpdateUserData $data): User;

    /**
     * Add user to company with specific role.
     */
    public function addToCompany(User $user, int $companyId, string $role, bool $isOwner = false): void;

    /**
     * Remove user from company.
     */
    public function removeFromCompany(User $user, int $companyId): void;

    /**
     * Check if user has permission in company.
     */
    public function hasPermissionInCompany(User $user, int $companyId, string $permission): bool;
}