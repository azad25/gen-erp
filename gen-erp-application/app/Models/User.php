<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Application user with multi-company membership support.
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar_url',
        'preferred_locale',
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
}
