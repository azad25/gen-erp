<?php

namespace App\Models;

use App\Enums\TaxType;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tax group model supporting VAT, SD, and AIT with compound calculation.
 */
class TaxGroup extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'name',
        'rate',
        'rate_basis_points',
        'type',
        'is_compound',
        'description',
        'is_default',
        'is_active',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TaxType::class,
            'rate' => 'float',
            'rate_basis_points' => 'integer',
            'is_compound' => 'boolean',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    // ── Relationships ────────────────────────────────────────

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    // ── Scopes ───────────────────────────────────────────────

    /**
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeOfType($query, TaxType $type)
    {
        return $query->where('type', $type->value);
    }

    // ── Accessors ────────────────────────────────────────────

    /**
     * Rate as a percentage for display.
     */
    public function formattedRate(): string
    {
        return number_format($this->rate, 2).'%';
    }

    /**
     * Rate from basis points as percentage.
     */
    public function rateFromBasisPoints(): float
    {
        return $this->rate_basis_points / 100;
    }
}
