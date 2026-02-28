<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Enums\StockMovementType;
use App\Models\Traits\BelongsToCompany;
use App\Models\Traits\DispatchesModelEvents;
use App\Models\Traits\HasCustomFields;
use App\Services\InventoryService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Invoice document — represents a bill to a customer. May originate from a sales order.
 */
class Invoice extends Model
{
    use BelongsToCompany;
    use DispatchesModelEvents;
    use HasCustomFields;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'sales_order_id',
        'customer_id',
        'warehouse_id',
        'invoice_number',
        'mushak_number',
        'invoice_date',
        'due_date',
        'status',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'shipping_amount',
        'total_amount',
        'amount_paid',
        'notes',
        'terms_conditions',
        'custom_fields',
        'stock_deducted',
        'created_by',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'invoice_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'integer',
            'discount_amount' => 'integer',
            'tax_amount' => 'integer',
            'shipping_amount' => 'integer',
            'total_amount' => 'integer',
            'amount_paid' => 'integer',
            'balance_due' => 'integer',
            'stock_deducted' => 'boolean',
            'custom_fields' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice): void {
            if ($invoice->invoice_number === null) {
                $date = now()->format('Ymd');
                $seq = static::withoutGlobalScopes()
                    ->where('company_id', $invoice->company_id)
                    ->count() + 1;
                $invoice->invoice_number = 'INV-'.$date.'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function customFieldEntityType(): string
    {
        return 'invoice';
    }

    public function alertEntityType(): string
    {
        return 'invoice';
    }

    public function workflowDocumentType(): string
    {
        return 'invoice';
    }

    /**
     * @return BelongsTo<SalesOrder, $this>
     */
    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * @return HasMany<InvoiceItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Whether this invoice is overdue.
     */
    public function isOverdue(): bool
    {
        if ($this->status === InvoiceStatus::PAID || $this->status === InvoiceStatus::CANCELLED) {
            return false;
        }

        return $this->due_date !== null && $this->due_date->lt(now()->startOfDay());
    }

    /**
     * Outstanding amount = total - paid.
     */
    public function outstandingAmount(): int
    {
        return $this->total_amount - $this->amount_paid;
    }

    /**
     * Deduct stock for each invoice item via InventoryService.
     */
    public function deductStock(): void
    {
        if ($this->stock_deducted) {
            return;
        }

        $inventoryService = app(InventoryService::class);

        foreach ($this->items as $item) {
            if ($item->product_id === null) {
                continue;
            }

            $product = Product::withoutGlobalScopes()->find($item->product_id);
            if ($product === null || ! $product->track_inventory) {
                continue;
            }

            $inventoryService->stockOut(
                $this->warehouse_id,
                $item->product_id,
                (float) $item->quantity,
                StockMovementType::SALE,
                $item->variant_id,
                "Invoice {$this->invoice_number}",
                $this,
            );
        }

        $this->update(['stock_deducted' => true]);
    }
}
