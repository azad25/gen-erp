<?php

namespace App\Domain\Auth\Models;

use App\Support\Enums\BusinessType;
use App\Support\Enums\Plan;
use App\Support\Enums\ValuationMethod;
use App\Domain\Auth\Models\Concerns\Auditable;
use App\Domain\Customer\Models\Customer;
use App\Domain\Product\Models\Product;
use App\Domain\Invoice\Models\Invoice;
use App\Domain\SalesOrder\Models\SalesOrder;
use App\Domain\Auth\Models\User;
use App\Domain\Auth\Models\CompanyUser;
use App\Domain\Auth\Models\Invitation;
use App\Domain\Shared\Models\EntityAlias;
use App\Domain\Audit\Models\AuditLog;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Auth\Models\Branch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Database\Factories\CompanyFactory;

/**
 * Represents a tenant company in the multi-tenant ERP system.
 */
class Company extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): CompanyFactory
    {
        return CompanyFactory::new();
    }

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
        'lock_date',
        'valuation_method',
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
            'lock_date' => 'date',
            'valuation_method' => ValuationMethod::class,
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

    /**
     * @return HasMany<Customer, $this>
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * @return HasMany<SalesOrder, $this>
     */
    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class);
    }

    /**
     * @return HasMany<Warehouse, $this>
     */
    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class);
    }

    /**
     * @return HasMany<Branch, $this>
     */
    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
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
