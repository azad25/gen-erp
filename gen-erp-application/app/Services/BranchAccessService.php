<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Branch-level access control — determines which branches a user can access.
 */
class BranchAccessService
{
    /**
     * Get all branches a user can access in a company.
     *
     * @return Collection<int, Branch>
     */
    public function accessibleBranches(User $user, Company $company): Collection
    {
        if ($this->isUnrestricted($user, $company)) {
            return Branch::withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->where('is_active', true)
                ->get();
        }

        return Branch::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->whereHas('users', fn ($q) => $q->where('users.id', $user->id))
            ->get();
    }

    /**
     * Check if user can access a specific branch.
     */
    public function canAccess(User $user, Branch $branch): bool
    {
        $company = Company::withoutGlobalScopes()->find($branch->company_id);
        if ($this->isUnrestricted($user, $company)) {
            return true;
        }

        return $branch->users()->where('users.id', $user->id)->exists();
    }

    /**
     * Check specific permission on branch (view|create|edit|delete).
     */
    public function can(User $user, Branch $branch, string $permission): bool
    {
        $company = Company::withoutGlobalScopes()->find($branch->company_id);
        if ($this->isUnrestricted($user, $company)) {
            return true;
        }

        $pivot = $branch->users()
            ->where('users.id', $user->id)
            ->first();

        if (! $pivot) {
            return false;
        }

        return (bool) $pivot->pivot->{'can_'.$permission};
    }

    /**
     * Assign user to branch with permissions.
     *
     * @param  array{can_view?: bool, can_create?: bool, can_edit?: bool, can_delete?: bool}  $permissions
     */
    public function assignUser(Branch $branch, User $user, array $permissions): void
    {
        $branch->users()->syncWithoutDetaching([
            $user->id => array_merge([
                'company_id' => $branch->company_id,
                'can_view' => true,
                'can_create' => true,
                'can_edit' => true,
                'can_delete' => false,
            ], $permissions),
        ]);
    }

    /**
     * Remove user from branch.
     */
    public function removeUser(Branch $branch, User $user): void
    {
        $branch->users()->detach($user->id);
    }

    /**
     * Get users not yet assigned to this branch.
     *
     * @return Collection<int, User>
     */
    public function unassignedUsers(Branch $branch): Collection
    {
        $assignedIds = $branch->users()->pluck('users.id');

        return User::whereHas('companies', fn ($q) => $q->where('companies.id', $branch->company_id))
            ->whereNotIn('id', $assignedIds)
            ->get();
    }

    /**
     * OWNER always unrestricted. ADMIN unrestricted by default.
     */
    private function isUnrestricted(User $user, Company $company): bool
    {
        $pivot = $user->companies()->where('companies.id', $company->id)->first();
        if (! $pivot) {
            return false;
        }

        $role = $pivot->pivot->role ?? null;

        return in_array($role, ['owner', 'admin'], true);
    }
}
