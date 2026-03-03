<?php

namespace App\Domain\Auth\Services;

use App\Domain\Auth\Actions\AddUserToCompanyAction;
use App\Domain\Auth\Actions\CreateUserAction;
use App\Domain\Auth\Actions\RemoveUserFromCompanyAction;
use App\Domain\Auth\Actions\UpdateUserAction;
use App\Domain\Auth\DTOs\CompanyMembershipData;
use App\Domain\Auth\DTOs\CreateUserData;
use App\Domain\Auth\DTOs\UpdateUserData;
use App\Domain\Auth\Models\CompanyUser;
use App\Domain\Auth\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Core user management service for authentication and company membership.
 */
class UserService
{
    public function __construct(
        private readonly CreateUserAction $createUserAction,
        private readonly UpdateUserAction $updateUserAction,
        private readonly AddUserToCompanyAction $addUserToCompanyAction,
        private readonly RemoveUserFromCompanyAction $removeUserFromCompanyAction,
    ) {}

    /**
     * Get paginated users with search functionality.
     */
    public function paginateUsers(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = User::query();

        if (isset($filters['search']) && $filters['search']) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        return $query->with(['companies'])
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Create a new user.
     */
    public function createUser(CreateUserData $data): User
    {
        return $this->createUserAction->execute($data);
    }

    /**
     * Update an existing user.
     */
    public function updateUser(User $user, UpdateUserData $data): User
    {
        return $this->updateUserAction->execute($user, $data);
    }

    /**
     * Add user to company with specific role.
     */
    public function addToCompany(User $user, int $companyId, string $role, bool $isOwner = false): void
    {
        $membershipData = new CompanyMembershipData(
            companyId: $companyId,
            role: $role,
            isOwner: $isOwner
        );

        $this->addUserToCompanyAction->execute($user, $membershipData);
    }

    /**
     * Remove user from company.
     */
    public function removeFromCompany(User $user, int $companyId): void
    {
        $this->removeUserFromCompanyAction->execute($user, $companyId);
    }

    /**
     * Get user's active companies.
     */
    public function getUserCompanies(User $user): \Illuminate\Database\Eloquent\Collection
    {
        return $user->companies()
            ->wherePivot('is_active', true)
            ->get();
    }

    /**
     * Check if user has permission in company.
     */
    public function hasPermissionInCompany(User $user, int $companyId, string $permission): bool
    {
        $permissions = $user->getPermissionsForCompany($companyId);
        
        return in_array('*', $permissions) || in_array($permission, $permissions);
    }

    /**
     * Send invitation to join company.
     */
    public function sendInvitation(array $data): \App\Domain\Auth\Models\Invitation
    {
        return \App\Domain\Auth\Models\Invitation::create($data);
    }

    /**
     * Get paginated invitations for company.
     */
    public function paginateInvitations(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = \App\Domain\Auth\Models\Invitation::query()
            ->where('company_id', activeCompany()->id);

        if (isset($filters['status']) && $filters['status']) {
            $query->where('status', $filters['status']);
        }

        return $query->with(['invitedBy'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Cancel an invitation.
     */
    public function cancelInvitation(\App\Domain\Auth\Models\Invitation $invitation): bool
    {
        return $invitation->delete();
    }
}
