<?php

namespace App\Models;

use App\Enums\BusinessType;
use App\Enums\Plan;
use App\Models\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Represents a tenant company in the multi-tenant ERP system.
 */
class Company extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'logo_url',
        'business_type',
        'country',
        'currency',
        'timezone',
        'locale',
        'vat_registered',
        'vat_bin',
        'address_line1',
        'address_line2',
        'city',
        'district',
        'postal_code',
        'phone',
        'email',
        'website',
        'is_active',
        'plan',
        'plan_expires_at',
        'settings',
        'onboarding_completed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'business_type' => BusinessType::class,
            'plan' => Plan::class,
            'settings' => 'array',
            'vat_registered' => 'boolean',
            'is_active' => 'boolean',
            'plan_expires_at' => 'datetime',
            'onboarding_completed_at' => 'datetime',
        ];
    }

    // ── Relationships ────────────────────────────────────────

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'company_user')
            ->withPivot(['role', 'is_owner', 'is_active', 'joined_at'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<CompanyUser, $this>
     */
    public function companyUsers(): HasMany
    {
        return $this->hasMany(CompanyUser::class);
    }

    /**
     * @return HasMany<Invitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    /**
     * @return HasMany<EntityAlias, $this>
     */
    public function entityAliases(): HasMany
    {
        return $this->hasMany(EntityAlias::class);
    }

    /**
     * @return HasMany<AuditLog, $this>
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    // ── Scopes ───────────────────────────────────────────────

    /**
     * Scope to only active companies.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Company>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Company>
     */
    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_active', true);
    }

    // ── Methods ──────────────────────────────────────────────

    /**
     * Returns the owner user of this company.
     */
    public function owner(): ?User
    {
        return $this->users()
            ->wherePivot('is_owner', true)
            ->first();
    }
}
