<?php

namespace App\Models;

use App\Enums\PurchaseOrderStatus;
use App\Models\Traits\BelongsToCompany;
use App\Models\Traits\DispatchesModelEvents;
use App\Models\Traits\HasCustomFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Purchase order document — the starting point of the procurement flow.
 */
class PurchaseOrder extends Model
{
    use BelongsToCompany;
    use DispatchesModelEvents;
    use HasCustomFields;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'supplier_id',
        'warehouse_id',
        'reference_number',
        'supplier_reference',
        'order_date',
        'expected_delivery_date',
        'status',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'shipping_amount',
        'total_amount',
        'amount_received_value',
        'notes',
        'terms_conditions',
        'custom_fields',
        'created_by',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'status' => PurchaseOrderStatus::class,
            'order_date' => 'date',
            'expected_delivery_date' => 'date',
            'subtotal' => 'integer',
            'discount_amount' => 'integer',
            'tax_amount' => 'integer',
            'shipping_amount' => 'integer',
            'total_amount' => 'integer',
            'amount_received_value' => 'integer',
            'custom_fields' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (PurchaseOrder $order): void {
            if ($order->reference_number === null) {
                $date = now()->format('Ymd');
                $seq = static::withoutGlobalScopes()
                    ->where('company_id', $order->company_id)
                    ->count() + 1;
                $order->reference_number = 'PO-'.$date.'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function customFieldEntityType(): string
    {
        return 'purchase_order';
    }

    public function alertEntityType(): string
    {
        return 'purchase_order';
    }

    public function workflowDocumentType(): string
    {
        return 'purchase_order';
    }

    /**
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<PurchaseOrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    /**
     * @return HasMany<GoodsReceipt, $this>
     */
    public function goodsReceipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class);
    }

    /**
     * Pending receipt quantity for a given product.
     */
    public function pendingReceiptQuantity(int $productId): float
    {
        return $this->items()
            ->where('product_id', $productId)
            ->get()
            ->sum(fn (PurchaseOrderItem $item): float => $item->remainingQuantity());
    }

    /**
     * Whether all items have been fully received.
     */
    public function isFullyReceived(): bool
    {
        return $this->items->every(
            fn (PurchaseOrderItem $item): bool => $item->remainingQuantity() <= 0
        );
    }

    public function receiptCount(): int
    {
        return $this->goodsReceipts()->count();
    }
}
