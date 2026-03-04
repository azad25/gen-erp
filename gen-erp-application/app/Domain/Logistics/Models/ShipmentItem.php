<?php

namespace App\Domain\Logistics\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentItem extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\Domain\Logistics\ShipmentItemFactory::new();
    }

    protected $fillable = [
        'shipment_id',
        'product_variant_id',
        'invoice_item_id',
        'product_name',
        'sku',
        'quantity',
        'unit_price',
        'total_price',
        'weight',
        'description',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'weight' => 'decimal:2',
    ];

    // Relationships
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Product\Models\ProductVariant::class);
    }

    public function invoiceItem(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Invoice\Models\InvoiceItem::class);
    }

    // Methods
    public function getTotalWeight(): float
    {
        $weight = $this->productVariant?->weight ?? 0.5; // default 0.5kg per item
        return $this->quantity * $weight;
    }

    public function getDisplayName(): string
    {
        return $this->product_name . ($this->sku ? " ({$this->sku})" : '');
    }
}