<?php

namespace App\Domain\Auth\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Application user with multi-company membership support.
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\UserFactory::new();
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar_url',
        'preferred_locale',
        'locale',
        'last_active_company_id',
        'is_superadmin',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'failed_login_count',
        'locked_until',
        'password_changed_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_superadmin' => 'boolean',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted',
            'two_factor_confirmed_at' => 'datetime',
            'failed_login_count' => 'integer',
            'locked_until' => 'datetime',
            'password_changed_at' => 'datetime',
        ];
    }

    // ── Relationships ────────────────────────────────────────

    /**
     * @return BelongsToMany<Company, $this>
     */
    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_user')
            ->withPivot(['role', 'is_owner', 'is_active', 'joined_at'])
            ->withTimestamps();
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function lastActiveCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'last_active_company_id');
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function currentCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'last_active_company_id');
    }

    // ── Methods ──────────────────────────────────────────────

    /**
     * Returns the currently resolved active company from session.
     */
    public function activeCompany(): ?Company
    {
        $companyId = session('active_company_id');

        if ($companyId) {
            return Company::find($companyId);
        }

        return $this->lastActiveCompany;
    }

    /**
     * Check if the user has a specific role in a given company.
     */
    public function hasRoleInCompany(string $role, Company $company): bool
    {
        return $this->companies()
            ->where('companies.id', $company->id)
            ->wherePivot('role', $role)
            ->wherePivot('is_active', true)
            ->exists();
    }

    /**
     * Check if the user is the owner of a given company.
     */
    public function isOwnerOf(Company $company): bool
    {
        return $this->companies()
            ->where('companies.id', $company->id)
            ->wherePivot('is_owner', true)
            ->exists();
    }

    /**
     * Get user permissions for a specific company.
     */
    public function getPermissionsForCompany(int $companyId): array
    {
        // Get the user's role in this company
        $companyUser = $this->companies()
            ->where('companies.id', $companyId)
            ->wherePivot('is_active', true)
            ->first();

        if (! $companyUser) {
            return [];
        }

        $role = $companyUser->pivot->role;
        $isOwner = $companyUser->pivot->is_owner;

        // Define permissions based on role
        $permissions = [];

        if ($isOwner || $role === 'owner') {
            $permissions = ['*']; // Full access
        } elseif ($role === 'admin') {
            $permissions = [
                'users.view', 'users.create', 'users.edit',
                'customers.view', 'customers.create', 'customers.edit', 'customers.delete',
                'products.view', 'products.create', 'products.edit', 'products.delete',
                'sales.view', 'sales.create', 'sales.edit', 'sales.delete',
                'purchases.view', 'purchases.create', 'purchases.edit', 'purchases.delete',
                'inventory.view', 'inventory.create', 'inventory.edit',
                'accounting.view', 'accounting.create', 'accounting.edit',
                'reports.view',
                'settings.view', 'settings.edit',
            ];
        } elseif ($role === 'manager') {
            $permissions = [
                'customers.view', 'customers.create', 'customers.edit',
                'products.view', 'products.create', 'products.edit',
                'sales.view', 'sales.create', 'sales.edit',
                'purchases.view', 'purchases.create', 'purchases.edit',
                'inventory.view', 'inventory.create',
                'reports.view',
            ];
        } elseif ($role === 'employee') {
            $permissions = [
                'customers.view',
                'products.view',
                'sales.view', 'sales.create',
                'purchases.view',
                'inventory.view',
            ];
        }

        return $permissions;
    }
}
