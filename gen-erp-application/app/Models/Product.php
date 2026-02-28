<?php

namespace App\Models;

use App\Enums\ProductType;
use App\Models\Traits\BelongsToCompany;
use App\Models\Traits\DispatchesModelEvents;
use App\Models\Traits\HasCustomFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Core product model — supports physical products, services, and digital goods.
 */
class Product extends Model
{
    use BelongsToCompany;
    use DispatchesModelEvents;
    use HasCustomFields;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'category_id',
        'name',
        'slug',
        'sku',
        'barcode',
        'description',
        'product_type',
        'unit',
        'cost_price',
        'selling_price',
        'min_selling_price',
        'tax_group_id',
        'track_inventory',
        'low_stock_threshold',
        'has_variants',
        'is_active',
        'image_url',
        'custom_fields',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'product_type' => ProductType::class,
            'cost_price' => 'integer',
            'selling_price' => 'integer',
            'min_selling_price' => 'integer',
            'low_stock_threshold' => 'integer',
            'track_inventory' => 'boolean',
            'has_variants' => 'boolean',
            'is_active' => 'boolean',
            'custom_fields' => 'array',
        ];
    }

    /**
     * Entity type for custom fields and alert rules.
     */
    public function customFieldEntityType(): string
    {
        return 'product';
    }

    /**
     * Entity type for alert rule evaluation.
     */
    public function alertEntityType(): string
    {
        return 'product';
    }

    /**
     * @return BelongsTo<ProductCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    /**
     * @return BelongsTo<TaxGroup, $this>
     */
    public function taxGroup(): BelongsTo
    {
        return $this->belongsTo(TaxGroup::class);
    }

    /**
     * @return HasMany<ProductVariant, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * Only active products.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Only products that have inventory tracking enabled.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeTrackingInventory($query)
    {
        return $query->where('track_inventory', true);
    }

    /**
     * Selling price formatted in BDT for display.
     */
    public function formattedSellingPrice(): string
    {
        return '৳'.number_format($this->selling_price / 100, 2);
    }

    /**
     * Cost price formatted in BDT for display.
     */
    public function formattedCostPrice(): string
    {
        return '৳'.number_format($this->cost_price / 100, 2);
    }

    /**
     * Profit margin as a percentage.
     */
    public function profitMargin(): float
    {
        if ($this->cost_price === 0) {
            return 0.0;
        }

        return round(
            (($this->selling_price - $this->cost_price) / $this->cost_price) * 100,
            2
        );
    }
}
