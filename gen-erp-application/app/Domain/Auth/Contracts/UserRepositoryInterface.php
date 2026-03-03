<?php

namespace App\Domain\Auth\Contracts;

use App\Domain\Auth\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Contract for User repository operations.
 */
interface UserRepositoryInterface
{
    /**
     * Find user by ID.
     */
    public function findById(int $id): ?User;

    /**
     * Find user by email.
     */
    public function findByEmail(string $email): ?User;

    /**
     * Get paginated users with filters.
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Create a new user.
     */
    public function create(array $data): User;

    /**
     * Update an existing user.
     */
    public function update(User $user, array $data): bool;

    /**
     * Delete a user.
     */
    public function delete(User $user): bool;

    /**
     * Get users by company ID.
     */
    public function getByCompanyId(int $companyId): \Illuminate\Database\Eloquent\Collection;
}